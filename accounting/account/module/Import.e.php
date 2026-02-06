<?php
namespace account;

class Import extends ImportElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'financialYear' => ['id', 'startDate', 'endDate'],
		];

	}

	public function acceptCancel(): bool {

		return in_array($this['status'], [Import::FEEDBACK_REQUESTED, Import::CREATED, Import::WAITING]);

	}

}
?>
