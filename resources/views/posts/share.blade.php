<!doctype html>
<html lang="it" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} - SpotOn</title>
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta name="description" content="{{ $description }}">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="SpotOn">
    <meta property="og:locale" content="it_IT">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $imageUrl }}">
    <meta property="og:image:secure_url" content="{{ $imageUrl }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="SpotOn">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $imageUrl }}">

    <style>
        * { box-sizing: border-box; }
        html { background: #f5f6f8; color: #111827; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { margin: 0; min-width: 0; overflow-x: hidden; }
        header {
            align-items: center;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            min-height: 68px;
            padding: 12px max(18px, calc((100% - 720px) / 2));
        }
        .brand { align-items: center; color: #111827; display: inline-flex; font-size: 20px; font-weight: 800; gap: 9px; text-decoration: none; }
        .brand img { display: block; height: 38px; width: 38px; }
        .open-link {
            align-items: center;
            background: #111827;
            border-radius: 7px;
            color: #fff;
            display: inline-flex;
            font-size: 13px;
            font-weight: 750;
            justify-content: center;
            min-height: 40px;
            padding: 0 14px;
            text-decoration: none;
        }
        main { margin: 0 auto; max-width: 720px; padding: 28px 18px 52px; width: 100%; }
        .eyebrow { color: #7c3aed; font-size: 12px; font-weight: 800; margin: 0 0 8px; text-transform: uppercase; }
        h1 { font-size: 38px; line-height: 1.12; margin: 0; overflow-wrap: anywhere; }
        .place { align-items: center; color: #667085; display: flex; font-size: 14px; gap: 7px; margin: 12px 0 0; min-width: 0; }
        .place span { overflow-wrap: anywhere; }
        article {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-top: 24px;
            overflow: hidden;
        }
        .content { padding: 22px; }
        .author { align-items: center; display: flex; gap: 11px; margin-bottom: 18px; }
        .avatar {
            align-items: center;
            background: {{ $post->is_anonymous ? '#111827' : ($post->author->avatar_color ?: '#8b5cf6') }};
            border-radius: 50%;
            color: #fff;
            display: flex;
            flex: 0 0 44px;
            font-size: 18px;
            font-weight: 800;
            height: 44px;
            justify-content: center;
            width: 44px;
        }
        .author-copy { min-width: 0; }
        .author-name { font-size: 15px; font-weight: 800; overflow-wrap: anywhere; }
        .author-meta { color: #98a2b3; font-size: 12px; margin-top: 2px; }
        .post-text { font-size: 19px; line-height: 1.55; margin: 0; overflow-wrap: anywhere; white-space: pre-wrap; }
        .song { border-left: 3px solid #ec4899; color: #475467; font-size: 14px; line-height: 1.45; margin: 20px 0 0; padding-left: 12px; overflow-wrap: anywhere; }
        audio { margin-top: 20px; max-width: 100%; width: 100%; }
        .date { border-top: 1px solid #eef0f3; color: #98a2b3; font-size: 12px; margin-top: 22px; padding-top: 15px; }
        .unavailable { padding: 34px 22px; text-align: center; }
        .unavailable-icon {
            align-items: center;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            font-size: 24px;
            height: 56px;
            justify-content: center;
            margin: 0 auto 14px;
            width: 56px;
        }
        .unavailable p { color: #667085; line-height: 1.5; margin: 10px 0 0; }
        .footer-note { color: #667085; font-size: 13px; line-height: 1.5; margin: 20px auto 0; max-width: 520px; text-align: center; }
        @media (max-width: 420px) {
            header { min-height: 62px; padding: 10px 14px; }
            .brand { font-size: 18px; }
            .brand img { height: 34px; width: 34px; }
            .open-link { font-size: 12px; min-height: 38px; padding: 0 11px; }
            main { padding: 22px 14px 40px; }
            .content { padding: 18px; }
            h1 { font-size: 27px; }
            .post-text { font-size: 17px; }
        }
    </style>
</head>
<body>
    <header>
        <a class="brand" href="{{ $canonicalUrl }}">
            <img src="{{ asset('images/share/spoton-symbol.png') }}" alt="">
            <span>SpotOn</span>
        </a>
        <a class="open-link" href="{{ $appUrl }}">Apri nell'app</a>
    </header>

    <main>
        @if ($available)
            <p class="eyebrow">{{ $categoryLabel }}</p>
            <h1>{{ $authorName === 'Ghost' ? 'Un incontro raccontato da Ghost' : "Un incontro raccontato da {$authorName}" }}</h1>
            <p class="place">
                <span aria-hidden="true">⌖</span>
                <span>{{ $post->location->name }}, {{ $post->location->city }}</span>
            </p>

            <article>
                <div class="content">
                    <div class="author">
                        <div class="avatar" aria-hidden="true">{{ $post->is_anonymous ? '◉' : mb_strtoupper(mb_substr($authorName, 0, 1)) }}</div>
                        <div class="author-copy">
                            <div class="author-name">{{ $post->is_anonymous ? 'Ghost - profilo nascosto' : $authorName }}</div>
                            <div class="author-meta">Pubblicato su SpotOn</div>
                        </div>
                    </div>

                    <p class="post-text">{{ $post->text }}</p>

                    @if ($post->song_quote)
                        <p class="song">“{{ $post->song_quote }}”</p>
                    @endif

                    @if ($audioUrl)
                        <audio controls preload="none" src="{{ $audioUrl }}">
                            Il browser non supporta la riproduzione audio.
                        </audio>
                    @endif

                    <div class="date">
                        Avvistamento del {{ $post->sighting_date->locale('it')->translatedFormat('d F Y') }}
                    </div>
                </div>
            </article>

            @if ($post->category === \App\Support\PostCategory::WEATHER_TRANSPORT)
                <p class="footer-note">
                    {{ $post->community_status === 'verified' ? '✓ Verificato dalla Community. ' : '' }}
                    Accedi a SpotOn per confermare la segnalazione o indicarla come falsa o risolta.
                </p>
            @elseif ($post->category === \App\Support\PostCategory::GOSSIP_EVENTS)
                <p class="footer-note">Accedi a SpotOn per commentare, mettere like o condividere.</p>
            @else
                <p class="footer-note">Accedi a SpotOn per commentare, mettere like o confermare “Io c'ero”.</p>
            @endif
        @else
            <article>
                <div class="unavailable">
                    <div class="unavailable-icon" aria-hidden="true">⌛</div>
                    <h1>Annuncio non disponibile</h1>
                    <p>Il post è scaduto, è stato rimosso oppure il luogo non è più attivo.</p>
                </div>
            </article>
        @endif
    </main>
</body>
</html>
