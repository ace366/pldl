<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegisterLockController extends Controller
{
    private const SETTING_KEY = 'registration_gate_password';

    public function show(Request $request): View|RedirectResponse
    {
        $unlockedUntil = (int) $request->session()->get('register_unlocked_until', 0);
        if ($unlockedUntil >= now()->timestamp) {
            return redirect()->route('register');
        }

        $request->session()->forget('register_unlocked_until');

        return view('auth.register-lock');
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gate_password' => ['required', 'string', 'max:255'],
        ], [
            'gate_password.required' => '事前共有パスワードを入力してください。',
        ]);

        $input = (string) $validated['gate_password'];
        $current = (string) AppSetting::getValue(self::SETTING_KEY, 'pldl-register');

        if (!hash_equals($current, $input)) {
            return back()
                ->withInput()
                ->withErrors([
                    'gate_password' => 'パスワードが正しくありません。',
                ]);
        }

        $request->session()->put('register_unlocked_until', now()->addMinutes(15)->timestamp);
        $request->session()->regenerateToken();

        return redirect()->route('register');
    }
}
