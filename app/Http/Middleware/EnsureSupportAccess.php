<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupportAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || ($user->isEmployee() && $user->perfil_empresarial === 'soporte')), 403);

        return $next($request);
    }
}
