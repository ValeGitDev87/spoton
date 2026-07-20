<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reimposta password - SpotOn</title>
    @include('auth._public-page-style')
</head>
<body>
    <section class="panel">
        <h1>Reimposta password</h1>
        <p>Scegli una nuova password per il tuo account SpotOn.</p>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email">

            <label for="password">Nuova password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password">

            <label for="password_confirmation">Conferma password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">

            <div style="margin-top:16px;">
                <button type="submit">Aggiorna password</button>
            </div>
        </form>
    </section>
</body>
</html>
