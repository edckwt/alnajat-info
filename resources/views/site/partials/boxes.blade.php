@foreach ($boxes as $item)
    @switch($item['kind'])
        @case('banner') @include('site.partials.banner', ['banner' => $item['box']->banner]) @break
        @case('code') {!! $item['box']->code !!} @break
        @default @include('site.partials.box', ['type' => (int) $item['box']->type, 'category' => $item['box']->category, 'posts' => $item['posts']])
    @endswitch
@endforeach
