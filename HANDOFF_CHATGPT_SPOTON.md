# Handoff ChatGPT - SpotOn Backend

Documento operativo per testare backend, API e portale admin SpotOn dopo push su GitHub e pull sul server.

## Contesto

Repo GitHub:

```text
git@github.com:ValeGitDev87/spoton.git
```

Root Laravel:

```text
/var/www/spotonapp.cloud/repo
```

Dominio:

```text
https://www.spotonapp.cloud
```

Public root:

```text
/var/www/spotonapp.cloud/repo/public
```

Ambiente attuale consigliato:

```env
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://www.spotonapp.cloud
```

Quando i test sono finiti:

```env
APP_ENV=production
APP_DEBUG=false
```

## Cosa E' Stato Implementato

Backend API:

- auth Sanctum: register, login, logout, me;
- utenti con UUID e flag `is_admin`;
- locations pubbliche e nearby;
- CRUD API admin locations;
- posts/annunci con `musica`, scadenza 24h, owner/admin permissions;
- feed nearby posts;
- map endpoint;
- stories per location;
- scheduler per scadenza post;
- like toggle;
- Io c'ero toggle;
- lista utenti Io c'ero solo per owner/admin;
- presence ping con sessioni attive nei luoghi;
- chiusura presence stale dopo 5 minuti;
- chat 1 a 1 con messaggi;
- demo seeder completo.

Portale admin web:

- login web;
- dashboard;
- utenti;
- posts con cambio stato;
- CRUD luoghi con coordinate;
- backup admin: lista, download e avvio manuale.

Client test:

```text
https://www.spotonapp.cloud/api-client.html
```

Il client ora prova auth, locations, admin locations, posts, nearby, map, stories, like, Io c'ero, presence e chat.

## Credenziali Seeder

Admin:

```text
admin@spoton.local
password123
```

Utente test:

```text
test@example.com
password123
```

## Comandi Server Dopo Push

Entrare sul server:

```bash
ssh root@187.77.89.94
cd /var/www/spotonapp.cloud/repo
```

Aggiornare codice e dipendenze:

```bash
git pull
composer install --optimize-autoloader
npm ci
npm run build
```

Applicare database e pulire cache:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan storage:link
```

Seeder demo solo in staging/test, non in produzione con dati veri:

```bash
php artisan db:seed --class=DemoDataSeeder --force
```

Verifiche Laravel:

```bash
php artisan route:list --path=api
php artisan route:list --path=admin
php artisan schedule:list
php artisan test
```

Se sul server hai installato Composer con `--no-dev`, `php artisan test` puo' non essere disponibile. In quel caso usare curl e browser.

Riavviare worker/supervisor se attivo:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all
```

## Env Backup Admin

Nel `.env` server verificare:

```env
SPOTON_BACKUP_PATH=/var/backups/spotonapp
SPOTON_BACKUP_COMMAND=/usr/local/sbin/spotonapp-backup.sh
```

La pagina admin backup usa solo file consentiti con nome:

```text
spotonapp_db_*.dump
```

## Test Portale Admin

Aprire:

```text
https://www.spotonapp.cloud/login
```

Login admin:

```text
admin@spoton.local
password123
```

Provare:

- `/admin`;
- `/admin/users`;
- `/admin/posts`;
- cambio stato post: `active`, `removed`, `flagged`, `expired`;
- `/admin/locations`;
- creazione/modifica/eliminazione luogo con latitudine e longitudine;
- `/admin/backups`;
- download backup;
- avvio backup manuale.

## Test API Rapidi Via Curl

Login utente:

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

Salvare token:

```bash
TOKEN="INCOLLA_TOKEN"
```

Me:

```bash
curl -s "https://www.spotonapp.cloud/api/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Locations:

```bash
curl -s "https://www.spotonapp.cloud/api/locations" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Nearby locations:

```bash
curl -s "https://www.spotonapp.cloud/api/locations/nearby?lat=40.8518&lng=14.2681&radius_km=200" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Creare post, sostituendo `LOCATION_ID`:

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
    \"sighting_date\": \"2026-07-13\"
  }"
```

Salvare post:

```bash
POST_ID="INCOLLA_POST_ID"
```

Like:

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/posts/$POST_ID/like" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Io c'ero:

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/posts/$POST_ID/io-cero" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Lista Io c'ero, solo owner/admin:

```bash
curl -s "https://www.spotonapp.cloud/api/posts/$POST_ID/io-cero-users" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Presence ping:

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/presence/ping" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"lat":40.8518,"lng":14.2681}'
```

Map:

```bash
curl -s "https://www.spotonapp.cloud/api/map?lat=40.8518&lng=14.2681&radius_km=200" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Stories:

```bash
curl -s "https://www.spotonapp.cloud/api/locations/$LOCATION_ID/stories" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## Test Chat

Serve uno user UUID diverso dal proprio. Con il seeder demo puoi prenderlo da database o dalla dashboard utenti.

```bash
OTHER_USER_ID="INCOLLA_USER_ID"

curl -s -X POST "https://www.spotonapp.cloud/api/chats/open" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{\"user_id\":\"$OTHER_USER_ID\"}"
```

Salvare chat:

```bash
CHAT_ID="INCOLLA_CHAT_ID"
```

Inviare messaggio:

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/chats/$CHAT_ID/messages" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"text":"Ciao, test chat SpotOn"}'
```

Leggere messaggi:

```bash
curl -s "https://www.spotonapp.cloud/api/chats/$CHAT_ID/messages" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## Test API Admin Locations

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

Creare location:

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

## Stato Verifiche Locali

Comandi gia' passati in locale:

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=DemoDataSeeder
php artisan test
```

Risultato ultimo test locale:

```text
40 test, 154 assertion, OK
```

## Cosa Resta Da Fare

Tecnico backend principale: completato per questa fase.

Resta:

- push su GitHub;
- pull sul server;
- `php artisan migrate --force`;
- eventuale `DemoDataSeeder` in staging;
- test browser e curl sul dominio;
- passare a `APP_ENV=production` e `APP_DEBUG=false` quando tutto e' confermato;
- futura app Expo;
- futura gestione notifiche real-time/push;
- eventuale ottimizzazione query presence/chat quando aumentano gli utenti.
