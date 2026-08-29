@extends('errors.layout')

@section('code', '403')
@section('title', 'Access denied')
@section('message', 'You don\'t have permission to view this page. If you think this is a mistake, please sign in or contact support.')

@section('extra_action')
    <a href="{{ route('frontend.login') }}" class="err-btn err-btn-outline">
        <i class="fa-solid fa-right-to-bracket" style="font-size:12px"></i> Sign in
    </a>
@endsection
