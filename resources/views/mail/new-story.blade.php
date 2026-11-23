<x-mail::message>
# A family sent in a story

**{{ $story->name }}**@if ($story->location), {{ $story->location }}@endif @if ($story->puppy_name) · with {{ $story->puppy_name }}@endif

{{ $story->body }}

It is **not on the website yet**. Open Happy families in the admin to approve it or delete it.@if ($story->email) Their email: {{ $story->email }}@endif

<x-mail::button :url="route('admin.testimonials.index')">
Review the story
</x-mail::button>
</x-mail::message>
