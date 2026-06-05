<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CSRF defense for the session-based `/design-api/*` endpoints.
 *
 * Those routes are exempt from Laravel's token CSRF check because the BOLETO
 * design front-end calls them with plain `fetch()` and no CSRF token. To still
 * block cross-site request forgery, this middleware verifies (for every
 * state-changing request) that the browser-sent Origin/Referer is same-origin
 * with the app host. Forged cross-site POSTs carry the attacker's Origin and
 * are rejected with 403. (OWASP-recommended Origin-header CSRF defense.)
 */
class VerifyDesignApiOrigin
{
    /** Methods that mutate state and therefore need the Origin check. */
    private const PROTECTED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        // Only the token-CSRF-exempt design-api routes rely on this check; every
        // other web route still has Laravel's normal token CSRF protection.
        if ($request->is('design-api/*') && in_array($request->getMethod(), self::PROTECTED_METHODS, true)) {
            $source = $request->headers->get('Origin') ?: $request->headers->get('Referer');

            // No Origin/Referer at all on a state-changing browser request is
            // suspicious for this flow — reject rather than fail open.
            if (! $source || parse_url($source, PHP_URL_HOST) !== $request->getHost()) {
                return response()->json(['message' => 'Cross-origin request blocked.'], 403);
            }
        }

        return $next($request);
    }
}
