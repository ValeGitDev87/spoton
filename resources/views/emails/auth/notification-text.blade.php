{{ $title }}

{{ $greeting }}

{{ $intro }}

@foreach ($lines as $line)
{{ $line }}

@endforeach
@if ($actionLabel && $actionUrl)
{{ $actionLabel }}:
{{ $actionUrl }}

@endif
@if ($notice)
{{ $notice }}

@endif
SpotOn - Incontri reali, connessioni autentiche
Assistenza: {{ $supportEmail }}
