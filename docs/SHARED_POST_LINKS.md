# Condivisione annunci SpotOn

## Cosa e stato implementato

- Pagina pubblica sicura: `GET /p/{post_uuid}`.
- Anteprima social Open Graph e Twitter con immagine `1200x630`.
- Link app: `spoton://p/{post_uuid}`.
- Universal Links iOS e Android App Links per `https://www.spotonapp.cloud/p/*`.
- Pagina non disponibile per post scaduti, disattivati o associati a luoghi disattivati.
- I post Ghost non espongono mai l'autore reale.
- La pagina pubblica non espone coordinate, domanda segreta o risposta segreta.

## Variabili server da completare

Nel file `.env` della VPS:

```env
SPOTON_ANDROID_PACKAGE=it.spotonapp.app
SPOTON_ANDROID_SHA256_CERT_FINGERPRINTS=
SPOTON_APPLE_BUNDLE_IDENTIFIER=it.spotonapp.app
SPOTON_APPLE_TEAM_ID=
```

`SPOTON_ANDROID_SHA256_CERT_FINGERPRINTS` deve contenere il fingerprint SHA-256
del certificato con cui viene firmata la build Android. Se ci sono piu
certificati, separarli con una virgola.

`SPOTON_APPLE_TEAM_ID` deve contenere il Team ID dell'account Apple Developer.

Finche questi valori non sono disponibili, la pagina pubblica e il link
`spoton://` funzionano, mentre l'apertura automatica tramite link HTTPS resta
disattivata.

## Fallback store (20 ottobre 2026)

Gli URL pubblici verificati sono:

- iOS: `https://apps.apple.com/it/app/spoton/id6807057516`
- Android: `https://play.google.com/store/apps/details?id=it.spotonapp.app`

La pagina `/p/{uuid}` restituisce sempre HTML e metadati Open Graph. Su un
browser mobile umano prova ad aprire `spoton://p/{uuid}` e, se la pagina resta
visibile, dopo 2,2 secondi apre lo store corrispondente. Desktop e crawler
social non ricevono lo script di fallback. Un post scaduto o rimosso resta
sulla pagina di indisponibilità. Questo non offre deferred deep linking dopo
l'installazione. Le due destinazioni si possono sovrascrivere con
`SPOTON_APP_STORE_URL` e `SPOTON_PLAY_STORE_URL`.

Il Team ID Apple presente nelle credenziali EAS del progetto è
`7L3Z9V5738`. Va impostato come `SPOTON_APPLE_TEAM_ID` sulla VPS. Il valore
SHA-256 Android deve provenire dal certificato che firma la build installata:
per le build Play Store usare il certificato **Firma dell'app** in Play Console;
per un APK distribuito direttamente usare il certificato dell'APK. Inserire
entrambi se servono entrambi. Non utilizzare un fingerprint ipotetico.

Dopo aver configurato i valori:

```bash
php artisan optimize:clear
php artisan config:cache
```

Endpoint da verificare:

```text
https://www.spotonapp.cloud/.well-known/apple-app-site-association
https://www.spotonapp.cloud/.well-known/assetlinks.json
```

Entrambi devono rispondere direttamente con JSON e senza redirect.
