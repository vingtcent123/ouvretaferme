<?php
namespace account;

Class SuperPdpLib {

	const TMP_SUPERPDP_FOLDER = '/tmp/superpdp';

	public static function sendInvoice(string $accessToken, string $filepath): ?int {
		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$accessToken,
			],
			CURLOPT_RETURNTRANSFER => TRUE,
		];

		$curl = new \util\CurlLib();

		$data = json_decode($curl->exec(PartnerSetting::SUPER_PDP_API_URL.'invoices', file_get_contents($filepath), 'POST', $options), TRUE);

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

		$data = json_decode($curl->exec(PartnerSetting::SUPER_PDP_API_URL.'validation_reports', $params, 'POST', $options), TRUE);

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

		$curl->exec(PartnerSetting::SUPER_PDP_API_URL.'invoices/'.$invoiceId.'/download', [], 'GET', $options);
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
		$data = json_decode($curl->exec(PartnerSetting::SUPER_PDP_API_URL.'invoices/'.$invoiceId, [], 'GET', $options), TRUE);

		if($data === NULL) {
			throw new \Exception('Unable to get invoice '.$invoiceId.' from SuperPDP.');
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
		$data = json_decode($curl->exec(PartnerSetting::SUPER_PDP_API_URL.'invoices', http_build_query($body), 'GET', $options), TRUE);

		LogLib::save('getInvoices', 'Superpdp');

		return $data;

	}

	/*** AUTH METHODS ***/

	public static function getAuthorizeUrl(\farm\Farm $eFarm): string {

		$clientId = PartnerSetting::$pdpCredentials['client_id'];
		$state = self::getState($eFarm);
		$redirectionUri = self::getRedirectionUri();

		return PartnerSetting::SUPER_PDP_OAUTH_URL.'authorize?grant_type=authorization_code&client_id='.$clientId.'&state='.$state.'&scopes=&response_type=code&redirect_uri='.urlencode($redirectionUri);
	}

	public static function getState(\farm\Farm $eFarm): string {

		return \main\CryptLib::encrypt($eFarm['id'], 'superpdp');

	}

	public static function getFarm(string $state): \farm\Farm {

		$farmId = (int)\main\CryptLib::decrypt($state, 'superpdp');
		return \farm\FarmLib::getById($farmId);

	}

	public static function retrieveToken(string $code, string $state): \farm\Farm {

		$eFarm = self::getFarm($state);
		if($eFarm->empty()) {
			throw new \NotExistsAction();
		}

		\farm\FarmLib::connectDatabase($eFarm);

		$url = PartnerSetting::SUPER_PDP_OAUTH_URL.'/token';
		$redirectionUri = self::getRedirectionUri();
		$clientId = PartnerSetting::$pdpCredentials['client_id'];
		$clientSecret = PartnerSetting::$pdpCredentials['client_secret'];

		$body = [
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $redirectionUri,
			'client_id' => $clientId,
			'client_secret' => $clientSecret,
		];

		$options = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/x-www-form-urlencoded',
			],
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_VERBOSE => TRUE,
		];

		$curl = new \util\CurlLib();
		$data = json_decode($curl->exec($url, http_build_query($body), 'POST', $options), TRUE);

		if(($data['error'] ?? NULL) !== NULL) {
			throw new \NotExpectedAction('SuperPdp, cannot retrieve access token, error : '.$data['error'].', error description : '.$data['error_description']);
		}

		$ePartner = new Partner([
			'partner' => PartnerSetting::SUPER_PDP,
			'accessToken' => $data['access_token'],
			'refreshToken' => $data['refresh_token'],
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

		return $eFarm;

	}

	public static function getCompany(): array {

		$accessToken = self::getValidToken();

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$accessToken,
				'Content-Type: application/json',
			],
			CURLOPT_RETURNTRANSFER => TRUE,
		];
		$curl = new \util\CurlLib();
		$data = json_decode($curl->exec(PartnerSetting::SUPER_PDP_API_URL.'companies/me', [], 'GET', $options), TRUE);

		if($data === NULL) {
			return [];
		}

		return $data;

	}

	public static function getValidToken(): ?string {

		$accessToken = Partner::model()
			->wherePartner(PartnerSetting::SUPER_PDP)
			->where(new \Sql('expiresAt > NOW()'))
			->getValue('accessToken');

		if($accessToken === NULL) {

			$accessToken = \account\SuperPdpLib::refreshAccessToken();

		}

		return $accessToken;

	}

	private static function getRedirectionUri(): string {

		if(LIME_ENV === 'dev') {
			return 'http://www.dev-ouvretaferme.localhost/public:superpdp';
		}

		return \Lime::getUrl().'/public:superpdp';

	}

	private static function refreshAccessToken(): ?string {

		$ePartner = PartnerLib::getByPartner(PartnerSetting::SUPER_PDP);
		if($ePartner->empty()) {
			return NULL;
		}

		$redirectionUri = self::getRedirectionUri();

		$body = [
			'grant_type' => 'refresh_token',
			'refresh_token' => $ePartner['refreshToken'],
			'redirect_uri' => $redirectionUri,
			'client_id' => PartnerSetting::$pdpCredentials['client_id'],
			'client_secret' => PartnerSetting::$pdpCredentials['client_secret'],
		];

		$options = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/x-www-form-urlencoded',
			],
			CURLOPT_RETURNTRANSFER => TRUE,
		];

		$curl = new \util\CurlLib();
		$data = json_decode($curl->exec(PartnerSetting::SUPER_PDP_OAUTH_URL.'token', http_build_query($body), 'POST', $options), TRUE);

		if(($data['error'] ?? NULL) !== NULL) {
			return null;
		}

		$ePartner = new Partner([
			'partner' => PartnerSetting::SUPER_PDP,
			'accessToken' => $data['access_token'],
			'refreshToken' => $data['refresh_token'],
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
