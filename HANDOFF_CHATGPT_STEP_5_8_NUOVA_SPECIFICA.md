# Handoff Test - SpotOn Nuova Specifica Step 5-8

Questo documento serve per testare gli ultimi 4 step della nuova specifica backend:

5. Challenge classica;
6. Challenge invertita;
7. Controproposta;
8. Foto profilo, karma e rifiniture finali.

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

## Endpoint Nuovi

Challenge classica su post Ghost:

```text
POST /api/posts/{post}/verify-answer
POST /api/posts/{post}/counter-propose
```

Challenge invertita:

```text
POST /api/challenges
POST /api/challenges/{challenge}/answer
GET  /api/challenges/pending
```

Controproposta:

```text
POST /api/challenges/{challenge}/counter-propose
POST /api/challenges/{challenge}/counter-review
```

Profilo:

```text
GET    /api/users/me/karma
POST   /api/users/me/photos
DELETE /api/users/me/photos/{photoId}
```

Chat aggiornata:

```text
GET /api/chats
```

Ora le chat possono includere:

```text
origin_challenge_id
origin_post_id
```

## Regole Da Verificare

Challenge classica:

- parte da un post con `secret_question` e `secret_answer_hash`;
- chi risponde correttamente sblocca il post Ghost;
- il post diventa `is_anonymous=false`;
- aumenta `spot_on_count`;
- si crea o riusa una chat tra autore e utente verificato;
- aumenta il karma di chi ha risposto;
- `secret_answer_hash` non deve mai uscire nelle API.

Challenge invertita:

- chi riconosce crea una challenge verso autore post o autore commento;
- se target e' autore post e il post ha gia domanda segreta, usare la classica;
- il target risponde;
- se risposta corretta, challenge `unlocked`;
- si crea chat tra target e challenger;
- aumenta karma del challenger;
- se target e' autore Ghost del post, viene sbloccata identita.

Controproposta:

- chi non ricorda la risposta puo proporre un dettaglio alternativo;
- in caso classico valuta l'autore del post;
- in caso invertito valuta il challenger;
- se accettata: chat, karma, eventuale reveal Ghost;
- se rifiutata: nessuna chat e nessun reveal.

Foto profilo:

- massimo 10 foto;
- `POST /api/users/me/photos` accetta `photo_url` oppure file `photo`;
- `DELETE /api/users/me/photos/{photoId}` elimina per indice;
- `GET /api/me` mostra `photos`.

## Test Manuale Browser

Aprire:

```text
https://www.spotonapp.cloud/api-client.html
```

Sequenza consigliata:

1. Login con `test@example.com / password123`.
2. Lista posts e scegli un post Ghost.
3. Inserisci la risposta demo `sorriso`.
4. Premi `Verifica classica`.
5. Controlla che torni `chat_id`, `karma`, `spot_on_count`.
6. Crea una challenge invertita su un post senza domanda segreta.
7. Entra con l'utente target e rispondi da `Pending`.
8. Prova `Counter post` su un post Ghost.
9. Entra come autore e fai `Accetta` o `Rifiuta`.
10. Prova `Karma`, `Aggiungi foto URL`, `Elimina foto`.

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

Verifica classica:

```bash
POST_ID="INCOLLA_POST_GHOST_ID"

curl -s -X POST "https://www.spotonapp.cloud/api/posts/$POST_ID/verify-answer" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"answer":"sorriso"}'
```

Creare challenge invertita:

```bash
POST_ID="INCOLLA_POST_ID"

curl -s -X POST "https://www.spotonapp.cloud/api/challenges" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d "{
    \"post_id\":\"$POST_ID\",
    \"target_type\":\"post_author\",
    \"mode\":\"question\",
    \"question\":\"Che dettaglio ricordi?\",
    \"answer\":\"sorriso\"
  }"
```

Pending:

```bash
curl -s "https://www.spotonapp.cloud/api/challenges/pending" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Rispondere a challenge:

```bash
CHALLENGE_ID="INCOLLA_CHALLENGE_ID"

curl -s -X POST "https://www.spotonapp.cloud/api/challenges/$CHALLENGE_ID/answer" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"answer":"sorriso"}'
```

Controproposta classica:

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/posts/$POST_ID/counter-propose" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"text":"Non ricordo la risposta esatta, ma ricordo un dettaglio preciso."}'
```

Review controproposta:

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/challenges/$CHALLENGE_ID/counter-review" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"accepted":true}'
```

Karma:

```bash
curl -s "https://www.spotonapp.cloud/api/users/me/karma" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Aggiungere foto URL:

```bash
curl -s -X POST "https://www.spotonapp.cloud/api/users/me/photos" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"photo_url":"https://example.test/photos/manual.jpg"}'
```

Creare post con nota audio max 10 secondi:

```bash
LOCATION_ID="INCOLLA_LOCATION_ID"

curl -s -X POST "https://www.spotonapp.cloud/api/posts" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -F "location_id=$LOCATION_ID" \
  -F "text=Post con nota audio test" \
  -F "sighting_date=2026-07-17" \
  -F "audio_duration_seconds=10" \
  -F "audio=@/percorso/nota.m4a;type=audio/mp4"
```

Rimuovere audio da un post:

```bash
POST_ID="INCOLLA_POST_ID"

curl -s -X PATCH "https://www.spotonapp.cloud/api/posts/$POST_ID" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"remove_audio":true}'
```

## Esito Atteso

- 42 route API disponibili.
- Test automatici locali: 60 test, 270 assertion.
- Challenge corrette non espongono `answer_hash`.
- Chat create da challenge riportano `origin_challenge_id`.
- Chat create da classica diretta riportano `origin_post_id`.
- `karma` aumenta solo su verifica riuscita o controproposta accettata.
- Foto profilo bloccate a massimo 10.
- Nota audio post bloccata a massimo 10 secondi e 1 MB.
- Response post con `audio.url`, `audio.mime`, `audio.size_bytes`, `audio.size_kb`, `audio.duration_seconds`.

## Stato Locale

Verifiche passate localmente:

```text
php artisan test
php artisan migrate:fresh --seed
php artisan db:seed --class=DemoDataSeeder
npm run build
```
