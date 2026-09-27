@extends('layouts.site')

@section('header')
    @include('site.partials.header-home', ['publication' => $publication])
@endsection

@section('content')
    @include('site.partials.boxes', ['boxes' => $boxes])
@endsection
