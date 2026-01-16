<?php
namespace journal;

class OperationCashflowLib extends OperationCrud {

	public static function delegateByCashflow(): OperationCashflowModel {

		return OperationCashflow::model()
			->select(OperationCashflow::getSelection())
			->delegateCollection('cashflow');

	}
	public static function delegateByOperation(): OperationCashflowModel {

		return OperationCashflow::model()
			->select(OperationCashflow::getSelection())
			->delegateCollection('operation');

	}

	public static function getOperationsByCashflows(array $ids): \Collection {

		return OperationCashflow::model()
			->select(['operation' => Operation::getSelection()])
			->whereCashflow('IN', $ids)
			->getCollection()
			->getColumnCollection('operation');

	}
}
?>
