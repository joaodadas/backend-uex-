<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request)
{
    if (! $request->expectsJson()) {
        return response()->json([
            'message' => 'Acesso não autorizado. Faça login pelo frontend.'
        ], 401);
    }
}
}
