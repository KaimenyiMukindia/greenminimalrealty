<x-mail::message>
# {{ $title }}

Follow the link below to {{ strtolower($actionLabel) }}. This link expires in {{ $expiresInMinutes }} minutes.

<x-mail::button :url="$url">
{{ $actionLabel }}
</x-mail::button>

If you did not request this email, you can safely ignore it.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
