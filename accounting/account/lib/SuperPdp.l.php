<?php
namespace account;

Class SuperPdpLib {


	const AUTHORIZE_URL = 'https://api.superpdp.tech/oauth2/';
	const API_URL = 'https://api.superpdp.tech/v1.beta/';

	const TMP_SUPERPDP_FOLDER = '/tmp/superpdp';

	public static function sendInvoice(string $accessToken, string $filepath): ?int {
		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$accessToken,
			],
			CURLOPT_RETURNTRANSFER => TRUE,
		];

		$curl = new \util\CurlLib();

		$data = json_decode($curl->exec(self::API_URL.'invoices', file_get_contents($filepath), 'POST', $options), TRUE);

		if(isset($data['id'])) {
			return $data['id'];
		}

		if($data['message'] === 'Fichier déjà chargé') {
			return NULL;
		}

		LogLib::save('sendInvoice', 'Superpdp', ['filepath' => $filepath]);

		throw new \Exception('Unable to send invoice '.$filepath.' to SuperPDP : '.$data['message']);

	}

	public static function validateInvoice(string $filepath): bool {

		$options = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: multipart/form-data',
			],
			CURLOPT_RETURNTRANSFER => true,
		];

		$params = [
			'file' => new \CURLFile($filepath)
		];
		$curl = new \util\CurlLib();

		$data = json_decode($curl->exec(self::API_URL.'validation_reports', $params, 'POST', $options), TRUE);

		if($data === NULL or isset($data['data'][0]) === FALSE) {
			throw new \Exception('Unable to validate invoice '.$filepath.' to SuperPDP');
		}

		return ($data['data'][0]['is_valid'] ?? FALSE);

	}

	public static function downloadInvoice(string $accessToken, int $invoiceId): string {

		$filepath = self::TMP_SUPERPDP_FOLDER.'/'.$invoiceId.'.tmp';
		$fp = fopen($filepath, 'w+');

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$accessToken,
				'Accept: application/json',
			],
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_FILE => $fp,
			CURLOPT_FOLLOWLOCATION => TRUE,
		];

		$curl = new \util\CurlLib();

		$curl->exec(self::API_URL.'invoices/'.$invoiceId.'/download', [], 'GET', $options);
		if(mb_strlen(file_get_contents($filepath)) === 0) {
			throw new \Exception('Unable to download invoice '.$invoiceId.' from SuperPDP.');
		}

		LogLib::save('downloadInvoice', 'Superpdp', ['invoiceId' => $invoiceId]);

		return $filepath;

	}
	public static function getInvoice(string $accessToken, int $invoiceId): array {

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$accessToken,
				'Content-Type: application/json',
			],
			CURLOPT_RETURNTRANSFER => TRUE,
		];

		$curl = new \util\CurlLib();
		$data = json_decode($curl->exec(self::API_URL.'invoices/'.$invoiceId, [], 'GET', $options), TRUE);

		if($data === NULL) {
			throw new \Exception('Unable to egt invoice '.$invoiceId.' from SuperPDP.');
		}

		LogLib::save('getInvoice', 'Superpdp', ['invoiceId' => $invoiceId]);

		return $data;

	}

	public static function getInvoices(string $accessToken): array {

		$body = [];

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$accessToken,
				'Content-Type: application/json',
			],
			CURLOPT_RETURNTRANSFER => TRUE,
		];

		$curl = new \util\CurlLib();
		$data = json_decode($curl->exec(self::API_URL.'invoices', http_build_query($body), 'GET', $options), TRUE);

		LogLib::save('getInvoices', 'Superpdp');

		return $data;

	}

	public static function getValidToken(): ?string {

		return Partner::model()
			->wherePartner(PartnerSetting::SUPER_PDP)
			->where(new \Sql('expiresAt > NOW()'))
			->getValue('accessToken');

	}


	public static function refreshAccessToken(array $clientIdentifiers): string {

		$body = [
			'grant_type' => 'client_credentials',
			'client_id' => $clientIdentifiers['id'],
			'client_secret' => $clientIdentifiers['secret'],
		];

		$options = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/x-www-form-urlencoded',
			],
			CURLOPT_RETURNTRANSFER => TRUE,
		];

		$curl = new \util\CurlLib();
		$data = json_decode($curl->exec(self::AUTHORIZE_URL.'token', http_build_query($body), 'POST', $options), TRUE);

		if(($data['error'] ?? NULL) !== NULL) {
			throw new \NotExpectedAction('SuperPdp : cannot retrieve access token');
		}

		$ePartner = new Partner([
			'partner' => PartnerSetting::SUPER_PDP,
			'accessToken' => $data['access_token'],
			'params' => [],
			'expiresAt' => new \Sql('ADDDATE(NOW(), INTERVAL '.$data['expires_in'].' SECOND)'),
			'updatedAt' => new \Sql('NOW()'),
			'updatedBy' => \user\ConnectionLib::getOnline(),
		]);

		if(LIME_ENV === 'dev') {
			$ePartner['updatedBy'] = new \user\User(['id' => 21]);
			$ePartner['createdBy'] = new \user\User(['id' => 21]);
		}

		Partner::model()->option('add-replace')->insert($ePartner);

		LogLib::save('getAccessToken', 'Superpdp');

		return $data['access_token'];

	}

}
