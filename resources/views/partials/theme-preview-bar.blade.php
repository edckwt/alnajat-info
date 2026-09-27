{{-- شريط يظهر للمدير فقط أثناء معاينة واجهة غير المعتمدة (?theme=…)؛ مستقل عن تنسيق الواجهتين. --}}
@if (session()->has(\App\Support\Theme::PREVIEW_KEY) && auth()->check())
    @php
        $previewing = \App\Support\Theme::THEMES[\App\Support\Theme::current()]['name'] ?? '';
        $live = \App\Support\Theme::THEMES[\App\Support\Theme::active()]['name'] ?? '';
    @endphp
    <div dir="rtl" role="status" style="position:fixed;inset-inline:0;bottom:0;z-index:9999;display:flex;flex-wrap:wrap;gap:10px 16px;align-items:center;justify-content:center;padding:10px 16px;background:#13232E;color:#fff;font:600 14px/1.6 system-ui,'Segoe UI',Tahoma,sans-serif;box-shadow:0 -6px 20px rgba(0,0,0,.2)">
        <span>تعاين «{{ $previewing }}» — الزوار يرون «{{ $live }}».</span>
        <a href="{{ request()->fullUrlWithQuery(['theme' => 'off']) }}" style="color:#13232E;background:#fff;padding:5px 12px;border-radius:8px;text-decoration:none">إنهاء المعاينة</a>
        @can('settings.general')
            <a href="{{ route('admin.settings.edit', ['tab' => 'general']) }}" style="color:#FBEBD3;text-decoration:underline">اعتمادها من الإعدادات</a>
        @endcan
    </div>
@endif
