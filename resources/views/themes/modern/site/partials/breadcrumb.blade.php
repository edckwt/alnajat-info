<nav class="crumbs" aria-label="مسار التصفح">
    <a href="{{ route('home') }}">الرئيسية</a>
    @foreach ($parents ?? [] as $parent)
        <span aria-hidden="true">/</span><a href="{{ $parent['url'] }}">{{ $parent['title'] }}</a>
    @endforeach
    <span aria-hidden="true">/</span><span aria-current="page">{{ \Illuminate\Support\Str::limit($current, 70) }}@if (($page ?? 1) > 1) — الصفحة {{ $page }}@endif</span>
</nav>
