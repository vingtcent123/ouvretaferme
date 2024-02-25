<?php

/**
 * Import dev errors for Android devices
 */
(new Page())
	->post('importAndroid', function($data) {

		$appVersion = POST('APP_VERSION_NAME', 'string', '?');
		$appCode = POST('APP_VERSION_CODE', '?int');
		$stackTrace = POST('STACK_TRACE');
		$customData = POST('CUSTOM_DATA');

		$match = NULL;

		if(preg_match('/user\s*\=\s*([0-9]+)/', $customData, $match) > 0) {

			$eUser = new \user\User([
				'id' => (int)$match[1]
			]);

			\dev\ErrorPhpLib::overrideUser($eUser);

		}

		$match = NULL;

		if(post_exists('code')) {
			$codeText = POST('code');
		} else if(preg_match('/code\s*\=\s*([a-zA-Z]+)/', $customData, $match) > 0) {
			$codeText = $match[1];
		} else {
			$codeText = NULL;
		}

		$errorMessage = $stackTrace ? strstr($stackTrace, "\n", TRUE) : 'No stack trace provided';

		$message = '[Android app '.$appVersion.'] '.$errorMessage;

		switch($codeText) {

			case 'Fatal' :
				$codePhp = E_ERROR;
				break;

			case 'Warning' :
				$codePhp = E_WARNING;
				break;

			case 'Notice' :
				$codePhp = E_NOTICE;
				break;

			default :
				$codePhp = (POST('IS_SILENT') === 'true') ? E_WARNING : E_ERROR;
				break;

		}

		$deprecated = (
			$appCode !== NULL and
			$appCode < \Setting::get('main\androidRequiredVersion')
		); // N'a rien à faire là

		\dev\ErrorPhpLib::handleError(\dev\Error::ANDROID, $codePhp, $message, NULL, NULL, [], $deprecated);

	});
?>
