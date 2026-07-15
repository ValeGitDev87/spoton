# Handoff Test - SpotOn Nuova Specifica Step 1-4

Questo documento serve per testare i primi 4 step della nuova specifica backend:

1. profilo utente esteso;
2. post Ghost con domanda segreta;
3. preferiti privati;
4. commenti con tag solo sui preferiti.

## Deploy Server

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

Seeder demo solo in staging/test:

```bash
php artisan db:seed --class=DemoDataSeeder --force
```

Verifiche:

```bash
php artisan route:list --path=api
php artisan test
```

## Credenziali Demo

```text
admin@spoton.local
password123

test@example.com
password123
```

## API Nuove O Aggiornate

Profilo:

```text
GET /api/me
```

Ora include:

```text
avatar_url
bio
photos
karma
auth_provider
```

Post Ghost:

```text
POST /api/posts
GET /api/posts/{post}
```

Nuovi campi:

```text
song_quote
is_anonymous
secret_question
secret_answer
```

Nota sicurezza:

- `secret_answer` viene salvata solo hashata;
- `secret_answer_hash` non deve mai uscire nelle API;
- se `is_anonymous=true`, i non-owner/non-admin vedono autore `Ghost`.

Preferiti:

```text
GET    /api/favorites?query=
POST   /api/favorites
DELETE /api/favorites/{targetName}
```

Commenti:

```text
GET  /api/posts/{post}/comments
POST /api/posts/{post}/comments
```

Il tag con `@Nome` e' valido solo se `Nome` e' nei preferiti dell'utente che commenta.

## Test Manuale Browser

Aprire:

```text
https://www.spotonapp.cloud/api-client.html
```

Sequenza consigliata:

1. Login con `test@example.com / password123`.
2. Cliccare `Locations` e prendere una location.
3. Creare un post normale.
4. Creare un post Ghost compilando:
   - Modalita Ghost;
   - Domanda Ghost;
   - Risposta Ghost.
5. Fare dettaglio post con un altro utente e verificare autore `Ghost`.
6. Aggiungere preferito, per esempio `Sara`.
7. Cercare preferito con query `sa`.
8. Creare commento con `@Sara`.
9. Provare commento con un nome non preferito: deve dare errore 422.

## Curl Base

Login:

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

Aggiungi preferito:

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/favorites" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"target_name":"Sara"}'
```

Autocomplete preferiti:

```bash
curl -s "https://www.spotonapp.cloud/api/favorites?query=sa" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Creare post Ghost:

```bash
LOCATION_ID="INCOLLA_LOCATION_ID"

curl -s -X POST "https://www.spotonapp.cloud/api/posts" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{
    \"location_id\": \"$LOCATION_ID\",
    \"text\": \"Ci siamo visti vicino alla metro.\",
    \"song_quote\": \"Una frase della canzone\",
    \"sighting_date\": \"2026-07-15\",
    \"is_anonymous\": true,
    \"secret_question\": \"Che colore era il libro?\",
    \"secret_answer\": \"blu\"
  }"
```

Commentare con tag:

```bash
POST_ID="INCOLLA_POST_ID"

curl -s -X POST "https://www.spotonapp.cloud/api/posts/$POST_ID/comments" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"text":"Ciao @Sara, secondo me eri tu."}'
```

Lista commenti:

```bash
curl -s "https://www.spotonapp.cloud/api/posts/$POST_ID/comments" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## Esito Atteso

- `GET /api/me` mostra i nuovi campi profilo.
- I post Ghost mascherano l'autore per altri utenti.
- `secret_answer_hash` non appare mai nelle response.
- I preferiti sono privati per utente.
- Aggiungere lo stesso preferito due volte non crea duplicati.
- L'autocomplete dei tag legge solo i preferiti.
- I commenti aggiornano `comment_count`.
- Un tag verso un nome non preferito viene rifiutato con 422.

## Stato Locale

Verifiche passate localmente:

```text
php artisan migrate:fresh --seed
php artisan db:seed --class=DemoDataSeeder
php artisan test --filter='FavoritesAndCommentsApiTest|GhostPostsApiTest|ProfileApiTest|DemoDataSeederTest'
```
