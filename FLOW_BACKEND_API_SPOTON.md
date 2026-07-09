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

## Step 3 - Frontend Admin Luoghi

Stato: completato localmente.

Incluso:

- login web con sessione Laravel;
- route `/login` e `/logout`;
- area `/admin/locations` protetta da login e flag `users.is_admin`;
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
- `php artisan test` OK: 22 test, 78 assertion.

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

## Step Finale Previsto - Fake Seeder Completo

Da fare quando le API principali saranno pronte.

Obiettivo:

- generare utenti fake;
- generare luoghi fake/reali;
- generare annunci fake con `musica`;
- generare like, Io c'ero, storie e chat quando saranno implementati;
- provare tutte le API dal client `public/api-client.html` e via curl.

---

## Prossimo Step

Step 6 consigliato:

- storie 24h;
- job scadenza annunci;
- endpoint `GET /api/locations/{location}/stories`.
