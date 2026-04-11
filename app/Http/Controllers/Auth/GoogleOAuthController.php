<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OauthAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleOAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect(route('login'))->withErrors([
                'email' => 'Google ile giriş başarısız oldu, lütfen tekrar deneyin.',
            ]);
        }

        $oauthAccount = OauthAccount::where('provider', 'google')
            ->where('provider_id', $googleUser->id)
            ->first();

        if ($oauthAccount) {
            Auth::login($oauthAccount->user);
            return redirect()->intended(route('dashboard'));
        }

        $user = User::where('email', $googleUser->email)->first();

        if (!$user) {
            $user = User::create([
                'email' => $googleUser->email,
                'display_name' => $googleUser->name ?? 'Kullanıcı',
                'password_hash' => null,
                'email_verified' => true,
                'status' => 'active',
                'avatar_url' => $googleUser->avatar
            ]);
        }

        OauthAccount::create([
            'workspace_id' => $user->current_workspace_id,
            'created_by_user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => $googleUser->id,
            'access_token' => $googleUser->token,
            'refresh_token' => $googleUser->refreshToken,
        ]);

        Auth::login($user);

        return redirect()->intended(route('dashboard'));
    }
}
