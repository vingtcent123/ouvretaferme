<?php
namespace dev;

class DevSetting extends \Settings {

	public static bool $minify = FALSE;
	const MINIFY_DIRECTORY = LIME_DIRECTORY.'/.min';

	// Path to PHP
	const PHP = 'php';
	// Lifetime in seconds of permanent crons
	// May be 300/600/900/1200/1800/3600/7200/10800/14400/21600/28800/43200/86400
	const CRON_PERMANENT_LIFETIME = 3600;
	const CRON_SAVE_DIRECTORY = '/conf/crontab';

	// Number of errors saved by page
	const ERROR_SAVE_MAX = 10;
	const ERROR_KEEP = 30;

	// Nginx log
	const ERROR_NGINX_INTERVAL = 5; // check for nginx's logs (in minutes)
	const ERROR_NGINX_PATH = '/var/log/nginx';
	const ERROR_NGINX_FILE_PATTERN = 'error.%Y.%m.%d-%H';
	const ERROR_NGINX_ACTIVE_FILE = 'error';
	const ERROR_NGINX_REPORT_ALL = TRUE; // if set to false, only errors regarding a file will be reported
	const ERROR_NGINX_MAX_LOG_SIZE = 5; // if log file is above this size (in Mb), do not parse it to get errors
	const ERROR_NGINX_FILTER = [
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
	];

	// Compile version number for CSS / JS files
	public static bool $featureCompileCodeVersion = TRUE;
	// Compile Image version number
	public static bool $featureCompileImageVersion = TRUE;
}

?>
