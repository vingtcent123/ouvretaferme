<?php
namespace mail;

class MailSetting extends \Settings {

	public static $emailName;

	const EMAIL_FROM = 'ne-pas-repondre@ouvretaferme.org';

	public static $devSendOnly = [];
	public static $smtpServers = [];
	public static $brevoApiKey;
	const MAX_WIDTH = 700;

}

// Name shown as email sender
MailSetting::$emailName = \L::getVariable('siteName');

MailSetting::$brevoApiKey = fn() => throw new \Exception('No Brevo API key set.');

?>
