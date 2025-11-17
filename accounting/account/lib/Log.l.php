<?php
namespace account;

class LogLib extends LogCrud {

	public static function get(int $page): array {

		$number = AccountSetting::LOG_PER_PAGE;
		$offset = $page * $number;

		Log::model()
			->select(Log::getSelection())
			->sort(['createdAt' => SORT_DESC])
			->option('count');

		return [Log::model()->getCollection($offset, AccountSetting::LOG_PER_PAGE), Log::model()->found()];

	}

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
