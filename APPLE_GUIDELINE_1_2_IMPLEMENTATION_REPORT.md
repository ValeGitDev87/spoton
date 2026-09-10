# SpotOn - Implementazione Apple Guideline 1.2

**Data:** 2026-09-10  
**Ambito:** UGC, contenuti Ghost, sicurezza e moderazione

## Esito

Sono stati implementati i requisiti tecnici mancanti rilevati nell'audit. Le funzioni di report e blocco già presenti sono state conservate e verificate.

### 1. Termini, EULA e maggiore eta

- Nuova pagina pubblica `/terms`, responsive e raggiungibile dall'app.
- I Termini vietano contenuti illegali/offensivi/abusivi, molestie, odio, minacce, spam, impersonificazione e abuso di Ghost.
- Dichiarate tolleranza zero, rimozione dei contenuti, sospensione/espulsione, report, blocco e moderazione tempestiva.
- La registrazione richiede un consenso esplicito 18+ e Termini/EULA.
- Il backend salva `terms_accepted_at` e `terms_version`.
- Gli account legacy possono fare login, logout, leggere `/api/me`, accettare i Termini o eliminare l'account; le altre API restano bloccate fino all'accettazione della versione corrente.

`APPLE AGE RATING REQUIRED: 18+`

### 2. Filtro contenuti

- Servizio centralizzato, deterministico e locale con esiti `allow`, `warn`, `block`.
- Normalizzazione di maiuscole/minuscole, accenti, punteggiatura, ripetizioni e sostituzioni leetspeak comuni.
- Configurazione centralizzata in `config/content_moderation.php`.
- Validazione applicata a post normali e Ghost, commenti, chat, controproposte, nome/bio/profilo e luoghi proposti dalla community.
- Nessun provider AI, servizio a pagamento o invio di UGC a terze parti.
- Audio e video non vengono analizzati automaticamente: restano soggetti a Termini, report, blocco e moderazione umana.

### 3. Nascondi post

- Nuova persistenza per utente in `hidden_posts`.
- Endpoint `POST /api/posts/{post}/hide` e `DELETE /api/posts/{post}/hide`.
- Il post viene rimosso subito dal feed locale e resta nascosto dopo refresh o nuovo login.
- Feed, nearby, Stories e contatori dei luoghi escludono i post nascosti solo per quell'utente.
- Il post non viene eliminato globalmente e resta disponibile agli admin.
- Non e possibile nascondere un proprio post.
- La risposta API non espone l'identita dell'autore Ghost.

### 4. Moderazione e contatti

- La coda `/admin/reports` mostra prima i report pending piu vecchi.
- Evidenza visuale dei report prossimi alle 24 ore e di quelli oltre SLA.
- Restano disponibili rimozione post, sospensione utente, revoca token, revisore, timestamp e nota di risoluzione.
- Profilo > Impostazioni ora include assistenza, segnalazione attivita inappropriata, Termini/EULA, Privacy e Sicurezza e minori.
- Contatto utilizzato: `privacy@spotonapp.cloud`.

`24-HOUR MODERATION SLA REQUIRES OPERATIONAL HUMAN PROCESS`

Procedura necessaria: una persona incaricata deve controllare `/admin/reports` con frequenza sufficiente, esaminare prima i pending piu vecchi, intervenire o archiviare ogni caso entro 24 ore e registrare una nota quando utile. Il software evidenzia le scadenze ma non sospende automaticamente un utente per una singola segnalazione.

## Verifiche automatiche

- `php artisan test`: **193 test, 1138 assertions, 0 failures**.
- `./node_modules/.bin/tsc --noEmit`: **exit code 0**.
- `git diff --check`: **nessun errore**.
- Route Terms, accettazione e hide verificate con `artisan route:list`.

Copertura nuova: consenso obbligatorio e account legacy, pagina Termini, filtro e normalizzazione su più tipi UGC incluso Ghost, hide/unhide persistente, esclusione da feed/Stories, self-hide vietato, ordinamento e badge SLA admin.

## Checklist manuale prima della nuova build iOS

1. Eseguire deploy backend e `php artisan migrate --force` dopo backup.
2. Verificare `/terms` e i collegamenti in Profilo > Impostazioni sul dominio pubblico.
3. Registrare un nuovo account senza e con checkbox e provare un account legacy.
4. Verificare su iPhone e iPad che checkbox, link, gate legacy e impostazioni non escano dallo schermo.
5. Nascondere un post normale e un post Ghost, aggiornare il feed e riaprire l'app.
6. Inviare testi di prova non consentiti in post, commento, chat e profilo; verificare il messaggio italiano.
7. Creare un report e verificare coda admin, intervento, archiviazione e indicatori SLA.
8. In App Store Connect impostare il rating dell'app a **18+** e riesaminare le risposte relative a UGC, chat/messaggistica, contenuti anonimi/Ghost, linguaggio maturo e contenuti potenzialmente offensivi.
9. Inserire nelle note di revisione Apple il percorso esatto: Profilo > Impostazioni per report, blocco, hide, contatti e documenti legali.
10. Solo dopo deploy e test, creare una nuova build iOS con numero build successivo e inviarla alla revisione.

## Modifiche escluse

Non sono stati eseguiti deploy, migrazioni production, seed, build EAS o modifiche a credenziali Apple, bundle ID, package Android, EAS projectId, owner Expo e `google-services.json`.
