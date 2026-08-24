<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standard di sicurezza dei minori - SpotOn</title>
    @include('legal._style')
</head>
<body>
<main>
    <h1>Standard di sicurezza dei minori &ndash; SpotOn</h1>
    <p class="meta">Ultimo aggiornamento: 24 agosto 2026</p>

    <h2>Impegno di SpotOn</h2>
    <p>SpotOn adotta una politica di tolleranza zero verso l'abuso e lo sfruttamento sessuale di minori e verso qualsiasi materiale pedopornografico, noto anche come Child Sexual Abuse Material (CSAM). La sicurezza dei minori e parte essenziale delle nostre regole di utilizzo e delle attivita di moderazione.</p>

    <h2>Contenuti e comportamenti vietati</h2>
    <p>Su SpotOn sono vietati, tra gli altri:</p>
    <ul>
        <li>contenuti sessuali che coinvolgono o rappresentano minori;</li>
        <li>creazione, caricamento, richiesta, condivisione, distribuzione o promozione di CSAM;</li>
        <li>adescamento o grooming e richieste di natura sessuale rivolte a minori;</li>
        <li>sfruttamento, coercizione, ricatto o traffico sessuale di minori;</li>
        <li>qualsiasi tentativo di utilizzare SpotOn per organizzare, favorire o facilitare tali comportamenti.</li>
    </ul>

    <h2>Segnalazioni</h2>
    <p>Chiunque rilevi un contenuto, un account o un comportamento che possa mettere in pericolo un minore deve segnalarlo tempestivamente all'indirizzo monitorato <a href="mailto:{{ $contactEmail }}?subject=Segnalazione%20sicurezza%20minori%20SpotOn">{{ $contactEmail }}</a>. La segnalazione dovrebbe includere le informazioni utili a identificare il contenuto o l'account, senza inoltrare o allegare materiale illegale.</p>
    <p>In situazioni di pericolo immediato, contatta direttamente le autorita competenti o i servizi di emergenza del tuo Paese.</p>

    <h2>Azioni di moderazione</h2>
    <p>Quando SpotOn viene a conoscenza di una possibile violazione, esamina la segnalazione e puo rimuovere o disabilitare i contenuti coinvolti, sospendere o disattivare gli account responsabili e limitare l'accesso al servizio. Le informazioni necessarie possono essere conservate quando previsto dalla legge o per tutelare la sicurezza degli utenti.</p>

    <h2>Segnalazione alle autorita e conformita legale</h2>
    <p>SpotOn gestisce i casi confermati nel rispetto delle leggi applicabili in materia di sicurezza dei minori. Quando richiesto dalla legge, collabora con le autorita competenti ed effettua le segnalazioni agli organismi regionali o nazionali preposti, preservando le informazioni necessarie secondo gli obblighi applicabili.</p>

    <h2>Contatto per la sicurezza dei minori</h2>
    <p>Per domande o segnalazioni relative alla sicurezza dei minori, scrivi a <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>. Questo indirizzo viene utilizzato per ricevere e gestire comunicazioni su abuso, sfruttamento sessuale di minori e CSAM.</p>

    <p class="actions">
        <a href="{{ route('privacy') }}">Privacy Policy</a>
        &middot;
        <a href="{{ route('delete-account') }}">Eliminazione account</a>
    </p>
</main>
</body>
</html>
