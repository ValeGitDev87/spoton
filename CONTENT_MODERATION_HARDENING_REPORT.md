# SpotOn - Content Moderation Hardening Report

**Data:** 2026-09-10  
**backend-only:** SI  
**nuova build iOS richiesta:** NO  
**deploy necessario:** SI, solo backend

## File modificati

- `app/Services/Moderation/ContentModerationService.php`
- `config/content_moderation.php`
- `tests/Feature/ContentModerationApiTest.php`
- `CONTENT_MODERATION_HARDENING_REPORT.md`

Non sono stati modificati frontend, route, migrazioni, configurazioni Expo/native, credenziali o dipendenze.

## Algoritmo prima e dopo

Prima il servizio normalizzava maiuscole, accenti, alcuni caratteri leetspeak, punteggiatura e lettere ripetute, poi confrontava prevalentemente frasi esatte con `str_contains`. Varianti come `ti ammazzi` e testo separato artificialmente potevano passare.

Ora il servizio conserva quel comportamento e aggiunge:

1. pattern hard-block specifici con confini di parola per minacce e semplici varianti grammaticali;
2. pattern warn specifici per insulti chiaramente rivolti a una persona;
3. ricomposizione controllata delle sole sequenze di almeno tre lettere singole separate;
4. pattern compatti limitati a minacce ad alta confidenza;
5. distinzione verificata fra `allow`, `warn` e `block`.

La regola `AcceptableContent` rifiuta sia `warn` sia `block`. `Warn` identifica gli insulti diretti meno gravi delle minacce, ma non rappresenta un'autorizzazione alla pubblicazione.

## Nuove regole

Le minacce coperte includono varianti specifiche di:

- ammazzare e uccidere;
- spaccare, fare male, menare, picchiare e distruggere rivolti alla persona;
- `devi morire` e `muori`.

Gli insulti diretti coperti includono varianti di:

- idiota;
- cretino/cretina;
- coglione;
- stronzo/stronza;
- imbecille;
- deficiente;
- `pezzo di merda` e `vaffanculo`.

Restano attive le regole precedenti per minacce, sfruttamento sessuale, materiale pedopornografico, espressioni naziste e altri termini configurati.

## Esempi coperti

I test verificano, tra gli altri:

- `ti ammazzo`, `ti ammazzi`, `ti ammazza`;
- `t.i a.m.m.a.z.z.o`, spazi multipli, maiuscole e lettere finali ripetute;
- `ti uccido`, `ucciditi`, `uccidetevi`;
- `ti spacco`, `ti faccio male`, `ti meno`, `ti picchio`, `ti distruggo`;
- `devi morire`, `muori`;
- forme singolari/plurali e maschili/femminili degli insulti richiesti;
- sostituzioni semplici come `c0gli0ne` e `m3rd4`.

## Falsi positivi considerati

Sono presenti 13 esempi consentiti, comprendenti testi su trasporti, eventi, oggetti smarriti, libri, storia, sport e segnalazioni normali.

In particolare:

- `Il romanzo L idiota è disponibile in biblioteca` resta consentito perché non è un insulto diretto;
- `La squadra ha distrutto il precedente record sportivo` resta consentito;
- `I prodotti usati ammazzano gli insetti infestanti` resta consentito e verifica che non vengano unite parole normali adiacenti.

## Esempi volutamente non coperti

- comprensione semantica completa di citazioni, ironia o contesto;
- lingue non configurate;
- offuscamenti sofisticati o immagini contenenti testo;
- analisi automatica di audio e video;
- classificazione tramite AI o servizi esterni.

Questi casi restano gestiti tramite Termini, segnalazione, blocco e moderazione umana. L'uso di pattern molto generici o fuzzy matching esteso è stato evitato per non bloccare nomi propri e frasi innocue.

## Ambiti verificati

Il filtro resta applicato a:

- post normali;
- post Ghost;
- commenti;
- chat;
- bio e profilo;
- domande/controproposte;
- luoghi proposti dalla Community.

## Test

Test dedicati dopo l'hardening:

- `ContentModerationApiTest`: **7 test, 74 assertions, 0 failures**.
- Sono verificati gli esiti precisi `block` per le minacce e `warn` per gli insulti diretti.
- Sono verificati il rifiuto API e la copertura dei differenti flussi UGC.

Test finali richiesti:

- `php artisan test`
- `git diff --check`

Risultati finali:

- `php artisan test`: **197 test, 1192 assertions, 0 failures**;
- `git diff --check`: **nessun errore**.

## Rischi residui

Un filtro deterministico a regole non può comprendere ogni formulazione o contesto. Le liste e i pattern dovranno essere aggiornati usando casi reali confermati dai moderatori. Pattern più aggressivi aumenterebbero i falsi positivi; per questo le radici vengono usate solo dentro forme grammaticali precise e le trasformazioni compatte sono limitate alle minacce ad alta confidenza.

Nessun deploy e nessuna build sono stati eseguiti durante questo intervento.
