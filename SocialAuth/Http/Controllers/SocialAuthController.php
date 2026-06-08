<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\SocialAuth\Models\Social;
use Modules\SocialAuth\Providers\AbstractOAuthProvider;
use Modules\SocialAuth\Providers\GithubProvider;
use Modules\SocialAuth\Providers\GoogleProvider;
use Modules\SocialAuth\Providers\VkProvider;
use Modules\SocialAuth\Providers\YandexProvider;
use RuntimeException;

class SocialAuthController extends Controller
{
    private array $providers = [
        'google' => GoogleProvider::class,
        'github' => GithubProvider::class,
        'yandex' => YandexProvider::class,
        'vk'     => VkProvider::class,
    ];

    /**
     * Redirect to provider OAuth page
     */
    public function redirect(string $provider, Request $request): RedirectResponse
    {
        $oauthProvider = $this->resolveProvider($provider);

        if (! $oauthProvider) {
            abort(404);
        }

        if (! setting('social_' . $provider . '_enabled')) {
            abort(403, __('social_auth::social_auth.provider_disabled'));
        }

        $state = Str::random(40);
        $request->session()->put('oauth_state', $state);
        $request->session()->put('oauth_provider', $provider);

        return redirect($oauthProvider->buildAuthUrl($state));
    }

    /**
     * Handle provider callback
     */
    public function callback(string $provider, Request $request): RedirectResponse
    {
        if ($request->input('error')) {
            return redirect('login')->with('danger', __('social_auth::social_auth.access_denied'));
        }

        $oauthProvider = $this->resolveProvider($provider);

        if (! $oauthProvider) {
            abort(404);
        }

        if (! setting('social_' . $provider . '_enabled')) {
            abort(403, __('social_auth::social_auth.provider_disabled'));
        }

        // CSRF state check
        $state = $request->input('state');
        $sessionState = $request->session()->pull('oauth_state');
        $request->session()->forget('oauth_provider');

        if (! $state || $state !== $sessionState) {
            return redirect('login')->with('danger', __('social_auth::social_auth.invalid_state'));
        }

        $code = $request->input('code');
        if (! $code) {
            return redirect('login')->with('danger', __('social_auth::social_auth.no_code'));
        }

        try {
            if ($oauthProvider instanceof VkProvider) {
                $tokenData = $oauthProvider->getTokenData($code);
                $token = $tokenData['access_token'];
                $oauthUser = $oauthProvider->getUser($token);
                // VK отдаёт email в ответе токена
                $oauthUser['email'] = $tokenData['email'] ?? null;
                $oauthUser['id'] = (string) ($tokenData['user_id'] ?? $oauthUser['id']);
            } else {
                $token = $oauthProvider->getToken($code);
                $oauthUser = $oauthProvider->getUser($token);
            }
        } catch (RuntimeException $e) {
            return redirect('login')->with('danger', __('social_auth::social_auth.provider_error'));
        }

        $providerId = $oauthUser['id'];

        // Текущий пользователь хочет привязать аккаунт
        if (Auth::check()) {
            return $this->linkAccount($provider, $providerId, $token, $request);
        }

        // Ищем существующую привязку
        $social = Social::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($social) {
            $user = $social->user;

            if (! $user || $user->level === User::BANNED) {
                return redirect('login')->with('danger', __('social_auth::social_auth.account_banned'));
            }

            $social->update(['token' => $token]);

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended('/')
                ->with('success', __('users.welcome', ['login' => $user->getName()], $user->language));
        }

        // Новый пользователь — регистрируем
        return $this->registerUser($provider, $providerId, $token, $oauthUser, $request);
    }

    /**
     * Привязка соцсети к текущему аккаунту
     */
    public function link(string $provider, Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            abort(403, __('main.not_authorized'));
        }

        if (! $this->resolveProvider($provider)) {
            abort(404);
        }

        if (! setting('social_' . $provider . '_enabled')) {
            setFlash('danger', __('social_auth::social_auth.provider_disabled'));

            return redirect('social/accounts');
        }

        $state = Str::random(40);
        $request->session()->put('oauth_state', $state);
        $request->session()->put('oauth_provider', $provider);
        $request->session()->put('oauth_link', true);

