<?php
Setting::register('mail', [

	// Name shown as email sender
	'emailName' => \L::getVariable('siteName'),

	'smtpServers' => fn() => throw new Exception('Missing SMTP servers'),
	'devSendOnly' => [],

]);
?>
