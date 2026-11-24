<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictUserResourceForPengurus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'pengurus') {
            $path = $request->path();

            if (str_starts_with($path, 'admin/users')) {
                if ($request->isMethod('get') && $path === 'admin/users') {
                    return $next($request);
                }

                abort(403);
            }
        }

        return $next($request);
    }
}

