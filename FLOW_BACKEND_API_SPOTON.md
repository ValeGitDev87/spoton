# Flow Backend API SpotOnApp

Questo file tiene traccia degli step implementati nel backend Laravel.

Dominio target:

```text
https://www.spotonapp.cloud
```

Repo:

```text
/Users/valentinoscianre/Desktop/SpotOn/backend
```

Regola di lavoro:

- Codex implementa uno step alla volta;
- Valentino controlla, fa commit e push;
- ogni API va provata prima in locale e poi sul server.

---

## Step 1 - Base Backend

Stato: completato.

Incluso:

- Laravel backend;
- Sanctum;
- login/register/logout/me;
- locations pubbliche autenticate;
- nearby locations con raggio km;
- client test API in `public/api-client.html`;
- test automatici base.

API:

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/me`
- `GET /api/locations`
- `GET /api/locations/nearby`

---

## Step 2 - Admin Luoghi

Stato: completato.

Incluso:

- campo `is_admin` su utenti;
- seed admin `admin@spoton.local` / `password123`;
- middleware admin;
- CRUD API admin locations;
- validazioni create/update location;
- client test aggiornato con sezione Admin luoghi;
- test automatici admin.

API:

- `GET /api/admin/locations`
- `POST /api/admin/locations`
- `GET /api/admin/locations/{location}`
- `PATCH /api/admin/locations/{location}`
- `DELETE /api/admin/locations/{location}`

Verifiche locali:

- `php artisan migrate:fresh --seed` OK;
- `php artisan test` OK: 10 test, 38 assertion;
- CRUD admin provato via HTTP locale con login admin.

Nota PostgreSQL/PostGIS:

- la migration prova ad attivare PostGIS solo se disponibile;
- se PostGIS non e' installato, il backend continua a usare latitudine/longitudine decimal;
- quando installeremo PostGIS sul server, l'estensione verra' attivata automaticamente se disponibile.

---

## Step 3 - Portale Admin

Stato: completato localmente.

Incluso:

- login web con sessione Laravel;
- route `/login` e `/logout`;
- area `/admin` protetta da login e flag `users.is_admin`;
- dashboard con conteggi utenti, luoghi e post;
- tabella utenti consultabile da admin;
- tabella post consultabile da admin;
- gestione stato post da admin:
  - `active`;
  - `removed`;
  - `flagged`;
  - `expired`;
- lista luoghi con ricerca;
- form creazione luogo;
- form modifica luogo;
- eliminazione luogo;
- campi coordinate obbligatori:
  - latitudine;
  - longitudine;
  - raggio metri;
- test automatici su guest, non-admin, admin, creazione e modifica coordinate.

Route web:

- `GET /login`
- `POST /login`
- `POST /logout`
- `GET /admin`
- `GET /admin/users`
- `GET /admin/posts`
- `PATCH /admin/posts/{post}/status`
- `GET /admin/locations`
- `GET /admin/locations/create`
- `POST /admin/locations`
- `GET /admin/locations/{location}/edit`
- `PATCH /admin/locations/{location}`
- `DELETE /admin/locations/{location}`

Credenziali seed admin:

```text
admin@spoton.local
password123
```

Verifiche locali:

- `php artisan migrate:fresh --seed` OK;
- `php artisan test` OK: 30 test, 105 assertion.

---

## Step 4 - Posts / Annunci

Stato: completato localmente.

Incluso:

- relazione con location;
- campo `musica`;
- scadenza 24h;
- validazione data avvistamento non futura;
- lista annunci con ricerca, status e filtro location;
- dettaglio annuncio;
- modifica solo owner o admin;
- rimozione logica con `status=removed`;
- response con counter iniziali a zero.

API:

- `GET /api/posts`
- `POST /api/posts`
- `GET /api/posts/{post}`
- `PATCH /api/posts/{post}`
- `DELETE /api/posts/{post}`

Verifiche locali:

- `php artisan migrate:fresh --seed` OK;
- `php artisan test` OK;
- creazione post provata via HTTP locale;
- risposta creazione post verificata con `like_count`, `comment_count`, `share_count`, `io_cero_count` a zero.

---

## Step 5 - Feed Nearby / Map API

Stato: completato localmente.

Incluso:

- endpoint annunci vicini a lat/lng entro raggio km;
- endpoint mappa con locations e posts vicini;
- distanza `distance_km` calcolata rispetto alla posizione utente;
- esclusione post scaduti da nearby/map;
- client test aggiornato con sezione Annunci e bottone Map.

API:

- `GET /api/posts/nearby?lat=...&lng=...&radius_km=200`
- `GET /api/map?lat=...&lng=...&radius_km=200`

Verifiche locali:

- `php artisan test` OK: 17 test, 64 assertion;
- nearby posts provato via HTTP locale;
- map endpoint provato via HTTP locale.

---

## Step 6 - Storie 24h / Scadenza Annunci

Stato: completato localmente.

Incluso:

- endpoint storie per location;
- filtro storie su post `active`, creati nelle ultime 24 ore e non scaduti;
- ordinamento storie dal piu' vecchio al piu' recente;
- `ExpirePostsJob` per portare a `expired` i post attivi scaduti;
- scheduler Laravel ogni minuto;
- client test aggiornato con bottone Stories.

API:

- `GET /api/locations/{location}/stories`

Scheduler:

- `Schedule::job(new ExpirePostsJob)->everyMinute()->withoutOverlapping()`

Nota server:

- serve cron ogni minuto con `php artisan schedule:run`;
- con `QUEUE_CONNECTION=redis` serve anche un queue worker attivo per eseguire i job schedulati.

Verifiche locali:

- `php artisan test` OK: 25 test, 87 assertion;
- storie protette da auth;
- storie scadute/vecchie escluse;
- job scadenza verificato da test automatico.

---

## Step 7 - Like / Io c'ero

Stato: completato localmente.

Incluso:

- tabella `likes`;
- tabella `post_i_was_there`;
- toggle like;
- toggle Io c'ero;
- blocco Io c'ero sul proprio post;
- lista utenti Io c'ero visibile solo al proprietario del post o admin;
- response post aggiornata con `liked_by_me` e `io_cero_by_me`.

API:

- `POST /api/posts/{post}/like`
- `POST /api/posts/{post}/io-cero`
- `GET /api/posts/{post}/io-cero-users`

---

## Step 8 - Presence / Tracciamento

Stato: completato localmente.

Incluso:

- tabella `presence_sessions`;
- endpoint ping posizione utente;
- aggiornamento ultima posizione su `users`;
- apertura/aggiornamento sessioni presenza per luoghi nel raggio;
- chiusura sessioni fuori raggio;
- job per chiudere sessioni stale dopo 5 minuti;
- conteggio `connected_now_count` sui luoghi.

API:

- `POST /api/presence/ping`

Scheduler:

- `CloseStalePresenceSessionsJob` ogni minuto.

---

## Step 9 - Chat

Stato: completato localmente.

Incluso:

- tabella `chats`;
- tabella `messages`;
- apertura chat 1 a 1;
- lista chat dell'utente;
- lista messaggi;
- invio messaggio;
- protezione accesso solo ai partecipanti.

API:

- `GET /api/chats`
- `POST /api/chats/open`
- `GET /api/chats/{chat}/messages`
- `POST /api/chats/{chat}/messages`

---

## Step 10 - Backup Admin

Stato: completato localmente.

Incluso:

- pagina admin `/admin/backups`;
- lista file backup consentiti;
- download backup solo admin;
- avvio backup manuale da portale;
- configurazione env:
  - `SPOTON_BACKUP_PATH`;
  - `SPOTON_BACKUP_COMMAND`.

Route web:

- `GET /admin/backups`
- `POST /admin/backups`
- `GET /admin/backups/{filename}`

---

## Step 11 - Fake Seeder Completo

Stato: completato localmente.

Incluso:

- seeder demo separato `DemoDataSeeder`;
- admin e test user;
- utenti demo;
- locations demo;
- post demo con musica;
- like;
- Io c'ero;
- presence sessions;
- chat e messaggi.

Comando:

```bash
php artisan db:seed --class=DemoDataSeeder
```

---

## Stato Finale Locale

Completato:

- API base;
- admin locations API;
- portale admin web;
- posts;
- nearby/map;
- stories e scadenza 24h;
- like;
- Io c'ero;
- presence;
- chat;
- backup admin;
- demo seeder;
- client test `public/api-client.html` aggiornato.

Verifiche locali finali:

- `php artisan migrate:fresh --seed` OK;
- `php artisan db:seed --class=DemoDataSeeder` OK;
- `php artisan test` OK: 40 test, 154 assertion.

Resta da fare dopo push/server:

- lanciare migration sul server;
- opzionalmente lanciare `DemoDataSeeder` in staging;
- provare API e portale admin sul dominio;
- quando tutto e' stabile, mettere `APP_ENV=production` e `APP_DEBUG=false`.

---

## Nuova Specifica - Blocco 1/2, Step 1-4

Stato: completato localmente.

Documento di riferimento:

```text
/Users/valentinoscianre/Desktop/SpotOn/Beccato_SpotOn - Specifica Backend (ultima versione).md
```

Incluso:

- profilo utente esteso:
  - `auth_provider`;
  - `avatar_url`;
  - `bio`;
  - `photos`;
  - `karma`;
- post Ghost:
  - `song_quote`;
  - `is_anonymous`;
  - `secret_question`;
  - `secret_answer_hash`;
  - risposta segreta mai esposta nelle API;
  - autore mascherato come `Ghost` per non-owner/non-admin;
- preferiti privati:
  - `GET /api/favorites?query=`;
  - `POST /api/favorites`;
  - `DELETE /api/favorites/{targetName}`;
- commenti:
  - `GET /api/posts/{post}/comments`;
  - `POST /api/posts/{post}/comments`;
  - tag `@Nome` consentito solo se quel nome e' nei preferiti dell'utente;
- seeder demo aggiornato con preferiti, commenti e post Ghost;
- client `public/api-client.html` aggiornato.

Doc test dedicato:

```text
HANDOFF_CHATGPT_STEP_1_4_NUOVA_SPECIFICA.md
```

Prossimo blocco:

- challenge classica;
- challenge invertita;
- controproposta;
- API foto/karma e rifiniture finali.

---

## Nuova Specifica - Blocco 2/2, Step 5-8

Stato: completato localmente.

Incluso:

- challenge classica:
  - `POST /api/posts/{post}/verify-answer`;
  - risposta corretta sblocca Ghost;
  - crea/riusa chat;
  - aumenta `spot_on_count`;
  - aumenta karma di chi risponde;
- challenge invertita:
  - `POST /api/challenges`;
  - target autore post o autore commento;
  - contatto diretto oppure domanda;
  - `POST /api/challenges/{challenge}/answer`;
- controproposta:
  - `POST /api/posts/{post}/counter-propose`;
  - `POST /api/challenges/{challenge}/counter-propose`;
  - `POST /api/challenges/{challenge}/counter-review`;
  - accettazione sblocca chat, karma e reveal Ghost quando previsto;
- pending challenge:
  - `GET /api/challenges/pending`;
- profilo finale:
  - `GET /api/users/me/karma`;
  - `POST /api/users/me/photos`;
  - `DELETE /api/users/me/photos/{photoId}`;
  - limite 10 foto;
- chat aggiornata con:
  - `origin_challenge_id`;
  - `origin_post_id`;
- seeder demo aggiornato con challenge;
- client `public/api-client.html` aggiornato;
- doc test dedicato:
  - `HANDOFF_CHATGPT_STEP_5_8_NUOVA_SPECIFICA.md`.

Verifiche locali:

- `php artisan test` OK: 60 test, 270 assertion;
- `php artisan migrate:fresh --seed` OK;
- `php artisan db:seed --class=DemoDataSeeder` OK;
- `npm run build` OK;
- `php artisan route:list --path=api` OK: 42 route.

---

## Extra - Note Audio Sui Post

Stato: completato localmente.

Incluso:

- nota audio opzionale su `POST /api/posts`;
- sostituzione audio su `PATCH /api/posts/{post}`;
- rimozione audio con `remove_audio=true`;
- durata massima 10 secondi;
- peso massimo 1 MB;
- formati accettati:
  - `audio/mp4`;
  - `audio/aac`;
  - `audio/mpeg`;
  - `audio/webm`;
  - `video/mp4`;
- storage Laravel configurabile:
  - `SPOTON_AUDIO_DISK=public`;
  - `SPOTON_AUDIO_DIRECTORY=post-audios`;
- metadati salvati su post:
  - `audio_disk`;
  - `audio_path`;
  - `audio_url`;
  - `audio_mime`;
  - `audio_size_bytes`;
  - `audio_duration_seconds`;
- response post con oggetto `audio`;
- client `public/api-client.html` aggiornato;
- verifica durata reale con `ffprobe` se `ffmpeg` e' installato sul server.

Nota prodotto:

- Per i post Ghost, l'audio puo rivelare la voce dell'autore. Lato app conviene mostrare un avviso prima della pubblicazione.

---

## Feed Vicino E Icone Luoghi

Stato: completato localmente.

Incluso:

- la posizione dell'utente filtra il feed, senza visualizzare una mappa;
- `GET /api/posts/nearby` restituisce 30 post per pagina ordinati per distanza;
- parametri `page` e `per_page`, con `per_page` massimo 30;
- metadati `current_page`, `last_page`, `per_page` e `total` per lo scroll progressivo;
- esclusione dei post associati a luoghi disattivati;
- catalogo controllato di codici Ionicons per i luoghi;
- selettore icona con anteprima nel CRUD web admin;
- normalizzazione dei vecchi codici icona tramite migration;
- API luoghi arricchita con `icon`, `icon_library=ionicons` e `stories_count`;
- frontend Expo aggiornato con feed geolocalizzato, caricamento progressivo e icone luogo nelle storie, nei post e nella scelta luogo.
- `DemoUsersSeeder` ripetibile con cinque account `@test.it` e password comune `password123`.

Verifiche locali:

- `php artisan test` OK: 79 test, 374 assertion;
- `npx tsc --noEmit` OK;
- `npx expo-doctor` OK: 18/18 controlli.

---

## Backend Blocco 1 - Profilo E Moderazione

Stato: completato localmente.

Incluso:

- `PATCH /api/me` per modificare `display_name`, `bio`, `avatar_color` e `avatar_url`;
- campi protetti come email, karma e ruolo non sono modificabili dal profilo;
- `POST /api/reports` per segnalare post o utenti;
- motivi supportati: `spam`, `harassment`, `inappropriate`, `fake`, `privacy`, `other`;
- prevenzione di auto-segnalazioni e duplicati pending;
- migration `reports` con revisore, stato, nota ed elemento polimorfico;
- stato sospensione utente con data e motivazione;
- token API revocati quando un utente viene sospeso;
- middleware che blocca i token gia emessi di un account sospeso;
- dashboard admin con conteggio segnalazioni pending;
- pagina `/admin/reports` con filtri e coda manuale;
- azione admin su report post: rimozione del post;
- azione admin su report utente: sospensione dell'account;
- sospensione e riattivazione manuale dalla tabella utenti;
- rate limiting dedicato per post, commenti, messaggi, engagement, challenge, risposte, controproposte e report.

Nuove rotte:

```text
PATCH /api/me
POST  /api/reports
GET   /admin/reports
PATCH /admin/reports/{report}
PATCH /admin/users/{user}/status
```

Verifiche locali:

- `php artisan test` OK: 88 test, 425 assertion.
