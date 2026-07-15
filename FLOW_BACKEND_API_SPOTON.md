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
