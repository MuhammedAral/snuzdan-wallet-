<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class TwoFactorController extends Controller
{
    /**
     * Enable 2FA: Generate secret & return QR code data.
     */
    public function enable(Request $request)
    {
        $user = $request->user();

        // Generate a random 16-char base32 secret
        $secret = $this->generateBase32Secret();

        // Store the secret (encrypted) in the user record
        $user->two_factor_secret = Crypt::encryptString($secret);
        $user->two_factor_confirmed_at = null; // Not confirmed yet
        $user->save();

        // Build the otpauth:// URI for QR code generation on frontend
        $issuer = config('app.name', 'Snuzdan');
        $otpauthUrl = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=6&period=30',
            urlencode($issuer),
            urlencode($user->email),
            $secret,
            urlencode($issuer)
        );

        return redirect()->back()->with('twoFactor', [
            'secret' => $secret,
            'qrCodeUrl' => $otpauthUrl,
        ]);
    }

    /**
     * Confirm 2FA: Verify the OTP code and lock in 2FA.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            return redirect()->back()->withErrors(['code' => '2FA henüz etkinleştirilmedi.']);
        }

        $secret = Crypt::decryptString($user->two_factor_secret);
        $validCode = $this->verifyTOTP($secret, $request->code);

        if (!$validCode) {
            return redirect()->back()->withErrors(['code' => 'Geçersiz doğrulama kodu. Lütfen tekrar deneyin.']);
        }

        // Generate recovery codes
        $recoveryCodes = collect(range(1, 8))->map(fn () => bin2hex(random_bytes(5)))->toArray();

        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = Crypt::encryptString(json_encode($recoveryCodes));
        $user->save();

        return redirect()->back()->with('success', 'İki faktörlü doğrulama başarıyla etkinleştirildi.');
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request)
    {
        $user = $request->user();

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return redirect()->back()->with('success', 'İki faktörlü doğrulama devre dışı bırakıldı.');
    }

    /**
     * Show the 2FA challenge page (after login).
     */
    public function challengeForm(Request $request)
    {
        // If the user doesn't have 2FA pending in session, redirect to dashboard
        if (!session('2fa:user_id')) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    /**
     * Verify the 2FA challenge code during login.
     */
    public function challengeVerify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $userId = session('2fa:user_id');
        if (!$userId) {
            return redirect()->route('login')->withErrors(['code' => 'Oturum süresi doldu. Lütfen tekrar giriş yapın.']);
        }

        $user = \App\Models\User::findOrFail($userId);
        $secret = Crypt::decryptString($user->two_factor_secret);

        // Check if it's a recovery code
        $code = $request->code;
        $isRecovery = strlen($code) === 10; // Recovery codes are 10 hex chars

        if ($isRecovery) {
            $recoveryCodes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true);

            if (!in_array($code, $recoveryCodes)) {
                return redirect()->back()->withErrors(['code' => 'Geçersiz kurtarma kodu.']);
            }

            // Remove used recovery code
            $recoveryCodes = array_values(array_diff($recoveryCodes, [$code]));
            $user->two_factor_recovery_codes = Crypt::encryptString(json_encode($recoveryCodes));
            $user->save();
        } else {
            if (!$this->verifyTOTP($secret, $code)) {
                return redirect()->back()->withErrors(['code' => 'Geçersiz doğrulama kodu.']);
            }
        }

        // Clear 2FA session, actually log the user in
        session()->forget('2fa:user_id');
        Auth::loginUsingId($userId);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    // ── TOTP Helpers (Pure PHP, no external dependency) ──

    /**
     * Generate a random Base32-encoded secret.
     */
    private function generateBase32Secret(int $length = 16): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Verify a TOTP code against the secret.
     * Allows ±1 time window for clock skew.
     */
    private function verifyTOTP(string $secret, string $code): bool
    {
        $timeSlice = floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            $calculatedCode = $this->calculateTOTP($secret, $timeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate a TOTP code for a given time slice.
     */
    private function calculateTOTP(string $secret, int $timeSlice): string
    {
        // Decode base32 secret
        $secretKey = $this->base32Decode($secret);

        // Pack time into 8 bytes (big-endian)
        $time = pack('N*', 0, $timeSlice);

        // HMAC-SHA1
        $hmac = hash_hmac('sha1', $time, $secretKey, true);

        // Dynamic truncation
        $offset = ord($hmac[strlen($hmac) - 1]) & 0x0F;
        $code = (
            ((ord($hmac[$offset]) & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
            (ord($hmac[$offset + 3]) & 0xFF)
        ) % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a Base32-encoded string.
     */
    private function base32Decode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, '='));
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $val = strpos($map, $input[$i]);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $result;
    }
}
