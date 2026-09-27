<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** إعداد «إغلاق الموقع»: الموقع العام يعرض سبب الإغلاق، ولوحة التحكم تبقى تعمل. */
class EnsureSiteIsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((string) Setting::get('close_site') === '1' && ! $request->user()) {
            return response()->view('site.closed', ['cause' => Setting::get('close_site_cause')], 503);
        }

        return $next($request);
    }
}
