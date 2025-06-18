<?php
namespace bank;

class Cashflow extends CashflowElement {

	public function canAllocate(): bool {

		if($this->empty()) {
			return FALSE;
		}

		return $this['status'] === Cashflow::WAITING;
	}
}
?>
