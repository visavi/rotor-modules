<?php

declare(strict_types=1);

namespace Modules\SocialAuth\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialAuthSettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::query()->pluck('value', 'name')->all();

        return view('social_auth::admin/settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $providers = ['google', 'github', 'yandex', 'vk'];
        $errors = [];

        foreach ($providers as $provider) {
            $clientId = trim((string) $request->input('sets.social_' . $provider . '_client_id', ''));
            $clientSecret = trim((string) $request->input('sets.social_' . $provider . '_client_secret', ''));
            $enabled = $request->boolean('sets.social_' . $provider . '_enabled');

            if ($enabled && (empty($clientId) || empty($clientSecret))) {
                $errors[] = ucfirst($provider) . ': ' . __('social_auth::social_auth.error_credentials_required');
                $enabled = false;
            }

            Setting::query()->updateOrInsert(['name' => 'social_' . $provider . '_client_id'], ['value' => $clientId]);
            Setting::query()->updateOrInsert(['name' => 'social_' . $provider . '_client_secret'], ['value' => $clientSecret]);
            Setting::query()->updateOrInsert(['name' => 'social_' . $provider . '_enabled'], ['value' => (int) $enabled]);
        }

        Setting::query()->updateOrInsert(
            ['name' => 'social_autolink_email'],
            ['value' => (int) $request->boolean('sets.social_autolink_email')]
        );

        clearCache('settings');

        if ($errors) {
            setFlash('warning', $errors);
        } else {
            setFlash('success', __('social_auth::social_auth.settings_saved'));
        }

        return redirect()->route('social_auth.settings');
    }
}
