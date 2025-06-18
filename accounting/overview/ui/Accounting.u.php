<?php
namespace overview;

class AccountingUi {

	private function displayDebitCredit(array $line): string {

		$h = '<td class="cell-bordered text-end util-unit">'.(new OverviewUi()->number($line['startDebit'], '')).'</td>';
		$h .= '<td class="cell-bordered text-end util-unit">'.(new OverviewUi()->number($line['startCredit'], '')).'</td>';
		$h .= '<td class="cell-bordered text-end util-unit">'.(new OverviewUi()->number($line['moveDebit'], '')).'</td>';
		$h .= '<td class="cell-bordered text-end util-unit">'.(new OverviewUi()->number($line['moveCredit'], '')).'</td>';
		$h .= '<td class="cell-bordered text-end util-unit">'.(new OverviewUi()->number($line['balanceDebit'], '')).'</td>';
		$h .= '<td class="cell-bordered text-end util-unit">'.(new OverviewUi()->number($line['balanceCredit'], '')).'</td>';
		$h .= '<td class="cell-bordered text-end util-unit">'.(new OverviewUi()->number($line['lastBalanceDebit'], '')).'</td>';
		$h .= '<td class="cell-bordered text-end util-unit">'.(new OverviewUi()->number($line['lastBalanceCredit'], '')).'</td>';

		return $h;

	}

	private function displayTotalAndBalance(array $balance): string {

		$h = '<tr class="row-bordered row-header">';

			$h .= '<td class="cell-bordered"></td>';
			$h .= '<td class="cell-bordered">'.s("Totaux").'</td>';
			$h .= $this->displayDebitCredit($balance);

		$h .= '</tr>';

		$debit = $balance['moveDebit'] + $balance['startDebit'];
		$credit = $balance['moveCredit'] + $balance['startCredit'];

		$balanceLine = [
			'startDebit' => 0,
			'startCredit' => 0,
			'moveDebit' => ($debit > $credit ? abs($debit - $credit) : 0),
			'moveCredit' => ($debit < $credit ? abs($debit - $credit) : 0),
			'balanceDebit' => ($balance['balanceDebit'] > $balance['balanceCredit'] ? abs($balance['balanceDebit'] - $balance['balanceCredit']) : 0),
			'balanceCredit' => ($balance['balanceDebit'] < $balance['balanceCredit'] ? abs($balance['balanceDebit'] - $balance['balanceCredit']) : 0),
			'lastBalanceDebit' => ($balance['lastBalanceDebit'] > $balance['lastBalanceCredit'] ? abs($balance['lastBalanceDebit'] - $balance['lastBalanceCredit']) : 0),
			'lastBalanceCredit' => ($balance['lastBalanceDebit'] < $balance['lastBalanceCredit'] ? abs($balance['lastBalanceDebit'] - $balance['lastBalanceCredit']) : 0),
		];

		$h .= '<tr class="row-bordered row-header">';

			$h .= '<td class="cell-bordered"></td>';
			$h .= '<td class="cell-bordered">'.s("Soldes").'</td>';
			$h .= $this->displayDebitCredit($balanceLine);

		$h .= '</tr>';

		return $h;

	}

	private function displayBalanceHeader(): string {

		$h = '<thead class="thead-sticky">';
			$h .= '<tr>';
				$h .= '<th class="text-end">'.s("Compte").'</th>';
				$h .= '<th>'.s("Libellé").'</th>';
				$h .= '<th class="text-end">'.s("Début débit").'</th>';
				$h .= '<th class="text-end">'.s("Début crédit").'</th>';
				$h .= '<th class="text-end">'.s("Mouvement débit").'</th>';
				$h .= '<th class="text-end">'.s("Mouvement crédit").'</th>';
				$h .= '<th class="text-end">'.s("Solde fin débiteur N").'</th>';
				$h .= '<th class="text-end">'.s("Solde fin créditeur N").'</th>';
				$h .= '<th class="text-end">'.s("Solde fin débiteur N-1").'</th>';
				$h .= '<th class="text-end">'.s("Solde fin créditeur N-1").'</th>';
			$h .= '</tr>';
		$h .= '</thead>';

		return $h;
	}

