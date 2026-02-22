<?php
namespace pdp;

class PdpSetting extends \Settings {

	const SUPER_PDP_OAUTH_URL = 'https://api.superpdp.tech/oauth2/';
	const SUPER_PDP_API_URL = 'https://api.superpdp.tech/v1.beta/';

	public static array $credentials = [
		'client_id' => '',
		'client_secret' => '',
	];

}
?>
