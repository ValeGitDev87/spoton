# Chat Ghost persistente

## Stato

Backend implementato localmente. Nessun push Git e nessun deploy VPS eseguito.

## Modello

La migration:

```text
2026_07_30_000100_add_ghost_context_to_chats_table.php
```

aggiunge a `chats`:

```text
conversation_key
context_type
ghost_owner_id
ghost_identity_revealed_at
```

Le chat esistenti vengono convertite in `direct`.

Chiavi:

```text
direct:{user_one_id}:{user_two_id}
ghost_post:{post_id}:{user_one_id}:{user_two_id}
```

Una chat normale e una chat Ghost possono quindi coesistere tra gli stessi utenti.

## Apertura

Il flusso frontend attuale continua a usare:

```http
POST /api/challenges
```

con:

```json
{
  "post_id": "UUID_POST",
  "target_type": "post_author",
  "mode": "direct"
}
```

E' inoltre possibile aprire esplicitamente una chat dal post:

```http
POST /api/chats/open
```

```json
{
  "user_id": "UUID_AUTORE",
  "post_id": "UUID_POST"
}
```

Senza `post_id`, `/api/chats/open` apre o riusa la chat `direct`.

## Payload chat

Prima della rivelazione, il partecipante esterno riceve:

```json
{
  "id": "UUID_CHAT",
  "context_type": "ghost_post",
  "is_ghost": true,
  "identity_revealed": false,
  "can_reveal_identity": false,
  "participant": {
    "id": null,
    "display_name": "Ghost",
    "avatar_color": null,
    "avatar_url": null,
    "is_ghost": true
  }
}
```

L'autore Ghost vede normalmente l'altro partecipante e riceve:

```json
{
  "is_ghost": true,
  "identity_revealed": false,
  "can_reveal_identity": true
}
```

## Messaggi e push

Prima della rivelazione, i messaggi del Ghost hanno sender mascherato.

La push usa:

```text
Nuovo messaggio
Ghost ti ha scritto su SpotOn.
```

Il payload contiene solo:

```json
{
  "type": "new_message",
  "chat_id": "UUID_CHAT",
  "message_id": "UUID_MESSAGGIO"
}
```

Non viene incluso il vero `sender_id`.

## Reveal

Endpoint:

```http
POST /api/chats/{chat}/reveal-identity
Authorization: Bearer TOKEN_GHOST_OWNER
```

Regole:

- solo il Ghost owner puo' usarlo;
- altro partecipante: `403`;
- non partecipante: `403`;
- chat direct: `422`;
- chiamate ripetute: `200`, senza notifiche duplicate;
- il post resta `is_anonymous = true`.

Alla prima rivelazione viene salvato `ghost_identity_revealed_at` e viene generata la notifica:

```text
Ghost ha rivelato la sua identita.
```

## Test API manuale

1. Creare un post Ghost con l'utente A.
2. Con l'utente B aprire il contatto diretto dal post.
3. Salvare il `chat_id`.
4. Con B chiamare `GET /api/chats` e verificare `participant.id = null`.
5. Con A inviare un messaggio.
6. Con B chiamare `GET /api/chats/{chat_id}/messages` e verificare sender `Ghost`.
7. Con A chiamare `POST /api/chats/{chat_id}/reveal-identity`.
8. Con B rileggere chat e messaggi e verificare il profilo reale.
9. Controllare che il post sia ancora Ghost.
10. Aprire anche una chat senza `post_id` e verificare che sia una seconda chat `direct`.

## Prossimo blocco frontend

- aggiornare i tipi `Chat` e `Message`;
- mostrare avatar e nome Ghost dai payload ricevuti;
- mostrare `Rivela la mia identita` solo con `can_reveal_identity = true`;
- chiedere conferma prima del reveal;
- aggiornare lista e thread dopo il reveal;
- gestire la notifica `ghost_identity_revealed`;
- verificare cache, polling, badge e navigazione push.
