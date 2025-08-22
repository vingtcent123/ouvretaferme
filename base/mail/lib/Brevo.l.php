<?php
namespace mail;

/**
 * Brevo management
 */
class BrevoLib {

	const API_URL = 'https://api.brevo.com/v3/smtp/email';

	public static function send(Email $eEmail): ?array {

		if(OTF_DEMO) {
			throw new \Exception('Mail sending disabled');
		}

		$object = [
			'subject' => $eEmail['subject'],
			'htmlContent' => $eEmail['html'] ?? nl2br($eEmail['text']),
			'textContent' => $eEmail['text'],
			'sender' => [
				'name' => $eEmail['fromName'],
				'email' => $eEmail['fromEmail']
			],
			'tags' => [
				LIME_ENV,
				(string)$eEmail['id']
			],
			'to' => [
				[
					'email' => $eEmail['to'],
				]
			]
		];

		if($eEmail['bcc'] !== NULL) {

			$object['bcc'] = [
				[
					'email' => $eEmail['bcc'],
				]
			];

		}

		if($eEmail['replyTo'] !== NULL) {

			$object['replyTo'] = [
				'email' => $eEmail['replyTo'],
			];

		}

		$attachments = unserialize($eEmail['attachments']);

		if($attachments) {

			$object['attachment'] = [];

			foreach($attachments as $attachment) {
				$object['attachment'][] = [
					'content' => base64_encode($attachment['content']),
					'name' => $attachment['name']
				];
			}

		}

		$data = json_encode($object);

		$header = [
			'api-key: '.\Setting::get('mail\brevoApiKey'),
			'accept: application/json',
			'Content-Type: application/json',
			'Content-Length: '.strlen($data)
		];

		$options = [
			CURLOPT_HTTPHEADER => $header
		];

		$curl = new \util\CurlLib();

		$data = $curl->exec(self::API_URL, $data, 'POST', $options);
		$httpCode = $curl->getLastInfos()['httpCode'];

		if($httpCode === 201 or $httpCode === 202) {
			return json_decode($data, TRUE);
		} else {
			throw new \Exception('Brevo error (HTTP code is '.$httpCode.')');
		}

	}

	public static function webhook(array $payload): void {

		if($payload['tags'] ?? []) {

			[$mode, $id] = $payload['tags'];

			if($mode === 'prod') {

				$eEmail = Email::model()
					->select(Email::getSelection())
					->whereId($id)
					->get();

				if($eEmail->notEmpty()) {
					self::updateEmail($eEmail, $payload);
				}

			}

		}

	}

	public static function updateEmail(Email $eEmail, array $payload): void {

		$eEmail->expects(['to']);

		if($eEmail['to'] !== $payload['email']) {
			return;
		}

		$properties = ['status'];

		switch($payload['event']) {

			case 'delivered' :
				$eEmail['status'] = Email::DELIVERED;
				Email::model()->whereStatus(Email::SENT);
				break;

			case 'unique_opened' :
			case 'opened' :
				$eEmail['status'] = Email::OPENED;
				$eEmail['openedAt'] = new \Sql('NOW()');
				$properties[] = 'openedAt';
				break;

			case 'spam' :
				$eEmail['status'] = Email::ERROR_SPAM;
				break;

			case 'soft_bounce' :
			case 'hard_bounce' :
				$eEmail['status'] = Email::ERROR_BOUNCE;
				break;

			case 'invalid_email' :
				$eEmail['status'] = Email::ERROR_INVALID;
				break;

			case 'blocked' :
			case 'error' :
			case 'unsubscribed' :
				$eEmail['status'] = Email::ERROR_BLOCKED;
				break;

			default :
				return;

		}

		$affected = Email::model()
			->select($properties)
			->whereStatus('!=', $eEmail['status']) // Le statut doit avoir changé
			->update($eEmail);

		if($affected > 0) {
			ContactLib::updateEmailStatus($eEmail);
		}

	}

	public static function getPayload(): array {

		$payload = @file_get_contents('php://input');

		if(self::checkIp() === FALSE) {
			throw new \Exception('Invalid Brevo IP');
		}

		return json_decode($payload, TRUE);

	}

	private static function checkIp(): bool {

		$ip = ip2long(getIp());

		return (
			$ip >= ip2long('1.179.112.0') and $ip <= ip2long('1.179.127.255') or
			$ip >= ip2long('172.246.240.0') and $ip <= ip2long('172.246.255.255')
		);

	}

}
