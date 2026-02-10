<?php
/**
 * Corrige les cashflow / suggestion : lie avec des Payment et plus avec des Invoice.
 *
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate module=Cashflow
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate module=Suggestion
 * php framework/lime.php -a ouvretaferme -e prod dev/module package=selling module=Payment flags=b
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260210
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260210
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

			// Cashflows
			d('cashflow');
			$cCashflow = \bank\Cashflow::model()
				->select(\bank\Cashflow::getSelection())
				->whereInvoice('!=', NULL)
				->getCollection();

			foreach($cCashflow as $eCashflow) {

				$cPayment = \selling\Payment::model()
					->select(\selling\Payment::getSelection())
					->whereInvoice($eCashflow['invoice'])
					->getCollection();

				if($cPayment->count() === 1) {
					\bank\Cashflow::model()->update($eCashflow, ['payment' => $cPayment->first()]);
					echo '.';
				} else {
					echo 'xx';
				}

			}

			// Suggestions
			echo "\n";
			d('suggestion');
			$cSuggestion = \preaccounting\Suggestion::model()
				->select(\preaccounting\Suggestion::getSelection())
				->getCollection();

			foreach($cSuggestion as $eSuggestion) {

				$cPayment = \selling\Payment::model()
					->select(\selling\Payment::getSelection())
					->whereInvoice($eSuggestion['invoice'])
					->getCollection();

				if($cPayment->count() === 1) {
					\preaccounting\Suggestion::model()->update($eSuggestion, ['payment' => $cPayment->first()]);
					echo '.';
				} else if($eSuggestion !== \preaccounting\Suggestion::VALIDATED) {
					echo 'x'.$eSuggestion['id'].'x';
				}

			}

			// Payments
			echo "\n";
			d('payments');
			$cInvoice = \selling\Invoice::model()
				->select(\selling\Invoice::getSelection())
				->whereCashflow('!=', NULL)
				->whereFarm($eFarm)
				->getCollection();

			foreach($cInvoice as $eInvoice) {

				\selling\Payment::model()
					->whereInvoice($eInvoice)
					->whereStatus(\selling\Payment::PAID)
					->update([
						'cashflow' => $eInvoice['cashflow'],
						'accountingDifference' => $eInvoice['accountingDifference'],
						'readyForAccounting' => $eInvoice['readyForAccounting'],
						'accountingHash' => $eInvoice['accountingHash'],
					]);

			}
		}

	});
?>
