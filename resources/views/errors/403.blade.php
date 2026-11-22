@extends('errors.layout')
@section('title', 'Not allowed')
@section('code', 'Error 403')
@section('heading', 'You can’t open that page')
@section('message', 'It is private, or you need to log in first.')
@section('extra')<a class="btn ghost" href="{{ route('login') }}">Owner login</a>@endsection
