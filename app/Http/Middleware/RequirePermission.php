<?php

namespace App\Http\Middleware;

use App\Services\RolePermissionService;
use Closure;
use Illuminate\Http\Request;

class RequirePermission
{
    /**
     * 使い方：middleware('perm:feature,view') / middleware('perm:feature,create')
     */
    public function handle(Request $request, Closure $next, string $feature, string $action = 'view')
    {
        $user = $request->user();
        if (!$user) {
            logger()->warning('perm denied: no user', [
                'feature' => $feature,
                'action' => $action,
                'path' => $request->path(),
            ]);
            abort(403, 'This action is unauthorized.');
        }

        if (!RolePermissionService::canUser($user, $feature, $action)) {
            logger()->warning('perm denied: insufficient permission', [
                'user_id' => $user->id ?? null,
                'role' => $user->role ?? null,
                'feature' => $feature,
                'action' => $action,
                'path' => $request->path(),
            ]);
            abort(403, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
