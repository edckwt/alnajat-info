@extends('layouts.site', ['title' => $title])

@section('header')
    @include('site.partials.header-inner')
@endsection

@section('content')
    <section class="sectinon-a">
        <div class="container">
            @include('site.partials.breadcrumb', ['current' => $title, 'page' => $news->currentPage()])
        </div>
    </section>
    @include('site.partials.box', ['type' => 7, 'category' => null, 'posts' => $news->getCollection(),
        'pagination' => $news->links('pagination::bootstrap-4')])
    @if ($news->isEmpty())
        <div class="container"><p>لا يوجد بيانات</p></div>
    @endif
@endsection
