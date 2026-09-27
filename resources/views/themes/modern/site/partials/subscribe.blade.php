@php
    $whatsapp = \App\Models\Setting::get('subscribe_whasapp');
    $email = \App\Models\Setting::get('subscribe_email');
@endphp
@if (filled($whatsapp) || filled($email))
    <section class="wrap">
        <div class="subscribe">
            <div>
                <h2>تصلك النشرة كل يوم</h2>
                <p>اشترك مجاناً، ويمكنك إلغاء الاشتراك في أي وقت.</p>
            </div>
            <div class="subscribe-actions">
                @if (filled($whatsapp))
                    <a class="btn btn-light" href="{{ $whatsapp }}" target="_blank" rel="noopener">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 0 1-13.3 7.9L3 21l1.1-4.6A9 9 0 1 1 21 12z"/></svg>
                        عبر واتساب</a>
                @endif
                @if (filled($email))
                    <a class="btn btn-ghost-light" href="#subscribe-email" data-dialog-open="subscribe-email">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        عبر البريد</a>
                @endif
            </div>
        </div>
    </section>
@endif
