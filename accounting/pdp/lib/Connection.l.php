<?php
namespace pdp;

/**
 *
 * Lib pour gérer l'OAuth avec Super PDP
 *
 */
class ConnectionLib {

	public static function getAuthorizeUrl(\farm\Farm $eFarm): string {

		$params = [
			'grant_type' => 'authorization_code',
			'client_id' => PdpSetting::$credentials['client_id'],
			'response_type' => 'code',
			'scopes' => '',
			'state' => self::getState($eFarm),
			'redirect_uri' => self::getRedirectionUri(),
		];

		return PdpSetting::SUPER_PDP_OAUTH_URL.'authorize?'.http_build_query($params);
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

		$url = PdpSetting::SUPER_PDP_OAUTH_URL.'/token';
		$redirectionUri = self::getRedirectionUri();
		$clientId = PdpSetting::$credentials['client_id'];
		$clientSecret = PdpSetting::$credentials['client_secret'];

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
		];

		$curl = new \util\CurlLib();
		$data = json_decode($curl->exec($url, http_build_query($body), 'POST', $options), TRUE);

		if(($data['error'] ?? NULL) !== NULL) {
			throw new \NotExpectedAction('SuperPdp, cannot retrieve access token, error : '.$data['error'].', error description : '.$data['error_description']);
		}

		$ePartner = new \account\Partner([
			'partner' => \account\PartnerSetting::SUPER_PDP,
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

		\account\Partner::model()->option('add-replace')->insert($ePartner);

		\farm\Farm::model()->update($eFarm, ['hasPdp' => TRUE]);
		\company\CompanyCronLib::addConfiguration($eFarm, \company\CompanyCronLib::SUPER_PDP_INITIALIZE, \company\CompanyCron::WAITING, $ePartner['id']);

		\account\LogLib::save('getAccessToken', 'Superpdp');

		return $eFarm;

	}

	public static function getValidToken(): ?string {

		$accessToken = \account\Partner::model()
			->wherePartner(\account\PartnerSetting::SUPER_PDP)
			->where(new \Sql('expiresAt > NOW()'))
			->getValue('accessToken');

		if($accessToken === NULL) {

			$accessToken = self::refreshAccessToken();

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

		$ePartner = \account\PartnerLib::getByPartner(\account\PartnerSetting::SUPER_PDP);
		if($ePartner->empty()) {
			return NULL;
		}

		$body = [
			'grant_type' => 'refresh_token',
			'refresh_token' => $ePartner['refreshToken'],
			'client_id' => PdpSetting::$credentials['client_id'],
			'client_secret' => PdpSetting::$credentials['client_secret'],
		];

		$options = [
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/x-www-form-urlencoded',
			],
			CURLOPT_RETURNTRANSFER => TRUE,
		];

		$curl = new \util\CurlLib();
		$data = json_decode($curl->exec(PdpSetting::SUPER_PDP_OAUTH_URL.'token', http_build_query($body), 'POST', $options), TRUE);

		if(($data['error'] ?? NULL) !== NULL) {
			return null;
		}

		$ePartner = new \account\Partner([
			'partner' => \account\PartnerSetting::SUPER_PDP,
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

		\account\Partner::model()->option('add-replace')->insert($ePartner);

		\account\LogLib::save('getAccessToken', 'Superpdp');

		return $data['access_token'];

	}

}
