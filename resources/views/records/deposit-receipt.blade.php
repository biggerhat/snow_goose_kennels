@extends('records.layout', ['title' => 'Deposit receipt'])
@section('content')
@php
    $fmt = fn ($d) => $d ? $d->format('M j, Y') : '';
    $val = fn ($v) => filled($v) ? $v : '—';
@endphp
<section class="record">
    <h1>Deposit receipt</h1>
    <div class="sub">{{ config('app.name') }} · Receipt no. {{ str_pad((string) $entry->id, 4, '0', STR_PAD_LEFT) }}</div>

    <table>
        <tr><th>Received from</th><td>{{ $entry->contact?->name ?? '—' }}</td></tr>
        <tr><th>Amount</th><td>{{ $entry->deposit_amount !== null ? '$'.number_format($entry->deposit_amount) : '$____________' }}</td></tr>
        <tr><th>Date received</th><td>{{ $entry->deposit_paid_on ? $fmt($entry->deposit_paid_on) : '__________' }}</td></tr>
        <tr><th>For</th><td>{{ $entry->litter?->title ?? 'A puppy from the next available litter' }}@if ($entry->pick_order) · pick no. {{ $entry->pick_order }}@endif @if ($entry->puppy) · {{ $entry->puppy->name ?: $entry->puppy->collar.' collar' }}@endif</td></tr>
        <tr><th>Received by</th><td>{{ $val(trim($owner['first'].' '.$owner['last'])) }}</td></tr>
    </table>

    @if ($deposit_policy)
        <h2>About this deposit</h2>
        <div class="terms"><p>{{ $deposit_policy }}</p></div>
    @endif
    @if ($payment)
        <p class="terms"><strong>How to pay the balance:</strong> {{ $payment }}</p>
    @endif

    <div class="sign"><div>Received by (signature)</div><div>Date</div></div>
    <div class="foot">Thank you. Keep this receipt with your puppy paperwork.</div>
</section>
@endsection
