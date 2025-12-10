<?php
namespace invoicing;

class Suggestion extends SuggestionElement {

	public function acceptAction(): bool {

		return $this['status'] === \invoicing\Suggestion::WAITING;

	}

	public static function validateBatch(\Collection $cSuggestion): void {

		if($cSuggestion->empty()) {

			throw new \FailAction('invoicing\Suggestion::suggestions.check');

		} else {

			foreach($cSuggestion as $eInvoice) {

				$eInvoice->validate('acceptAction');

			}
		}

	}
}
?>
