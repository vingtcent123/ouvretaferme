<?php
namespace journal;

class OperationCashflow extends OperationCashflowElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
				'cashflow' => \bank\Cashflow::getSelection(),
				'operation' => ['id', 'accountLabel', 'description', 'type', 'amount'],
			];

	}
}
?>
