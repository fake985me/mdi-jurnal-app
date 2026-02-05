<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TwoFactorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Step 1: Validate credentials and send 2FA code via email
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate and send 2FA code
        TwoFactorCode::generateFor($user);

        // Return response indicating 2FA is required
        return response()->json([
            'requires_2fa' => true,
            'message' => 'Kode verifikasi telah dikirim ke email Anda.',
            'email' => $this->maskEmail($user->email),
            'user_id' => $user->id,
        ]);
    }

    /**
     * Step 2: Verify 2FA code and issue token
     */
    public function verify2fa(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'code' => 'required|string|size:6',
        ]);

        $user = User::findOrFail($request->user_id);
        $twoFactorCode = TwoFactorCode::findValidCode($user->id, $request->code);

        if (!$twoFactorCode) {
            throw ValidationException::withMessages([
                'code' => ['Kode verifikasi tidak valid atau sudah kadaluarsa.'],
            ]);
        }

        // Mark code as used
        $twoFactorCode->markAsUsed();

        // Create auth token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Resend 2FA code
     */
    public function resend2fa(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        // Generate and send new 2FA code
        TwoFactorCode::generateFor($user);

        return response()->json([
            'message' => 'Kode verifikasi baru telah dikirim ke email Anda.',
            'email' => $this->maskEmail($user->email),
        ]);
    }

    /**
     * Mask email address for privacy (show first 2 chars and domain)
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1];
        
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 3));
        
        return $maskedName . '@' . $domain;
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