        return redirect($this->resolveProvider($provider)->buildAuthUrl($state));
    }

    /**
     * Отвязка соцсети от аккаунта
     */
    public function unlink(string $provider): RedirectResponse
    {
        if (! $user = getUser()) {
            abort(403, __('main.not_authorized'));
        }

        Social::query()
            ->where('user_id', $user->id)
            ->where('provider', $provider)
            ->delete();

        setFlash('success', __('social_auth::social_auth.unlinked'));

        return redirect('social/accounts');
    }

    /**
     * Страница управления привязанными аккаунтами
     */
    public function accounts(): \Illuminate\View\View
    {
        if (! $user = getUser()) {
            abort(403, __('main.not_authorized'));
        }

        $socials = Social::query()
            ->where('user_id', $user->id)
            ->pluck('provider_id', 'provider');

        $availableProviders = array_filter(
            array_keys($this->providers),
            fn ($p) => (bool) setting('social_' . $p . '_enabled')
        );

        return view('social_auth::accounts', compact('socials', 'availableProviders', 'user'));
    }

    private function linkAccount(string $provider, string $providerId, string $token, Request $request): RedirectResponse
    {
        $user = Auth::user();

        $existing = Social::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($existing && $existing->user_id !== $user->id) {
            setFlash('danger', __('social_auth::social_auth.already_linked_other'));

            return redirect('social/accounts');
        }

        Social::query()->updateOrCreate(
            ['provider' => $provider, 'user_id' => $user->id],
            ['provider_id' => $providerId, 'token' => $token, 'created_at' => SITETIME]
        );

        $request->session()->forget('oauth_link');

        setFlash('success', __('social_auth::social_auth.linked'));

        return redirect('social/accounts');
    }

    private function registerUser(string $provider, string $providerId, string $token, array $oauthUser, Request $request): RedirectResponse
    {
        if (! setting('openreg')) {
            return redirect('login')->with('danger', __('users.registration_suspended'));
        }

        // Проверка занятости email
        if (! empty($oauthUser['email'])) {
            $existing = User::query()->where('email', $oauthUser['email'])->first();

            if ($existing && ! setting('social_autolink_email')) {
                return redirect('login')->with('danger', __('social_auth::social_auth.email_already_exists'));
            }

            if ($existing && setting('social_autolink_email')) {
                if ($existing->level === User::BANNED) {
                    return redirect('login')->with('danger', __('social_auth::social_auth.account_banned'));
                }

                Social::query()->create([
                    'user_id'     => $existing->id,
                    'provider'    => $provider,
                    'provider_id' => $providerId,
                    'token'       => $token,
                    'created_at'  => SITETIME,
                ]);

                Auth::login($existing, true);
                $request->session()->regenerate();

                return redirect()->intended('/')
                    ->with('success', __('users.welcome', ['login' => $existing->getName()], $existing->language));
            }
        }

        $login = $this->generateLogin($oauthUser['name'] ?? '');

        $user = User::query()->create([
            'login'      => $login,
            'password'   => bcrypt(Str::random(32)),
            'email'      => $oauthUser['email'] ?? ($login . '@social.local'),
            'level'      => User::USER,
            'gender'     => User::MALE,
            'themes'     => setting('themes'),
            'point'      => 0,
            'language'   => setting('language'),
            'money'      => setting('registermoney'),
            'subscribe'  => Str::random(32),
            'updated_at' => SITETIME,
            'created_at' => SITETIME,
        ]);

        Social::query()->create([
            'user_id'     => $user->id,
            'provider'    => $provider,
            'provider_id' => $providerId,
            'token'       => $token,
            'created_at'  => SITETIME,
        ]);

        $textNotice = textNotice('register', ['username' => $login]);
        $user->sendMessage(null, $textNotice);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect('/')
            ->with('success', __('users.welcome', ['login' => $login], $user->language));
    }

    private function generateLogin(string $name): string
    {
        $login = Str::ascii(mb_strtolower(trim($name)));
        $login = preg_replace('/[^a-z0-9\-]/', '-', $login);
        $login = preg_replace('/-+/', '-', $login);
        $login = trim($login, '-');
        $login = Str::substr($login, 0, 16);

        if (strlen($login) < 3) {
            $login = 'user';
        }

        $base = $login;
        $i = 0;

        while (
            User::query()->where('login', $login)->exists()
            || strlen($login) < 3
        ) {
            $i++;
            $login = $base . $i;
        }

        return $login;
    }

    private function resolveProvider(string $name): ?AbstractOAuthProvider
    {
        $class = $this->providers[$name] ?? null;

        return $class ? new $class() : null;
    }
}
