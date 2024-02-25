<?php
Feature::register('user', [

	// Enable/disable signup
	'signUp' => TRUE,

	// Enable/disable ban
	'ban' => FALSE,

]);

Privilege::register('user', [
	'admin' => FALSE,
	'ban' => FALSE,
	'privilege' => FALSE
]);

Setting::register('user', [

	'passwordSizeMin' => 8,
	'nameSizeMax' => 50,
	'signUpRoles' => [],

	'statsRoles' => ['farmer', 'customer'],

	// Number of days for saving logs
	'keepLogs' => 90,

	// Number of minutes after login to close its account
	'closeTimeLimit' => 3,

	// Number of days to cancel account closing
	'closeTimeout' => 10,

	// Authorized authentication
	'auth' => ['basic'],

	// Maximum allowed people on the same IP to allow banishment by IP
	'maxBanOnSameIp' => 1000,

	// Maximum ban displayed per page on ban admin page
	'maxByPage' => 50,

	'logSplit' => 1,

]);
?>