	public function displaySummaryAccountingBalance(array $accountingBalanceSheet): string {

		\Asset::css('company', 'design.css');

		$categories = \Setting::get('accounting\summaryAccountingBalanceCategories');

		if(empty($accountingBalanceSheet) === TRUE) {
			return '<div class="util-info">'.s("Il n'y a rien à afficher pour le moment.").'</div>';
		}

		$h = '<div class="util-overflow-sm">';

			$h .= '<table id="account-list" class=" tr-even tr-hover table-bordered">';

				$h .= $this->displayBalanceHeader();
				$h .= '<tbody>';

					$lastClass = NULL;
					foreach($categories as $category) {

						$currentClass = substr((string)$category['min'], 0, 1);
						if($lastClass === NULL or $lastClass !== $currentClass) {

							if($lastClass !== NULL) {

								$h .= self::displayTotalAndBalance($accountingBalanceSheet['total-'.$lastClass]);

							}

							$h .= '</body>';
							$h .= '<tbody>';
							$h .= '<tr class="row-bordered row-emphasis row-bold">';
								$h .= '<td colspan="2" class="cell-bordered text-center">';
									$h .= $currentClass;
								$h .= '</td>';
								$h .= '<td colspan="8" class="cell-bordered text-center">';
									$h .= match($currentClass) {
										'1' => \s("Comptes de bilan"),
										'2' => \s("Comptes d'immobilisation"),
										'3' => \s("Comptes de stocks et encours"),
										'4' => \s("Comptes de tiers"),
										'5' => \s("Comptes financiers"),
										'6' => \s("Comptes de charges"),
										'7' => \s("Comptes de produits"),
									};
								$h .= '</td>';
							$h .= '</tr>';
						}

						if(isset($accountingBalanceSheet[$category['min'].'-'.$category['max']]) === TRUE) {
							$balance = $accountingBalanceSheet[$category['min'].'-'.$category['max']];
						} else {
							$balance = [
								'accountLabel' => $category['name'],
								'description' => $category['name'],
								'startDebit' => 0,
								'startCredit' => 0,
								'moveDebit' => 0,
								'moveCredit' => 0,
								'balanceDebit' => 0,
								'balanceCredit' => 0,
								'lastBalanceDebit' => 0,
								'lastBalanceCredit' => 0,
							];
						}

						$label = $category['min'] === $category['max'] ? $category['min'] : \s("{min} à {max}", ['min' => $category['min'], 'max' => $category['max']]);
						$description = $category['name'];

						$h .= '<tr class="row-bordered">';

							$h .= '<td class="cell-bordered">'.$label.'</td>';
							$h .= '<td class="cell-bordered">'.\encode($description).'</td>';
							$h .= $this->displayDebitCredit($balance);

						$h .= '</tr>';

						$lastClass = $currentClass;
					}

					$h .= self::displayTotalAndBalance($accountingBalanceSheet['total-'.$lastClass]);

				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function displayAccountingBalanceSheet(array $accountingBalanceSheet): string {

		if(empty($accountingBalanceSheet) === TRUE) {
			return '<div class="util-info">'.s("Il n'y a rien à afficher pour le moment.").'</div>';
		}

		$h = '<div class="util-overflow-sm table-sticky-container">';

		$h .= '<table id="account-list" class="table-sticky tr-even tr-hover">';

			$h .= $this->displayBalanceHeader();

			$h .= '<tbody>';

			foreach($accountingBalanceSheet as $balance) {

				$isTotal = $balance['accountLabel'] === 'total';

				$h .= '<tr'.($isTotal === TRUE ? ' class="row-header"' : '').'>';

				$h .= '<td>'.($isTotal === TRUE ? s("Totaux") : \encode($balance['accountLabel'])).'</td>';
				$h .= '<td>'.($isTotal === TRUE ? s("comptes") : \encode($balance['description'])).'</td>';
				$h .= '<td class="text-end util-unit">'.(new OverviewUi()->number($balance['startDebit'], '')).'</td>';
				$h .= '<td class="text-end util-unit">'.(new OverviewUi()->number($balance['startCredit'], '')).'</td>';
				$h .= '<td class="text-end util-unit">'.(new OverviewUi()->number($balance['moveDebit'], '')).'</td>';
				$h .= '<td class="text-end util-unit">'.(new OverviewUi()->number($balance['moveCredit'], '')).'</td>';
				$h .= '<td class="text-end util-unit">'.(new OverviewUi()->number($balance['balanceDebit'], '')).'</td>';
				$h .= '<td class="text-end util-unit">'.(new OverviewUi()->number($balance['balanceCredit'], '')).'</td>';
				$h .= '<td class="text-end util-unit">'.(new OverviewUi()->number($balance['lastBalanceDebit'], '')).'</td>';
				$h .= '<td class="text-end util-unit">'.(new OverviewUi()->number($balance['lastBalanceCredit'], '')).'</td>';

				$h .= '</tr>';
			}

			$h .= '</tbody>';
		$h .= '</table>';

		$h .= '</div>';

		return $h;

	}
}

?>
