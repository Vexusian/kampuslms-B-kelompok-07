<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. Jika pengguna belum terautentikasi (belum login) -> 401 Unauthorized
        if (! $request->user()) {
            abort(401, 'Unauthenticated.');
        }

        // 2. Jika pengguna terautentikasi tetapi role tidak diizinkan -> 403 Forbidden
        abort_unless(
            in_array($request->user()->role, $roles, true),
            403,
            'Akses ditolak: Anda tidak memiliki peran yang diizinkan untuk mengakses halaman ini.'
        );

        return $next($request);
    }
}
