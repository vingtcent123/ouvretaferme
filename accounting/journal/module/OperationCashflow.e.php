<?php
namespace journal;

class OperationCashflow extends OperationCashflowElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
				'cashflow' => ['id', 'hash', 'amount', 'hash', 'invoice', 'type', 'date', 'memo'],
				'operation' => ['id', 'accountLabel', 'description', 'type', 'amount', 'asset', 'hash'],
			];

	}
}
?>
