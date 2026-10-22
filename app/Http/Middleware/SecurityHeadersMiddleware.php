<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and attach hardened HTTP security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Prevent MIME-sniffing exploits (Attacker cannot fool browser into executing non-script as script)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 2. Clickjacking Defense: Prevent embedding in malicious iframes
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 3. Browser-level Cross-Site Scripting (XSS) filter
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // 4. Referrer Policy: Send full referrer on same-origin, protect query strings on cross-origin
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 5. Restrict device hardware permissions
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // 6. Strict Transport Security (HSTS) - Enforce HTTPS when on secure connection
        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // 7. Comprehensive Content Security Policy (CSP)
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://accounts.google.com https://apis.google.com https://*.firebaseapp.com https://*.googleapis.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https://* http://* http://127.0.0.1:* http://localhost:*",
            "connect-src 'self' https://accounts.google.com https://identitytoolkit.googleapis.com https://securetoken.googleapis.com https://*.firebaseio.com https://*.firebaseapp.com https://*.googleapis.com https://one.exnessonelink.com ws://localhost:* ws://127.0.0.1:*",
            "frame-src 'self' https://accounts.google.com https://*.firebaseapp.com https://*.firebase.com https://one.exnessonelink.com",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self' https://accounts.google.com https://one.exnessonelink.com",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        return $response;
    }
}
