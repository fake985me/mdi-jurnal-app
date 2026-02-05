<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // Untuk API request, return null (akan return 401 JSON response)
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // Untuk web request, bisa redirect ke halaman login jika ada
        return null; // atau return '/login' jika punya halaman login
    }
}