@extends('records.layout', ['title' => 'Emergency plan worksheet'])
@section('content')
@php $blank = '<span style="display:inline-block;min-width:2.5in;border-bottom:1px solid #666">&nbsp;</span>'; @endphp
<section class="record">
    <h1>Emergency plan: {{ config('app.name') }}</h1>
    <div class="sub">A worksheet to fill in by hand · printed {{ now()->format('M j, Y') }}</div>
    <p class="muted">AKC asks breeders to keep a written emergency plan. This is a starting point, not an AKC form: change it to fit your kennel, sign and date it, and keep a copy under Kennel papers on the Records page. Review it once a year.</p>

    <h2>Who and where</h2>
    <table>
        <tr><th>Kennel owner</th><td>{{ trim($owner['first'].' '.$owner['last']) ?: '—' }} {{ $owner['phone'] }}</td></tr>
        <tr><th>Address</th><td>{{ collect([$owner['address'], $owner['city'], trim($owner['state'].' '.$owner['zip'])])->filter()->join(', ') ?: '—' }}</td></tr>
        <tr><th>Dogs living here</th><td>{{ $dogCount }} adults{{ $puppyCount ? ', '.$puppyCount.' puppies' : '' }}</td></tr>
    </table>

    <h2>People to call</h2>
    <table>
        <tr><th>Backup person who can take over</th><td>{!! $blank !!} &nbsp; phone {!! $blank !!}</td></tr>
        <tr><th>Second backup</th><td>{!! $blank !!} &nbsp; phone {!! $blank !!}</td></tr>
        @forelse ($vets as $v)
            <tr><th>Vet: {{ $v->name }}</th><td>{{ $v->phone }} {{ collect([$v->address, $v->city, $v->state])->filter()->join(', ') }}</td></tr>
        @empty
            <tr><th>Regular vet</th><td>{!! $blank !!} &nbsp; phone {!! $blank !!}</td></tr>
        @endforelse
        <tr><th>24-hour emergency vet</th><td>{!! $blank !!} &nbsp; phone {!! $blank !!}</td></tr>
        <tr><th>Utility company (power / water)</th><td>{!! $blank !!}</td></tr>
        <tr><th>Fire / police / animal control</th><td>911 · local non-emergency {!! $blank !!}</td></tr>
    </table>

    <h2>If we have to leave</h2>
    <table>
        <tr><th>Where the dogs would go</th><td>{!! $blank !!}</td></tr>
        <tr><th>A second place if that is not possible</th><td>{!! $blank !!}</td></tr>
        <tr><th>Crates or carriers kept ready ({{ $dogCount }}+ needed)</th><td>{!! $blank !!}</td></tr>
        <tr><th>Who loads the dogs, and in what order</th><td>{!! $blank !!}</td></tr>
        <tr><th>Mothers with nursing puppies</th><td>{!! $blank !!}</td></tr>
    </table>

    <h2>Grab-and-go checklist</h2>
    <table>
        @foreach (['Leashes, collars and ID tags', 'Food and bowls for at least 3 days', 'Water and a way to carry it', 'Medicines (heartworm, flea and tick) and the vet’s instructions', 'Copies of vaccine records, microchip numbers and registration papers (print the Records page)', 'Current photos of each dog', 'Towels, waste bags, newspaper, cleaning supplies', 'First-aid kit, flashlight, batteries, phone chargers'] as $item)
            <tr><td style="width:24px">☐</td><td>{{ $item }}</td></tr>
        @endforeach
    </table>

    <h2>If the power goes out, or it is dangerously hot or cold</h2>
    <table>
        <tr><th>Backup power or heat</th><td>{!! $blank !!}</td></tr>
        <tr><th>Where the dogs go to stay cool or warm</th><td>{!! $blank !!}</td></tr>
        <tr><th>Who checks on them, and how often</th><td>{!! $blank !!}</td></tr>
    </table>

    <h2>If a dog is hurt or sick</h2>
    <table>
        <tr><th>First person to call</th><td>{!! $blank !!}</td></tr>
        <tr><th>How the dog is taken to the vet</th><td>{!! $blank !!}</td></tr>
    </table>

    <table style="margin-top:28px"><tr>
        <td>Signed {!! $blank !!}</td><td>Date {!! $blank !!}</td><td>Next review {!! $blank !!}</td>
    </tr></table>
    <div class="foot">Review this plan every year, and after any move or change in your dogs, helpers or vet.</div>
</section>
@endsection
