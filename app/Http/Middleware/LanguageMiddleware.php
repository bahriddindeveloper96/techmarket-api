<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check header request and determine localization
        $locale = ($request->hasHeader('Accept-Language')) ? $request->header('Accept-Language') : 'en';
        
        // Check if the requested language is supported
        if (!in_array($locale, ['en', 'ru', 'uz'])) {
            $locale = 'en'; // default to English
        }
        
        // Set the locale
        app()->setLocale($locale);
        
        return $next($request);
    }
}
