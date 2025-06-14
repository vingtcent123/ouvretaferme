<?php
namespace mail;


/**
 * Stripe management
 */
class BrevoLib {

	public static function webhook(array $payload): void {

		file_put_contents('/tmp/webhook'.time(), var_export($payload, true));

	}

	public static function getPayload(): array {

		$payload = @file_get_contents('php://input');

		self::checkSignature();

		return json_decode($payload, TRUE);

	}

	private static function checkSignature() {

		$apiKey = \Setting::get('mail\brevoApiKey');
		$apiKeyReceived = \SERVER('HTTP_API_KEY');

		if($apiKey !== $apiKeyReceived) {
			throw new \Exception('Brevo webhook: API key does not match');
		}

	}

}
