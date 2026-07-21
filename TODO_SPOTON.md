# SpotOn - TODO E Stato Progetto

Ultimo aggiornamento: 21 luglio 2026.

Questo file e' il riferimento operativo da aggiornare dopo ogni blocco, test locale e verifica VPS.

## Decisioni Confermate

- Backend Laravel nella root della repository `spoton`.
- API pubbliche dell'app: `https://www.spotonapp.cloud`.
- Home senza mappa: feed entro 200 km, 30 post per pagina.
- Icone luoghi salvate come codici Ionicons.
- Login MVP tramite email; Google rimandato.
- Push tramite Expo Push Service.
- Logica push implementata prima; credenziali e test reali lasciati all'ultimo step.
- Android: development/release build con FCM V1 configurato successivamente.
- iOS: EAS build e TestFlight con APNs gestito tramite EAS.
- Test VPS guidati da un documento da consegnare a ChatGPT.

## Gia' Completato

- [x] Autenticazione email, verifica email e recupero password.
- [x] CRUD admin luoghi, utenti, post e backup.
- [x] Feed geolocalizzato entro 200 km con paginazione da 30.
- [x] Icone Ionicons per luoghi con anteprima admin.
- [x] Post, Ghost, musica e nota audio massimo 10 secondi.
- [x] Storie a 24 ore.
- [x] Like, commenti, preferiti e `Io c'ero`.
- [x] Challenge classica, invertita e controproposta.
- [x] Chat REST.
- [x] Foto profilo e karma.
- [x] Presence e chiusura sessioni scadute.
- [x] Push token e logica push per commenti/challenge.
- [x] Cinque utenti demo `@test.it` tramite `DemoUsersSeeder`.
- [x] Configurazione Expo SDK 54 e identificatore `it.spotonapp.app`.

## Backend - Blocco 1

Stato: completato localmente; attende commit/push e successiva verifica VPS con il Blocco 2.

- [x] Endpoint `PATCH /api/me` per nome, bio, colore e avatar.
- [x] Migration e modello segnalazioni post/utenti.
- [x] Endpoint autenticato `POST /api/reports`.
- [x] Prevenzione segnalazioni duplicate ancora in attesa.
- [x] Coda moderazione nella dashboard admin.
- [x] Azione admin per rimuovere un post segnalato.
- [x] Azione admin per sospendere/riattivare un utente.
- [x] Login negato agli utenti sospesi e token revocati alla sospensione.
- [x] Rate limiting su post, commenti, messaggi, challenge e report.
- [x] Test automatici Blocco 1: 88 test, 425 assertion.
- [x] Aggiornamento documentazione API/flow.

## Backend - Blocco 2

Stato: da iniziare dopo il Blocco 1.

- [ ] Endpoint eliminazione account con conferma password.
- [ ] Revoca immediata di tutti i token.
- [ ] Anonimizzazione/cancellazione sicura dei dati collegati.
- [ ] Retention delle coordinate e sessioni di presenza.
- [ ] Push per nuovi messaggi chat.
- [ ] Pagine pubbliche Privacy Policy ed Eliminazione account.
- [ ] Test automatici Blocco 2.
- [ ] Aggiornamento documentazione API/flow.

## Verifica VPS Backend

Stato: da fare dopo entrambi i blocchi backend.

- [ ] Commit e push eseguiti dall'utente.
- [ ] Documento handoff ChatGPT aggiornato in `Downloads`.
- [ ] Pull `main` sulla VPS.
- [ ] Composer install e build Vite.
- [ ] Migration in staging.
- [ ] `DemoUsersSeeder` eseguito.
- [ ] Queue e scheduler verificati.
- [ ] API provate sul dominio reale.
- [ ] Dashboard e moderazione provate sul dominio reale.

## Frontend Expo

Stato: da iniziare dopo la conferma del backend sulla VPS.

- [ ] Viewer storie completo.
- [ ] Creazione post completa, inclusa nota audio.
- [ ] Like, commenti, preferiti e `Io c'ero` interattivi.
- [ ] Challenge e controproposte.
- [ ] Chat reale.
- [ ] Profilo, foto e gestione account.
- [ ] Cache/offline leggero e gestione errori.
- [ ] Test completi su development build.

## Notifiche - Ultimo Step

- [ ] Installazione/configurazione client `expo-notifications`.
- [ ] Registrazione Expo Push Token e apertura destinazione interna.
- [ ] Creazione progetto Firebase Android.
- [ ] Configurazione credenziali FCM V1 in EAS.
- [ ] Configurazione APNs tramite EAS/Apple Developer.
- [ ] Development build Android e test push reale.
- [ ] Build iOS TestFlight e test push reale.
- [ ] Verifica ticket/receipt e token `DeviceNotRegistered`.

## Ultima Verifica Locale Nota

- Laravel: 88 test, 425 assertion.
- TypeScript: `npx tsc --noEmit` OK.
- Expo Doctor SDK 54: 18/18 controlli OK.
