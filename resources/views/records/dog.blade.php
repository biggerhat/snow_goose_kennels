@extends('records.layout', ['title' => 'Dog record: '.$dog->name])
@section('content')
    @include('records._dog', ['dog' => $dog, 'litters' => $litters])
@endsection
