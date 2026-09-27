{{--
    صورة مصغّرة حية لصفحة من قالب مصمَّم (بنسبة الورق الحقيقية)، تُرسم من JSON التصميم نفسه.
    <x-admin.pdf-design-page :design="$template->design" page="cover" />
--}}
@props(['design', 'page' => 'cover', 'vars' => null])
@php
    $d = \App\Support\PdfDesign::fromArray((array) $design);
    [$pw, $ph] = $d->pageSize();
    $p = $d->page($page);
    $vars ??= [
        'date_long' => \App\Support\ArabicDate::long(now()), 'date_hijri' => \App\Support\ArabicDate::hijri(now()),
        'date' => now()->toDateString(), 'day_name' => \App\Support\ArabicDate::dayName(now()),
        'publication_title' => 'نشرة '.now()->toDateString(), 'publication_number' => '612',
        'site_title' => (string) \App\Models\Setting::get('site_title'), 'site_slogan' => (string) \App\Models\Setting::get('site_slogan'),
        'category_name' => 'النجاة في الصحف', 'whatsapp' => (string) \App\Models\Setting::get('site_whatsapp'),
    ];
    $pct = fn ($v, $total) => round($v / $total * 100, 3).'%';
    $bg = $p['background'];
@endphp
<span {{ $attributes->merge(['class' => 'block relative overflow-hidden rounded-lg border border-line shadow-card']) }}
      style="aspect-ratio: {{ $pw }} / {{ $ph }}; container-type: inline-size; background-color: {{ $bg['color'] }};
             @if ($bg['image']) background-image: url('{{ \App\Support\Media::url($bg['image']) }}'); background-repeat: no-repeat; background-position: top center;
             background-size: {{ $bg['fit'] === 'width' ? '100% auto' : '100% 100%' }}; @endif">
    @if ($page === 'section')
        @php
            $c = $p['content'];
        @endphp
        <span class="absolute border border-dashed border-primary-400/70 bg-primary-500/5"
              style="left: {{ $pct($c['x'], $pw) }}; top: {{ $pct($c['y'], $ph) }}; width: {{ $pct($c['w'], $pw) }}; height: {{ $pct($c['h'], $ph) }};"></span>
    @endif
    @foreach ($p['elements'] as $el)
        @php
            $s = $el['style'];
            $box = 'left: '.$pct($el['x'], $pw).'; top: '.$pct($el['y'], $ph).'; width: '.$pct($el['w'], $pw).'; height: '.$pct($el['h'], $ph).';';
        @endphp
        @switch($el['type'])
            @case('text')
                <span class="absolute overflow-hidden" dir="rtl"
                      style="{{ $box }} font-size: {{ round($s['size'] * 0.3528 / $pw * 100, 3) }}cqw; line-height: {{ $s['lineHeight'] }}; color: {{ $s['color'] }};
                             text-align: {{ $s['align'] }}; font-weight: {{ $s['bold'] ? 700 : 400 }};
                             @if ($s['bg']) background: {{ $s['bg'] }}; @endif border-radius: {{ round($s['radius'] / $pw * 100, 3) }}cqw;
                             padding: {{ round($s['padding'] / $pw * 100, 3) }}cqw; white-space: pre-line;">{{ \App\Support\PdfDesign::fill($el['text'], $vars + ['page' => '3']) }}</span>
                @break
            @case('image')
                @if ($el['src'])<img src="{{ \App\Support\Media::url($el['src']) }}" alt="" class="absolute" style="{{ $box }} @if ($el['fit'] === 'width') height: auto; @endif" loading="lazy">@endif
                @break
            @case('rect')
                <span class="absolute" style="{{ $box }} background: {{ $s['bg'] ?? 'transparent' }}; border-radius: {{ round($s['radius'] / $pw * 100, 3) }}cqw;
                      @if ($s['borderWidth'] > 0) border: {{ max(1, round($s['borderWidth'] / $pw * 300)) }}px solid {{ $s['borderColor'] }}; @endif"></span>
                @break
            @case('line')
                <span class="absolute" style="left: {{ $pct($el['x'], $pw) }}; top: {{ $pct($el['y'], $ph) }}; width: {{ $pct($el['w'], $pw) }}; height: max(1px, {{ round($s['borderWidth'] / $pw * 100, 3) }}cqw); background: {{ $s['color'] }};"></span>
                @break
            @case('qr')
                <span class="absolute grid place-items-center bg-white text-[8px] font-bold text-ink border border-ink/60" style="{{ $box }}">QR</span>
                @break
        @endswitch
    @endforeach
</span>
