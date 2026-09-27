<nav class="breadcrumb">
    <a class="breadcrumb-item" href="{{ route('home') }}">الرئيسية</a>
    @foreach ($parents ?? [] as $parent)
        <a class="breadcrumb-item" href="{{ $parent['url'] }}">{{ $parent['title'] }}</a>
    @endforeach
    <span class="breadcrumb-item active">{{ $current }}@if (($page ?? 1) > 1) | الصفحة {{ $page }}@endif</span>
</nav>
