@php
    $day = $publication->publication_date ? \App\Support\ArabicDate::long($publication->publication_date) : $publication->title;
@endphp
@extends('layouts.site', [
    'title' => $day,
    'description' => $publication->description,
    'image' => \App\Support\Media::url($publication->image),
    'canonical' => route('publication.show', $publication->id),
    'active' => 'archive',
])

@section('content')
    <div class="wrap">
        @include('site.partials.breadcrumb', ['parents' => [['title' => 'أرشيف النشرات', 'url' => route('publications.index')]], 'current' => $day])
    </div>

    <section class="wrap issue-hero">
        <a class="issue-hero-cover" href="{{ route('publication.pdf', $publication->id) }}" target="_blank">
            @include('site.partials.cover', ['publication' => $publication])
        </a>
        <div class="issue-hero-text">
            <span class="pill">نشرة</span>
            <h1>{{ $day }}</h1>
            @if (filled($publication->description))<p class="lead">{{ $publication->description }}</p>@endif
            <div class="actions">
                <a class="btn btn-primary btn-lg" href="{{ route('publication.pdf', $publication->id) }}" target="_blank">تصفّح النشرة PDF</a>
                <a class="btn btn-outline btn-lg" href="{{ route('publication.pdf', ['id' => $publication->id, 'download' => 1]) }}">تحميل</a>
                @if ($publication->other_file)
                    <a class="btn btn-outline btn-lg" href="{{ \App\Support\Media::url($publication->other_file) }}" target="_blank">ملحق النشرة</a>
                @endif
            </div>
            @if (filled($publication->body))<div class="prose">{!! $publication->body !!}</div>@endif
        </div>
    </section>

    @if ($boxes->isEmpty())
        <div class="wrap"><div class="empty"><h2>لا توجد أخبار منشورة لهذه النشرة على الموقع</h2><p>يمكنك تصفّح ملف الـ PDF.</p></div></div>
    @else
        @include('site.partials.boxes', ['boxes' => $boxes])
    @endif
@endsection
