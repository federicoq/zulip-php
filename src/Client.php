<?php

namespace ZulipPhp;

class Client {

	protected $realm = '';
	protected $username = '';
	protected $password = '';

	private $client;

	public function __construct($realm, $username, $password) {

		$this->username = $username;
		$this->password = $password;
		$this->realm = rtrim($realm, '/') . '/api/v1/';

		$this->getClient();

	}

	private function getClient() {

		if(!isset($this->client)) {
			$this->client = new \GuzzleHttp\Client([
				'base_uri' => $this->realm,
				'headers' => [
					'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
				]
			]);
		}

		return $this->client;

	}

	## Emojis
	public function getEmojis() {

	}

	## Events
	public function getEvents($options) {
		/*
		{
			queue_id: 'the queue id',
			last_event_id: -1,
			dont_block: false
		};
		*/
		$response = $this->client->request('GET', 'events', [
			'query' => $options
		]);

		if((int) $response->getStatusCode() == 200)
			return json_decode( (string) $response->getBody(), true )['events'];

		return false;
	}

	############
	## Reactions
	public function sendReaction($message_id, $emoji) {

		$response = $this->client->request('PUT', 'messages/' . $message_id . '/emoji_reactions/' . $emoji);

	}

	###########
	## Messages

	public function _getMessagesStream($stream_name, $topic = false, $options) {

		$options['narrow'] = [
			[ 'stream', $stream_name ]
		];

		if($topic)
			$options['narrow'][] = ['topic', $topic];

		$options['narrow'] = json_encode($options['narrow']);

		return $this->getMessages($options);

	}

	public function getMessages($options) {

		$options['apply_markdown'] = 'false';

		$response = $this->client->request('GET', 'messages', [
			'query' => $options
		]);

		if((int) $response->getStatusCode() == 200)
			return json_decode( (string) $response->getBody(), true )['messages'];

		return false;

	}

	public function sendMessage($options) {

		$response = $this->client->request('POST', 'messages', [
			'form_params' => $options
		]);

		if((int) $response->getStatusCode() == 200)
			return json_decode( (string) $response->getBody(), true );

		return false;

	}

	########
	## Queue
	public function registerQueue($event_types = ['message'], $apply_markdown = 'false') {

		$response = $this->client->request('POST', 'register', [
			'form_params' => ['event_types' => $message, 'apply_markdown' => $apply_markdown]
		]);

		if((int) $response->getStatusCode() == 200)
			return json_decode( (string) $response->getBody(), true );

		return false;

	}

	##########
	## Streams
	public function getStreams() {

		$response = $this->client->request('GET', 'streams');
		if($response->getStatusCode() == 200)
			return json_decode( (string) $response->getBody(), true )['streams'];

		return false;

	}

	public function getSubscriptions() {

		$response = $this->client->request('GET', 'users/me/subscriptions');
		if((int)$response->getStatusCode() == 200)
			return json_decode((string) $response->getBody(), true)['subscriptions'];

		return false;

	}

	#########
	## Typing
	public function setTyping($to) {

		$response = $this->client->request('POST', 'typing', [
			'form_params' => ['to' => $to, 'op' => 'start']
		]);

		if((int) $response->getStatusCode() == 200)
			return true;

		return false;

	}

	public function unsetTyping($to) {

		$response = $this->client->request('POST', 'typing', [
			'form_params' => ['to' => $to, 'op' => 'stop']
		]);

		if((int) $response->getStatusCode() == 200)
			return true;

		return false;

	}

	########
	## Users
	public function getUsers() {

		$response = $this->client->request('GET', 'users');
		if((int)$response->getStatusCode() == 200)
			return json_decode((string) $response->getBody(), true)['members'];

		return false;

	}

	public function getPointer() {

		$response = $this->client->request('GET', 'users/me/pointer');
		if((int)$response->getStatusCode() == 200)
			return json_decode((string) $response->getBody(), true);

		return false;

	}

}
