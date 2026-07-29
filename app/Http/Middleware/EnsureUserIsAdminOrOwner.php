<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdminOrOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array(auth()->user()?->role, ['owner', 'admin'], true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
