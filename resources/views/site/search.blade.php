@extends('layouts.site', ['title' => $term !== '' ? 'نتائج البحث: '.$term : 'البحث'])

@section('header')
    @include('site.partials.header-inner')
@endsection

@section('content')
    <div class="container">
        <form role="search" method="get" action="{{ route('search') }}" class="mt-3">
            <div class="form-group"><input type="text" name="s" class="form-control" placeholder="بحث" value="{{ $term }}" required></div>
            <button type="submit" class="btn btn-default">بحث</button>
        </form>
    </div>
    @if ($news)
        <section class="sectinon-a"><div class="container">
            @include('site.partials.breadcrumb', ['current' => 'نتائج البحث', 'page' => $news->currentPage()])
        </div></section>
        @include('site.partials.box', ['type' => 7, 'category' => null, 'posts' => $news->getCollection(),
            'pagination' => $news->links('pagination::bootstrap-4')])
        @if ($news->isEmpty())
            <div class="container"><p>لا يوجد بيانات</p></div>
        @endif
    @endif
@endsection
