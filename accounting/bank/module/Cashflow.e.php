<?php
namespace bank;

class Cashflow extends CashflowElement {

	public static function getSelection(): array {

		return Cashflow::model()->getProperties() + [
			'import' => ['account' => ['label']],
			'createdBy' => ['id', 'firstName', 'lastName'],
		];

	}

	public function acceptDeallocate(): bool {

		return $this['status'] === Cashflow::ALLOCATED and $this['hash'] !== NULL;

	}

	public function acceptCancelReconciliation(): bool {

		return $this['isReconciliated'] === TRUE;

	}

	public function acceptUndoDelete(): bool {

		return $this['status'] == \bank\Cashflow::DELETED;

	}

	public function acceptAllocate(): bool {

		if($this->empty()) {
			return FALSE;
		}

		return $this['status'] === Cashflow::WAITING;
	}

	public function accept(): bool {

		return $this->canAllocate();

	}
}
?>
