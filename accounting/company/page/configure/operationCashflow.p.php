<?php
/**
 * Script qui corrige le lien entre les opérations bancaires et les écritures (on passe de 1:n à n:n)
 * php framework/lime.php -a ouvretaferme -e prod company/configure/operationCashflow
 */
new Page()
	->cli('index', function($data) {

		$farm = GET('farm', 'int');
		if($farm === 0) {
			dd('il faut la ferme en param.');
		}

		$eFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId($farm)
			->get();

		d('------------------ Ferme #'.$eFarm['id'].'--------------------');

		$count = 0;

		\company\CompanyLib::connectSpecificDatabaseAndServer($eFarm);

		$cOperation = \journal\Operation::model()
			->select(['id', 'amount', 'cashflow' => new Sql('cashflow', 'bank\Cashflow')])
			->highlight()
			->where('cashflow IS NOT NULL')
			->getCollection();

		foreach($cOperation as $eOperation) {
			$eCashflow = \bank\CashflowLib::getById($eOperation['cashflow']);
			$eOperationCashflow = new \journal\OperationCashflow([
				'operation' => $eOperation,
				'cashflow' => $eCashflow,
				'amount' => min($eOperation['amount'], abs($eCashflow['amount'])),
			]);

			\journal\OperationCashflow::model()->insert($eOperationCashflow);
			$count++;

		}

		d($count.' entrées créées.');

	});
?>
