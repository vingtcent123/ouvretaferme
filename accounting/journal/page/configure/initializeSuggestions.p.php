<?php

// Exemple d'utilisation :
// Lancer le script php framework/lime.php -a ouvretaferme -e dev journal/configure/initializeSuggestions farm=7
new Page()
	->cli('index', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm', 'int', 7));

		\company\CompanyLib::connectSpecificDatabaseAndServer($eFarm);

		$cCashflow = \bank\CashflowLib::getAll(new Search(['isReconciliated' => FALSE]), FALSE);

		foreach($cCashflow as $eCashflow) {
			\preaccounting\SuggestionLib::calculateForCashflow($eFarm, $eCashflow);
		}


		$cOperation = \journal\Operation::model()
			->select([
				'id', 'amount', 'type', 'description', 'date', 'thirdParty' => ['id', 'name', 'normalizedName'],
				'paymentMethod' => ['id', 'fqn'],
				'cOperationLinked' => new \journal\OperationModel()
					->select('id', 'operation', 'amount', 'type')
					->delegateCollection('operation'),
			])
			->whereOperation(NULL)
			->getCollection();

		foreach($cOperation as $eOperation) {
			if($eOperation['cOperationLinked']->notEmpty()) {
				$amount = ($eOperation['type'] === \journal\Operation::CREDIT ? -1 * $eOperation['amount'] : $eOperation['amount']);
				foreach($eOperation['cOperationLinked'] as $eOperationLinked) {
					$amount += ($eOperationLinked['type'] === \journal\Operation::CREDIT ? -1 * $eOperationLinked['amount'] : $eOperationLinked['amount']);
				}
				$amount = round($amount, 2);
				$eOperation['amount'] = abs($amount);
			}
			\preaccounting\SuggestionLib::calculateForOperation($eOperation);
		}

	});
