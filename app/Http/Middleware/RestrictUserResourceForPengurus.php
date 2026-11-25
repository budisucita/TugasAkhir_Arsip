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

        // Blokir seluruh rute dengan prefix "admin/users" untuk peran pengurus,
        // termasuk halaman daftar, agar tidak ada kebocoran data (mis. email).
        if ($user && $user->role === 'pengurus' && str_starts_with($request->path(), 'admin/users')) {
            abort(403);
        }

        return $next($request);
    }
}

