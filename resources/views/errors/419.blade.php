@extends('errors.layout')
@section('title', 'Page expired')
@section('code', 'Error 419')
@section('heading', 'That page timed out')
@section('message', 'It sat open for a while. Go back, refresh the page, and try again. Nothing was lost on the server.')
@section('extra')<a class="btn ghost" href="javascript:history.back()">Go back</a>@endsection
