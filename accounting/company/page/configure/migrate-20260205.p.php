<?php
/**
 * Redresse les numéros de hash des écritures importées depuis les FEC
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260205
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260205
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		foreach($cFarm as $eFarm) {

			d($eFarm['id']);

			\farm\FarmLib::connectDatabase($eFarm);
			$vatRates = \selling\SellingSetting::getVatRates($eFarm);

			$cImport = \account\Import::model()
				->select(\account\Import::getSelection())
				->whereStatus(\bank\Import::DONE)
				->getCollection();

			foreach($cImport as $eImport) {

				$cOperation = \journal\Operation::model()
					->select('id', 'hash', 'number')
					->whereFinancialYear($eImport['financialYear'])
					->whereNumber('!=', NULL)
					->getCollection();

				$numberTreated = [];
				foreach($cOperation as $eOperation) {

					if(in_array($eOperation['number'], $numberTreated)) {
						continue;
					}
					$numberTreated[] = $eOperation['number'];
					\journal\Operation::model()
            ->select('number')
            ->whereNumber($eOperation['number'])
            ->update(['hash' => \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_FEC_IMPORT,]);

				}

				// On va ensuite regarder si on peut grouper les écritures de TVA avec leur copine
				$ccOperation = \journal\Operation::model()
					->select(['id', 'accountLabel', 'hash', 'type', 'amount', 'vatRate', 'account' => ['id', 'vatAccount', 'class'], 'vatAccount' => ['id', 'class']])
					->whereFinancialYear($eImport['financialYear'])
					->getCollection(index: ['hash', 'id']);

				foreach($ccOperation as $hash => $cOperation) {
					foreach($cOperation as $id => $eOperation) {

						if(
							\account\AccountLabelLib::isFromClass($eOperation['accountLabel'], \account\AccountSetting::VAT_BUY_CLASS_ACCOUNT) or
							\account\AccountLabelLib::isFromClass($eOperation['accountLabel'], \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)
						) {
							// On cherche la copine
							$cOperationOrigin = $cOperation->find(fn($e) => $e['account']['vatAccount']->notEmpty());
							if($cOperationOrigin->count() === 1) {
								$eOperationOrigin = $cOperationOrigin->first();
								if($eOperationOrigin['type'] !== $eOperation['type']) {
									continue;
								}
								$vatRate = $eOperation['amount'] / $eOperationOrigin['amount'] * 100;

								\journal\Operation::model()->update($eOperation, ['operation' => $eOperationOrigin]);
								\journal\Operation::model()->update($eOperationOrigin, ['vatAccount' => $eOperation['account'], 'vatRate' => $vatRate]);

								//d('yes !! ('.$eOperation['id'].') '.round($vatRate, 2).'');
							} else if($cOperationOrigin->count() > 1) {

								d('trop ('.$eOperation['id'].') cherche');

								foreach($cOperationOrigin as $eOperationOrigin) {

									$vatRate = $eOperation['amount'] / $eOperationOrigin['amount'] * 100;

									foreach($vatRates as $vatRateTheoric) {

										if(abs($vatRate - $vatRateTheoric) < 0.5) {

											\journal\Operation::model()->update($eOperation, ['operation' => $eOperationOrigin]);
											\journal\Operation::model()->update($eOperationOrigin, ['vatAccount' => $eOperation['account'], 'vatRate' => $vatRate]);

											d('trop ('.$eOperation['id'].') OK');
										}
									}

								}

							} else {
								continue;
							}
						}
					}
				}

			}

		}

	});
?>
