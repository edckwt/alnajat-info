@php($day = $publication->publication_date ? \App\Support\ArabicDate::long($publication->publication_date) : $publication->title)
@extends('layouts.site', [
    'title' => $day,
    'description' => $publication->description,
    'image' => \App\Support\Media::url($publication->image),
    'canonical' => route('publication.show', $publication->id),
])

@section('header')
    @include('site.partials.header-inner')
@endsection

@section('content')
    <section class="sectinon-a">
        <div class="container">
            @include('site.partials.breadcrumb', ['current' => $day])
            <div class="publication-content">
                @if ($publication->image)
                    <div class="largeimage"><img src="{{ \App\Support\Media::url($publication->image) }}" alt="{{ $publication->title }}" class="w-100"></div>
                @endif
                <div class="news-title"><h2 class="text-center border-bottom mb-4 pb-3">{{ $day }}</h2></div>
                <div class="news-excerpet">
                    {!! $publication->body !!}
                    @include('site.partials.boxes', ['boxes' => $boxes])
                </div>
            </div>
        </div>
    </section>
@endsection
