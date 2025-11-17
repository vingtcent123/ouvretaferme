<?php
namespace account;

class LogLib extends LogCrud {

	public static function save(string $action, string $element, array $params = []): void {

		$eLog = new Log([
			'action' => $action,
			'element' => mb_strtolower($element),
			'params' => $params
		]);

		Log::model()->insert($eLog);

	}

}
?>
