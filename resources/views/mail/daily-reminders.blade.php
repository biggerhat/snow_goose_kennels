<x-mail::message>
# Kennel reminders

@if ($due->isNotEmpty())
## Litters due this week
@foreach ($due as $l)
- **{{ $l->title }}**: due {{ $l->due_on->format('D, M j') }}
@endforeach

@endif
@if ($goingHome->isNotEmpty())
## Puppies going home this week
@foreach ($goingHome as $l)
- **{{ $l->title }}**: home {{ $l->go_home_on->format('D, M j') }}
@endforeach

@endif
@if ($attention->isNotEmpty())
## Due or running out
@foreach ($attention as $a)
- **{{ $a['title'] }}**: {{ \Illuminate\Support\Carbon::parse($a['date'])->format('D, M j') }}@if ($a['overdue']) (overdue)@endif

@endforeach

@endif
@if ($unanswered->isNotEmpty())
## Applications still waiting for a reply
@foreach ($unanswered as $e)
- **{{ $e->contact->name }}** ({{ $e->contact->phone ?: $e->contact->email }}), applied {{ $e->created_at->diffForHumans() }}
@endforeach

@endif
<x-mail::button :url="route('dashboard')">
Open the dashboard
</x-mail::button>
</x-mail::message>
