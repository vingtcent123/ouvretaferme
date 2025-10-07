<?php
namespace overview;

class BalanceSheetUi {

	public function __construct() {
		\Asset::css('overview', 'overview.css');
	}

	public function getTitle(): string {
		return '<h1>'.s("Bilan").'</h1>';
	}

	public function getTable(\account\FinancialYear $eFinancialYear, \Collection $cOperation, float $result, \Collection $cAccount): string {

		$h = '';

		$balanceSheetData = [
			'fixedAssets' => [], // actif immobilisé : 2*
			'currentAssets' => [], // circulant : 3, 4 (si débiteur), 5 (si débiteur)
			'equity' => [], // capitaux propres : 10*, 12*
			'debts' => [], // dettes : 4 (si créditeur), 5 (si créditeur)
		];

		$totals = [
			'fixedAssets' => 0,
			'currentAssets' => 0,
			'equity' => 0,
			'debts' => 0,
		];

		foreach($cOperation as $eOperation) {

			if($eOperation['amount'] === 0.0) {
				continue;
			}

			switch((int)substr($eOperation['class'], 0, 1)) {

				case \account\AccountSetting::ASSET_GENERAL_CLASS:
					$balanceSheetData['fixedAssets'][] = $eOperation;
					$totals['fixedAssets'] += $eOperation['amount'];
					break;

				case \account\AccountSetting::STOCK_GENERAL_CLASS:
					$balanceSheetData['currentAssets'][] = $eOperation;
					$totals['currentAssets'] += $eOperation['amount'];
					break;

				case \account\AccountSetting::THIRD_PARTY_GENERAL_CLASS:
				case \account\AccountSetting::FINANCIAL_GENERAL_CLASS:
					if($eOperation['amount'] > 0) { // compte débiteur
						$balanceSheetData['currentAssets'][] = $eOperation;
						$totals['currentAssets'] += $eOperation['amount'];
					} else {
						$eOperation['amount'] *= -1;
						$balanceSheetData['debts'][] = $eOperation;
						$totals['debts'] += $eOperation['amount'];
					}
					break;

				case \account\AccountSetting::CAPITAL_GENERAL_CLASS:
					$eOperation['amount'] *= -1;
					if((int)substr($eOperation['class'], 0, 2) < 16) {
						$balanceSheetData['equity'][] = $eOperation;
						$totals['equity'] += $eOperation['amount'];
					} else {
						$balanceSheetData['debts'][] = $eOperation;
						$totals['debts'] += $eOperation['amount'];
					}
			}
		}

		// On rajoute le résultat
		$balanceSheetData['equity'][] = [
			'class' => (string)($result > 0 ? \account\AccountSetting::PROFIT_CLASS : \account\AccountSetting::LOSS_CLASS),
			'amount' => $result,
		];
		$totals['equity'] += $result;

		if($eFinancialYear['endDate'] > date('Y-m-d')) {
			$date = date('Y-m-d');
		} else {
			$date = $eFinancialYear['endDate'];
		}

		$h .= '<table class="overview_income-statement tr-hover tr-even">';

			$h .= '<tr class="overview_income-statement_row-title">';
				$h .= '<th class="text-center" colspan="6">'.s("Bilan au au {date}", ['date' => \util\DateUi::numeric($date)]).'</th>';
			$h .= '</tr>';

			$h .= '<tr class="overview_income-statement_group-title">';
				$h .= '<th colspan="3">'.s("Actif").'</th>';
				$h .= '<th colspan="3">'.s("Passif").'</th>';
			$h .= '</tr>';

			$h .= $this->displaySubCategoryLines($balanceSheetData['fixedAssets'], $balanceSheetData['equity'], $cAccount);

			$h .= '<tr class="overview_income-statement_group-total row-bold">';

				$h .= '<th colspan="2">'.s("Total actif immobilisé").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['fixedAssets'], precision: 0).'</td>';
				$h .= '<th colspan="2">'.s("Total capitaux propres").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['equity'], precision: 0).'</td>';

			$h .= '</tr>';

			$h .= $this->displaySubCategoryLines($balanceSheetData['currentAssets'], $balanceSheetData['debts'], $cAccount);

			$h .= '<tr class="overview_income-statement_group-total row-bold">';

				$h .= '<th colspan="2">'.s("Total actif circulant").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['currentAssets'], precision: 0).'</td>';
				$h .= '<th colspan="2">'.s("Total dettes").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['debts'], precision: 0).'</td>';

			$h .= '</tr>';

			$h .= '<tr class="overview_income-statement_group-total row-bold">';

				$h .= '<th colspan="2">'.s("Total Actif").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['fixedAssets'] + $totals['currentAssets'], precision: 0).'</td>';
				$h .= '<th colspan="2">'.s("Total passif").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['equity'] + $totals['debts'], precision: 0).'</td>';

			$h .= '</tr>';

		$h .= '</table>';

		return $h;

	}

	private function displaySubCategoryLines(array $assets, array $liabilities, \Collection $cAccount): string {

		$h = '';

		while(count($assets) > 0 or count($liabilities) > 0) {

			$asset = array_shift($assets);
			$liability = array_shift($liabilities);

			$h .= '<tr class="overview_income-statement_line">';

			if($asset !== null) {

				$h .= '<td class="text-end td-min-content">'.encode($asset['class']).'</td>';
				$h .= '<td>';
				if($cAccount->offsetExists($asset['class'])) {
					$eAccount = $cAccount->offsetGet($asset['class']);
				} else {
					$eAccount = $cAccount->offsetGet(substr($asset['class'], 0, 2));
				}
				$h .= encode($eAccount['description']);
				$h .= '</td>';
				$h .= '<td class="text-end">'.\util\TextUi::money($asset['amount'], precision: 0).'</td>';

			} else {
				$h .= '<td></td>';
				$h .= '<td></td>';
				$h .= '<td></td>';
			}

			if($liability !== null) {

				$h .= '<td class="text-end td-min-content">'.encode($liability['class']).'</td>';
				$h .= '<td>';
				if($cAccount->offsetExists($liability['class'])) {
					$eAccount = $cAccount->offsetGet($liability['class']);
				} else {
					$eAccount = $cAccount->offsetGet(substr($liability['class'], 0, 2));
				}
				$h .= encode($eAccount['description']);
				$h .= '</td>';
				$h .= '<td class="text-end">'.\util\TextUi::money($liability['amount'], precision: 0).'</td>';

			} else {
				$h .= '<td></td>';
				$h .= '<td></td>';
				$h .= '<td></td>';
			}

			$h .= '</tr>';

		}

		return $h;
	}

}
?>
