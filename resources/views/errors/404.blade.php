@extends('errors.layout')
@section('title', 'Page not found')
@section('code', 'Error 404')
@section('heading', 'We can’t find that page')
@section('message', 'It may have moved, or the link may be old. The home page is a good place to start.')
@section('extra')<a class="btn ghost" href="{{ url('/litters') }}">See litters</a>@endsection
