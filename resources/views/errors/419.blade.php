@extends('errors.layout')

@section('code', '419')
@section('title', 'Session expired')
@section('message', 'Your session has expired. Please refresh the page and try again.')

@section('extra_action')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="err-btn err-btn-outline"
       onclick="event.preventDefault(); window.location.reload();">
        <i class="fa-solid fa-rotate-right" style="font-size:12px"></i> Refresh page
    </a>
@endsection
