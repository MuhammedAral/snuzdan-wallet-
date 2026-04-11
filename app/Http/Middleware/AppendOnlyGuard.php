<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppendOnlyGuard
{
    /**
     * Handle an incoming request.
     * Prevents UPDATE (PUT/PATCH) and DELETE methods for financial ledger tables.
     * Only allows POST/GET. To void a transaction, a specific POST endpoint must be used.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $forbiddenMethods = ['PUT', 'PATCH', 'DELETE'];

        if (in_array($request->method(), $forbiddenMethods)) {
            return response()->json([
                'message' => 'Append-only mimarisi gereği finansal tablolarda doğrudan UPDATE veya DELETE işlemi yapılamaz. Lütfen işlemi iptal mekanizması (void) ile gerçekleştirin.',
                'error'   => 'METHOD_NOT_ALLOWED_IN_LEDGER'
            ], 403);
        }

        return $next($request);
    }
}
