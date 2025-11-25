<?php
namespace journal;

class Lettering extends LetteringElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
				'credit' => Operation::getSelection(),
				'debit' => Operation::getSelection(),
			];

	}
}
?>
