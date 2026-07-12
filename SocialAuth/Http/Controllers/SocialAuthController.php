<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BlackList;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\SocialAuth\Http\Requests\CompleteRequest;
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
        $oauthProvider = $this->resolveEnabledProvider($provider);

        return $this->redirectToProvider($oauthProvider, $request);
    }

    /**
     * Handle provider callback
     */
    public function callback(string $provider, Request $request): RedirectResponse
    {
        if ($request->input('error')) {
            return redirect('login')->with('danger', __('social_auth::social_auth.access_denied'));
        }

        $oauthProvider = $this->resolveEnabledProvider($provider);

        // CSRF state check
        $state = $request->input('state');
        $sessionState = $request->session()->pull('oauth_state');

        if (! $state || $state !== $sessionState) {
            return redirect('login')->with('danger', __('social_auth::social_auth.invalid_state'));
        }

        $code = $request->input('code');
        if (! $code) {
            return redirect('login')->with('danger', __('social_auth::social_auth.no_code'));
        }

        try {
            if ($oauthProvider instanceof VkProvider) {
                $codeVerifier = $request->session()->pull('oauth_code_verifier', '');
                $deviceId = $request->input('device_id', '');
                $tokenData = $oauthProvider->getTokenData($code, $codeVerifier, $deviceId);
                $token = $tokenData['access_token'];
                $oauthUser = $oauthProvider->getUser($token);
            } else {
                $token = $oauthProvider->getToken($code);
                $oauthUser = $oauthProvider->getUser($token);
            }
        } catch (ConnectionException|RuntimeException $e) {
            report($e);

            $fallback = Auth::check() ? redirect()->route('social.accounts') : redirect('login');

            return $fallback->with('danger', __('social_auth::social_auth.provider_error'));
        }

        $providerId = $oauthUser['id'];

        // Текущий пользователь хочет привязать аккаунт
        if (Auth::check()) {
            return $this->linkAccount($provider, $providerId, $token);
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

        $oauthProvider = $this->resolveProvider($provider);

        if (! $oauthProvider) {
            abort(404);
        }

        if (! setting('social_' . $provider . '_enabled')) {
            setFlash('danger', __('social_auth::social_auth.provider_disabled'));

            return redirect()->route('social.accounts');
        }

        return $this->redirectToProvider($oauthProvider, $request);
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

        return redirect()->route('social.accounts');
    }

    /**
     * Страница управления привязанными аккаунтами
     */
    public function accounts(): View
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

    /**
     * Форма ввода email (когда провайдер не вернул email)
     */
    public function completeForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('social_pending')) {
            return redirect('login');
        }

        return view('social_auth::complete');
    }

    /**
     * Завершение регистрации — сохраняем email введённый вручную
     */
    public function complete(CompleteRequest $request): RedirectResponse
    {
        $pending = $request->session()->pull('social_pending');

        if (! $pending) {
            return redirect('login');
        }

        $email = strtolower($request->validated('email'));

        $existing = User::query()->where('email', $email)->first();

        if ($existing && setting('social_autolink_email')) {
            return $this->attachAndLogin($existing, $pending['provider'], $pending['provider_id'], $pending['token'], $request);
        }

        if (! setting('openreg')) {
            return redirect('login')->with('danger', __('users.registration_suspended'));
        }

        return $this->createUserWithSocial(
            $email,
            $pending['name'] ?? '',
            $pending['provider'],
            $pending['provider_id'],
            $pending['token'],
            $request
        );
    }

    private function linkAccount(string $provider, string $providerId, string $token): RedirectResponse
    {
        $user = Auth::user();

        $existing = Social::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($existing && $existing->user_id !== $user->id) {
            setFlash('danger', __('social_auth::social_auth.already_linked_other'));

            return redirect()->route('social.accounts');
        }

        Social::query()->updateOrCreate(
            ['provider' => $provider, 'user_id' => $user->id],
            ['provider_id' => $providerId, 'token' => $token]
        );

        setFlash('success', __('social_auth::social_auth.linked'));

        return redirect()->route('social.accounts');
    }

    private function registerUser(string $provider, string $providerId, string $token, array $oauthUser, Request $request): RedirectResponse
    {
        if (! setting('openreg')) {
            return redirect('login')->with('danger', __('users.registration_suspended'));
        }

        // Проверка занятости email
        if (! empty($oauthUser['email'])) {
            if (BlackList::isBlacklisted('email', $oauthUser['email'])) {
                return redirect('login')->with('danger', __('users.email_is_blacklisted'));
            }

            $domain = Str::substr(strrchr(strtolower($oauthUser['email']), '@'), 1);

            if (BlackList::isBlacklisted('domain', $domain)) {
                return redirect('login')->with('danger', __('users.domain_is_blacklisted'));
            }

            $existing = User::query()->where('email', $oauthUser['email'])->first();

            if ($existing && ! setting('social_autolink_email')) {
                return redirect('login')->with('danger', __('social_auth::social_auth.email_already_exists'));
            }

            if ($existing && setting('social_autolink_email')) {
                return $this->attachAndLogin($existing, $provider, $providerId, $token, $request);
            }
        }

        // Email не получен от провайдера — просим ввести вручную
        if (empty($oauthUser['email'])) {
            $request->session()->put('social_pending', [
                'provider'    => $provider,
                'provider_id' => $providerId,
                'token'       => $token,
                'name'        => $oauthUser['name'] ?? '',
            ]);

            return redirect()->route('social.complete');
        }

        return $this->createUserWithSocial(
            $oauthUser['email'],
            $oauthUser['name'] ?? '',
            $provider,
            $providerId,
            $token,
            $request
        );
    }

    /**
     * Привязывает соцсеть к существующему пользователю и логинит его
     */
    private function attachAndLogin(User $user, string $provider, string $providerId, string $token, Request $request): RedirectResponse
    {
        if ($user->level === User::BANNED) {
            return redirect('login')->with('danger', __('social_auth::social_auth.account_banned'));
        }

        Social::query()->create([
            'user_id'     => $user->id,
            'provider'    => $provider,
            'provider_id' => $providerId,
            'token'       => $token,
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended('/')
            ->with('success', __('users.welcome', ['login' => $user->getName()], $user->language));
    }

    private function createUserWithSocial(string $email, string $name, string $provider, string $providerId, string $token, Request $request): RedirectResponse
    {
        $login = $this->generateLogin($name);

        $user = User::query()->create([
            'login'      => $login,
            'password'   => Hash::make(Str::random(32)),
            'email'      => $email,
            'level'      => User::USER,
            'gender'     => User::MALE,
            'themes'     => setting('themes'),
            'point'      => 0,
            'language'   => setting('language'),
            'money'      => setting('registermoney'),
            'subscribe'  => Str::random(32),
            'updated_at' => now(),
        ]);

        Social::query()->create([
            'user_id'     => $user->id,
            'provider'    => $provider,
            'provider_id' => $providerId,
            'token'       => $token,
        ]);

        $textNotice = textNotice('register', ['username' => $login]);
        $user->sendMessage(null, $textNotice);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect('/')
            ->with('success', __('users.welcome', ['login' => $login], $user->language));
    }

    private function redirectToProvider(AbstractOAuthProvider $oauthProvider, Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('oauth_state', $state);

        if ($oauthProvider instanceof VkProvider) {
            $pkce = $oauthProvider->buildAuthUrlWithPkce($state);
            $request->session()->put('oauth_code_verifier', $pkce['code_verifier']);

            return redirect($pkce['url']);
        }

        return redirect($oauthProvider->buildAuthUrl($state));
    }

    private function generateLogin(string $name): string
    {
        $login = Str::ascii(mb_strtolower(trim($name)));
        $login = preg_replace('/[^a-z0-9\-]/', '-', $login);
        $login = preg_replace('/-+/', '-', $login);
        $login = trim($login, '-');
        $login = Str::substr($login, 0, 20);

        if (strlen($login) < 3) {
            $login = 'user';
        }

        $base = $login;
        $i = 0;

        // login = varchar(20): база подрезается под длину суффикса, чтобы сумма не превысила 20.
        // Блэклист-логин не блокирует регистрацию (логин автогенерится) — просто перегенерируется
        while (
            User::query()->where('login', $login)->exists()
            || BlackList::isBlacklisted('login', $login)
        ) {
            $i++;
            $suffix = (string) $i;
            $login = Str::substr($base, 0, 20 - strlen($suffix)) . $suffix;
        }

        return $login;
    }

    private function resolveProvider(string $name): ?AbstractOAuthProvider
    {
        $class = $this->providers[$name] ?? null;

        return $class ? new $class() : null;
    }

    /**
     * Возвращает включённый провайдер либо обрывает запрос (404/403)
     */
    private function resolveEnabledProvider(string $provider): AbstractOAuthProvider
    {
        $oauthProvider = $this->resolveProvider($provider);

        if (! $oauthProvider) {
            abort(404);
        }

        if (! setting('social_' . $provider . '_enabled')) {
            abort(403, __('social_auth::social_auth.provider_disabled'));
        }

        return $oauthProvider;
    }
}
