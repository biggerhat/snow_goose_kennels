@extends('records.layout', ['title' => 'Stud agreement'])
@section('content')
@php
    $fmt = fn ($d) => $d ? $d->format('M j, Y') : '';
    $val = fn ($v) => filled($v) ? $v : '—';
    $ownerName = fn ($dog) => $dog?->is_ours === false ? ($dog->ownerContact?->name ?? 'Owner not entered') : (trim($owner['first'].' '.$owner['last']) ?: config('app.name'));
    $ownerLine = fn ($dog) => $dog?->is_ours === false
        ? collect([$dog->ownerContact?->address, $dog->ownerContact?->city, $dog->ownerContact?->state, $dog->ownerContact?->phone])->filter()->join(', ')
        : collect([$owner['address'], $owner['city'], $owner['state'], $owner['phone']])->filter()->join(', ');
@endphp
<section class="record">
    <h1>Stud service agreement</h1>
    <div class="sub">{{ $litter->title }} · {{ now()->format('M j, Y') }}</div>

    <h2>The sire (stud)</h2>
    <table>
        <tr><th>Name</th><td>{{ $val($litter->sire?->name) }} {{ $litter->sire?->registered_name ? '· '.$litter->sire->registered_name : '' }}</td></tr>
        <tr><th>AKC number</th><td>{{ $val($litter->sire?->registry_number) }}</td></tr>
        <tr><th>Owner</th><td>{{ $ownerName($litter->sire) }}</td></tr>
        <tr><th>Contact</th><td>{{ $val($ownerLine($litter->sire)) }}</td></tr>
    </table>

    <h2>The dam (mother)</h2>
    <table>
        <tr><th>Name</th><td>{{ $val($litter->dam?->name) }} {{ $litter->dam?->registered_name ? '· '.$litter->dam->registered_name : '' }}</td></tr>
        <tr><th>AKC number</th><td>{{ $val($litter->dam?->registry_number) }}</td></tr>
        <tr><th>Owner</th><td>{{ $ownerName($litter->dam) }}</td></tr>
        <tr><th>Contact</th><td>{{ $val($ownerLine($litter->dam)) }}</td></tr>
    </table>

    <h2>The breeding</h2>
    <table>
        <tr><th>Date bred</th><td>{{ $litter->bred_on ? $fmt($litter->bred_on) : '__________' }}</td></tr>
        <tr><th>How and where</th><td>{{ $litter->breeding_method?->label() ?? '—' }} · {{ $val($litter->bred_place) }}</td></tr>
        <tr><th>Stud fee</th><td>{{ $litter->stud_fee !== null ? '$'.number_format($litter->stud_fee) : '$____________' }}@if ($litter->stud_fee_paid_on), paid {{ $fmt($litter->stud_fee_paid_on) }}@endif</td></tr>
        <tr><th>Return service or other terms</th><td><span class="blank"></span></td></tr>
    </table>

    @if ($terms)
        <h2>Terms</h2>
        <div class="terms"><p>{{ $terms }}</p></div>
    @else
        <p class="notice">No terms are written yet. Add them under Website text → Buyer papers → Terms for the stud agreement, or write them by hand below.</p>
        <p style="height:1in"></p>
    @endif

    <p class="muted">AKC needs the signature of the sire's owner on the litter application, and of every owner of the dam. This agreement does not replace that.</p>

    <div class="sign">
        <div>Owner of the sire: signature and date</div>
        <div>Owner of the dam: signature and date</div>
    </div>
    <div class="notice">This paper is built from your own wording. It is not legal advice. Have it checked by someone who knows the rules in your state before you rely on it.</div>
</section>
@endsection
