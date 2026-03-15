<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureFamilyChildAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->has('family_child_id')) {
            return redirect()->route('family.login');
        }
        return $next($request);
    }
}
