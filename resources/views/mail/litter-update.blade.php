<x-mail::message>
Hi {{ $first }},

{{ $messageBody }}

Thank you,<br>
{{ trim($owner['first'].' '.$owner['last']) ?: config('app.name') }}<br>
{{ config('app.name') }}@if ($owner['phone']) · {{ $owner['phone'] }}@endif

<small>You are getting this because you asked to be on the list for {{ $litterTitle }}. Reply to this email if you would like to be taken off.</small>
</x-mail::message>
