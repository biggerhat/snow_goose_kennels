@extends('records.layout', ['title' => 'All records'])
@section('content')
    @foreach ($dogs as $dog)
        @include('records._dog', ['dog' => $dog, 'litters' => $litterMap[$dog->id] ?? collect()])
    @endforeach
    @foreach ($litters as $litter)
        @include('records._litter', ['litter' => $litter])
    @endforeach
@endsection
