<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthenticationException();
        }

        $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        if (! in_array($role, $roles, true)) {
            throw new AccessDeniedHttpException('Доступ запрещён');
        }

        return $next($request);
    }
}