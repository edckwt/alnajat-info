@extends('layouts.site', ['title' => 'أرشيف النشرات', 'canonical' => route('publications.index')])

@section('header')
    @include('site.partials.header-inner')
@endsection

@section('content')
    <section class="sectinon-a">
        <div class="container">
            @include('site.partials.breadcrumb', ['current' => 'أرشيف النشرات', 'page' => $publications->currentPage()])
            @if ($publications->isEmpty())
                <p>لا يوجد بيانات</p>
            @else
                <table class="table table-striped mt-4">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">العنوان</th>
                            <th scope="col" class="text-center">تاريخ النشرة</th>
                            <th scope="col" class="text-center">ملحق النشرة</th>
                            <th scope="col" class="text-center"><i class="fas fa-file-pdf"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($publications as $publication)
                            <tr>
                                <td><a href="{{ route('publication.show', $publication->id) }}">{{ $publication->title }}</a></td>
                                <td class="text-center">{{ $publication->publication_date?->toDateString() }}</td>
                                <td class="text-center">
                                    @if ($publication->other_file)
                                        <a target="_blank" href="{{ \App\Support\Media::url($publication->other_file) }}"><i class="fas fa-file-pdf"></i></a>
                                    @else - - - @endif
                                </td>
                                <td class="text-center"><a target="_blank" href="{{ route('publication.pdf', $publication->id) }}"><i class="fas fa-file-pdf"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $publications->links('pagination::bootstrap-4') }}
            @endif
        </div>
    </section>
@endsection
