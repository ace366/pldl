<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectIfFamilyChildAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('family_child_id')) {
            return redirect()->route('family.home');
        }
        return $next($request);
    }
}
