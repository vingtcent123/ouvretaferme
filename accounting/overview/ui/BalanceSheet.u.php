<?php
namespace overview;

class BalanceSheetUi {

	public function __construct() {
		\Asset::css('overview', 'overview.css');
	}

	public function getTitle(): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Bilan");
			$h .= '</h1>';

			$h .= '<div>';
				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#balance-sheet-search")').' class="btn btn-primary">'.\Asset::icon('filter').' '.s("Configurer la synthèse").'</a> ';
			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

	public function getSearch(\Search $search, \account\FinancialYear $eFinancialYear): string {

		$h = '<div id="balance-sheet-search" class="util-block-search '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';
					$h .= $form->checkbox('detailed', 1, ['checked' => $search->get('detailed'), 'callbackLabel' => fn($input) => $input.' '.s("Afficher le détail")]);
					$h .= $form->submit(s("Valider"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}


	public function getTable(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cOperation, \Collection $cOperationDetail, ?float $result, \Collection $cAccount): string {

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

			// Recherche des détails d'opération
			$cOperationSubClasses = $cOperationDetail->find(fn($e) => mb_substr($e['class'], 0, 3) === $eOperation['class'])->getArrayCopy();

			switch((int)substr($eOperation['class'], 0, 1)) {

				case \account\AccountSetting::ASSET_GENERAL_CLASS:
					$balanceSheetData['fixedAssets'] = array_merge($balanceSheetData['fixedAssets'], $cOperationSubClasses);
					$balanceSheetData['fixedAssets'][] = $eOperation;
					$totals['fixedAssets'] += $eOperation['amount'];
					break;

				case \account\AccountSetting::STOCK_GENERAL_CLASS:
					$balanceSheetData['currentAssets'] = array_merge($balanceSheetData['currentAssets'], $cOperationSubClasses);
					$balanceSheetData['currentAssets'][] = $eOperation;
					$totals['currentAssets'] += $eOperation['amount'];
					break;

				case \account\AccountSetting::THIRD_PARTY_GENERAL_CLASS:
				case \account\AccountSetting::FINANCIAL_GENERAL_CLASS:
					if($eOperation['amount'] > 0) { // compte débiteur
						$balanceSheetData['currentAssets'] = array_merge($balanceSheetData['currentAssets'], $cOperationSubClasses);
						$balanceSheetData['currentAssets'][] = $eOperation;
						$totals['currentAssets'] += $eOperation['amount'];
					} else {
						$eOperation['amount'] *= -1; // compte créditeur
						$balanceSheetData['debts'] = array_merge($balanceSheetData['debts'], array_map(function($e) {
							$e['amount'] *= -1;
							return $e;
						}, $cOperationSubClasses));
						$balanceSheetData['debts'][] = $eOperation;
						$totals['debts'] += $eOperation['amount'];
					}
					break;

				case \account\AccountSetting::CAPITAL_GENERAL_CLASS:
					if((int)substr($eOperation['class'], 0, 2) < \account\AccountSetting::LOANS_CLASS) {
						$balanceSheetData['equity'] = array_merge($balanceSheetData['equity'], $cOperationSubClasses);
						$balanceSheetData['equity'][] = $eOperation;
						$totals['equity'] += $eOperation['amount'];
					} else { // Les emprunts + dettes + comptes de liaison partent en dettes
						$balanceSheetData['debts'] = array_merge($balanceSheetData['debts'], array_map(function($e) {
							$e['amount'] *= -1;
							return $e;
						}, $cOperationSubClasses));
						$balanceSheetData['debts'][] = $eOperation;
						$totals['debts'] += $eOperation['amount'];
					}
			}
		}

		// On rajoute le résultat si l'exercice est encore ouvert
		if($eFinancialYear->canUpdate() and $result !== NULL) {
			$balanceSheetData['equity'][] = [
				'class' => (string)($result > 0 ? \account\AccountSetting::PROFIT_CLASS : \account\AccountSetting::LOSS_CLASS),
				'amount' => $result,
			];
			$totals['equity'] += $result;
		}

		if($eFinancialYear['endDate'] > date('Y-m-d')) {
			$date = date('Y-m-d');
		} else {
			$date = $eFinancialYear['endDate'];
		}

		$h .= '<table class="overview_income-statement tr-hover tr-even">';

			$h .= '<tr class="overview_income-statement_row-title">';
				$h .= '<th class="text-center" colspan="6">'.s("{farm} - exercice {year}<br />Balance au {date}", ['farm' => $eFarm['legalName'], 'year' => \account\FinancialYearUi::getYear($eFinancialYear), 'date' => \util\DateUi::numeric($date)]).'</th>';
			$h .= '</tr>';

			$h .= '<tr class="overview_income-statement_group-title">';
				$h .= '<th colspan="3">'.s("Actif").'</th>';
				$h .= '<th colspan="3">'.s("Passif").'</th>';
			$h .= '</tr>';

			$h .= $this->displaySubCategoryLines(eFarm: $eFarm, assets: $balanceSheetData['fixedAssets'], liabilities: $balanceSheetData['equity'],cAccount: $cAccount, hasDetail: $cOperationDetail->notEmpty());

			$h .= '<tr class="overview_income-statement_group-total row-bold">';

				$h .= '<th colspan="2">'.s("Total actif immobilisé").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['fixedAssets'], precision: 2).'</td>';
				$h .= '<th colspan="2">'.s("Total capitaux propres").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['equity'], precision: 2).'</td>';

