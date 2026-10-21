<?php

namespace App\Http\Middleware;

use App\Support\Firewall as Guard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * أول ما يمر عليه كل طلب (بعد TrustProxies): العنوان المحظور يُرفض (403) قبل أي عمل،
 * وطلب رابط من المصيدة يُحظر عنوانه فوراً. التفاصيل في App\Support\Firewall.
 */
class Firewall
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Guard::enabled() || ! ($ip = $request->ip()) || Guard::isAllowed($ip)) {
            return $next($request);
        }

        if (Guard::isBlocked($ip)) {
            Guard::countBlockedHit($ip);

            return $this->denied($ip);
        }

        if (Guard::trapMatch($request) !== null) {
            Guard::block($ip, 'trap', rawurldecode($request->getRequestUri()));

            return $this->denied($ip);
        }

        return $next($request);
    }

    private function denied(string $ip): Response
    {
        return response()->view('errors.blocked', ['ip' => $ip], 403)
            ->header('Cache-Control', 'no-store, private');
    }
}
