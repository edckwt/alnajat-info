<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** الدخول بحساب عضو بلا كلمة مرور، والعودة إلى الحساب الأصلي. */
class ImpersonationController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        if ($reason = Impersonation::denial($request->user(), $user)) {
            return back()->withErrors(['impersonate' => $reason]);
        }

        Impersonation::start($request, $user);

        return redirect()->route('admin.dashboard')->with('status', "دخلت بحساب «{$user->name}». للعودة إلى حسابك استخدم الشريط أسفل الصفحة.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        abort_unless(Impersonation::active(), 404);

        $was = $request->user()?->name;
        $original = Impersonation::stop($request);

        if (! $original) {
            return redirect()->route('admin.login')->withErrors(['login' => 'انتهت الجلسة؛ ادخل بحسابك من جديد.']);
        }

        return redirect()->route($original->can('users.view') ? 'admin.users.index' : 'admin.dashboard')
            ->with('status', "عدت إلى حسابك من حساب «{$was}».");
    }
}