			$h .= '</tr>';

			$h .= $this->displaySubCategoryLines(eFarm: $eFarm, assets: $balanceSheetData['currentAssets'],liabilities: $balanceSheetData['debts'], cAccount: $cAccount, hasDetail: $cOperationDetail->notEmpty());

			$h .= '<tr class="overview_income-statement_group-total row-bold">';

				$h .= '<th colspan="2">'.s("Total actif circulant").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['currentAssets'], precision: 2).'</td>';
				$h .= '<th colspan="2">'.s("Total dettes").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['debts'], precision: 2).'</td>';

			$h .= '</tr>';

			$h .= '<tr class="overview_income-statement_group-total row-bold">';

				$h .= '<th colspan="2">'.s("Total Actif").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['fixedAssets'] + $totals['currentAssets'], precision: 2).'</td>';
				$h .= '<th colspan="2">'.s("Total passif").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['equity'] + $totals['debts'], precision: 2).'</td>';

			$h .= '</tr>';

		$h .= '</table>';

		return $h;

	}

	private function displaySubCategoryLines(\farm\Farm $eFarm, array $assets, array $liabilities, \Collection $cAccount, bool $hasDetail): string {

		$h = '';
		$class = ($hasDetail ? ' overview_income-statement_cell-summary' : '');

		while(count($assets) > 0 or count($liabilities) > 0) {

			$asset = array_shift($assets);
			$liability = array_shift($liabilities);

			$h .= '<tr class="overview_income-statement_line">';

			if($asset !== null) {

				if($asset['description'] ?? NULL) {

					$h .= '<td class="text-end td-min-content overview_income-statement_cell-links">';
						$h .= '<small><a title="'.s("Voir les écritures au journal").'" href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?accountLabel='.encode($asset['class']).'">'.\Asset::icon('journal-text').'</a> ';
						$h .= '<a title="'.s("Voir les enregistrements au grand-livre").'" href="'.\company\CompanyUi::urlJournal($eFarm).'/book?accountLabel='.encode($asset['class']).'">'.\Asset::icon('book').'</a> ';
						$h .= '<a title="'.s("Voir la balance").'" href="'.\company\CompanyUi::urlJournal($eFarm).'/balance?accountLabel='.encode($asset['class']).'&precision=8">'.\Asset::icon('calculator').'</a></small>';
					$h .= '</td>';
					$h .= '<td><i>';
						$h .= encode($asset['class']).' ';
						if($cAccount->offsetExists((int)trim($asset['class'], '0'))) {
							$h .= encode($cAccount[(int)trim($asset['class'], '0')]['description']);
						} else {
							$h .= encode($asset['description']);
						}
					$h .= '</i></td>';
					$h .= '<td class="text-end">'.\util\TextUi::money($asset['amount'], precision: 2).'</td>';

				} else {

					$h .= '<td class="text-end td-min-content'.$class.'">'.encode($asset['class']).'</td>';
					$h .= '<td class="'.$class.'">';
						if($cAccount->offsetExists($asset['class'])) {
							$eAccount = $cAccount->offsetGet($asset['class']);
						} else {
							$eAccount = $cAccount->offsetGet(substr($asset['class'], 0, 2));
						}
						$h .= encode($eAccount['description']);
					$h .= '</td>';
					$h .= '<td class="text-end'.$class.'">'.\util\TextUi::money($asset['amount'], precision: 2).'</td>';

				}
			} else {
				$h .= '<td></td>';
				$h .= '<td></td>';
				$h .= '<td></td>';
			}

			if($liability !== null) {

				if($liability['description'] ?? NULL) {

					$h .= '<td class="text-end td-min-content overview_income-statement_cell-links">';
						$h .= '<small><a title="'.s("Voir les écritures au journal").'"href="'.\company\CompanyUi::urlJournal($eFarm).'/operations?accountLabel='.encode($liability['class']).'">'.\Asset::icon('journal-text').'</a> ';
						$h .= '<a title="'.s("Voir les enregistrements au grand-livre").'" href="'.\company\CompanyUi::urlJournal($eFarm).'/book?accountLabel='.encode($liability['class']).'">'.\Asset::icon('book').'</a> ';
						$h .= '<a title="""'.s("Voir la balance").'" href="'.\company\CompanyUi::urlJournal($eFarm).'/balance?accountLabel='.encode($liability['class']).'&precision=8">'.\Asset::icon('calculator').'</a></small>';
					$h .= '</td>';
					$h .= '<td><i>';
						$h .= encode($liability['class']).' ';
						if($cAccount->offsetExists((int)trim($liability['class'], '0'))) {
							$h .= encode($cAccount[(int)trim($liability['class'], '0')]['description']);
						} else {
							$h .= encode($liability['description']);
						}
					$h .= '</i></td>';
					$h .= '<td class="text-end">'.\util\TextUi::money($liability['amount'], precision: 2).'</td>';

				} else {

					$h .= '<td class="text-end td-min-content'.$class.'">'.encode($liability['class']).'</td>';
					$h .= '<td class="'.$class.'">';

						if($cAccount->offsetExists($liability['class'])) {
							$eAccount = $cAccount->offsetGet($liability['class']);
						} else {
							$eAccount = $cAccount->offsetGet(substr($liability['class'], 0, 2));
						}
						$h .= encode($eAccount['description']);

					$h .= '</td>';
					$h .= '<td class="text-end'.$class.'">'.\util\TextUi::money($liability['amount'], precision: 2).'</td>';

				}

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
