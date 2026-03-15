<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 未ログインは弾く
        if (!$user) {
            logger()->warning('ensure_admin denied: no user', [
                'path' => $request->path(),
                'route' => optional($request->route())->getName(),
            ]);
            abort(403, '管理者専用ページです。');
        }

        // role が admin のユーザーのみ許可
        $role = mb_strtolower((string) ($user->role ?? ''));
        if ($role !== 'admin') {
            logger()->warning('ensure_admin denied: role mismatch', [
                'path' => $request->path(),
                'route' => optional($request->route())->getName(),
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'role' => $role,
            ]);
            abort(403, '管理者専用ページです。');
        }

        return $next($request);
    }
}
