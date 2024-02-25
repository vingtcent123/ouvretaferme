<?php
Feature::register('dev', [

	// Compile version number for CSS / JS files
	'compileCodeVersion' => TRUE,

	// Compile JS/CSS minify files
	'compileCodeMinify' => TRUE,

	// Compile Image version number
	'compileImageVersion' => TRUE,

]);

Privilege::register('dev', [

	// Enable or disable access to monitoring admin pages
	'admin' => FALSE,

]);

Setting::register('dev', [

	'minify' => FALSE,
	'minifyDirectory' => LIME_DIRECTORY.'/.min',

	// Url of monitoring site
	'monitoringUrl' => [
		'prod' => '',
		'preprod' => '',
		'dev' => '',
	],

	// Path to PHP
	'php' => 'php',

	// Lifetime in seconds of permanent crons
	// May be 300/600/900/1200/1800/3600/7200/10800/14400/21600/28800/43200/86400
	'cronPermanentLifetime' => 3600,

	'cronSaveDirectory' => '/conf/crontab',

	// Number of errors saved by page
	'errorSaveMax' => 10,
	'errorKeep' => 60,

	// Nginx log
	'errorNginxInterval' => 5, // check for nginx's logs (in minutes)
	'errorNginxPath' => "/var/log/nginx",
	'errorNginxFilePattern' => "error.%Y.%m.%d-%H",
	'errorNginxActiveFile' => "error",
	'errorNginxReportAll' => TRUE, // if set to false, only errors regarding a file will be reported
	'errorNginxMaxLogSize' => 5, // if log file is above this size (in Mb), do not parse it to get errors

	/**
	 * Defines strings that should not be reported from the nginx error logs
	 *
	 * (!) strings are case insensitive
	 */
	'errorNginxFilters' => [
		'word' => [
			'No such file or directory',
			'client intended to send too large body',
			'upstream timed out (110: Connection timed out) while reading response header from upstream',
			'bind() to 0.0.0.0:80 failed',
			'still could not bind()'
		],
		'regex' => [
			'/directory index of (.*) is forbidden/i'
		]
	],

]);
?>
