<?php
namespace account;

class Log extends LogElement {

	public static function getSelection(): array {

		return Log::model()->getProperties() + [
			'doneBy' => ['id', 'firstName', 'lastName'],
		];

	}

}
?>
