<?php
namespace preaccounting;

class RatioLib {

	private \Collection $cPayment;

	private array $byVat = [];
	private array $overpayment = [];

	private $vat = [];

	public function __construct(
		private \selling\Sale|\selling\Invoice $e,
		private \Collection $cAccount = new \Collection()
	) {

		$e->expects(['cItem', 'vatByRate', 'priceIncludingVat', 'priceExcludingVat']);

		if($e instanceof \selling\Invoice) {
			$e->expects(['cSale']);
		}

		$this->cPayment = \selling\PaymentTransactionLib::getAll($e, index: 'id');

		$this->splitByVat();
		$this->splitByPayments();
		$this->splitByAccounts();

		//$this->dump();

	}

	public function getByVat(): array {
		return $this->byVat;
	}

	public function filterByPayment(\selling\Payment $ePayment): array {

		$ePayment->expects(['id']);

		$ratiosByVat = [];

		foreach($this->byVat as $rate => $byVat) {

			$splitByPaymentFiltered = array_filter($byVat['splitByPayments'], fn($key) => $key === $ePayment['id'], ARRAY_FILTER_USE_KEY);

			$byVat['splitByPayments'] = $splitByPaymentFiltered;

			$ratiosByVat[$rate] = $byVat;

		}
		return $ratiosByVat;

	}

	protected function splitByVat(): void {

		$base = max($this->e['priceIncludingVat'], $this->e['paymentAmount']);

		foreach($this->e['vatByRate'] as ['vatRate' => $vatRate, 'amount' => $amount, 'vat' => $vat]) {

			$amountIncludingVat = match($this->e['taxes']) {
				\selling\Sale::EXCLUDING => round($amount + $vat, 2),
				\selling\Sale::INCLUDING => $amount
			};

			$amountExcludingVat = match($this->e['taxes']) {
				\selling\Sale::EXCLUDING => $amount,
				\selling\Sale::INCLUDING => round($amount - $vat, 2)
			};

			$this->byVat[(string)$vatRate] = [
				'vatRate' => (float)$vatRate,
				'amountIncludingVat' => $amountIncludingVat,
				'amountExcludingVat' => $amountExcludingVat,
				'part' => $amountIncludingVat / $base,
				'vat' => $vat,
				'splitByPayments' => []
			];

		}

		if($this->e['paymentAmount'] > $this->e['priceIncludingVat']) {

			$amount = round($this->e['paymentAmount'] - $this->e['priceIncludingVat'], 2);

			$this->overpayment = [
				'amount' => $amount,
				'splitByPayments' => []
			];

		}

	}

	protected function splitByPayments(): void {

		$payments = $this->getPayments();


		$lastPaymentId = array_key_last($payments);

		$calculatedVat = [];

		foreach($payments as $paymentId => $payment) {

			$lastVatRate = array_key_last($this->byVat);

			$calculatedAmountIncludingVat = 0.0;

			foreach($this->byVat as $vatRate => $vatValues) {

				$calculatedVat[$vatRate] ??= 0.0;

				if($vatRate !== $lastVatRate) {

					$amountIncludingVat = round($payment['part'] * $vatValues['amountIncludingVat'], 2);
					$calculatedAmountIncludingVat += $amountIncludingVat;

				} else {

					// Le montant vendu avec ce moyen de paiement moins ce qui a déjà été calculé
					$amountIncludingVat = $payment['amountSold'] - $calculatedAmountIncludingVat;

				}

				if($paymentId !== $lastPaymentId) {

					$vat = round($payment['part'] * $vatValues['vat'], 2);
					$calculatedVat[$vatRate] += $vat;

				} else {

					// La TVA restant à affecter
					$vat = $vatValues['vat'] - $calculatedVat[$vatRate];

				}

				$this->byVat[$vatRate]['splitByPayments'][$paymentId] = [
					'amountIncludingVat' => $amountIncludingVat,
					'amountExcludingVat' => round($amountIncludingVat - $vat, 2),
					'vat' => $vat,
				];

				if($this->overpayment) {
					$this->overpayment['splitByPayments'][$paymentId] = round($payment['amount'] - $payment['amountSold'], 2);
				}

			}

		}

	}

	protected function splitByAccounts(): void {

		if($this->cAccount->empty()) {
			return;
		}

		$itemsByVat = [];

		foreach($this->e['cItem'] as $eItem) {

			$key = (string)$eItem['vatRate'];

			$itemsByVat[$key] ??= [];

			$eAccount = self::getAccountFromItem($eItem, $this->cAccount);
			$accountId = $eAccount->empty() ? NULL : $eAccount['id'];

			$itemsByVat[$key][$accountId] ??= 0.0;
			$itemsByVat[$key][$accountId] = round($itemsByVat[$key][$accountId] + $eItem['priceStats'], 2);



		}
//dd($itemsByVat);
	}

