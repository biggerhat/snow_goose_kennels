@php
    $fmt = fn ($d) => $d ? $d->format('M j, Y') : '';
    $val = fn ($v) => filled($v) ? $v : '—';
@endphp
<section class="record">
    <h1>Dog record: {{ $dog->name }}</h1>
    <div class="sub">{{ config('app.name') }} · printed {{ now()->format('M j, Y') }}</div>

    <h2>Identification</h2>
    <table>
        <tr><th>Registered name</th><td>{{ $val($dog->registered_name) }}</td></tr>
        <tr><th>Everyday name</th><td>{{ $dog->name }}</td></tr>
        <tr><th>Registration number</th><td>{{ $val($dog->registry_number) }}</td></tr>
        <tr><th>AKC DNA profile</th><td>{{ $val($dog->dna_profile) }}</td></tr>
        <tr><th>Microchip</th><td>{{ $val($dog->microchip) }}</td></tr>
        <tr><th>Titles</th><td>{{ $val($dog->titles) }}</td></tr>
        <tr><th>Sex / color</th><td>{{ $dog->sex->label() }} · {{ $dog->color?->label() ?? '—' }}</td></tr>
        <tr><th>Date of birth</th><td>{{ $val($fmt($dog->birth_date)) }}</td></tr>
        <tr><th>Sire (father)</th><td>{{ $dog->sire ? $dog->sire->name.($dog->sire->registry_number ? ' ('.$dog->sire->registry_number.')' : '') : '—' }}</td></tr>
        <tr><th>Dam (mother)</th><td>{{ $dog->dam ? $dog->dam->name.($dog->dam->registry_number ? ' ('.$dog->dam->registry_number.')' : '') : '—' }}</td></tr>
    </table>

    <h2>Ownership</h2>
    <table>
        <tr><th>Owner</th><td>
            @if ($dog->is_ours) {{ config('app.name') }}
            @else {{ $dog->ownerContact?->name ?? 'Another kennel' }}
                @if ($dog->ownerContact) <span class="muted">{{ collect([$dog->ownerContact->address, $dog->ownerContact->city, $dog->ownerContact->state, $dog->ownerContact->postal_code])->filter()->join(', ') }} {{ $dog->ownerContact->phone }}</span> @endif
            @endif
        </td></tr>
        <tr><th>Came to the kennel</th><td>{{ $dog->acquired_on ? $fmt($dog->acquired_on) : ($dog->birth_date && $dog->is_ours ? 'Born here, '.$fmt($dog->birth_date) : '—') }}</td></tr>
        <tr><th>Left the kennel</th><td>{{ $dog->left_on ? $fmt($dog->left_on).' ('.($dog->left_reason?->label() ?? '').')' : 'Still here' }}</td></tr>
    </table>

    <h2>Matings and litters</h2>
    @if ($litters->isEmpty())
        <p class="muted">None recorded.</p>
    @else
        <table class="grid">
            <tr><th>Bred</th><th>Other dog</th><th>Born</th><th>M / F</th><th>AKC litter #</th></tr>
            @foreach ($litters as $l)
                @php $other = $l->sire_id === $dog->id ? $l->dam : $l->sire; @endphp
                <tr>
                    <td>{{ $val($fmt($l->bred_on)) }}@if ($l->bred_place) <span class="muted">· {{ $l->bred_place }}</span>@endif</td>
                    <td>{{ $other?->name ?? '—' }} {{ $other?->registry_number ? '('.$other->registry_number.')' : '' }}</td>
                    <td>{{ $val($fmt($l->born_on)) }}</td>
                    <td>{{ $l->puppies->where('sex.value', 'male')->count() }} / {{ $l->puppies->where('sex.value', 'female')->count() }}</td>
                    <td>{{ $val($l->akc_litter_number) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($dog->heatCycles->isNotEmpty())
        <h2>Heat cycles</h2>
        <table class="grid">
            <tr><th>Started</th><th>Ended</th><th>Bred</th><th>Notes</th></tr>
            @foreach ($dog->heatCycles as $h)
                <tr><td>{{ $fmt($h->started_on) }}</td><td>{{ $val($fmt($h->ended_on)) }}</td><td>{{ $val($fmt($h->bred_on)) }}</td><td>{{ $h->notes }}</td></tr>
            @endforeach
        </table>
    @endif

    <h2>Health clearances</h2>
    @if ($dog->healthTests->isEmpty())
        <p class="muted">None recorded.</p>
    @else
        <table class="grid">
            <tr><th>Test</th><th>Result</th><th>Date</th><th>Certificate</th></tr>
            @foreach ($dog->healthTests as $t)
                <tr><td>{{ $t->name }}</td><td>{{ $t->result }}</td><td>{{ $val($fmt($t->tested_on)) }}</td><td>{{ $val($t->certificate_number) }}</td></tr>
            @endforeach
        </table>
    @endif

    <h2>Vaccines, treatments and vet visits</h2>
    @if ($dog->healthRecords->isEmpty())
        <p class="muted">None recorded.</p>
    @else
        <table class="grid">
            <tr><th>Date</th><th>What</th><th>Kind</th><th>Next due</th><th>Given by</th></tr>
            @foreach ($dog->healthRecords as $r)
                <tr><td>{{ $fmt($r->given_on) }}</td><td>{{ $r->name }}</td><td>{{ $r->type->label() }}</td><td>{{ $val($fmt($r->next_due_on)) }}</td><td>{{ $val($r->given_by) }}</td></tr>
            @endforeach
        </table>
    @endif

    @if ($dog->documents->isNotEmpty())
        <h2>Documents on file</h2>
        <table class="grid">
            <tr><th>Title</th><th>Kind</th><th>Dated</th></tr>
            @foreach ($dog->documents as $d)
                <tr><td>{{ $d->title }}</td><td>{{ \App\Enums\DocumentCategory::from($d->category)->label() }}</td><td>{{ $val($fmt($d->issued_on)) }}</td></tr>
            @endforeach
        </table>
    @endif

    <div class="foot">Keep this record for at least 5 years after the dog dies, is sold or is given away.</div>
</section>
