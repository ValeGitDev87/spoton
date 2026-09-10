<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termini di utilizzo - SpotOn</title>
    @include('legal._style')
</head>
<body>
<main>
    <h1>Termini di utilizzo ed EULA di SpotOn</h1>
    <p class="meta">Versione {{ $termsVersion }} - servizio riservato a utenti maggiorenni (18+)</p>

    <p>Usando SpotOn accetti questi Termini e confermi di avere almeno 18 anni. SpotOn ospita contenuti generati dagli utenti, inclusi post, commenti, profili, chat, audio, video e pubblicazioni in modalita Ghost.</p>

    <h2>Tolleranza zero</h2>
    <p>Non sono consentiti contenuti illegali, offensivi o abusivi, molestie, odio o discriminazione, minacce, violenza, sfruttamento sessuale, spam, truffe, impersonificazione o violazioni dei diritti altrui. La modalita Ghost non puo essere usata per eludere queste regole, intimidire altre persone o nascondere comportamenti abusivi.</p>

    <h2>Filtro, segnalazione e blocco</h2>
    <p>SpotOn applica controlli automatici ai contenuti testuali. Ogni utente puo segnalare contenuti o profili, bloccare utenti abusivi e nascondere immediatamente un post dal proprio feed. Le segnalazioni non devono essere usate in modo falso o strumentale.</p>

    <h2>Moderazione</h2>
    <p>Le segnalazioni vengono esaminate tempestivamente. SpotOn puo rimuovere contenuti, limitare funzioni, sospendere o espellere gli utenti responsabili, revocare sessioni e collaborare con le autorita quando richiesto dalla legge. La tolleranza verso contenuti inaccettabili e utenti abusivi e zero.</p>

    <h2>Contenuti e responsabilita</h2>
    <p>Conservi la responsabilita dei contenuti che pubblichi e dichiari di avere il diritto di condividerli. Non devi pubblicare dati personali di terzi senza autorizzazione. I contenuti possono scadere o essere rimossi secondo le funzioni e le regole del servizio.</p>

    <h2>Contatti</h2>
    <p>Per assistenza o per segnalare attivita inappropriate: <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.</p>

    <p class="actions">
        <a href="{{ route('privacy') }}">Privacy</a>
        &middot;
        <a href="{{ route('child-safety') }}">Sicurezza dei minori</a>
        &middot;
        <a href="{{ route('delete-account') }}">Cancellazione account</a>
    </p>
</main>
</body>
</html>
