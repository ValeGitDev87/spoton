# Handoff ChatGPT - SpotOn Backend

Questo documento serve per dare contesto a ChatGPT o a un altro assistente tecnico sullo stato del backend SpotOn, cosa testare, quali comandi lanciare sul server e cosa resta da implementare.

## Contesto Progetto

Backend Laravel per SpotOnApp / Beccato.

Repo GitHub:

```text
git@github.com:ValeGitDev87/spoton.git
```

Dominio:

```text
https://www.spotonapp.cloud
```

Root Laravel prevista sul server:

```text
/var/www/spotonapp.cloud/repo
```

Public root del dominio:

```text
/var/www/spotonapp.cloud/repo/public
```

Ambiente server iniziale:

```env
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://www.spotonapp.cloud
```

Quando tutto sara' stabile:

```env
APP_ENV=production
APP_DEBUG=false
```

## Cosa E' Gia' Stato Fatto

### Backend Base

- Laravel backend.
- Laravel Sanctum per API token.
- Login/register/logout API.
- Endpoint `/api/me`.
- User UUID.
- Flag admin su utenti: `users.is_admin`.
- Template env server: `.env.server.example`.

### Admin Luoghi API

CRUD API admin protetto da token e flag `is_admin`.

Endpoint:

```text
GET    /api/admin/locations
POST   /api/admin/locations
GET    /api/admin/locations/{location}
PATCH  /api/admin/locations/{location}
DELETE /api/admin/locations/{location}
```

Campi luogo:

```text
name
short
city
type
latitude
longitude
geo_radius_meters
icon
is_active
```

### Portale Admin Web

Dashboard web protetta da sessione Laravel e flag `users.is_admin`.

Route:

```text
GET    /login
POST   /login
POST   /logout
GET    /admin
GET    /admin/users
GET    /admin/posts
PATCH  /admin/posts/{post}/status
GET    /admin/locations
GET    /admin/locations/create
POST   /admin/locations
GET    /admin/locations/{location}/edit
PATCH  /admin/locations/{location}
DELETE /admin/locations/{location}
```

Accesso consentito solo a utenti con:

```text
is_admin = true
```

Il portale admin permette di:

- vedere una dashboard riepilogativa;
- vedere utenti registrati;
- vedere luoghi;
- creare/modificare/eliminare luoghi;
- vedere post/annunci;
- disattivare un post impostando `status=removed`;
- riattivare un post impostando `status=active`;
- flaggare un post impostando `status=flagged`.

Credenziali seed:

```text
admin@spoton.local
password123
```

### Annunci / Posts

Implementata tabella `posts`.

Campi principali:

```text
author_id
location_id
text
musica
sighting_date
expires_at
like_count
comment_count
share_count
io_cero_count
status
```

Endpoint:

```text
GET    /api/posts
POST   /api/posts
GET    /api/posts/{post}
PATCH  /api/posts/{post}
DELETE /api/posts/{post}
```

Regole:

- `musica` opzionale, max 255 caratteri.
- `sighting_date` non puo' essere futura.
- `expires_at = now + 24h`.
- Update/delete solo owner o admin.
- Delete logico: `status=removed`.

### Feed Nearby / Map

Endpoint:

```text
GET /api/posts/nearby?lat=...&lng=...&radius_km=200
GET /api/map?lat=...&lng=...&radius_km=200
```

Comportamento:

- calcola distanza da lat/lng;
- restituisce `distance_km`;
- esclude post scaduti;
- restituisce location e post per mappa.

### Storie 24h

Endpoint:

```text
GET /api/locations/{location}/stories
```

Comportamento:

- solo post `active`;
- solo post creati nelle ultime 24 ore;
- solo post con `expires_at > now()`;
- ordinati dal piu' vecchio al piu' recente.

Job:

```text
ExpirePostsJob
```

Scheduler:

```php
Schedule::job(new ExpirePostsJob)->everyMinute()->withoutOverlapping();
```

### Client Test API

Browser client disponibile in:

```text
/api-client.html
```

Sul server:

```text
https://www.spotonapp.cloud/api-client.html
```

Include test manuale per:

- auth;
- locations;
- admin locations;
- posts;
- nearby posts;
- map;
- stories.

## Comandi Server Da Lanciare

### Entrare Sul Server

