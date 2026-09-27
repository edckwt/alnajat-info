{{--
    صناديق الصفحة الرئيسية (أو صفحة النشرة) بترتيب الإعدادات.
    صندوق مرئيات بجانب صندوق صوتيات متتاليين يُعرضان معاً في شريط داكن واحد (7/5).
--}}
@php
    $items = $boxes->values();
    $isNews = fn ($item, $types) => $item && $item['kind'] === 'news' && in_array((int) $item['box']->type, $types, true);
    $skip = -1;
@endphp
@foreach ($items as $i => $item)
    @continue($i === $skip)
    @php
        $next = $items->get($i + 1);
    @endphp
    @if ($isNews($item, [3, 4]) && $isNews($next, [3, 4]) && (int) $item['box']->type !== (int) $next['box']->type)
        @php
            $skip = $i + 1;
            [$video, $audio] = (int) $item['box']->type === 4 ? [$item, $next] : [$next, $item];
        @endphp
        <section class="band band-dark">
            <div class="wrap media-pair">
                <div class="media-pair-main">
                    @include('site.partials.box', ['type' => 4, 'category' => $video['box']->category, 'posts' => $video['posts'], 'embedded' => true])
                </div>
                <div class="media-pair-side">
                    @include('site.partials.box', ['type' => 3, 'category' => $audio['box']->category, 'posts' => $audio['posts'], 'embedded' => true])
                </div>
            </div>
        </section>
        @continue
    @endif
    @switch($item['kind'])
        @case('banner') @include('site.partials.banner', ['banner' => $item['box']->banner]) @break
        @case('code') <div class="wrap band-code">{!! $item['box']->code !!}</div> @break
        @default @include('site.partials.box', ['type' => (int) $item['box']->type, 'category' => $item['box']->category, 'posts' => $item['posts']])
    @endswitch
@endforeach
