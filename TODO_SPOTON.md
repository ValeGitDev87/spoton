# SpotOn - TODO E Stato Progetto

Ultimo aggiornamento: 24 luglio 2026.

Questo file e' il riferimento operativo da aggiornare dopo ogni blocco, test locale e verifica VPS.

## Decisioni Confermate

- Backend Laravel nella root della repository `spoton`.
- API pubbliche dell'app: `https://www.spotonapp.cloud`.
- Home senza mappa: feed entro 200 km, 30 post per pagina.
- Icone luoghi salvate come codici Ionicons.
- Login MVP tramite email; Google rimandato.
- Push tramite Expo Push Service.
- Logica push implementata prima; credenziali e test reali lasciati all'ultimo step.
- Android: development/release build con FCM V1 configurato successivamente.
- iOS: EAS build e TestFlight con APNs gestito tramite EAS.
- Test VPS guidati da un documento da consegnare a ChatGPT.

## Gia' Completato

- [x] Autenticazione email, verifica email e recupero password.
- [x] CRUD admin luoghi, utenti, post e backup.
- [x] Feed geolocalizzato entro 200 km con paginazione da 30.
- [x] Icone Ionicons per luoghi con anteprima admin.
- [x] Post, Ghost, musica e nota audio massimo 10 secondi.
- [x] Storie a 24 ore.
- [x] Like, commenti, preferiti e `Io c'ero`.
- [x] Challenge classica, invertita e controproposta.
- [x] Chat REST.
- [x] Foto profilo e karma.
- [x] Presence e chiusura sessioni scadute.
- [x] Push token e logica push per commenti/challenge.
- [x] Cinque utenti demo `@test.it` tramite `DemoUsersSeeder`.
- [x] Configurazione Expo SDK 54 e identificatore `it.spotonapp.app`.

## Backend - Blocco 1

Stato: completato localmente; attende commit/push e verifica VPS insieme al Blocco 2.

- [x] Endpoint `PATCH /api/me` per nome, bio, colore e avatar.
- [x] Migration e modello segnalazioni post/utenti.
- [x] Endpoint autenticato `POST /api/reports`.
- [x] Prevenzione segnalazioni duplicate ancora in attesa.
- [x] Coda moderazione nella dashboard admin.
- [x] Azione admin per rimuovere un post segnalato.
- [x] Azione admin per sospendere/riattivare un utente.
- [x] Login negato agli utenti sospesi e token revocati alla sospensione.
- [x] Rate limiting su post, commenti, messaggi, challenge e report.
- [x] Test automatici Blocco 1: 88 test, 425 assertion.
- [x] Aggiornamento documentazione API/flow.

## Backend - Blocco 2

Stato: completato localmente; attende commit/push e verifica VPS.

- [x] Endpoint `DELETE /api/me` con password e conferma `DELETE`.
- [x] Revoca immediata di tutti i token Sanctum e push.
- [x] Cancellazione sicura di dati collegati, foto locali e note audio.
- [x] Retention automatica delle coordinate e sessioni di presenza.
- [x] Push accodata al destinatario per nuovi messaggi chat.
- [x] Pagine pubbliche `/privacy` e `/delete-account`.
- [x] Test automatici Blocco 2: suite completa 95 test, 462 assertion.
- [x] Aggiornamento documentazione API/flow.

## Verifica VPS Backend

Stato: da fare dopo entrambi i blocchi backend.

- [ ] Commit e push eseguiti dall'utente.
- [x] Documento handoff ChatGPT finale creato in `Downloads`.
- [ ] Pull `main` sulla VPS.
- [ ] Composer install e build Vite.
- [ ] Migration in staging.
- [ ] `DemoUsersSeeder` eseguito.
- [ ] Queue e scheduler verificati.
- [ ] API provate sul dominio reale.
- [ ] Dashboard e moderazione provate sul dominio reale.

## Frontend Expo

Stato: implementazione completata localmente. Restano collaudo manuale su development build e rifiniture emerse dai test reali.

