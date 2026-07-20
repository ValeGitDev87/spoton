<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - SpotOn</title>
    @include('auth._public-page-style')
</head>
<body>
    <section class="panel">
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a class="btn" href="{{ config('services.spoton_auth.email_verified_url') }}">Continua</a>
    </section>
</body>
</html>
