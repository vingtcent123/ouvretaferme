<?php
namespace preaccounting;

class Suggestion extends SuggestionElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
				'cashflow' => \bank\Cashflow::getSelection(),
				'payment' => [
					'id', 'source', 'amountIncludingVat',
					'invoice' => \selling\Invoice::getSelection(),
					'sale' => \selling\Sale::getSelection()
				],
				'paymentMethod' => \payment\Method::getSelection(),
			];

	}

	public function acceptIgnore(): bool {

		return $this['status'] === \preaccounting\Suggestion::WAITING;

	}
	public function acceptReconciliate(): bool {

		return $this['status'] === \preaccounting\Suggestion::WAITING and $this['paymentMethod']->notEmpty();

	}

	public function acceptUpdate(): bool {

		return $this['status'] === Suggestion::WAITING;

	}

	public static function validateBatch(\Collection $cSuggestion): void {

		if($cSuggestion->empty()) {

			throw new \FailAction('preaccounting\Suggestion::suggestions.check');

		} else {

			foreach($cSuggestion as $eInvoice) {

				$eInvoice->validate('acceptIgnore');

			}
		}

	}

	public static function validateBatchReconciliate(\Collection $cSuggestion): void {

		if($cSuggestion->empty()) {

			throw new \FailAction('preaccounting\Suggestion::suggestions.check');

		} else {

			foreach($cSuggestion as $eInvoice) {

				$eInvoice->validate('acceptReconciliate');

			}
		}

	}

}
?>