	protected function getPayments(): array {

		$base = $this->e['priceIncludingVat'];

		$payments = [];

		$totalPaid = 0.0;
		$totalSold = 0.0;

		$ePaymentLast = $this->cPayment->last();

		foreach($this->cPayment as $ePayment) {

			if($this->e['paymentAmount'] <= $this->e['priceIncludingVat']) {
				$amountSold = $ePayment['amountIncludingVat'];
			} else {

				if($ePayment->is($ePaymentLast)) {

					$amountSold = round($this->e['priceIncludingVat'] - $totalSold, 2);

				} else {

					$amountSold = round($ePayment['amountIncludingVat'] * ($this->e['priceIncludingVat'] / $this->e['paymentAmount']), 2);
					$totalSold += $amountSold;

				}

			}

			$payments[$ePayment['id']] = [
				'part' => $amountSold / $base,
				'amount' => $ePayment['amountIncludingVat'],
				'amountSold' => $amountSold,
			];

			$totalPaid += $ePayment['amountIncludingVat'];


		}

		$totalPaid = round($totalPaid, 2);

		if($totalPaid < $this->e['priceIncludingVat']) { // PARTIAL_PAID

			$amount = round($this->e['priceIncludingVat'] - $totalPaid, 2);

			$payments[NULL] = [
				'part' => $amount / $base,
				'amount' => $amount,
				'amountSold' => $amount
			];

		}

		return $payments;

	}

	private static function getAccountFromItem(\selling\Item $eItem, \Collection $cAccount): \account\Account {

		// Account défini dans l'item
		if($eItem['account']->notEmpty() and $cAccount->offsetExists($eItem['account']['id'])) {

			return $eItem['account'];

		// Fallback sur le produit : cas du private
		} else if(
			$eItem['product']->notEmpty() and
			$eItem['type'] === \selling\Item::PRIVATE and
			$eItem['product']['privateAccount']->notEmpty() and
			$cAccount->offsetExists($eItem['product']['privateAccount']['id'])
		) {

			return $eItem['product']['privateAccount'];

		// Fallback sur le produit : cas du pro
		} else if(
			$eItem['product']->notEmpty() and
			$eItem['type'] === \selling\Item::PRO and
			$eItem['product']['proAccount']->notEmpty() and
			$cAccount->offsetExists($eItem['product']['proAccount']['id'])
		) {

			return $eItem['product']['proAccount'];

		// Fallback sur le produit : cas du pro sans account pro mais avec account private
		} else if(
			$eItem['product']->notEmpty() and
			$eItem['type'] === \selling\Item::PRO and
			$eItem['product']['privateAccount']->notEmpty() and
			$cAccount->offsetExists($eItem['product']['privateAccount']['id'])
		) {

			return $eItem['product']['privateAccount'];

		// On sait pas.
		} else {

			return new \account\Account();

		}

	}

	public function dump(): void {

		foreach($this->vat as $key => $vat) {
			$this->vat[$key]['vatControl'] = 0.0;
			$this->vat[$key]['amountControl'] = 0.0;
		}

		echo '<h4>'.s("Résultats").'</h4>';

		echo '<dl class="util-presentation util-presentation-1">';

			foreach($this->byVat as $vatRate => $vatValues) {

				echo '<dt>';
					echo (float)$vatRate.' %';
				echo '</dt>';
				echo '<dd>';

					echo \util\TextUi::money($vatValues['amountIncludingVat']).' TTC ';
					echo '<small class="color-muted">('.\util\TextUi::money($vatValues['amountExcludingVat']).' HT + '.\util\TextUi::money($vatValues['vat']).' TVA)</small>';

					echo '<table style="padding: 0rem; font-weight: normal">';

						foreach($vatValues['splitByPayments'] as $paymentId => $paymentValues) {

							echo '<tr>';

								echo '<td>';
									if($paymentId) {
										echo $this->cPayment[$paymentId]['methodName'];
									} else {
										echo 'Manque';
									}
								echo '</td>';

								echo '<td>';
									echo \util\TextUi::money($paymentValues['amountIncludingVat']).' TTC ';
									echo '<small class="color-muted">('.\util\TextUi::money($paymentValues['amountExcludingVat']).' HT + '.\util\TextUi::money($paymentValues['vat']).' TVA)</small>';
								echo '</td>';

							echo '</tr>';

						}

						echo '<tr style="font-style: italic">';

							echo '<td>';
								echo 'Total calculé';
							echo '</td>';

							echo '<td>';
								echo \util\TextUi::money(round(array_sum(array_column($vatValues['splitByPayments'], 'amountIncludingVat')), 2)).' TTC ';
								echo '<small class="color-muted">('.\util\TextUi::money(round(array_sum(array_column($vatValues['splitByPayments'], 'amountExcludingVat')), 2)).' HT + '.\util\TextUi::money(round(array_sum(array_column($vatValues['splitByPayments'], 'vat')), 2)).' TVA)</small>';
							echo '</td>';

						echo '</tr>';

					echo '</table>';

				echo '</dd>';

			}

			if($this->overpayment) {

				echo '<dt>';
					echo 'Trop payé';
				echo '</dt>';
				echo '<dd>';

					echo \util\TextUi::money($this->overpayment['amount']);

					echo '<table style="padding: 0rem; font-weight: normal">';

						foreach($this->overpayment['splitByPayments'] as $paymentId => $paymentAmount) {

							echo '<tr>';

								echo '<td>';
									if($paymentId) {
										echo $this->cPayment[$paymentId]['methodName'];
									} else {
										echo 'Trop perçu';
									}
								echo '</td>';

								echo '<td>';
									echo \util\TextUi::money($paymentAmount);
								echo '</td>';

							echo '</tr>';

						}

					echo '</table>';

				echo '</dd>';

			}

		echo '</dl>';

		exit;

	}


}

?>
