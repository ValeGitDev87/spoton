# Checklist Test Finale SpotOn

Usare questo file dopo il push su GitHub e il pull sul server.

## 1. Deploy Server

```bash
ssh root@187.77.89.94
cd /var/www/spotonapp.cloud/repo
git pull
composer install --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan storage:link
```

Solo in staging/test, se vuoi dati fake completi:

```bash
php artisan db:seed --class=DemoDataSeeder --force
```

Verifica route e scheduler:

```bash
php artisan route:list --path=api
php artisan route:list --path=admin
php artisan schedule:list
```

## 2. Test Admin Web

Aprire:

```text
https://www.spotonapp.cloud/login
```

Credenziali:

```text
admin@spoton.local
password123
```

Provare:

- dashboard `/admin`;
- utenti `/admin/users`;
- posts `/admin/posts`;
- cambio stato post;
- luoghi `/admin/locations`;
- crea/modifica luogo con latitudine e longitudine;
- backup `/admin/backups`.

## 3. Test Client API Browser

Aprire:

```text
https://www.spotonapp.cloud/api-client.html
```

Login utente:

```text
test@example.com
password123
```

Provare in ordine:

1. Login.
2. Locations.
3. Nearby.
4. Map.
5. Lista posts.
6. Creazione post.
7. Like toggle.
8. Io c'ero toggle.
9. Presence ping.
10. Stories.
11. Chat, usando uno User UUID diverso.

## 4. Test API Curl Minimi

Login:

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

Salva token:

```bash
TOKEN="INCOLLA_TOKEN"
```

Presence:

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

Lista chat:

```bash
curl -s "https://www.spotonapp.cloud/api/chats" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 5. Esito Atteso

- admin web accessibile solo con admin;
- utenti normali non entrano in `/admin`;
- post disattivabili da admin;
- locations gestibili con coordinate;
- nearby/map restituiscono dati coerenti;
- like e Io c'ero aggiornano i contatori;
- presence aggiorna la posizione utente e i luoghi connessi;
- chat crea e legge messaggi;
- backup admin non espone file fuori dalla cartella configurata.
