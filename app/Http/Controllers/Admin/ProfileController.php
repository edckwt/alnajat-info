<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * الملف الشخصي لكل عضو في اللوحة (بلا صلاحية خاصة): بياناته وصورته وكلمة مروره وجلساته.
 * الدور والصلاحيات والتفعيل لا تُعدّل من هنا؛ مكانها صفحة الأعضاء.
 */
class ProfileController extends Controller
{
    public const TABS = ['overview' => 'نظرة عامة', 'account' => 'البيانات الشخصية', 'security' => 'الأمان'];

    public function show(Request $request): View
    {
        $user = $request->user();
        $news = News::where('created_by', $user->id);

        return view('admin.profile.show', [
            'user' => $user,
            'tab' => array_key_exists((string) $request->query('tab'), self::TABS) ? $request->query('tab') : 'overview',
            'tabs' => self::TABS,
            'stats' => [
                'news' => (clone $news)->count(),
                'month' => (clone $news)->where('created_at', '>=', now()->startOfMonth())->count(),
                'published' => (clone $news)->where('is_active', true)->count(),
            ],
            'recentNews' => (clone $news)->latest('id')->limit(6)->get(['id', 'title', 'is_active', 'published_date', 'created_at']),
            'sessions' => $this->sessions($request),
        ]);
    }

    public function update(Request $request, ImageUploader $uploader): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s()-]+$/'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar_file' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ], [
            'username.regex' => 'اسم المستخدم بالأحرف الإنجليزية والأرقام و . _ - فقط.',
            'phone.regex' => 'رقم الجوال بالأرقام فقط (ويُسمح بـ + والمسافات).',
        ], [
            'name' => 'الاسم', 'username' => 'اسم المستخدم', 'email' => 'البريد الإلكتروني',
            'phone' => 'الجوال', 'bio' => 'النبذة', 'avatar_file' => 'الصورة الشخصية',
        ]);

        $user->fill(collect($data)->only(['name', 'username', 'email', 'phone', 'bio'])->all());

        if ($request->hasFile('avatar_file')) {
            $user->avatar = $uploader->store($request->file('avatar_file'), 'avatar');
        } elseif ($request->boolean('remove_avatar')) {
            $user->avatar = null;
        }

        $user->save();

        return redirect()->route('admin.profile.show', ['tab' => 'account'])->with('status', 'تم حفظ بياناتك.');
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.',
            'password.different' => 'اختر كلمة مرور مختلفة عن الحالية.',
        ], [
            'current_password' => 'كلمة المرور الحالية', 'password' => 'كلمة المرور الجديدة',
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => $request->input('password'),
            'legacy_password' => null,
            'remember_token' => Str::random(60),
        ])->save();

        // الأجهزة الأخرى تخرج؛ هذه الجلسة تبقى بمعرّف جديد
        $this->deleteOtherSessions($request);
        $request->session()->regenerate();

        return redirect()->route('admin.profile.show', ['tab' => 'security'])
            ->with('status', 'تم تغيير كلمة المرور، وخرجت جلساتك على الأجهزة الأخرى.');
    }

    /** إنهاء كل جلساتي على الأجهزة الأخرى (يتطلب كلمة المرور). */
    public function logoutOthers(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']], [
            'current_password.current_password' => 'كلمة المرور غير صحيحة.',
        ], ['current_password' => 'كلمة المرور']);

        $request->user()->forceFill(['remember_token' => Str::random(60)])->save();
        $this->deleteOtherSessions($request);

        return redirect()->route('admin.profile.show', ['tab' => 'security'])->with('status', 'تم تسجيل الخروج من الأجهزة الأخرى.');
    }

    /** الجلسات النشطة (فقط إن كانت الجلسات مخزنة في قاعدة البيانات). */
    private function sessions(Request $request): ?array
    {
        if (config('session.driver') !== 'database') {
            return null;
        }

        return DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'current' => $s->id === $request->session()->getId(),
                'ip' => $s->ip_address,
                'device' => $this->device((string) $s->user_agent),
                'last' => \Illuminate\Support\Carbon::createFromTimestamp($s->last_activity),
            ])
            ->all();
    }

    private function deleteOtherSessions(Request $request): void
    {
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }
    }

    /** «macOS · Chrome» من نص المتصفح. */
    private function device(string $agent): string
    {
        $os = match (true) {
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'جهاز',
        };
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'متصفح',
        };

        return $os.' · '.$browser;
    }
}
