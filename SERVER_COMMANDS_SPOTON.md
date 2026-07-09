# Comandi Server SpotOn

Dominio:

```text
https://www.spotonapp.cloud
```

Root Laravel da usare sul server:

```text
/var/www/spotonapp.cloud/repo
```

Public root del dominio:

```text
/var/www/spotonapp.cloud/repo/public
```

---

## Primo Pull / Aggiornamento

```bash
ssh root@187.77.89.94
cd /var/www/spotonapp.cloud/repo
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
php artisan storage:link
```

Se il file `.env` non esiste ancora:

```bash
cp .env.server.example .env
php artisan key:generate
nano .env
```

Valori importanti:

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
```

---

## Cron Scheduler

Serve per far scadere automaticamente gli annunci/storie.

Aprire crontab:

```bash
crontab -e
```

Aggiungere:

```cron
* * * * * cd /var/www/spotonapp.cloud/repo && php artisan schedule:run >> /dev/null 2>&1
```

---

## Queue Worker Temporaneo

Per test immediato:

```bash
cd /var/www/spotonapp.cloud/repo
php artisan queue:work redis --sleep=3 --tries=3 --timeout=120
```

Poi andra' messo sotto Supervisor.

---

## Test Dashboard

Aprire:

```text
https://www.spotonapp.cloud/login
```

Credenziali seed:

```text
admin@spoton.local
password123
```

Pagina luoghi:

```text
https://www.spotonapp.cloud/admin/locations
```

---

## Test API Rapidi

Login API:

```bash
curl -X POST "https://www.spotonapp.cloud/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

Salvare il token:

```bash
TOKEN="INCOLLA_TOKEN"
```

Me:

```bash
curl "https://www.spotonapp.cloud/api/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Locations:

```bash
curl "https://www.spotonapp.cloud/api/locations" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Map:

```bash
curl "https://www.spotonapp.cloud/api/map?lat=40.8518&lng=14.2681&radius_km=200" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Client API browser:

```text
https://www.spotonapp.cloud/api-client.html
```
