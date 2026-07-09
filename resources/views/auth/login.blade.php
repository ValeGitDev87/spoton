<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login SpotOn Admin</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f6f7f9;
            color: #101828;
        }
        .panel {
            width: min(420px, calc(100% - 32px));
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
        }
        h1 { margin: 0 0 18px; font-size: 24px; }
        label {
            display: block;
            margin: 12px 0 5px;
            font-size: 13px;
            font-weight: 750;
            color: #344054;
        }
        input {
            width: 100%;
            height: 42px;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            padding: 8px 10px;
            font: inherit;
        }
        .check-row { display: flex; align-items: center; gap: 8px; margin: 14px 0; }
        .check-row input { width: 18px; height: 18px; }
        button {
            width: 100%;
            height: 42px;
            border: 0;
            border-radius: 8px;
            background: #111827;
            color: #fff;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
        }
        .errors {
            margin-bottom: 14px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #fef3f2;
            color: #b42318;
        }
    </style>
</head>
<body>
    <section class="panel">
        <h1>SpotOn Admin</h1>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="post" action="{{ route('login') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', 'admin@spoton.local') }}" required autofocus>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <label class="check-row">
                <input name="remember" type="checkbox" value="1">
                <span>Ricordami</span>
            </label>

            <button type="submit">Entra</button>
        </form>
    </section>
</body>
</html>
