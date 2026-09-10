# SpotOn - Audit Apple Guideline 1.2

**Data:** 2026-09-10

| Requisito Apple | Stato iniziale | Intervento |
| --- | --- | --- |
| Age rating 18+ | Parziale | Dichiarazione nei Termini e consenso in app; impostazione App Store Connect manuale |
| EULA/Termini obbligatori | Mancante | Pagina pubblica, consenso registrazione/legacy e persistenza versione |
| Tolleranza zero | Parziale | Regole esplicite nei Termini |
| Filtro contenuti | Mancante | Filtro deterministico server-side su UGC |
| Segnalazione post/utenti | Presente | Verificata, nessuna riscrittura |
| Blocco utenti | Presente | Verificato anche per Ghost, feed, chat e notifiche |
| Nascondi post personale | Mancante | Persistenza, API e rimozione immediata dal feed |
| Moderazione entro 24 ore | Parziale | Coda oldest-first e indicatori SLA nel pannello admin |
| Contatti dentro l'app | Mancante | Supporto, abuso, Privacy, Child Safety e Termini nelle impostazioni |

## Evidenze già presenti

- Cancellazione account disponibile in app e sul web.
- I report conservano stato, autore, revisore, timestamp e nota di risoluzione.
- L'admin può rimuovere un post o sospendere un utente revocandone i token.
- Il blocco impedisce interazioni e notifiche tra i due utenti senza rivelare l'identità Ghost.
- Non risultano acquisti in-app o moderazione AI esterna.

## Vincoli

Nessun deploy, build EAS, seed di produzione, modifica credenziali Apple o configurazione identificativa dell'app fa parte di questo intervento.
