<?php
/**
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate module=OperationCashflow
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-16012026
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate module=OperationCashflow
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-16012026
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		$account706ID = 471;

		foreach($cFarm as $eFarm) {

			d($eFarm['id']);

			\farm\FarmLib::connectDatabase($eFarm);

			// 1. Corriger l'id de la classe 706

			$eAccount = \account\AccountLib::getByClass('706');

			\account\Account::model()
				->whereId($eAccount['id'])
				->update(['id' => $account706ID]);

			\journal\Operation::model()
				->whereAccount($eAccount)
				->update(['account' => new \account\Account(['id' => $account706ID])]);

			// 2. Corriger OperationCashflow data

			$cOperationCashflow = \journal\OperationCashflow::model()
				->select(['operation' => ['id', 'hash'], 'cashflow' => ['id', 'memo']])
				->getCollection();

			foreach($cOperationCashflow as $eOperationCashflow) {
				if(
					isset($eOperationCashflow['operation']['hash']) === FALSE or
					isset($eOperationCashflow['cashflow']['memo']) === FALSE
				) {
					\journal\OperationCashflow::model()
						->whereOperation($eOperationCashflow['operation'])
						->whereCashflow($eOperationCashflow['cashflow'])
						->delete();
					echo 'x';
				} else {
					\journal\OperationCashflow::model()
						->whereOperation($eOperationCashflow['operation'])
						->whereCashflow($eOperationCashflow['cashflow'])
						->update(['hash' => $eOperationCashflow['operation']['hash']]);
					echo '.';
				}
			}

			// Refaire une passe sur tous les cashflow => vérifier qu'ils ont bien le couple operation, cashflow

			$cCashflow = \bank\Cashflow::model()
				->select(['id', 'hash'])
				->whereHash('!=', NULL)
				->getCollection(NULL, NULL, 'hash');

			if($cCashflow->notEmpty()) {
				$ccOperation = \journal\Operation::model()
					->select(['id', 'hash'])
					->whereHash('IN', $cCashflow->getColumn('hash'))
					->getCollection(NULL, NULL, ['hash', 'id']);

				foreach($cCashflow as $eCashflow) {

					if($ccOperation->offsetExists($eCashflow['hash']) === FALSE) {

						echo '['.$eCashflow['hash'].']';

					} else {

						foreach($ccOperation[$eCashflow['hash']] as $eOperation) {

							$eOperationCashflow = new \journal\OperationCashflow(['operation' => $eOperation, 'cashflow' => $eCashflow, 'hash' => $eOperation['hash']]);

							\journal\OperationCashflow::model()
                ->option('add-replace')
                ->insert($eOperationCashflow);

							echo '*';

						}
					}
				}
			}

		}

	});
?>
