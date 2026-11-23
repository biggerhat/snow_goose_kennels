@extends('records.layout', ['title' => 'Go-home packet'])
@section('content')
@php
    $fmt = fn ($d) => $d ? $d->format('M j, Y') : '';
    $val = fn ($v) => filled($v) ? $v : '—';
    $who = $puppy->name ?: ($puppy->collar ? $puppy->collar.' collar' : 'Puppy');
    $lastWeight = $puppy->weights->last();
    $weightText = fn ($g) => $g ? floor($g / 453.592).' lb '.round(fmod($g, 453.592) / 28.3495).' oz' : '—';
    $due = $puppy->healthRecords->filter(fn ($r) => $r->next_due_on)->sortBy('next_due_on');
@endphp
<section class="record">
    <h1>Going home: {{ $who }}</h1>
    <div class="sub">From {{ config('app.name') }}@if ($puppy->buyer) · for {{ $puppy->buyer->name }}@endif · {{ now()->format('M j, Y') }}</div>

    <h2>About {{ $who }}</h2>
    <table>
        <tr><th>Boy or girl</th><td>{{ $puppy->sex->label() }}</td></tr>
        <tr><th>Colour</th><td>{{ $val($puppy->color?->label()) }}</td></tr>
        <tr><th>Born</th><td>{{ $val($fmt($litter->born_on)) }}</td></tr>
        <tr><th>Father (sire)</th><td>{{ $val($litter->sire?->registered_name ?: $litter->sire?->name) }}</td></tr>
        <tr><th>Mother (dam)</th><td>{{ $val($litter->dam?->registered_name ?: $litter->dam?->name) }}</td></tr>
        <tr><th>Went home</th><td>{{ $val($fmt($puppy->went_home_on)) }}</td></tr>
        <tr><th>Weight</th><td>{{ $lastWeight ? $weightText($lastWeight->weight_g).' on '.$fmt($lastWeight->weighed_on) : '—' }}</td></tr>
    </table>

    <h2>Registration and microchip</h2>
    <table>
        <tr><th>AKC registration</th><td>{{ $puppy->registration_type?->label() ?? '—' }}</td></tr>
        <tr><th>AKC litter number</th><td>{{ $val($litter->akc_litter_number) }}</td></tr>
        <tr><th>AKC number for this puppy</th><td>{{ $val($puppy->akc_number) }}</td></tr>
        <tr><th>Papers given</th><td>{{ $val($fmt($puppy->papers_given_on)) }}</td></tr>
        <tr><th>Microchip number</th><td>{{ $val($puppy->microchip) }}</td></tr>
        <tr><th>Microchip enrolled</th><td>{{ $puppy->reunite_enrolled_on ? $fmt($puppy->reunite_enrolled_on) : 'Not recorded. Ask us, or register the chip yourself with the chip company.' }}</td></tr>
    </table>

    <h2>Health care so far</h2>
    @if ($puppy->healthRecords->isEmpty())
        <p class="muted">Nothing recorded yet.</p>
    @else
        <table class="grid">
            <tr><th>Date</th><th>What</th><th>Kind</th><th>Next due</th></tr>
            @foreach ($puppy->healthRecords->sortBy('given_on') as $r)
                <tr><td>{{ $fmt($r->given_on) }}</td><td>{{ $r->name }}</td><td>{{ $r->type->label() }}</td><td>{{ $val($fmt($r->next_due_on)) }}</td></tr>
            @endforeach
        </table>
    @endif
    <p>Vet check before going home: {{ $puppy->vet_checked_on ? $fmt($puppy->vet_checked_on) : '—' }}</p>
    @if ($due->isNotEmpty())
        <p><strong>Coming up:</strong> {{ $due->map(fn ($r) => $r->name.' on '.$fmt($r->next_due_on))->join('; ') }}. Take this page to your vet.</p>
    @endif

    @if ($food)
        <h2>What {{ $who }} eats</h2>
        <div class="terms"><p>{{ $food }}</p></div>
    @endif
    @if ($care)
        <h2>The first weeks at home</h2>
        <div class="terms"><p>{{ $care }}</p></div>
    @endif

    <h2>Questions? Call us</h2>
    <p>{{ config('app.name') }}@if ($owner['first'] || $owner['last']) · {{ trim($owner['first'].' '.$owner['last']) }}@endif<br>
        {{ collect([$contact['phone'], $contact['email']])->filter()->join(' · ') }}
        @if ($vet)<br>Our vet: {{ $vet }}@endif</p>

    <h2>Notes</h2>
    <p style="height:1.2in"></p>
    <div class="foot">Keep this page with {{ $who }}'s vet records.</div>
</section>
@endsection
