<?php
namespace mail;

/**
 * Brevo management
 */
class BrevoLib {

	public static function webhook(array $payload): void {

		file_put_contents('/tmp/webhook'.time(), var_export($payload, true));

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
