<?php
namespace bank;

class Cashflow extends CashflowElement {

	public static function getSelection(): array {

		return Cashflow::model()->getProperties() + [
			'import' => ['account' => ['label']],
			'createdBy' => ['id', 'firstName', 'lastName'],

		];

	}


	public function canAllocate(): bool {

		if($this->empty()) {
			return FALSE;
		}

		return $this['status'] === Cashflow::WAITING;
	}

	public function canDelete(): bool {

		return $this->canAllocate();

	}
}
?>
