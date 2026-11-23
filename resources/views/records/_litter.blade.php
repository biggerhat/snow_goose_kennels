@php
    $fmt = fn ($d) => $d ? $d->format('M j, Y') : '';
    $val = fn ($v) => filled($v) ? $v : '—';
    $males = $litter->puppies->where('sex.value', 'male')->count();
    $females = $litter->puppies->where('sex.value', 'female')->count();
@endphp
<section class="record">
    <h1>Litter record: {{ $litter->title }}</h1>
    <div class="sub">{{ config('app.name') }} · printed {{ now()->format('M j, Y') }}</div>

    <h2>Parents and mating</h2>
    <table>
        <tr><th>Sire (father)</th><td>{{ $litter->sire?->name ?? '—' }} {{ $litter->sire?->registered_name ? '· '.$litter->sire->registered_name : '' }} {{ $litter->sire?->registry_number ? '· '.$litter->sire->registry_number : '' }}</td></tr>
        <tr><th>Dam (mother)</th><td>{{ $litter->dam?->name ?? '—' }} {{ $litter->dam?->registered_name ? '· '.$litter->dam->registered_name : '' }} {{ $litter->dam?->registry_number ? '· '.$litter->dam->registry_number : '' }}</td></tr>
        <tr><th>Date bred</th><td>{{ $val($fmt($litter->bred_on)) }}</td></tr>
        <tr><th>How and where</th><td>{{ $litter->breeding_method?->label() ?? '—' }} · {{ $val($litter->bred_place) }}</td></tr>
        <tr><th>Handled by</th><td>{{ $val($litter->bred_handler) }}</td></tr>
        <tr><th>Stud fee</th><td>{{ $litter->stud_fee !== null ? '$'.number_format($litter->stud_fee).($litter->stud_fee_paid_on ? ', paid '.$fmt($litter->stud_fee_paid_on) : '') : '—' }}</td></tr>
    </table>

    <h2>Whelping and AKC</h2>
    <table>
        <tr><th>Due date</th><td>{{ $val($fmt($litter->due_on)) }}</td></tr>
        <tr><th>Date of birth</th><td>{{ $val($fmt($litter->born_on)) }}</td></tr>
        <tr><th>Puppies</th><td>{{ $litter->puppies->count() }} ({{ $males }} males, {{ $females }} females)@if ($litter->stillborn_count) · {{ $litter->stillborn_count }} not born alive @endif</td></tr>
        <tr><th>AKC application sent</th><td>{{ $val($fmt($litter->akc_applied_on)) }}</td></tr>
        <tr><th>AKC litter number</th><td>{{ $val($litter->akc_litter_number) }}</td></tr>
    </table>

    <h2>Puppies</h2>
    @if ($litter->puppies->isEmpty())
        <p class="muted">None recorded.</p>
    @else
        <table class="grid">
            <tr><th>Puppy</th><th>Born</th><th>Sex / color</th><th>Birth wt</th><th>Microchip</th><th>Papers</th><th>Buyer</th><th>Went home</th></tr>
            @foreach ($litter->puppies as $p)
                <tr>
                    <td>{{ $p->name ?: ($p->collar ? $p->collar.' collar' : 'Puppy '.$p->id) }}<br><span class="muted">{{ $p->status->label() }}</span></td>
                    <td>{{ $p->birth_order ? '#'.$p->birth_order : '—' }}<br><span class="muted">{{ $p->born_time }} {{ $p->birth_notes }}</span></td>
                    <td>{{ $p->sex->label() }}<br>{{ $p->color?->label() }}</td>
                    <td>{{ $p->birth_weight_g ? round($p->birth_weight_g / 28.3495, 1).' oz' : '—' }}</td>
                    <td>{{ $val($p->microchip) }}</td>
                    <td>{{ $p->registration_type?->label() ?? '—' }}<br><span class="muted">{{ $p->akc_number }} {{ $p->papers_given_on ? 'given '.$fmt($p->papers_given_on) : '' }}</span></td>
                    <td>
                        @if ($p->buyer)
                            {{ $p->buyer->name }}<br><span class="muted">{{ collect([$p->buyer->address, $p->buyer->city, $p->buyer->state, $p->buyer->postal_code])->filter()->join(', ') }}<br>{{ $p->buyer->phone }} {{ $p->buyer->email }}</span>
                        @else — @endif
                    </td>
                    <td>{{ $val($fmt($p->went_home_on)) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @php $care = $litter->puppies->flatMap->healthRecords->sortBy('given_on')->groupBy(fn ($r) => $r->given_on->toDateString().'|'.$r->name); @endphp
    @if ($care->isNotEmpty())
        <h2>Litter health care</h2>
        <table class="grid">
            <tr><th>Date</th><th>What</th><th>Kind</th><th>Puppies</th></tr>
            @foreach ($care as $group)
                @php $r = $group->first(); @endphp
                <tr><td>{{ $fmt($r->given_on) }}</td><td>{{ $r->name }}</td><td>{{ $r->type->label() }}</td><td>{{ $group->count() }}</td></tr>
            @endforeach
        </table>
    @endif

    <div class="foot">Keep this record for at least 5 years after the last puppy is sold or given away.</div>
</section>
