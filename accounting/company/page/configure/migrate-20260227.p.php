<?php
/**
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260227
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260227
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

			$ccOperation = \journal\Operation::model()
				->select(\journal\Operation::getSelection())
				->whereDate('LIKE', '2025%')
				->group(['hash', 'id'])
				->getCollection(index: ['hash', 'id']);

			foreach($ccOperation as $hash => $cOperation) {

				if(str_ends_with($hash, 'n') or $cOperation->count() < 3) { // à nouveau
					continue;
				}

				$cOperationVat = $cOperation->find(fn($e) => str_starts_with($e['accountLabel'], '445') and $e['operation']->empty());
				if($cOperationVat->count() === 0) {
					continue;
				}

				$cOperationSell = $cOperation->find(fn($e) => str_starts_with($e['accountLabel'], '7') and $e['operation']->empty());
				if($cOperationSell->empty()) {
					continue;
				}

				if($cOperationVat->count() > 1) {
					d($hash.' : '.$cOperationVat->count().' / '.$cOperationSell->count());
					continue;
				} else {
					d($hash.' : ok - '.join(', ', $cOperation->getColumn('accountLabel')));
				}


				$eOperationVat = $cOperationVat->first();
				$eOperationSell = $cOperationSell->first();
	/*
				\journal\Operation::model()
					->whereId($eOperationSell['id'])
					->update(['vatAccount' => $eOperationVat['account'], 'vatRule' => \journal\Operation::VAT_STD]);

				\journal\Operation::model()
					->whereId($eOperationVat['id'])
					->update(['operation' => $eOperationSell, 'vatRule' => \journal\Operation::VAT_STD]);
			}
			*/
			}
		}

	});
?>
