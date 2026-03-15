<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureWebOrFamilyAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        // 管理者/職員（webガード）ログイン済みならOK
        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        // ご家庭（セッション）ログイン済みならOK
        if ($request->session()->has('family_child_id')) {
            return $next($request);
        }

        // 未ログイン：ご家庭ログインへ（運用的に分かりやすい）
        return redirect()->route('family.login');
    }
}
