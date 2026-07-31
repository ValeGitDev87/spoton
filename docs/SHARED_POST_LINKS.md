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
