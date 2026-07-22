<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminazione account - SpotOn</title>
    @include('legal._style')
</head>
<body>
<main>
    <h1>Eliminazione account SpotOn</h1>
    <p class="meta">Procedura disponibile per tutti gli account utente</p>

    <h2>Dall'app</h2>
    <p>Apri Profilo, entra nelle impostazioni dell'account e seleziona Elimina account. Per sicurezza dovrai inserire la password attuale e confermare l'operazione.</p>

    <h2>Cosa viene eliminato</h2>
    <p>L'operazione revoca subito le sessioni API e cancella definitivamente il profilo, le foto locali, i post e le note audio, le interazioni, le chat, i token push e i dati di posizione collegati.</p>

    <h2>Richiesta via email</h2>
    <p>Se non riesci ad accedere all'app, scrivi dall'indirizzo associato all'account a <a href="mailto:{{ $contactEmail }}?subject=Richiesta%20eliminazione%20account%20SpotOn">{{ $contactEmail }}</a>. Potremo chiederti informazioni aggiuntive per verificare che l'account sia tuo.</p>

    <p class="actions"><a href="{{ route('privacy') }}">Leggi la Privacy Policy</a></p>
</main>
</body>
</html>
