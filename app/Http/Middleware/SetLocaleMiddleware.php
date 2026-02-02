<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'el';

        if ($request->user() && $request->user()->language_preference) {
            $locale = $request->user()->language_preference;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
