<?php
namespace journal;

Class VatUi {

	public function __construct() {
		\Asset::css('journal', 'vat.css');
		\Asset::css('company', 'company.css');
	}

	public function getTableContainer(
		\farm\Farm $eFarm,
		\Collection $cccOperation,
		string $type,
		\Search $search = new \Search(),
	): string {

		if($cccOperation->empty() === TRUE) {

			if($search->empty(['ids']) === TRUE) {
				return '<div class="util-empty">'.s("Aucune écriture n'a encore été enregistrée").'</div>';
			}
			return '<div class="util-empty">'.s("Aucune écriture ne correspond à vos critères de recherche").'</div>';

		}

		$h = '<div class="stick-sm util-overflow-sm">';
			$h .= $this->getTables($eFarm, $cccOperation, $search);
		$h .= '</div>';

		return $h;
	}

	public function getTables(
		\farm\Farm $eFarm,
		\Collection $cccOperation,
		\Search $search = new \Search(),
		string $for = 'web',
	): string {

		$h = '';

		$lastAccountLabel = NULL;
		foreach($cccOperation as $currentAccountLabel => $ccOperation) {

			$eOperation = $ccOperation->first()->first();

			$showInitialColumns = FALSE;
			foreach($ccOperation as $cOperation) {
				$showInitialColumns = ($showInitialColumns or $cOperation->find(fn($e) => $e['operation']->notEmpty())->notEmpty());
			}

			if($lastAccountLabel === NULL or $currentAccountLabel !== $lastAccountLabel) {

				$h .= '<div class="vat-account-title">';
					$h .= '<div class="title">'.s("Numéro de compte").'</div>';
					$h .= '<div class="label">'.encode($eOperation['accountLabel']).'&nbsp;-&nbsp;'.encode($eOperation['account']['description']).'</div>';
				$h .= '</div>';

				$lastAccountLabel = $currentAccountLabel;

			}

			$bigTotals = [
				'withVat' => 0,
				'withoutVat' => 0,
				'vat' => 0,
			];
			$h .= '<table class="td-vertical-top tbody-hover table-'.$for.' no-background">';

				$h .= '<thead '.($for === 'web' ? 'class="thead-sticky"' : '').'>';
					$h .= '<tr '.($for === 'pdf' ? 'class="tr-header tr-upper"' : '').'>';
						$h .= '<th>';
							$label = s("Date");
							$h .= (($search and $for !== 'pdf') ? $search->linkSort('date', $label) : $label);
						$h .= '</th>';
						$h .= '<th>';
							$label = s("Pièce comptable");
							$h .= (($search and $for !== 'pdf') ? $search->linkSort('document', $label) : $label);
						$h .= '</th>';
						$h .= '<th>';
							$label = s("Description");
							$h .= (($search and $for !== 'pdf') ? $search->linkSort('description', $label) : $label);
						$h .= '</th>';
						$h .= '<th>'.s("Tiers").'</th>';

						if($showInitialColumns) {
							$h .= '<th class="td-min-content text-end rowspaned-center">'.s("Taux TVA").'</th>';
							$h .= '<th class="text-end td-min-content highlight-stick-right rowspaned-center">'.s("Montant (TTC)").'</th>';
							$h .= '<th class="text-end td-min-content highlight-stick-left rowspaned-center">'.s("Montant (HT)").'</th>';
						}

						$h .= '<th class="text-end td-min-content highlight-stick-right rowspaned-center">'.s("TVA").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$currentMonth = NULL;

				$monthTotals = [
					'withVat' => 0,
					'withoutVat' => 0,
					'vat' => 0,
				];

				foreach($ccOperation as $month => $cOperation) {

					foreach($cOperation as $eOperation) {

						if($currentMonth !== NULL and $currentMonth !== $month) {

							$monthTotals = [
								'withVat' => 0,
								'withoutVat' => 0,
								'vat' => 0,
							];
						}

						$currentMonth = $month;

						$h .= '<tbody>';
							$h .= '<tr class="tr-border-top">';

								if($eOperation['operation']->notEmpty()) {

									$eOperationInitial = $eOperation['operation'];
									$monthTotals['withVat'] += $eOperationInitial['amount'] + $eOperation['amount'];
									$monthTotals['withoutVat'] += $eOperationInitial['amount'];
									$monthTotals['vat']+= $eOperation['amount'];


									$h .= '<td '.($for === 'pdf' ? 'class="pdf-text-small"' : '').'>';
										$h .= \util\DateUi::numeric($eOperationInitial['date']);
									$h .= '</td>';

									$h .= '<td>';
										$h .= '<div class="operation-info">';
											if($eOperationInitial['document'] !== NULL) {
												$h .= '<a href="'.new JournalUi()->getBaseUrl($eFarm, $eOperationInitial['financialYear']).'&document='.urlencode($eOperationInitial['document']).'" title="'.s("Voir les écritures liées à cette pièce comptable").'">'.encode($eOperationInitial['document']).'</a>';
											}
										$h .= '</div>';
									$h .= '</td>';

									$h .= '<td class="td-description">';
										$h .= '<div class="description">';
											$h .= encode($eOperationInitial['description']);
										$h .= '</div>';
									$h .= '</td>';

									$h .= '<td>';
										if($eOperationInitial['thirdParty']->exists() === TRUE) {
											$h .= encode($eOperationInitial['thirdParty']['name']);
										}
									$h .= '</td>';

									$h .= '<td class="td-min-content text-end">';
											$h .= $eOperationInitial['vatRate'];
									$h .= '</td>';

									$h .= '<td class="text-end td-min-content highlight-stick-right td-vertical-align-top">';
											$h .= \util\TextUi::money(round($eOperationInitial['amount'] + $eOperation['amount'], 2));
									$h .= '</td>';

									$h .= '<td class="text-end td-min-content highlight-stick-left td-vertical-align-top">';
											$h .= \util\TextUi::money($eOperationInitial['amount']);
									$h .= '</td>';

									$h .= '<td class="text-end td-min-content highlight-stick-right td-vertical-align-top">';
											$h .= \util\TextUi::money($eOperation['amount']);
									$h .= '</td>';


								} else {

									$monthTotals['withVat'] += $eOperation['amount'];
									$monthTotals['vat']+= $eOperation['amount'];

									$h .= '<td '.($for === 'pdf' ? 'class="pdf-text-small"' : '').'>';
										$h .= \util\DateUi::numeric($eOperation['date']);
									$h .= '</td>';

									$h .= '<td>';
										$h .= '<div class="operation-info">';
											if($eOperation['document'] !== NULL) {
												$h .= '<a href="'.new JournalUi()->getBaseUrl($eFarm, $eOperation['financialYear']).'&document='.urlencode($eOperation['document']).'" title="'.s("Voir les écritures liées à cette pièce comptable").'">'.encode($eOperation['document']).'</a>';
											}
										$h .= '</div>';
									$h .= '</td>';

									$h .= '<td class="td-description">';
										$h .= '<div class="description">';
											$h .= encode($eOperation['description']);
										$h .= '</div>';
									$h .= '</td>';

									$h .= '<td>';
										if($eOperation['thirdParty']->exists() === TRUE) {
											$h .= encode($eOperation['thirdParty']['name']);
										}
									$h .= '</td>';

									$h .= '<td class="text-end td-min-content highlight-stick-right td-vertical-align-top">';
											$h .= \util\TextUi::money($eOperation['amount']);
									$h .= '</td>';

								}
							$h .= '</tr>';

						$h .= '</tbody>';

					}

					$bigTotals['withVat'] += $monthTotals['withVat'];
					$bigTotals['withoutVat'] += $monthTotals['withoutVat'];
					$bigTotals['vat'] += $monthTotals['vat'];

					$h .= self::getMonthTotal($currentMonth, $monthTotals, $showInitialColumns);

				}

				$h .= self::getMonthTotal(NULL, $bigTotals, $showInitialColumns);

			$h .= '</table>';
		}
		return $h;
	}

	private static function getMonthTotal(?string $currentMonth, array $totals, bool $showInitialColumns): string {

		$h = '<tr class="tr-bold tr-border-top">';

			$h .= '<td colspan="2">';
				$h .= s("Total TVA");
			$h .= '</td>';

			$h .= '<td>';
				if($currentMonth !== NULL) {
					$h .= s("Mois de {month}", ['month' => \util\DateUi::textual($currentMonth.'-01', \util\DateUi::MONTH_YEAR)]);
				} else {
					$h .= s("Total de la période");
				}
			$h .= '</td>';

			$h .= '<td>';
			$h .= '</td>';

			if($showInitialColumns) {

				$h .= '<td>';
				$h .= '</td>';

				$h .= '<td class="text-end highlight-stick-right">';
				$h .= \util\TextUi::money($totals['withVat']);
				$h .= '</td>';

				$h .= '<td class="text-end highlight-stick-left">';
					$h .= \util\TextUi::money($totals['withoutVat']);
				$h .= '</td>';

			}

			$h .= '<td class="text-end highlight-stick-right">';
				$h .= \util\TextUi::money($totals['vat']);
			$h .= '</td>';

		$h .= '</tr>';

		return $h;
	}
}
