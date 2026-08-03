<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - SpotOn</title>
    @include('auth._public-page-style')
    <style>
        body { background: #f7f8fb; }
        .verification-panel { padding: 0; overflow: hidden; text-align: center; }
        .verification-accent { height: 7px; background: linear-gradient(90deg, #38cfd0 0 34%, #ec4899 34% 67%, #facc15 67%); }
        .verification-content { padding: 34px 28px 30px; }
        .brand-symbol { display: block; width: 68px; height: 68px; margin: 0 auto 22px; object-fit: contain; }
        .status-icon { display: grid; place-items: center; width: 54px; height: 54px; margin: 0 auto 18px; border-radius: 50%; background: #ecfdf3; color: #027a48; font-size: 30px; font-weight: 800; }
        .status-already { background: #eff8ff; color: #175cd3; }
        .verification-content h1 { margin-bottom: 10px; }
        .verification-content p { max-width: 360px; margin: 0 auto 24px; }
        .verification-content .btn { width: 100%; min-height: 48px; }
        .close-note { display: block; margin-top: 14px; color: #98a2b3; font-size: 12px; }
        @media (max-width: 520px) {
            body { padding: 0; background: #ffffff; }
            .verification-panel { min-height: 100vh; width: 100%; border: 0; border-radius: 0; display: grid; grid-template-rows: 7px 1fr; }
            .verification-content { align-self: center; padding: 28px 22px; }
        }
    </style>
</head>
<body>
    <section class="panel verification-panel">
        <div class="verification-accent"></div>
        <div class="verification-content">
            <img class="brand-symbol" src="{{ asset('images/share/spoton-symbol.png') }}" alt="SpotOn">
            <div class="status-icon {{ $status === 'already' ? 'status-already' : '' }}" aria-hidden="true">✓</div>
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
            <a class="btn" href="spoton://email-verified">Apri SpotOn</a>
            <span class="close-note">Se l app non si apre, puoi chiudere questa pagina e tornare su SpotOn.</span>
        </div>
    </section>
</body>
</html>
