@extends('errors.layout')

@section('code', '404')
@section('title', 'Page not found')
@section('message', 'The page you\'re looking for doesn\'t exist or may have been moved.')

@section('extra_action')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/shop') }}" class="err-btn err-btn-outline">
        <i class="fa-solid fa-arrow-left" style="font-size:12px"></i> Go back
    </a>
@endsection
