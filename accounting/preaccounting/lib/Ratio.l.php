<?php
namespace preaccounting;

class RatioLib {

	private \Collection $cPayment;

	private array $byVat = [];
	private array $overpayment = [];

	private $vat = [];

	public function __construct(
		private \selling\Sale|\selling\Invoice $e,
		private \Collection $cAccount = new \Collection(),
		private bool $isCashReceipt = FALSE,
	) {

		$e->expects(['cItem', 'vatByRate', 'priceIncludingVat', 'priceExcludingVat', 'paymentAmount']);

		if($e instanceof \selling\Invoice) {
			$e->expects(['cSale']);
		}

		$this->cPayment = \selling\PaymentTransactionLib::getAll($e, selection: \selling\Payment::getSelection() + [
			'cashflow' => ['amount', 'account' => ['account']]
			], index: 'id');

		$this->splitByVat();
		$this->splitByPayments();
		$this->splitByAccounts();

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

	public function filter(\selling\Payment $ePaymentFilter, \payment\Method $eMethodFilter): array {

		foreach($this->byVat as &$ratios) {
			foreach($ratios['splitByPayments'] as $keyPayment => &$payment) {
				if($ePaymentFilter->notEmpty() and $payment['payment']->is($ePaymentFilter) === FALSE) {
					unset($ratios['splitByPayments'][$keyPayment]);
				} else if($eMethodFilter->notEmpty() and $payment['payment']['method']->notEmpty() and $payment['payment']['method']->is($eMethodFilter) === FALSE) {
					unset($ratios['splitByPayments'][$keyPayment]);
				}
			}
		}

		return $this->byVat;

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

		ksort($this->byVat);

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
					$vat = round($vatValues['vat'] - $calculatedVat[$vatRate], 2);

				}

				$this->byVat[$vatRate]['splitByPayments'][$paymentId] = [
					'payment' => $payment['payment'] ?? new \selling\Payment(),
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

		$accounts = $this->getAccounts();

		foreach($this->byVat as $vatRate => $vatValues) {

			$vatAccounts = $accounts[$vatRate] ?? [];

			foreach($vatAccounts as $accountId => $accountAmount) {

				$accountAmount = round($accountAmount, 2);

				$ratio = $accountAmount / $vatValues['amountExcludingVat'];

				$lastPaymentId = array_key_last($vatValues['splitByPayments']);

				$calculatedAmountExcludingVat = 0.0;

				foreach($vatValues['splitByPayments'] as $paymentId => $payment) {

					if($paymentId !== $lastPaymentId) {

						$amountExcludingVat = round($payment['amountExcludingVat'] * $ratio, 2);
						$calculatedAmountExcludingVat += $amountExcludingVat;

					} else {

						// La TVA restant à affecter
						$amountExcludingVat = round($accountAmount - $calculatedAmountExcludingVat, 2);

					}

					$this->byVat[$vatRate]['splitByPayments'][$paymentId]['splitByAccounts'] ??= [];
					$this->byVat[$vatRate]['splitByPayments'][$paymentId]['splitByAccounts'][$accountId] = [
						'amountExcludingVat' => $amountExcludingVat
					];

				}

			}

			// Calcul de la TVA
			foreach($this->byVat[$vatRate]['splitByPayments'] as $paymentId => $payment) {

				$lastAccountId = array_key_last($payment['splitByAccounts']);
				$calculatedVat = 0.0;

				foreach($payment['splitByAccounts'] as $accountId => $account) {

					if($accountId !== $lastAccountId) {

						$vat = \util\AmountUi::vatFromExcluding($account['amountExcludingVat'], $vatValues['vatRate']);
						$calculatedVat += $vat;

					} else {

						// La TVA restant à affecter
						$vat = round($payment['vat'] - $calculatedVat, 2);

					}

					$this->byVat[$vatRate]['splitByPayments'][$paymentId]['splitByAccounts'][$accountId]['vat'] = $vat;


				}

			}


		}

	}

	protected function getAccounts(): array {

		$accounts = [];

		foreach($this->e['cItem'] as $eItem) {

			$key = (string)$eItem['vatRate'];

			$accounts[$key] ??= [];

			$eAccount = $this->getAccountFromItem($eItem, $this->cAccount);
			$accountId = $eAccount->empty() ? NULL : $eAccount['id'];

			$accounts[$key][$accountId] ??= 0.0;
			$accounts[$key][$accountId] += $eItem['netPriceExcludingVat'];

		}

		return $accounts;

	}

	protected function getPayments(): array {

		if($this->cPayment->empty()) {
			return [];
		}

		$payments = [];

		$totalPaid = 0.0;
		$totalSold = 0.0;

		$ratioSold = $this->getSoldRatio();

		$ePaymentLast = $this->cPayment->last();

		foreach($this->cPayment as $ePayment) {

			if($this->e['paymentAmount'] <= $this->e['priceIncludingVat']) {
				$amountSold = $ePayment['amountIncludingVat'] * $ratioSold;
			} else {

				if($ePayment->is($ePaymentLast)) {

					$amountSold = round($this->e['priceIncludingVat'] - $totalSold, 2);

				} else {

					$amountSold = round($ePayment['amountIncludingVat'] * $ratioSold, 2);
					$totalSold += $amountSold;

				}

			}

			$payments[$ePayment['id']] = [
				'payment' => $ePayment,
				'part' => $amountSold / $this->e['priceIncludingVat'],
				'amount' => $ePayment['amountIncludingVat'],
				'amountSold' => $amountSold,
			];

			$totalPaid += $ePayment['amountIncludingVat'];


		}

		$totalPaid = round($totalPaid, 2);

		if($totalPaid < $this->e['priceIncludingVat']) { // PARTIAL_PAID

			$amount = round($this->e['priceIncludingVat'] - $totalPaid, 2);

			$payments[NULL] = [
				'part' => $amount / $this->e['priceIncludingVat'],
				'amount' => $amount,
				'amountSold' => $amount
			];

		}

		return $payments;

	}

	private function getSoldRatio(): float {

		if($this->e['paymentAmount'] <= $this->e['priceIncludingVat']) {
			return 1;
		} else {
			return ($this->e['priceIncludingVat'] / $this->e['paymentAmount']);
		}

	}

	private function getAccountFromItem(\selling\Item $eItem, \Collection $cAccount): \account\Account {

		if($this->isCashReceipt) {
			return $cAccount->find(fn($e) => $e['class'] === (string)\account\AccountSetting::PRODUCT_SOLD_ACCOUNT_CLASS, limit: 1, default: new \account\Account());
		}

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

		} else if(
			$eItem['product']->notEmpty() and
			$eItem['product']['profile'] === \selling\Product::SHIPPING
		) {

			return $cAccount->find(fn($e) => $e['class'] === (string)\account\AccountSetting::PRODUCT_SHIPPING_ACCOUNT_CLASS, limit: 1, default: new \account\Account());

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

								echo '<td class="td-vertical-align-top">';
									if($paymentId) {
										echo $this->cPayment[$paymentId]['methodName'];
									} else {
										echo 'Manque';
									}
								echo '</td>';

								echo '<td>';
									echo \util\TextUi::money($paymentValues['amountIncludingVat']).' TTC ';
									echo '<small class="color-muted">('.\util\TextUi::money($paymentValues['amountExcludingVat']).' HT + '.\util\TextUi::money($paymentValues['vat']).' TVA)</small>';

									if(
										$this->cAccount->notEmpty() and
										$paymentValues['splitByAccounts']
									) {

										echo '<ul style="font-size: 0.9rem; margin-top: 0.25rem; color: var(--secondary); margin-bottom: 0">';

											foreach($paymentValues['splitByAccounts'] as $accountId => $amount) {
												echo '<li>'.($accountId ? $this->cAccount[$accountId]['class'] : '?').' '.\Asset::icon('arrow-right-short').' '.\util\TextUi::money($amount['amountExcludingVat']).' HT + '.\util\TextUi::money($amount['vat']).' TVA</li>';
											}

										echo '</ul>';

									}

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
