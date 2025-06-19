<?php
namespace selling;

class History extends HistoryElement {

	public static function getSelection(): array {

		return [
			'id',
			'event' => ['name', 'color'],
			'comment',
			'user' => ['firstName', 'lastName', 'visibility'],
			'date'
		];

	}

}
?>