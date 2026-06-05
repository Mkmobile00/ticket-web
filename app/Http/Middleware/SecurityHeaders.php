<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds baseline security response headers (clickjacking, MIME-sniffing,
 * referrer leakage, HTTPS pinning). A strict Content-Security-Policy is left
 * out on purpose: the BOLETO design pages run inline scripts, so a tight CSP
 * would break them — add a tailored/report-only CSP once the inline scripts
 * are nonce'd or externalised.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // Only advertise HSTS over HTTPS, so local HTTP development isn't pinned.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
