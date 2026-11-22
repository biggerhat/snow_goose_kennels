@extends('records.layout', ['title' => 'Litter record: '.$litter->title])
@section('content')
    @include('records._litter', ['litter' => $litter])
@endsection
