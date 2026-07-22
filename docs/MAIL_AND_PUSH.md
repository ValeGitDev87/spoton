# SpotOn - Mail e Push

Documento operativo per verificare email account, reset password e notifiche push Expo.

## Cosa e stato aggiunto

- Verifica email dopo registrazione con link firmato e scadenza configurabile.
- Email welcome inviata una sola volta dopo verifica email.
- Forgot/reset password via Password Broker Laravel, con token monouso.
- Cambio password da utente loggato, con revoca degli altri token Sanctum.
- Code separate: email su queue `emails`, push su queue `notifications`.
- Device token Expo per utente/dispositivo.
- Driver push `log` di default, senza chiamate esterne.
- Driver push `expo` attivabile da `.env` quando serve inviare davvero a Expo.
- Push `new_message` accodata al destinatario quando arriva un nuovo messaggio chat.
- Endpoint dev per inviare una push di prova, disabilitato in `production`.
- Client test aggiornato in `public/api-client.html`.

## Variabili .env

Locale o staging:

```env
APP_ENV=local
APP_URL=http://127.0.0.1:8000

MAIL_MAILER=log
QUEUE_CONNECTION=redis

SPOTON_EMAIL_VERIFICATION_EXPIRE_MINUTES=60
SPOTON_PASSWORD_RESET_EXPIRE_MINUTES=30
SPOTON_PASSWORD_RESET_URL=http://127.0.0.1:8000/reset-password
SPOTON_EMAIL_VERIFIED_URL=http://127.0.0.1:8000/email-verified

PUSH_DRIVER=log
EXPO_PUSH_ENDPOINT=https://exp.host/--/api/v2/push/send
EXPO_PUSH_RECEIPTS_ENDPOINT=https://exp.host/--/api/v2/push/getReceipts
EXPO_ACCESS_TOKEN=
```

Server con SMTP Hostinger:

```env
APP_ENV=staging
APP_URL=https://www.spotonapp.cloud

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=noreply@spotonapp.cloud
MAIL_PASSWORD=INSERIRE_PASSWORD_REALE_SUL_SERVER
MAIL_FROM_ADDRESS="noreply@spotonapp.cloud"
MAIL_FROM_NAME="SpotOn"

QUEUE_CONNECTION=redis

SPOTON_EMAIL_VERIFICATION_EXPIRE_MINUTES=60
SPOTON_PASSWORD_RESET_EXPIRE_MINUTES=30
SPOTON_PASSWORD_RESET_URL=https://www.spotonapp.cloud/reset-password
SPOTON_EMAIL_VERIFIED_URL=https://www.spotonapp.cloud/email-verified

PUSH_DRIVER=log
EXPO_ACCESS_TOKEN=
```

Quando l'app Expo e pronta e vuoi inviare push reali:

```env
PUSH_DRIVER=expo
EXPO_ACCESS_TOKEN=
```

`EXPO_ACCESS_TOKEN` resta vuoto se il progetto Expo non richiede token.

## Comandi server

Da lanciare nella cartella Laravel `backend` sul server:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Worker code in test/staging:

```bash
php artisan queue:work --queue=emails,notifications,default --tries=3
```

In produzione conviene gestirlo con Supervisor/systemd, sempre sulla stessa lista code:

```bash
emails,notifications,default
```

Pulizia token reset password schedulata:

```bash
php artisan schedule:list
php artisan auth:clear-resets
```

## Endpoint API

Auth gia esistenti:

```http
POST /api/auth/register
POST /api/auth/login
GET  /api/me
POST /api/auth/logout
```

Nuovi endpoint email/password:

```http
POST  /api/auth/email/verification-notification
POST  /api/auth/forgot-password
POST  /api/auth/reset-password
PATCH /api/auth/password
```

Nuovi endpoint push:

```http
PUT    /api/me/push-tokens/{deviceId}
DELETE /api/me/push-tokens/{deviceId}
POST   /api/dev/push/test
```

`POST /api/dev/push/test` funziona solo se `APP_ENV` non e `production`.

Eventi push backend attivi:

- challenge e controproposte;
- nuovi commenti e menzioni;
- nuovi messaggi chat con payload `type=new_message`, `chat_id`, `message_id` e `sender_id`.

Il testo scritto in chat non viene copiato nel corpo della notifica.

## Prove rapide API

1. Registrazione utente:

```bash
curl -X POST https://www.spotonapp.cloud/api/auth/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"display_name":"Test Mail","email":"test@example.com","password":"password123","password_confirmation":"password123"}'
```

Risultato atteso:

- risposta `201`
- `data.user.email_verified` uguale a `false`
- mail di verifica accodata su queue `emails`

2. Reinvia verifica email:

```bash
curl -X POST https://www.spotonapp.cloud/api/auth/email/verification-notification \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN"
```

3. Forgot password:

```bash
curl -X POST https://www.spotonapp.cloud/api/auth/forgot-password \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'
```

La risposta deve essere generica anche se l'email non esiste.

4. Reset password:

```bash
curl -X POST https://www.spotonapp.cloud/api/auth/reset-password \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","token":"TOKEN_DA_EMAIL","password":"new-password123","password_confirmation":"new-password123"}'
```

5. Cambio password da loggato:

```bash
curl -X PATCH https://www.spotonapp.cloud/api/auth/password \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"current_password":"password123","password":"new-password123","password_confirmation":"new-password123"}'
```

6. Registra token push:

```bash
curl -X PUT https://www.spotonapp.cloud/api/me/push-tokens/iphone-test \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"token":"ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]","platform":"ios","app_version":"1.0.0","locale":"it-IT","timezone":"Europe/Rome"}'
```

7. Push test in staging/local:

```bash
curl -X POST https://www.spotonapp.cloud/api/dev/push/test \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"title":"SpotOn test","body":"Push di prova"}'
```

Con `PUSH_DRIVER=log` controllare `storage/logs/laravel.log`: devono comparire hash/token parziali, mai il token completo.

## Prove frontend web

Aprire:

```text
https://www.spotonapp.cloud/api-client.html
```

Flusso consigliato:

1. Login o register.
2. Copiare il token salvato dal client.
3. Provare `Reinvia verifica`.
4. Provare `Forgot password`.
5. Aprire il link ricevuto via email.
6. Provare `Registra token`.
7. Provare `Push test` con `PUSH_DRIVER=log`.

## Test locali gia pronti

```bash
php artisan test tests/Feature/AuthMailApiTest.php tests/Feature/PushNotificationApiTest.php
php artisan test
```

Verifiche extra:

```bash
php artisan route:list --path=api
php artisan schedule:list
./vendor/bin/pint --dirty
```

## Cosa resta da fare

- Configurare SMTP reale sul server con password Hostinger.
- Configurare worker queue persistente sul server.
- Quando l'app Expo sara pronta, sostituire `PUSH_DRIVER=log` con `PUSH_DRIVER=expo`.
- Recuperare dall'app Expo il vero `ExponentPushToken[...]` e registrarlo via API.
- Decidere se aggiungere un pannello admin per vedere i push token registrati.
- Fare test end-to-end da app Expo reale: register, verifica email, login, registra token, ricezione push.
