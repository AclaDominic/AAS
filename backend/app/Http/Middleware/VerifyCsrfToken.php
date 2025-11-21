<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'login',
        'register',
        'logout',
        'forgot-password',
        'reset-password',
        'email/verification-notification',
    ];

    /**
     * Determine if the request should be excluded from CSRF verification.
     * Exclude all API routes since they use token-based authentication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough($request)
    {
        // Exclude all API routes from CSRF verification
        $path = $request->path();
        if (strpos($path, 'api/') === 0) {
            return true;
        }

        // Also check using is() method for route matching
        if ($request->is('api/*')) {
            return true;
        }

        return parent::shouldPassThrough($request);
    }
}
