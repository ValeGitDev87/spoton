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

Stato: completato localmente.

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

## Prossimo Step

Step 3 consigliato:

- posts / annunci;
- relazione con location;
- campo `musica`;
- scadenza 24h;
- prime API create/list/detail.
