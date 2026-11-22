<x-mail::message>
# New puppy application

**{{ $entry->contact->name }}** applied on the website.

- Phone: {{ $entry->contact->phone ?: '—' }}
- Email: {{ $entry->contact->email ?: '—' }}
- Litter: {{ $entry->litter?->title ?? 'Any upcoming litter' }}

@foreach (($entry->application ?? []) as $question => $answer)
**{{ $question }}**
{{ $answer }}

@endforeach
<x-mail::button :url="route('admin.waitlist.edit', $entry)">
Open in the admin
</x-mail::button>
</x-mail::message>
