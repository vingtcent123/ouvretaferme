<?php
namespace journal;

class OperationCashflow extends OperationCashflowElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
				'cashflow' => ['id', 'hash', 'amount', 'hash', 'invoice', 'type', 'date', 'memo', 'name', 'account' => ['id', 'account'], 'createdAt', 'createdBy' => ['id', 'firstName', 'lastName']],
				'operation' => ['id', 'accountLabel', 'description', 'type', 'amount', 'asset', 'hash'],
			];

	}
}
?>
