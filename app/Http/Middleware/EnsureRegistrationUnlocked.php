<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $unlockedUntil = (int) $request->session()->get('register_unlocked_until', 0);
        if ($unlockedUntil >= now()->timestamp) {
            return $next($request);
        }

        $request->session()->forget('register_unlocked_until');

        return redirect()
            ->route('register.lock')
            ->with('error', '新規登録には事前共有パスワードが必要です。');
    }
}
