<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleCheck
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (in_array($request->user()->role, $roles))
            return $next($request);

        if ($request->user()->role == 'admin')
            // return redirect()->route('dashboard.admin');

        if ($request->user()->role == 'department_head')
            // return redirect()->route('dashboard.expert');

        if ($request->user()->role == 'lecturer')
            // return redirect()->route('cases.index');

        return redirect('/');
    }
}