<?php
namespace account;

/**
 * En utilisant NGROK, il faut faire ces changements localement :
 * - getAccessToken et refreshToken, ajouter ces 2 lignes dans ePartner :
'updatedBy' => new \user\User(['id' => VOTRE_ID]),
'createdBy' => new \user\User(['id' => VOTRE_ID]),
 * - getRedirectUri, remplacer le domaine par l'URL ngrok
 */
Class DropboxLib {

	const AUTHORIZE_URL = 'https://www.dropbox.com/oauth2/authorize';
	const OAUTH_TOKEN_URL = 'https://api.dropbox.com/oauth2/token';
	const API_URL = 'https://api.dropboxapi.com';
	const API_CONTENT_URL = 'https://content.dropboxapi.com';

	public static function getPartnerData(\farm\Farm $eFarm): array {

		$partner = ['partner' => \account\DropboxLib::getPartner()];
		if($partner['partner']->notEmpty()) {

			$partner['quota'] = \account\DropboxLib::getQuota();

		}
		$partner['authorizationUrl'] = \account\DropboxLib::getAuthorizeUrl($eFarm);

		return $partner;
	}

	public static function getPartner(): Partner {

		$ePartner = PartnerLib::getByPartner(Partner::DROPBOX);

		if($ePartner->empty()) {
			return $ePartner;
		}

		// Access might has been revoked
		if($ePartner->isValid() and self::isTokenValid($ePartner) === FALSE) {
			return new Partner();
		}

		if($ePartner->isValid()) {
			return $ePartner;
		}

		return self::refreshToken($ePartner);

	}

	public static function isTokenValid(Partner $ePartner): bool {

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$ePartner['accessToken'],
				'Content-Type: application/json',
			],
			CURLOPT_VERBOSE => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
		];
		$params = json_encode(['query' => 'foo']);

		$curl = new \util\CurlLib();
		$curl->exec(self::API_URL.'/2/check/user', $params, 'POST', $options);

		return $curl->getLastInfos()['httpCode'] === 200;
	}

	public static function getQuota(): array {

		$ePartner = self::getPartner();

		if($ePartner->empty()) {
			return [null, null];
		}

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$ePartner['accessToken'],
				'Content-Type:', // Bizarre mais nécessaire
			],
		];

		$curl = new \util\CurlLib();

		$data = json_decode($curl->exec(self::API_URL.'/2/users/get_space_usage', json_encode([]), 'POST', $options), TRUE);

		$used = $data['used'] / 1024 / 1024 / 1024; // in Mo
		$allocated = $data['allocation']['allocated'] / 1024 / 1024 / 1024; // in Mo

		return [$used, $allocated];

	}

	/**
	 * Envoie un fichier sur Dropbox
	 * Note : pas la peine de créer le dossier en amont, Dropbox s'en occupe
	 *
	 * @param string $distantFile Le fichier et son path sur dropbox
	 * @param string $localFile Le fichier et son path en local
	 *
	 * exemple d'usage : \account\DropboxLib::uploadFile('/2025/fec.txt', '/tmp/shared/fec.txt');
	 */
	public static function uploadFile(string $distantFile, string $localFile): void {

		$ePartner = self::getPartner();

		if($ePartner->empty()) {
			return;
		}

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$ePartner['accessToken'],
				'Content-Type: application/octet-stream',
				'Dropbox-API-Arg: '.json_encode([
					'autorename' => FALSE,
					'mode' => 'overwrite',
					'mute' => FALSE,
					'path' => $distantFile,
					'strict_conflict' => FALSE,
				]),
			],
			CURLOPT_VERBOSE => TRUE,
			CURLOPT_PUT => TRUE,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_INFILE => fopen($localFile, 'rb'),
			CURLOPT_INFILESIZE => filesize($localFile),
		];

		$params = json_encode([]);

		$curl = new \util\CurlLib();

		$curl->exec(self::API_CONTENT_URL.'/2/files/upload', $params, 'POST', $options);

	}

	public static function createFolder(string $folderPath): void {

		$ePartner = self::getPartner();

		if($ePartner->empty()) {
			return;
		}

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$ePartner['accessToken'],
				'Content-Type: application/json',
			],
			CURLOPT_VERBOSE => TRUE,
			CURLOPT_RETURNTRANSFER => true,
		];
		$params = json_encode([
			'autorename' => FALSE,
			'path' => '/'.$folderPath,
		]);

		$curl = new \util\CurlLib();
		$curl->exec(self::API_URL.'/2/files/create_folder_v2', $params, 'POST', $options);

	}
	public static function listFolder(string $folderPath = ''): array {

		$ePartner = self::getPartner();

		if($ePartner->empty()) {
			return [];
		}

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$ePartner['accessToken'],
				'Content-Type: application/json',
				CURLOPT_RETURNTRANSFER => true,
			],
		];

		if(mb_strlen($folderPath) > 0 and mb_substr($folderPath, 0, 1) !== '/') {
			$folderPath = '/'.$folderPath;
		}

		$params = json_encode([
			'path' => $folderPath,
		]);
		$curl = new \util\CurlLib();

		$data = json_decode($curl->exec(self::API_URL.'/2/files/list_folder', $params, 'POST', $options), TRUE);

		return $data['entries'];

	}

	public static function refreshToken(Partner $ePartner): Partner {

		$params = [
			'grant_type' => 'refresh_token',
			'refresh_token' => $ePartner['params']['refresh_token'],
			'client_id' => \Setting::get('account\dropbox')['appKey'],
			'client_secret' => \Setting::get('account\dropbox')['appSecret'],
		];

		$curl = new \util\CurlLib();

		$data = json_decode($curl->exec(self::OAUTH_TOKEN_URL, $params, 'POST'), TRUE);

		if(($data['error'] ?? NULL) !== NULL) {

			Partner::model()
				->wherePartner(Partner::DROPBOX)
				->delete();

		} else {

			$ePartner = new Partner([
				'partner' => Partner::DROPBOX,
				'accessToken' => $data['access_token'],
				'params' => ['account_id' => $data['account_id'], 'refresh_token' => $data['refresh_token']],
				'expiresAt' => new \Sql('ADDDATE(NOW(), INTERVAL '.$data['expires_in'].' SECOND)'),
				'updatedAt' => new \Sql('NOW()'),
				'updatedBy' => \user\ConnectionLib::getOnline(),
			]);

			Partner::model()->option('add-replace')->insert($ePartner);

		}

		return $ePartner;

	}

	public static function getAccessToken(\farm\Farm $eFarm, string $authorizationCode): void {

		$params = [
			'code' => $authorizationCode,
			'grant_type' => 'authorization_code',
			'client_id' => \Setting::get('account\dropbox')['appKey'],
			'client_secret' => \Setting::get('account\dropbox')['appSecret'],
			'redirect_uri' => self::getRedirectUri($eFarm),
		];

		$curl = new \util\CurlLib();

		$data = json_decode($curl->exec(self::OAUTH_TOKEN_URL, $params, 'POST'), TRUE);

		if(($data['error'] ?? NULL) !== NULL) {
			throw new \NotExpectedAction('Dropbox : cannot retrieve access token');
		}
		$ePartner = new Partner([
			'partner' => Partner::DROPBOX,
			'accessToken' => $data['access_token'],
			'params' => ['uid' => $data['uid'], 'account_id' => $data['account_id'], 'refresh_token' => $data['refresh_token']],
			'expiresAt' => new \Sql('ADDDATE(NOW(), INTERVAL '.$data['expires_in'].' SECOND)'),
			'updatedAt' => new \Sql('NOW()'),
			'updatedBy' => \user\ConnectionLib::getOnline(),
		]);

		Partner::model()->option('add-replace')->insert($ePartner);

	}
	public static function getAuthorizeUrl(\farm\Farm $eFarm): string {

		$params = [
			'client_id' => \Setting::get('account\dropbox')['appKey'],
			'response_type' => 'code',
			'redirect_uri' => self::getRedirectUri(),
			'token_access_type' => 'offline',
			'state' => $eFarm['id'],
		];

		return self::AUTHORIZE_URL.'?'.http_build_query($params);
	}

	public static function revoke(): bool {

		$ePartner = self::getPartner();

		if($ePartner->empty()) {
			return FALSE;
		}

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$ePartner['accessToken'],
				'Content-Type: ',
			],
			CURLOPT_VERBOSE => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
		];
		$params = json_encode([]);

		$curl = new \util\CurlLib();

		$curl->exec(self::API_URL.'/2/auth/token/revoke', $params, 'POST', $options);

		return $curl->getLastInfos()['httpCode'] === 200;
	}

	private static function getRedirectUri(): string {

		return \Lime::getUrl().'/public:dropbox';

	}
}
