{{--
    رسم مصغّر لقالب عرض صندوق: شكل تقريبي لما سيظهر في الموقع (home) أو في صفحة النشرة (pdf).
    <x-admin.template-thumb context="home" :type="1" />
--}}
@props(['context' => 'home', 'type' => 1])
@php
    $type = (int) $type;
    $pdf = $context === 'pdf';

    // عناصر الرسم (الإحداثيات من اليسار، والنص محاذى لليمين كالموقع العربي)
    $img = fn ($x, $y, $w, $h) => '<g class="text-primary-500"><rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" rx="2.5" fill="currentColor" fill-opacity=".16"/>'
        .'<path d="M'.($x + $w * .12).' '.($y + $h * .85).' L'.($x + $w * .38).' '.($y + $h * .45).' L'.($x + $w * .55).' '.($y + $h * .68).' L'.($x + $w * .7).' '.($y + $h * .52).' L'.($x + $w * .9).' '.($y + $h * .85).'Z" fill="currentColor" fill-opacity=".35"/>'
        .'<circle cx="'.($x + $w * .78).'" cy="'.($y + $h * .25).'" r="'.(min($w, $h) * 0.09).'" fill="currentColor" fill-opacity=".45"/></g>';
    $title = fn ($x, $y, $w) => '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="3.6" rx="1.8" fill="currentColor" fill-opacity=".72"/>';
    $line = fn ($x, $y, $w) => '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="2.2" rx="1.1" fill="currentColor" fill-opacity=".28"/>';
    $btn = fn ($x, $y, $w, $color = 'text-primary-500') => '<rect class="'.$color.'" x="'.$x.'" y="'.$y.'" width="'.$w.'" height="5" rx="2.5" fill="currentColor" fill-opacity=".9"/>';
    $dot = fn ($cx, $cy, $r, $o = '.35') => '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'" fill="currentColor" fill-opacity="'.$o.'"/>';
    $frame = fn ($x, $y, $w, $h) => '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" rx="3" fill="none" stroke="currentColor" stroke-opacity=".16"/>';
    $play = fn ($cx, $cy) => '<g class="text-primary-500"><circle cx="'.$cx.'" cy="'.$cy.'" r="5" fill="currentColor" fill-opacity=".9"/><path d="M'.($cx - 1.6).' '.($cy - 2.6).' L'.($cx + 2.8).' '.$cy.' L'.($cx - 1.6).' '.($cy + 2.6).'Z" fill="#fff"/></g>';

    $w = $pdf ? 64 : 120;
    $h = $pdf ? 88 : 76;

    $body = match (true) {
        // ---------------- الموقع ----------------
        ! $pdf && $type === 1 => $img(8, 7, 104, 34).$title(28, 46, 84).$line(14, 54, 98).$line(40, 60, 72).$btn(8, 64, 22),
        ! $pdf && $type === 5 => $img(8, 5, 104, 28).$title(34, 37, 78).$line(8, 45, 104).$line(8, 50, 104).$line(8, 55, 104).$line(30, 60, 82).$btn(8, 66, 22),
        ! $pdf && $type === 6 => $img(64, 8, 48, 26).$title(72, 38, 40).$line(64, 45, 48).$line(76, 50, 36)
            .$img(8, 8, 48, 26).$title(16, 38, 40).$line(8, 45, 48).$line(20, 50, 36),
        ! $pdf && $type === 7 => collect([6, 29, 52])->map(fn ($y) => $img(88, $y, 24, 17).$title(34, $y + 2, 48).$line(18, $y + 9, 64).$line(40, $y + 14, 42))->implode(''),
        ! $pdf && $type === 8 => $img(8, 6, 104, 64),
        ! $pdf && $type === 2 => collect([8, 64])->map(fn ($x) => $frame($x, 8, 48, 60).$dot($x + 40, 18, 5.5).$line($x + 6, 29, 36).$line($x + 6, 35, 36).$line($x + 14, 41, 28).$btn($x + 12, 55, 24))->implode(''),
        ! $pdf && $type === 3 => collect([6, 29, 52])->map(fn ($y) => $img(94, $y, 18, 18).$title(40, $y + 2, 48).$line(30, $y + 9, 58).$btn(8, $y + 11, 18))->implode(''),
        ! $pdf && $type === 4 => '<rect x="8" y="12" width="56" height="44" rx="3" fill="currentColor" fill-opacity=".14"/>'.$play(36, 34)
            .$title(74, 16, 38).$line(70, 25, 42).$line(70, 30, 42).$line(82, 35, 30).$btn(88, 46, 24),

        // ---------------- النشرة (صفحة طولية) ----------------
        $pdf && $type === 1 => $dot(52, 11, 5, '.45').$img(6, 19, 52, 30).$title(14, 54, 44).$line(6, 61, 52).$line(18, 66, 40)
            .$btn(38, 74, 20).$btn(14, 74, 20, 'text-warning-500'),
        $pdf && $type === 5 => $img(6, 8, 52, 38).$title(14, 52, 44).$line(6, 60, 52).$line(6, 65, 52).$line(22, 70, 36).$btn(6, 77, 20),
        $pdf && $type === 6 => $img(34, 10, 24, 20).$title(38, 34, 20).$line(34, 40, 24).$line(40, 45, 18)
            .$img(6, 10, 24, 20).$title(10, 34, 20).$line(6, 40, 24).$line(12, 45, 18),
        $pdf && $type === 7 => collect([8, 34, 60])->map(fn ($y) => $img(40, $y, 18, 14).$title(10, $y + 1, 26).$line(6, $y + 7, 30).$line(14, $y + 11, 22))->implode(''),
        $pdf && $type === 8 => $dot(52, 11, 5, '.45').$img(6, 19, 52, 50).$btn(38, 76, 20),
        $pdf && $type === 9 => $dot(52, 11, 5, '.45').$img(6, 19, 52, 30).$title(14, 54, 44).$line(6, 61, 52).$line(18, 66, 40).$btn(18, 74, 28, 'text-success-500'),
        $pdf && $type === 2 => collect([8, 47])->map(fn ($y) => $frame(6, $y, 52, 34).$dot(50, $y + 9, 4.5).$line(10, $y + 17, 42).$line(10, $y + 22, 42).$line(22, $y + 27, 30))->implode(''),
        $pdf && $type === 3 => '<rect x="6" y="6" width="52" height="8" rx="2" fill="currentColor" fill-opacity=".14"/>'.$title(28, 8.2, 22)
            .collect([20, 42, 64])->map(fn ($y) => $img(44, $y, 14, 14).$line(8, $y + 2, 32).$line(14, $y + 7, 26).$btn(6, $y + 12, 16))->implode(''),
        $pdf && $type === 4 => $title(32, 12, 26).$line(34, 20, 24).$line(34, 25, 24).$line(34, 30, 24).$line(34, 35, 24).$line(40, 40, 18).$btn(38, 48, 20)
            .'<rect x="6" y="12" width="24" height="42" rx="2.5" fill="currentColor" fill-opacity=".14"/>'.$play(18, 33),

        default => '<text x="'.($w / 2).'" y="'.($h / 2 + 4).'" text-anchor="middle" font-size="11" font-weight="700" fill="currentColor" fill-opacity=".45">'.$type.'</text>',
    };

    // النشرة: إطار الصفحة وشريط عنوان القسم
    $page = $pdf ? '<rect x=".5" y=".5" width="63" height="87" rx="3" fill="none" stroke="currentColor" stroke-opacity=".18"/>' : '';
@endphp
<svg viewBox="0 0 {{ $w }} {{ $h }}" {{ $attributes->merge(['class' => 'block text-ink', 'aria-hidden' => 'true']) }}>{!! $page.$body !!}</svg>