```bash
ssh root@187.77.89.94
```

### Primo Clone, Se La Repo Non Esiste Ancora Sul Server

```bash
mkdir -p /var/www/spotonapp.cloud
cd /var/www/spotonapp.cloud
git clone git@github.com:ValeGitDev87/spoton.git repo
cd /var/www/spotonapp.cloud/repo
```

### Aggiornamento Da Git

```bash
cd /var/www/spotonapp.cloud/repo
git pull
composer install --no-dev --optimize-autoloader
```

### Env Server

Se `.env` non esiste:

```bash
cp .env.server.example .env
php artisan key:generate
nano .env
```

Valori minimi richiesti:

```env
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://www.spotonapp.cloud

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=spotonapp_db
DB_USERNAME=spotonapp_user
DB_PASSWORD=PASSWORD_REALE

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

### Migrate E Seed

Se e' un primo setup/staging e si possono resettare i dati:

```bash
php artisan migrate:fresh --seed
```

Se ci sono gia' dati da conservare:

```bash
php artisan migrate --force
php artisan db:seed --force
```

Nota: `migrate:fresh --seed` cancella tutte le tabelle.

### Cache / Route / View Clear

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
php artisan storage:link
```

### Test Laravel Sul Server

Se sono disponibili dipendenze dev:

```bash
php artisan test
```

Se sul server e' stato fatto `composer install --no-dev`, PHPUnit potrebbe non essere disponibile. In quel caso testare con curl e browser.

## Scheduler E Queue

Serve per far scadere automaticamente gli annunci/storie.

Aprire crontab:

```bash
crontab -e
```

Aggiungere:

```cron
* * * * * cd /var/www/spotonapp.cloud/repo && php artisan schedule:run >> /dev/null 2>&1
```

Con `QUEUE_CONNECTION=redis`, serve anche un queue worker.

Test temporaneo:

```bash
cd /var/www/spotonapp.cloud/repo
php artisan queue:work redis --sleep=3 --tries=3 --timeout=120
```

Da mettere poi sotto Supervisor.

## Web Server

Il dominio deve puntare alla cartella:

```text
/var/www/spotonapp.cloud/repo/public
```

Non deve puntare a:

```text
/var/www/spotonapp.cloud/repo
```

Verificare che il web server abbia permessi su:

```text
storage/
bootstrap/cache/
```

Comando utile:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
```

Adattare `www-data` se il server usa un altro utente PHP/Nginx.

## Come Testare Il Frontend Admin

Aprire:

```text
https://www.spotonapp.cloud/login
```

Credenziali:

```text
admin@spoton.local
password123
```

Poi testare:

```text
https://www.spotonapp.cloud/admin/locations
```

Checklist frontend:

- login admin funziona;
- utente non loggato viene mandato a `/login`;
- dashboard `/admin` visibile;
- lista utenti `/admin/users` visibile;
- lista post `/admin/posts` visibile;
- disattivazione post dal pannello funziona;
- lista luoghi visibile;
- creare un luogo con:
  - nome;
  - citta;
  - latitudine;
  - longitudine;
  - raggio metri;
- modificare coordinate luogo;
- disattivare luogo;
- eliminare luogo.

## Come Testare Le API

### 1. Login API

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

Copiare il token e salvarlo:

```bash
TOKEN="INCOLLA_TOKEN"
```

### 2. Me

```bash
curl -s "https://www.spotonapp.cloud/api/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### 3. Locations

```bash
curl -s "https://www.spotonapp.cloud/api/locations" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Prendere un `LOCATION_ID` dalla risposta.

### 4. Nearby Locations

```bash
curl -s "https://www.spotonapp.cloud/api/locations/nearby?lat=40.8518&lng=14.2681&radius_km=200" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### 5. Creare Post

```bash
LOCATION_ID="INCOLLA_LOCATION_ID"

curl -s -X POST "https://www.spotonapp.cloud/api/posts" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{
    \"location_id\": \"$LOCATION_ID\",
    \"text\": \"Annuncio test sul server\",
    \"musica\": \"Ritornello test\",
    \"sighting_date\": \"2026-07-09\"
  }"
```

Prendere un `POST_ID` dalla risposta.

### 6. Lista Posts

