{{-- شريط يظهر لمن دخل بحساب عضو آخر (Impersonation) في كل الصفحات، للعودة إلى حسابه. تنسيق مستقل عن اللوحة والواجهات. --}}
@if (\App\Support\Impersonation::active() && auth()->check())
    @php($impersonator = \App\Support\Impersonation::impersonator())
    <div dir="rtl" role="status" data-impersonation-bar
         style="position:fixed;inset-inline-start:16px;bottom:{{ session()->has(\App\Support\Theme::PREVIEW_KEY) ? '72px' : '16px' }};z-index:10000;display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center;max-width:calc(100vw - 32px);padding:10px 12px 10px 16px;border-radius:16px;background:#5A3604;color:#fff;font:600 14px/1.6 system-ui,'Segoe UI',Tahoma,sans-serif;box-shadow:0 10px 30px rgba(0,0,0,.25)">
        <span>
            أنت داخل حساب «{{ auth()->user()->name }}»
            @if ($impersonator)<span style="opacity:.75;font-weight:400">— حسابك: {{ $impersonator->name }}</span>@endif
        </span>
        <form method="POST" action="{{ route('admin.impersonate.leave') }}" style="margin:0">
            @csrf @method('DELETE')
            <button type="submit" style="cursor:pointer;border:0;color:#5A3604;background:#FBEBD3;padding:6px 14px;border-radius:10px;font:inherit">العودة إلى حسابي</button>
        </form>
    </div>
@endif
