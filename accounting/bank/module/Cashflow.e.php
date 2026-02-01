<?php
namespace bank;

class Cashflow extends CashflowElement {

	public static function getSelection(): array {

		return Cashflow::model()->getProperties() + [
			'account' => BankAccount::getSelection(),
			'import' => ['account' => BankAccount::getSelection()],
			'createdBy' => ['id', 'firstName', 'lastName'],
			'cOperationCashflow' => \journal\OperationCashflowLib::delegateByCashflow(),
		];

	}

	public function acceptDeallocate(): bool {

		return $this['status'] === Cashflow::ALLOCATED and $this['hash'] !== NULL and
			($this['cOperationCashflow']->empty() or $this['cOperationCashflow']->getColumnCollection('asset')->empty());

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

		return $this['status'] === Cashflow::WAITING or $this['status'] === Cashflow::ALLOCATED;
	}

	public function accept(): bool {

		return $this->acceptAllocate();

	}

	public function acceptDelete(): bool {
		return $this['status'] === Cashflow::WAITING and $this['invoice']->empty() and $this['isReconciliated'] === FALSE;
	}

	public function getMemo(): string {

		if(mb_strlen($this['memo']) <= 1) {
			return $this['name'];
		}
		return $this['memo'];

	}
}
?>