- [x] Struttura frontend separata in navigazione, schermate, componenti e stato feed.
- [x] Navigazione tab nativa con safe area universale.
- [x] Gestione tastiera iOS/Android con resize, keyboard avoiding e contenuti scorrevoli.
- [x] Login, registrazione, recupero password e sessione SecureStore.
- [x] Rimozione del falso accesso Google dal flusso MVP.
- [x] Home geolocalizzata, paginazione da 30 e refresh.
- [x] Barra storie fedele al prototipo con icone luogo, anelli e badge.
- [x] Viewer storie fullscreen con progressi, timer e navigazione touch.
- [x] Ricerca API con debounce e tastiera gestita.
- [x] Interazioni del viewer storie collegate a `Io c'ero` e audio.
- [x] Creazione post completa con Ghost, challenge, data, musica e nota audio da massimo 10 secondi.
- [x] Registrazione, anteprima, cancellazione e riproduzione delle note audio.
- [x] Like, commenti, preferiti, condivisione e `Io c'ero` interattivi.
- [x] Verifica challenge classica collegata a `Io c'ero`.
- [x] Aggiornamento immediato del feed dopo pubblicazione e interazioni.
- [x] Persistenza idempotente backend di `Io c'ero` dopo risposta corretta.
- [x] Challenge invertita verso autore del post o autore di un commento.
- [x] Contatto diretto oppure challenge con domanda e risposta segreta.
- [x] Risposta alle challenge e controproposte con accettazione o rifiuto.
- [x] Badge richieste pendenti nella tab Messaggi.
- [x] Chat reale con elenco conversazioni, ultimi messaggi e invio.
- [x] Thread con polling leggero, caricamento messaggi precedenti e protezione doppio invio.
- [x] Composer chat e modali challenge protetti da tastiera, safe area e scroll iOS/Android.
- [x] Profilo modificabile con nome, bio, colore avatar e verifica email.
- [x] Upload massimo 10 foto, scelta avatar ed eliminazione sicura delle foto.
- [x] Cambio password, logout ed eliminazione definitiva account.
- [x] Cache offline leggera per sessione, feed, luoghi, conversazioni e richieste.
- [x] Banner offline, retry, timeout API e messaggi di errore uniformi.
- [x] Pulizia cache al logout, account eliminato o sessione rifiutata.
- [x] Development client installato per provare moduli nativi e push senza Expo Go.
- [x] Consenso notifiche richiesto solo dalle impostazioni, senza popup automatico.
- [x] Expo Push Token registrato per account e dispositivo sulle API server.
- [x] Token push revocato alla disattivazione e prima del logout.
- [x] Tap push instradato a Home, richieste/challenge o thread chat.
- [ ] Test completi su development build.

## Notifiche - Ultimo Step

- [x] Pacchetto e plugin client `expo-notifications` presenti.
- [x] File Android `google-services.json` collegato in `app.json`.
- [x] Package/bundle `it.spotonapp.app` ed EAS projectId preservati.
- [x] Registrazione Expo Push Token e apertura destinazione interna.
- [x] Gestione foreground, avvio da app chiusa, badge e canale Android.
- [x] Interruttore notifiche nel profilo con stato autorizzazione.
- [x] Rimozione token push al logout e disattivazione manuale.
- [x] Configurazione Firebase Android presente nel frontend.
- [ ] Configurazione credenziali FCM V1 in EAS.
- [ ] Configurazione APNs tramite EAS/Apple Developer.
- [ ] Development build Android e test push reale.
- [ ] Build iOS TestFlight e test push reale.
- [ ] Verifica ticket/receipt e token `DeviceNotRegistered`.

## Ultima Verifica Locale Nota

- Laravel: 99 test, 482 assertion.
- Composer: configurazione valida.
- Pint sui file modificati: OK.
- Pint globale: restano 3 rilievi storici in file non modificati dal Blocco 2.
- TypeScript: `npx tsc --noEmit` OK.
- Expo Doctor SDK 54: 18/18 controlli OK.
- Expo export iOS, Android e web dopo lo step finale: OK.
- Configurazione protetta verificata: package/bundle, EAS projectId, Firebase e plugin notifiche invariati.
- `expo-dev-client` installato; nessuna build cloud avviata e nessuna credenziale EAS modificata.
- `npm audit` segnala dipendenze transitive Expo/PostCSS; il fix automatico richiede l'upgrade breaking a SDK 57 e va valutato in un blocco separato.
- API server reale: login demo e lista luoghi OK.
- Storie attive presenti sul server durante l'ultima prova: 0; serve un post recente per la prova visiva.

## Prova Frontend Step 3 E 4

- [ ] Avviare Expo con cache pulita e aprire una development build o Expo Go compatibile.
- [ ] Creare un post normale scegliendo luogo, testo e data.
- [ ] Verificare che la tastiera non copra campi, pulsanti e selettore data su iOS e Android.
- [ ] Registrare una nota audio, fermarla manualmente, ascoltarla e registrarla di nuovo.
- [ ] Lasciare la registrazione attiva e verificare lo stop automatico a 10 secondi.
- [ ] Pubblicare e verificare che il nuovo post compaia subito in Home e nelle storie del luogo.
- [ ] Riprodurre la nota audio sia dalla card Home sia dal viewer storie.
- [ ] Con un secondo utente provare like, preferito, commento, menzione e condivisione.
- [ ] Provare `Io c'ero` su un post senza domanda segreta.
- [ ] Provare una risposta errata e poi corretta su un post con domanda segreta.
- [ ] Ripetere la risposta corretta e verificare che contatore e karma non aumentino due volte.
- [ ] Verificare dal Profilo che il numero dei preferiti si aggiorni.

