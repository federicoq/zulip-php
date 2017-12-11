# Zulip PHP

We have been playing around with Zulip and noticed there was no a **good** PHP client... So we made another one!

## Installation

Using composer!

`composer require federicoq/zulip-php`

## Usage (italian only)

La libreria è basica, e permette di fare tutte le chiamate (note) alle rEST API di Zulip.

Ci sono 3 possibili metodi d'implementazione:

- [WebHook (o simili, chiamateli come volete.)](#webhook)
- [Client sempre connesso](#client-sempre-connesso)
- [Batch](#batch)

La prima parte, è comune a tutte e 3 le versioni, ed è la **creazione del client**.

```php
require 'vendor/autoload.php';

$client = new \ZulipPhp\Client('https://chat.mndrn.cloud/', 'bot-email', 'bot-api-key');
```

A questo punto abbiamo stabilito la connessione col server zulip, e possiamo lanciare tutti i metodi che vogliamo.

WebHook
-------

è la modalità più semplice, orientata alla scrittura "estemporanea", come traspare dal nome.. un evento esterno fà partire il messaggio.  
in questo caso il .php di riferimento è piuttosto semplice, basta creare un client, formattare il messaggio e inviarlo.

### Messaggio Privato

```php
$client->sendMessage([ 'to' => 'email-utente', 'type' => 'private', 'content' => "Messaggio!" ]);
```

### Messaggio ad uno Stream

```php
$client->sendMessage([ 'to' => 'Canale', 'subject' => 'nome-dello-stream', 'type' => 'stream', 'content' => "Messaggio!" ]);
```

Client Sempre Connesso
----------------------

Per il client sempre connesso, và prima capita un po' di logica.

- Il primo step è capire la logica che fa muovere Zulip.
- Il secondo step, è immaginare che abbiamo bisogno di uno script sempre in esecuzione, che sia in ascolto continuo, per percepire la ricezione di un nuovo messaggio.

---

1) La logica di Zulip:

Zulip ci permette di leggere i messaggi in due modi: **registrando una queue** e **richiedendo i singoli/gruppi di messaggi**.  

La **queue** è la modalità più indicata, perché permette di ricevere tutti gli eventi che sono accaduti a partire dalla sua registrazione; non scade e può quindi essere registrata una sola volta e l'informazione poi salvata in archivio. Una volta registrata una queue si può chiedere a Zulip se ci sono stati dei cambiamenti ed eventualmente decidere come trattarli.

La richiesta dei **messaggi** è meno indicata per lo scopo, quindi la tratterò nella versione `Batch`

---

2) Script sempre in esecuzione

per fare un client sempre attivo, abbiamo bisogno di inserire la logica in un `while(true)`. Easy Peasy; Lemon Squeezy! :)

scriviamo la porzione di codice per leggere tutti gli eventi di una queue

```php
$client = new \ZulipPhp\…

$queue_id = false;
$last_id = -1; // Di default, -1 ti risponde con tutti gli eventi dalla creazione della queue.
// il last_id non è il vero identificativo dell'evento, è un indice parziale a partire dalla nascita della query (-1, 0, 1, 2, 3, …)

// Registriamo la queue:
$queue_id = $zulip->registerQueue()['queue_id'];

while(true) {

	try {

		$events = $client->getEvents([ 'queue_id' => $queue_id, 'last_event_id' => $last_id, 'dont_block' => 'false']);

		if(count($events) > 0) {
			$last_id += count($events);
			print_r($events);
		} else {
			echo "No new messages. Last event id: ".$msg.".\n";
		}

		sleep(2);

	} catch (Exception $e) {
		echo "Error in fetching Events. Try again in 1 sec.\n";
		sleep(1);
	}

}
```

e, una volta progettato, questo script dovrà essere eseguito da cli (`php script.php`) e vedremo che terrà traccia di tutti gli eventi che accadono.. i messaggi, le reazioni, le sottoscrizioni, heartbeat, ecc.

a questo punto, l'eventuale logica applicativa va inserita nel blocco `if(count($events)>0) {}` dove possiamo processare evento per evento, e guardarne il `$event['type']` per discriminarne le azioni.

in qualunque contesto, ricordiamo, possiamo richiamare i vari `$client->sendMessage()` e tutti gli altri metodi che possono ritornarci utili.

Batch
-----

Questa modalità è da intendersi percorribile quando il nostro bot effettua un lavoro da "archivista" e non è richiesto che reagisca in tempo reale; in questo caso infatti chiederemo a Zulip di inviarci dei messaggi, a partire da un id (questa volta un vero id).

```php
$client = new \ZulipPhp\…

$messages = $client->getMessages([ 'narrow' => json_encode([ [ 'stream', 'NomeStream' ] ]), 'had_error_retry' => 'false', 'anchor' => 0, 'num_before' => 1, 'num_after' => 5]);
// Fare riferimento alla loro documentazione, per l'endpoint GET /messages
```

Esempi di Eventi
----------------

### Messaggio Privato
```json
{
  "type": "private",
  "reactions": [],
  "sender_realm_str": "",
  "timestamp": 1512934642,
  "id": 6516,
  "content": "$ time",
  "display_recipient": [
    {
      "id": 9,
      "full_name": "Federico Quagliotto",
      "email": "f.quagliotto@mandarinoadv.com",
      "is_mirror_dummy": false,
      "short_name": "f.quagliotto"
    },
    {
      "id": 19,
      "full_name": "Khaleesi",
      "email": "khaleesi-bot@chat.mndrn.cloud",
      "is_mirror_dummy": false,
      "short_name": "khaleesi-bot"
    }
  ],
  "sender_short_name": "f.quagliotto",
  "sender_id": 9,
  "sender_full_name": "Federico Quagliotto",
  "client": "website",
  "content_type": "text/x-markdown",
  "sender_email": "f.quagliotto@mandarinoadv.com",
  "avatar_url": "/user_avatars/2/ad4fa2008dc3b96e839ce1ec9d80ad743b5362c2.png?x=x&version=2",
  "recipient_id": 34,
  "is_me_message": false,
  "subject": "",
  "subject_links": []
}
```

### Messaggio publico
```json
{
  "sender_short_name": "f.quagliotto",
  "sender_realm_str": "",
  "timestamp": 1512982424,
  "client": "website",
  "display_recipient": "Città dei Bot",
  "sender_full_name": "Federico Quagliotto",
  "sender_id": 9,
  "reactions": [],
  "content": "@**Alessandro Marino** eheh vuoi altri messaggi?",
  "id": 6574,
  "sender_email": "f.quagliotto@mandarinoadv.com",
  "stream_id": 17,
  "type": "stream",
  "recipient_id": 43,
  "is_me_message": false,
  "subject": "hello",
  "subject_links": [],
  "content_type": "text/x-markdown"
}
```

### Reazione
```json
{
  "emoji_name": "dragon_face",
  "message_id": 6517,
  "id": 9,
  "op": "add",
  "user": {
    "user_id": 9,
    "full_name": "Federico Quagliotto",
    "email": "f.quagliotto@mandarinoadv.com"
  },
  "type": "reaction",
  "emoji_code": "1f432",
  "reaction_type": "unicode_emoji"
}
```
