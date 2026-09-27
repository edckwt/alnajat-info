<?php

namespace App\Http\Middleware;

use App\Support\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** عمليات حساسة لا تتاح لمن دخل بحساب غيره: كلمة المرور وإنهاء جلسات العضو. */
class PreventDuringImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Impersonation::active()) {
            return back()->withErrors(['current_password' => 'غير متاح أثناء الدخول بحساب عضو آخر؛ عُد إلى حسابك أولاً.']);
        }

        return $next($request);
    }
}
