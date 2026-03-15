<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    /**
     * 使い方：middleware('role:admin') / middleware('role:admin,teacher')
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'This action is unauthorized.');
        }

        $role = (string)($user->role ?? 'user');

        // roles が未指定なら拒否（安全側）
        if (empty($roles)) {
            abort(403, 'This action is unauthorized.');
        }

        if (!in_array($role, $roles, true)) {
            abort(403, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
