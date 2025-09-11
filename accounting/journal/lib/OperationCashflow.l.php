<?php
namespace journal;

class OperationCashflowLib extends OperationCrud {

	public static function delegateByCashflow(): OperationCashflowModel {

		return OperationCashflow::model()
			->select(OperationCashflow::getSelection())
			->delegateCollection('cashflow', 'id');

	}
	public static function delegateByOperation(): OperationCashflowModel {

		return OperationCashflow::model()
			->select(OperationCashflow::getSelection())
			->delegateCollection('operation', 'id');

	}

}
?>
