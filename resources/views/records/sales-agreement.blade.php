@extends('records.layout', ['title' => 'Sales agreement'])
@section('content')
@php
    $fmt = fn ($d) => $d ? $d->format('M j, Y') : '';
    $val = fn ($v) => filled($v) ? $v : '—';
    $who = $puppy->name ?: ($puppy->collar ? $puppy->collar.' collar' : 'Puppy');
    $price = $puppy->sale_price ?? $litter->price;
    $deposit = $entry?->deposit_paid_on ? ($entry->deposit_amount ?? 0) : 0;
    $buyer = $puppy->buyer;
@endphp
<section class="record">
    <h1>Puppy sales agreement</h1>
    <div class="sub">{{ config('app.name') }} · {{ now()->format('M j, Y') }}</div>

    <h2>Seller (breeder)</h2>
    <table>
        <tr><th>Kennel</th><td>{{ config('app.name') }}</td></tr>
        <tr><th>Name</th><td>{{ $val(trim($owner['first'].' '.$owner['last'])) }}</td></tr>
        <tr><th>Address</th><td>{{ $val(collect([$owner['address'], $owner['city'], $owner['state'], $owner['zip']])->filter()->join(', ')) }}</td></tr>
        <tr><th>Phone / email</th><td>{{ $val(collect([$owner['phone'], $owner['email']])->filter()->join(' · ')) }}</td></tr>
    </table>

    <h2>Buyer</h2>
    <table>
        <tr><th>Name</th><td>{{ $buyer?->name ?? '________________________________' }}</td></tr>
        <tr><th>Address</th><td>{{ $buyer ? $val(collect([$buyer->address, $buyer->city, $buyer->state, $buyer->postal_code])->filter()->join(', ')) : '________________________________' }}</td></tr>
        <tr><th>Phone / email</th><td>{{ $buyer ? $val(collect([$buyer->phone, $buyer->email])->filter()->join(' · ')) : '________________________________' }}</td></tr>
    </table>

    <h2>The puppy</h2>
    <table>
        <tr><th>Name or collar</th><td>{{ $who }}</td></tr>
        <tr><th>Boy or girl · colour</th><td>{{ $puppy->sex->label() }} · {{ $val($puppy->color?->label()) }}</td></tr>
        <tr><th>Born</th><td>{{ $val($fmt($litter->born_on)) }}</td></tr>
        <tr><th>Sire · dam</th><td>{{ $val($litter->sire?->registered_name ?: $litter->sire?->name) }} · {{ $val($litter->dam?->registered_name ?: $litter->dam?->name) }}</td></tr>
        <tr><th>Registration</th><td>{{ $puppy->registration_type?->label() ?? '—' }}</td></tr>
        <tr><th>Microchip</th><td>{{ $val($puppy->microchip) }}</td></tr>
        <tr><th>Go-home date</th><td>{{ $val($fmt($puppy->went_home_on ?? $litter->go_home_on)) }}</td></tr>
    </table>

    <h2>Price</h2>
    <table>
        <tr><th>Purchase price</th><td>{{ $price !== null ? '$'.number_format($price) : '$____________' }}</td></tr>
        <tr><th>Deposit received</th><td>{{ $deposit ? '$'.number_format($deposit).' on '.$fmt($entry->deposit_paid_on) : '$____________ on __________' }}</td></tr>
        <tr><th>Balance due</th><td>{{ $price !== null ? '$'.number_format(max($price - $deposit, 0)) : '$____________' }}</td></tr>
    </table>
    @if ($payment)<p class="terms"><strong>How to pay:</strong> {{ $payment }}</p>@endif

    @if ($policies->isNotEmpty() || $extra)
        <h2>Terms</h2>
        <div class="terms">
            @foreach ($policies as $s)
                <h3>{{ $s['title'] }}</h3>
                <p>{{ $s['body'] }}</p>
            @endforeach
            @if ($extra)
                <h3>Other terms</h3>
                <p>{{ $extra }}</p>
            @endif
        </div>
    @else
        <p class="notice">No terms are written yet. Add your deposit, refund, health guarantee, spay/neuter and take-back wording under Website text → How it works, and extra terms under Buyer papers. They print here automatically.</p>
    @endif

    <div class="sign">
        <div>Buyer signature and date</div>
        <div>Seller signature and date</div>
    </div>
    <div class="notice">This paper is built from your own wording. It is not legal advice. Have it checked by someone who knows the rules in your state before you rely on it. Both of you keep a signed copy.</div>
</section>
@endsection
