<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // ✅ 直前URL(Referer)から「admin画面でログアウトしたか」を判定
        $referer = (string) $request->headers->get('referer', '');
        $isAdminContext = str_contains($referer, '/admin');

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ✅ adminでログアウトしたら adminログインへ
        // ※ admin専用ログインが無い場合でも、とりあえず /login?admin=1 で出し分け可能
        if ($isAdminContext) {
            return redirect('/login?admin=1');
        }

        // 通常はログインへ
        return redirect()->route('login');
    }
}
