<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $location['name'] }}, {{ $location['city'] }} - SpotOn</title>
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="description" content="Scopri gli annunci SpotOn pubblicati presso {{ $location['name'] }}, {{ $location['city'] }}.">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SpotOn">
    <meta property="og:locale" content="it_IT">
    <meta property="og:title" content="{{ $location['name'] }} su SpotOn">
    <meta property="og:description" content="Scopri cosa succede a {{ $location['name'] }}, {{ $location['city'] }}.">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ asset('images/share/spoton-share.png') }}">
    <style>
        * { box-sizing: border-box; }
        html { background: #f7f8fb; color: #111827; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { margin: 0; overflow-x: hidden; }
        header { align-items: center; background: #fff; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; min-height: 68px; padding: 12px max(18px, calc((100% - 760px) / 2)); }
        .brand { align-items: center; color: #111827; display: flex; font-size: 20px; font-weight: 800; gap: 9px; text-decoration: none; }
        .brand img { height: 38px; width: 38px; }
        .open { background: #111827; border-radius: 7px; color: #fff; font-size: 13px; font-weight: 750; padding: 12px 15px; text-decoration: none; }
        main { margin: 0 auto; max-width: 760px; padding: 30px 18px 56px; }
        .place { background: #fff; border-bottom: 4px solid #38cfd0; padding: 26px 0; }
        .eyebrow { color: #7c3aed; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        h1 { font-size: 40px; margin: 8px 0 0; overflow-wrap: anywhere; }
        .city { color: #667085; font-size: 16px; margin: 9px 0 0; }
        h2 { font-size: 20px; margin: 30px 0 14px; }
        article { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; margin-top: 12px; padding: 18px; }
        .meta { color: #7c3aed; font-size: 12px; font-weight: 750; }
        .text { font-size: 16px; line-height: 1.5; margin: 10px 0 0; overflow-wrap: anywhere; }
        .audio { color: #667085; font-size: 13px; margin-top: 12px; }
        .empty { color: #667085; padding: 30px 0; }
        @media (max-width: 520px) { h1 { font-size: 30px; } header { padding: 10px 14px; } main { padding: 22px 14px 44px; } }
    </style>
</head>
<body>
    <header>
        <a class="brand" href="{{ $canonicalUrl }}"><img src="{{ asset('images/share/spoton-symbol.png') }}" alt=""><span>SpotOn</span></a>
        <a class="open" href="{{ $appUrl }}">Apri nell'app</a>
    </header>
    <section class="place">
        <main style="padding-top:0;padding-bottom:0;">
            <div class="eyebrow">Bacheca del luogo</div>
            <h1>{{ $location['name'] }}</h1>
            <p class="city">{{ $location['city'] }}</p>
        </main>
    </section>
    <main>
        <h2>Annunci attivi</h2>
        @forelse ($posts as $post)
            <article>
                <div class="meta">{{ $post['category'] }} · {{ $post['author'] }}</div>
                <p class="text">{{ \Illuminate\Support\Str::limit($post['text'], 220) }}</p>
                @if ($post['has_audio'])<div class="audio">Nota audio disponibile nell'app</div>@endif
            </article>
        @empty
            <p class="empty">Non ci sono annunci attivi in questo luogo.</p>
        @endforelse
    </main>
</body>
</html>
