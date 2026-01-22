<?php
/**
 * php framework/lime.php -a ouvretaferme -e prod company/configure/repairVatRate
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasFinancialYears(TRUE)
			//->whereId(7)
			->getCollection();

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			d('------------------ Ferme #'.$eFarm['id'].'--------------------');

			// Première étape : réparer les hash dans les invoice
			$cSuggestion = \preaccounting\Suggestion::model()
				->select(['cashflow', 'invoice' => ['id', 'accountingHash']])
				->whereStatus(\preaccounting\Suggestion::VALIDATED)
				->getCollection();

			foreach($cSuggestion as $eSuggestion) {

				$idOperations = \journal\OperationCashflow::model()
					->select('operation')
					->whereCashflow($eSuggestion['cashflow'])
					->getCollection()
					->getColumnCollection('operation')
					->getIds();

				$eOperation = \journal\Operation::model()
					->select(['hash'])
					->whereAccountLabel('LIKE', '512%')
					->whereId('IN', $idOperations)
					->get();

				if($eOperation->notEmpty() and $eOperation['hash'] !== $eSuggestion['invoice']['accountingHash']) {
					$eSuggestion['invoice']['accountingHash'] = $eOperation['hash'];
					\selling\Invoice::model()->update($eSuggestion['invoice'], ['accountingHash' => $eOperation['hash']]);
					echo '.';
				}

			}

			// Deuxième étape : réparer le taux de TVA
			$cOperation = \journal\Operation::model()
				->select(array_merge(\journal\Operation::getSelection(), ['operation' => ['id', 'accountLabel', 'vatRate', 'amount', 'vatAccount', 'account']]))
				->whereAccountLabel('LIKE', '445%')
				->getCollection();

			foreach($cOperation as $eOperation) {
				if($eOperation['operation']->empty() or $eOperation['operation']['vatRate'] !== 0.0) {
					continue;
				}
				$vatRate = round($eOperation['amount'] / $eOperation['operation']['amount'] * 100, 2);
				if($vatRate === 0.0 and $eOperation['operation']['vatRate'] === 0.0) {
					continue;
				}
				$cOperationByHash = \journal\OperationLib::getByHash($eOperation['hash']);
				$cOperationProduct = $cOperationByHash->find(fn($e) => str_starts_with($e['accountLabel'], '7'));

				if($cOperationProduct->count() === 1) {
					$properties = [];
					$update = [];
					if($eOperation['operation']['vatAccount']->empty()) {
						$eAccount = \account\AccountLib::getById($eOperation['operation']['account']['id']);
						$properties[] = 'vatAccount';
						$update['vatAccount'] = $eAccount;
					}
					$properties[] = 'vatRate';
					$update['vatRate'] = $vatRate;
					\journal\Operation::model()->update($eOperation['operation'], ['vatRate' => $vatRate]);
					echo '.';
				} else {
					echo "\n".$eOperation['hash']."\n";
				}
			}

		}

	});
?>
