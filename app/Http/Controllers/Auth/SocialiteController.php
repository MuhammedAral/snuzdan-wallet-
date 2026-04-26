<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OauthAccount;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialiteController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['oauth' => 'OAuth ile bağlantı başarısız oldu.']);
        }

        // Kullanıcı önceden oturum açmışsa (Hesap bağlama işlemi)
        if (Auth::check()) {
            $user = Auth::user();
            
            // Bu hesap başka bir kullanıcıya bağlı mı kontrolü
            $existingAccount = OauthAccount::where('provider', $provider)
                                           ->where('provider_id', $socialUser->getId())
                                           ->first();
                                           
            if ($existingAccount && $existingAccount->user_id !== $user->id) {
                return redirect()->route('settings.index')->withErrors(['oauth' => 'Bu hesap başka bir kullanıcıya ait.']);
            }

            OauthAccount::updateOrCreate(
                ['provider' => $provider, 'provider_id' => $socialUser->getId()],
                [
                    'user_id' => $user->id,
                    'access_token' => $socialUser->token,
                    'refresh_token' => $socialUser->refreshToken,
                    'expires_at' => now()->addSeconds($socialUser->expiresIn ?? 3600),
                ]
            );

            return redirect()->route('settings.index')->with('success', 'Hesap başarıyla bağlandı.');
        }

        // Misafir kullanıcı (Giriş veya Kayıt)
        $oauthAccount = OauthAccount::where('provider', $provider)
                                    ->where('provider_id', $socialUser->getId())
                                    ->first();

        if ($oauthAccount) {
            // Zaten bağlı, direkt giriş yap
            Auth::login($oauthAccount->user);
            return redirect()->route('dashboard');
        }

        // E-posta adresi sistemde var mı?
        $user = User::where('email', $socialUser->getEmail())->first();

        if (!$user) {
            // Kullanıcı yoksa oluştur
            $user = User::create([
                'email' => $socialUser->getEmail(),
                'display_name' => $socialUser->getName() ?? 'Kullanıcı',
                'password_hash' => Hash::make(Str::random(24)),
                'email_verified' => true,
                'avatar_url' => $socialUser->getAvatar(),
            ]);
        }

        // Oauth kaydını oluştur
        OauthAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'access_token' => $socialUser->token,
            'refresh_token' => $socialUser->refreshToken,
            'expires_at' => now()->addSeconds($socialUser->expiresIn ?? 3600),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
