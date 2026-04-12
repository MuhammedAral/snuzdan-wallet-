<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function page(Request $request)
    {
        $user = $request->user();
        $user->load('oauthAccounts');
        $workspaces = $user->workspaces()->withPivot('role')->get();

        return Inertia::render('Settings', [
            'oauthAccounts' => $user->oauthAccounts,
            'workspaces' => $workspaces,
            'flash' => [
                'success' => session('success'),
                'twoFactor' => session('twoFactor'),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'avatar_url' => ['nullable', 'url', 'max:1000'],
        ]);

        $request->user()->update($validated);

        return redirect()->back()->with('success', 'Profil başarıyla güncellendi.');
    }

    public function updateCurrency(Request $request)
    {
        $validated = $request->validate([
            'base_currency' => ['required', 'string', Rule::in(['USD', 'EUR', 'TRY'])],
        ]);

        $request->user()->update($validated);

        return redirect()->back()->with('success', 'Para birimi güncellendi.');
    }

    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', Rule::in(['light', 'dark'])],
        ]);

        $request->user()->update($validated);

        return redirect()->back()->with('success', 'Tema güncellendi.');
    }

    public function removeLinkedAccount(Request $request, string $id)
    {
        $user = $request->user();
        
        $account = $user->oauthAccounts()->where('id', $id)->firstOrFail();

        // Eğer kullanıcının şifresi yoksa ve bu son OAuth hesabıysa, silemez.
        if (empty($user->password_hash) && $user->oauthAccounts()->count() === 1) {
            return redirect()->back()->withErrors([
                'oauth' => 'Şifreniz olmadığı için bu bağlantıyı kaldıramazsınız. Hesabınıza erişimi kaybedebilirsiniz.'
            ]);
        }

        $account->delete();

        return redirect()->back()->with('success', 'Bağlantı başarıyla kaldırıldı.');
    }
}