## Prova Frontend Step 5 E 6

- [ ] Pubblicare un post senza domanda segreta e, con un secondo utente, premere `Io c'ero`.
- [ ] Provare sia `Apri chat direttamente` sia `Invia una domanda`.
- [ ] Aprire i commenti e usare `Contatta` sul commento di un altro utente.
- [ ] Verificare che la tab Messaggi mostri il badge delle richieste ricevute.
- [ ] Rispondere in modo errato e poi corretto a una challenge ricevuta.
- [ ] Inviare una controproposta e provarne sia il rifiuto sia l'accettazione.
- [ ] Verificare che l'accettazione apra la conversazione corretta.
- [ ] Inviare rapidamente due volte lo stesso comando e verificare che non crei duplicati.
- [ ] Scrivere messaggi corti, multilinea e vicini al limite di 2000 caratteri.
- [ ] Con tastiera aperta verificare che input, pulsante invio e ultimo messaggio restino visibili.
- [ ] Trascinare la lista mentre la tastiera e' aperta e verificare la chiusura naturale.
- [ ] Aprire una chat lunga, caricare i messaggi precedenti e verificare che lo scroll non salti.
- [ ] Lasciare la chat aperta mentre il secondo utente invia un messaggio e attendere il polling.
- [ ] Mandare l'app in background e riaprirla verificando l'aggiornamento delle conversazioni.
- [ ] Su Android usare il tasto indietro nel thread e verificare il ritorno all'elenco.
- [ ] Su iOS provare tastiera normale, emoji e dettatura senza copertura del composer.

## Prova Frontend Step 7 E 8

- [ ] Modificare nome, bio e colore avatar verificando il salvataggio dopo il riavvio.
- [ ] Aprire i form con tastiera iOS e Android e verificare che campi e pulsanti restino raggiungibili.
- [ ] Caricare foto JPG, PNG e una foto recente scattata con iPhone.
- [ ] Verificare il rifiuto di una foto superiore a 4 MB e il limite massimo di 10 foto.
- [ ] Impostare una foto caricata come avatar e verificare Home, commenti e chat.
- [ ] Eliminare la foto avatar e verificare il ritorno all'avatar colorato.
- [ ] Cambiare password con password attuale errata, conferma diversa e infine dati corretti.
- [ ] Verificare che il cambio password non disconnetta il dispositivo corrente.
- [ ] Reinviare la verifica email da un account non ancora verificato.
- [ ] Provare il logout e verificare che feed e conversazioni memorizzati vengano cancellati.
- [ ] Accedere, caricare Home e Messaggi, chiudere l'app e disattivare completamente la rete.
- [ ] Riaprire offline e verificare sessione, feed, luoghi e lista conversazioni dalla cache.
- [ ] Verificare che il banner offline mostri l'orario dei dati e che `Riprova` aggiorni al ritorno della rete.
- [ ] Offline, tentare una modifica profilo o un upload e verificare che non venga mostrato un falso successo.
- [ ] Provare un timeout o server irraggiungibile verificando la scomparsa dello spinner entro 20 secondi.
- [ ] Con un utente non admin, provare eliminazione con password errata e conferma diversa da `DELETE`.
- [ ] Eliminare un account demo e verificare ritorno al login e assenza di dati cache del vecchio account.

## Prova Frontend Step 9 E 10

- [ ] Creare una development build Android o iOS; le push remote non si provano con Expo Go.
- [ ] Accedere come `luca@test.it`, aprire Profilo > Impostazioni e attivare le notifiche.
- [ ] Verificare sul server un solo token attivo per account/dispositivo senza stampare il token completo.
- [ ] Chiudere e riaprire l'app verificando che lo stato notifiche resti attivo.
- [ ] Inviare una push test e provarla con app in primo piano, background e chiusa.
- [ ] Toccare una push commento/menzione e verificare l'apertura di Home.
- [ ] Toccare una push challenge/controproposta e verificare l'apertura di Messaggi.
- [ ] Toccare una push `new_message` e verificare l'apertura del thread corretto.
- [ ] Disattivare le notifiche dal profilo e verificare la revoca server.
- [ ] Riattivarle e verificare che il token torni attivo senza duplicati.
- [ ] Eseguire logout, accedere con un secondo utente sullo stesso dispositivo e verificare che le push arrivino solo al nuovo account.
- [ ] Negare il permesso di sistema, riprovare dall'app e verificare l'apertura delle impostazioni del dispositivo.
- [ ] Verificare Android con canale `default`, suono, badge e tap.
- [ ] Verificare iOS con APNs configurato da EAS, suono, badge e tap.
- [ ] Provare tutte le schermate principali con tastiera normale, emoji e dettatura su iOS/Android.
- [ ] Provare scroll, safe area e pulsanti su almeno un telefono piccolo e uno grande.