```bash
curl -s "https://www.spotonapp.cloud/api/posts" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### 7. Dettaglio Post

```bash
POST_ID="INCOLLA_POST_ID"

curl -s "https://www.spotonapp.cloud/api/posts/$POST_ID" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### 8. Nearby Posts

```bash
curl -s "https://www.spotonapp.cloud/api/posts/nearby?lat=40.8518&lng=14.2681&radius_km=200" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### 9. Map

```bash
curl -s "https://www.spotonapp.cloud/api/map?lat=40.8518&lng=14.2681&radius_km=200" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### 10. Stories

```bash
curl -s "https://www.spotonapp.cloud/api/locations/$LOCATION_ID/stories" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### 11. API Admin Locations

Login admin:

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@spoton.local","password":"password123"}'
```

Salvare token admin:

```bash
ADMIN_TOKEN="INCOLLA_ADMIN_TOKEN"
```

Creare location admin:

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/admin/locations" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{
    "name": "Test Server Location",
    "short": "Test Server",
    "city": "Napoli",
    "type": "altro",
    "latitude": 40.8518,
    "longitude": 14.2681,
    "geo_radius_meters": 100,
    "icon": "map-pin",
    "is_active": true
  }'
```

## Client Browser API

Aprire:

```text
https://www.spotonapp.cloud/api-client.html
```

Usare:

```text
test@example.com
password123
```

oppure admin:

```text
admin@spoton.local
password123
```

## Seeder Attuali

`DatabaseSeeder` crea:

- utente test:

```text
test@example.com
password123
is_admin=false
```

- utente admin:

```text
admin@spoton.local
password123
is_admin=true
```

- luoghi iniziali Napoli:

```text
Metro Mergellina
Bar Nilo
Piazza Plebiscito
Lungomare Caracciolo
```

## Stato Test Locale

Ultimo stato locale verificato:

```text
30 test passed
105 assertions
```

Comando:

```bash
php artisan test
```

## Cosa Resta Da Fare

### Step 7 - Like + Io C'ero

Da implementare:

- tabella `likes`;
- tabella `post_i_was_there`;
- toggle like;
- toggle Io c'ero;
- proprietario non puo' cliccare Io c'ero sul proprio post;
- counter transazionali;
- lista utenti Io c'ero visibile solo al proprietario;
- privacy: non esporre email/posizione.

Endpoint previsti:

```text
POST /api/posts/{post}/like
POST /api/posts/{post}/io-cero
GET  /api/posts/{post}/io-cero-users
```

### Step 8 - Presence / Posizione Utente

Da implementare:

- endpoint `POST /api/presence/ping`;
- aggiornamento `users.last_known_latitude`;
- aggiornamento `users.last_known_longitude`;
- tabella `presence_sessions`;
- conteggio utenti attivi per luogo;
- chiusura sessioni stale.

### Step 9 - Chat

Da implementare:

- tabella `chats`;
- tabella `messages`;
- apertura chat tra due utenti;
- lista chat;
- lista messaggi;
- invio messaggio;
- policy solo partecipanti.

Endpoint previsti:

```text
GET  /api/chats
POST /api/chats/open
GET  /api/chats/{chat}/messages
POST /api/chats/{chat}/messages
```

### Step 10 - Fake Seeder Completo

Da implementare dopo Like/Io c'ero/Presence/Chat:

- seeder fake utenti;
- seeder fake luoghi;
- seeder fake posts con `musica`;
- seeder fake like;
- seeder fake Io c'ero;
- seeder fake presence;
- seeder fake chat/messages;
- comando unico per popolare dati demo.

### Step 11 - Hardening Produzione

Da fare quando staging e test sono ok:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- proteggere o rimuovere `api-client.html`;
- configurare Supervisor per queue;
- verificare cron scheduler;
- configurare backup DB;
- controllare CORS/app mobile Expo;
- configurare policy privacy/GDPR.

## Note Importanti

- Il server deve lavorare sulla repo Laravel root, cioe' `/var/www/spotonapp.cloud/repo`.
- Il dominio deve puntare a `/var/www/spotonapp.cloud/repo/public`.
- Non committare mai `.env`.
- Non committare password reali.
- Per ora PostGIS e' opzionale: se disponibile viene attivato, altrimenti il backend usa latitudine/longitudine decimal.
- Il raggio di test principale e' `200 km`.
