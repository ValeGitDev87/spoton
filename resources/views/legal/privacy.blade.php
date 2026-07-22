<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - SpotOn</title>
    @include('legal._style')
</head>
<body>
<main>
    <h1>Privacy Policy di SpotOn</h1>
    <p class="meta">Ultimo aggiornamento: 21 luglio 2026</p>

    <p>Questa informativa descrive in modo trasparente come SpotOn tratta i dati necessari al funzionamento dell'app e dei suoi servizi.</p>

    <h2>Dati trattati</h2>
    <ul>
        <li>dati dell'account, come email, nome visualizzato, bio e foto profilo;</li>
        <li>contenuti pubblicati, commenti, interazioni, segnalazioni, challenge e messaggi chat;</li>
        <li>posizione fornita dal dispositivo quando l'utente autorizza questa funzione;</li>
        <li>dati tecnici, token di autenticazione e token push associati al dispositivo.</li>
    </ul>

    <h2>Finalita</h2>
    <p>I dati sono utilizzati per autenticare gli utenti, mostrare contenuti pertinenti alla posizione, offrire chat e funzioni social, inviare notifiche richieste, moderare abusi e proteggere il servizio.</p>

    <h2>Posizione e conservazione</h2>
    <p>La posizione viene trattata solo quando l'utente concede il permesso e la trasmette all'API. Le ultime coordinate vengono eliminate automaticamente dopo {{ config('spoton.privacy.location_retention_hours') }} ore senza aggiornamenti. Le sessioni di presenza concluse vengono eliminate dopo {{ config('spoton.privacy.presence_retention_days') }} giorni.</p>

    <h2>Condivisione e sicurezza</h2>
    <p>I dati non vengono venduti. Possono essere trattati da fornitori tecnici indispensabili per hosting, email, notifiche e distribuzione dell'app, secondo le relative condizioni di servizio. SpotOn applica controlli di accesso, autenticazione e misure tecniche proporzionate per limitare accessi non autorizzati.</p>

    <h2>Diritti e cancellazione</h2>
    <p>L'utente puo aggiornare il proprio profilo e cancellare definitivamente l'account dall'app. La cancellazione rimuove token, contenuti, chat e dati collegati, salvo eventuali dati che debbano essere conservati per obblighi di legge o tutela da abusi.</p>

    <h2>Contatti</h2>
    <p>Per richieste relative alla privacy o ai propri dati: <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.</p>

    <p class="actions"><a href="{{ route('delete-account') }}">Come cancellare l'account</a></p>
</main>
</body>
</html>
