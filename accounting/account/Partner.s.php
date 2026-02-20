<?php
namespace account;

class PartnerSetting extends \Settings {

	const SUPER_PDP = 'super-pdp';
	const SUPER_PDP_URL = 'https://www.superpdp.tech/';
	const SUPER_PDP_OAUTH_URL = 'https://api.superpdp.tech/oauth2/';
	const SUPER_PDP_API_URL = 'https://api.superpdp.tech/v1.beta/';

	public static $PARTNERS = [self::SUPER_PDP];

	public static array $pdpCredentials = [
		'client_id' => '',
		'client_secret' => '',
	];

}
?>
