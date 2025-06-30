<?php
namespace account;

class LogLib extends LogCrud {

	public static function save(string $action, string $element, array $params = []): void {

		$eLog = new Log([
			'action' => $action,
			'element' => $element,
			'params' => $params
		]);

		Log::model()->insert($eLog);

	}

}
?>
