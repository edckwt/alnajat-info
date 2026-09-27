{{--
    غلاف النشرة كما يُطبع في ملف الـ PDF: يُرسم من تصميم قالبها (المصمَّم أو نسخة من القديم vN)
    بمتغيراتها الحقيقية (التاريخ الميلادي والهجري …)، بنسبة الورق. $publication، $class (اختياري).
--}}
@php
    use App\Support\Media;
    use App\Support\PdfDesign;
    use App\Support\Theme;
    $design = Theme::coverDesign($publication);
    [$pw, $ph] = $design->pageSize();
    $page = $design->page('cover');
    $vars = Theme::coverVariables($publication);
    $pct = fn ($v, $total) => round($v / $total * 100, 3).'%';
    $cq = fn ($mm) => round($mm / $pw * 100, 3).'cqw';
    $bg = $page['background'];
@endphp
<span class="cover {{ $class ?? '' }}" role="img" aria-label="غلاف نشرة {{ $vars['date_long'] }}"
      style="aspect-ratio: {{ $pw }} / {{ $ph }}; background-color: {{ $bg['color'] }};@if ($bg['image']) background-image: url('{{ Media::url($bg['image']) }}'); background-size: {{ $bg['fit'] === 'width' ? '100% auto' : '100% 100%' }};@endif">
    @foreach ($page['elements'] as $el)
        @php
            $s = $el['style'];
            $box = 'left:'.$pct($el['x'], $pw).';top:'.$pct($el['y'], $ph).';width:'.$pct($el['w'], $pw).';height:'.$pct($el['h'], $ph).';';
        @endphp
        @switch($el['type'])
            @case('text')
                <span class="cover-el cover-text" aria-hidden="true"
                      style="{{ $box }}font-size:{{ $cq($s['size'] * 0.3528) }};line-height:{{ $s['lineHeight'] }};color:{{ $s['color'] }};text-align:{{ $s['align'] }};font-weight:{{ $s['bold'] ? 700 : 400 }};@if ($s['bg'])background:{{ $s['bg'] }};@endif border-radius:{{ $cq($s['radius']) }};padding:{{ $cq($s['padding']) }};">{{ PdfDesign::fill($el['text'], $vars) }}</span>
                @break
            @case('image')
                @if ($el['src'])<img class="cover-el" src="{{ Media::url($el['src']) }}" alt="" style="{{ $box }}@if ($el['fit'] === 'width')height:auto;@endif" loading="lazy">@endif
                @break
            @case('rect')
                <span class="cover-el" style="{{ $box }}background:{{ $s['bg'] ?? 'transparent' }};border-radius:{{ $cq($s['radius']) }};@if ($s['borderWidth'] > 0)border:{{ $cq($s['borderWidth']) }} solid {{ $s['borderColor'] }};@endif"></span>
                @break
            @case('line')
                <span class="cover-el" style="left:{{ $pct($el['x'], $pw) }};top:{{ $pct($el['y'], $ph) }};width:{{ $pct($el['w'], $pw) }};height:max(1px,{{ $cq($s['borderWidth']) }});background:{{ $s['color'] }};"></span>
                @break
        @endswitch
    @endforeach
</span>
