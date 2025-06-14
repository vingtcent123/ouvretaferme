<?php
Setting::register('mail', [

	// Name shown as email sender
	'emailName' => \L::getVariable('siteName'),
	'emailFrom' => 'ne-pas-repondre@ouvretaferme.org',

	'devSendOnly' => [],

]);
?>
