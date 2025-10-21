<?php
namespace overview;

Class VatUi {

	public function __construct() {
		\Asset::css('overview', 'vat.css');
	}

	public function getTitle(\farm\Farm $eFarm, \Collection $cFinancialYear): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("La TVA");
			$h .= '</h1>';

			$h .= '<div>';
				$h .= '<a href="'.\company\CompanyUi::urlSummary($eFarm).'/vat/history" class="btn btn-primary">'.\Asset::icon('list-check').' '.s("Historique").'</a> ';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}
	public function getHistoryTitle(\farm\Farm $eFarm, \Collection $cFinancialYear): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("L'historique de TVA");
			$h .= '</h1>';

		$h .= '</div>';

		return $h;

	}

	public function getVatTabs(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, ?string $selectedTab = NULL): string {

		$h = '<div class="tabs-item">';

			$h .= '<a class="tab-item'.($selectedTab === NULL ? ' selected' : '').'" data-tab="journal" href="'.\company\CompanyUi::urlSummary($eFarm).'/vat/">'.s("Général").'</a>';
			$h .= '<a class="tab-item'.($selectedTab === 'journal-sell' ? ' selected' : '').'" data-tab="journal-sell" href="'.\company\CompanyUi::urlSummary($eFarm).'/vat/?tab=journal-sell">';
					$h .= '<div class="text-center">';
						$h .= s("Écritures de vente");
						$h .= '<br /><small><span style="font-weight: lighter" class="opacity-75">('.s("TVA collectée").')</span></small>';
					$h .= '</div>';
			$h .= '</a>';
			$h .= '<a class="tab-item'.($selectedTab === 'journal-buy' ? ' selected' : '').'" data-tab="journal-buy" href="'.\company\CompanyUi::urlSummary($eFarm).'/vat/?tab=journal-buy">';
					$h .= '<div class="text-center">';
						$h .= s("Écritures d'achat");
						$h .= '<br /><small><span style="font-weight: lighter" class="opacity-75">('.s("TVA déductible").')</span></small>';
					$h .= '</div>';
			$h .= '</a>';
			$h .= '<a class="tab-item'.($selectedTab === 'check' ? ' selected' : '').'" data-tab="journal-check" href="'.\company\CompanyUi::urlSummary($eFarm).'/vat/?tab=check">'.s("Contrôle").'</a>';
			if($eFinancialYear['vatFrequency'] === \account\FinancialYear::ANNUALLY) {
				$textCerfa = s("Formulaire CA12");
			} else {
				$textCerfa = s("Formulaire CA3");
			}
			$h .= '<a class="tab-item'.($selectedTab === 'cerfa' ? ' selected' : '').'" data-tab="journal-cerfa" href="'.\company\CompanyUi::urlSummary($eFarm).'/vat/?tab=cerfa">'.$textCerfa.'</a>';
		$h .= '</div>';

		return $h;
	}

	public function getGeneralTab(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear): string {

		$h = '<div class="tab-panel selected" data-tab="journal">';

			$h .= '<div class="util-block stick-xs bg-background-light mt-1 mb-1">';

				$h .= '<dl class="util-presentation util-presentation-2">';

					foreach(['siret', 'legalName', 'legalEmail'] as $field) {

						$h .= '<dt>'.\farm\FarmUi::p($field)->label.'</dt>';
						$h .= '<dd>'.encode($eFarm[$field]).'</dd>';

					}

					$h .= '<dt>'.s("Siège social de la ferme").'</dt>';
					$h .= '<dd>'.$eFarm->getLegalAddress('html').'</dd>';

					$h .= '<dt>'.s("Dates d'exercice").'</dt>';
					$h .= '<dd>'.s("{startDate} au {endDate}", ['startDate' => \util\DateUi::numeric($eFinancialYear['startDate']), 'endDate' => \util\DateUi::numeric($eFinancialYear['endDate'])]).'</dd>';

					$h .= '<dt>'.\account\FinancialYearUi::p('hasVat')->label.'</dt>';
					$h .= '<dd>'.($eFinancialYear['hasVat'] ? s("Oui") : s("Non")).'</dd>';

					$h .= '<dt>'.\account\FinancialYearUi::p('vatFrequency')->label.'</dt>';
					$h .= '<dd>';
						$h .= \account\FinancialYearUi::p('vatFrequency')->values[$eFinancialYear['vatFrequency']];
						if($eFinancialYear['vatFrequency'] === \account\FinancialYear::ANNUALLY) {
							$h .= ' - '.s("Formulaire CA12");
						} else {
							$h .= ' - '.s("Formulaire CA3");
						}
					$h .= '</dd>';

					$h .= '<dt>'.\account\FinancialYearUi::p('taxSystem')->label.'</dt>';
					$h .= '<dd>'.\account\FinancialYearUi::p('taxSystem')->values[$eFinancialYear['taxSystem']].'</dd>';

				$h .= '</dl>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getOperationsTab(\farm\Farm $eFarm, string $type, \Collection $cOperation, \Search $search, bool $isSelected): string {

		$h = '<div class="tab-panel'.($isSelected ? ' selected' : '').'" data-tab="journal-'.$type.'">';

		$h .= '<table class="tr-even tr-hover">';

			$h .= '<thead class="thead-sticky">';
				$h .= '<tr>';
					$h .= '<th rowspan="2">';
						$label = s("Date");
						$h .= $search->linkSort('date', $label);
					$h .= '</th>';
					$h .= '<th rowspan="2">'.s("N° compte").'</th>';
					$h .= '<th rowspan="2">';
						$label = s("Pièce comptable");
						$h .= $search->linkSort('document', $label);
					$h .= '</th>';
					$h .= '<th rowspan="2">';
						$label = s("Description");
						$h .= $search->linkSort('description', $label);
					$h .= '</th>';
					$h .= '<th rowspan="2">'.s("Tiers").'</th>';
					$h .= '<th colspan="2" class="text-center">'.s("Taux TVA (%)").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Montant (TTC)").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Montant (HT)").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("TVA (€)").'</th>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<th class="td-min-content text-end rowspaned-center">'.s("déclaré").'</th>';
					$h .= '<th class="td-min-content text-end rowspaned-center">'.s("calculé").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';
				foreach($cOperation as $eOperation) {

					$eOperationInitial = $eOperation['operation'];

					$h .= '<tr class="">';

						$h .= '<td>';
							$h .= \util\DateUi::numeric($eOperationInitial['date']);
						$h .= '</td>';

						$h .= '<td>';
							$h .= '<div class="journal-operation-description" data-dropdown="bottom" data-dropdown-hover="true">';
							if($eOperation['accountLabel'] !== NULL) {
								$h .= encode(trim($eOperation['accountLabel'], '0'));
							} else {
								$h .= encode($eOperation['account']['class'], 8, 0);
							}
							$h .= '</div>';
							$h .= '<div class="dropdown-list bg-primary">';
							$h .= '<span class="dropdown-item">'.encode($eOperation['account']['class']).' '.encode($eOperation['account']['description']).'</span>';
							$h .= '</div>';
						$h .= '</td>';

						$h .= '<td>';
							$h .= '<div class="operation-info">';
								if($eOperationInitial['document'] !== NULL) {
									$h .= '<a href="'.new \journal\JournalUi()->getBaseUrl($eFarm, $eOperationInitial['financialYear']).'&document='.urlencode($eOperationInitial['document']).'" title="'.s("Voir les écritures liées à cette pièce comptable").'">'.encode($eOperationInitial['document']).'</a>';
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

						$calculatedVatRate = round(($eOperation['amount'] / $eOperationInitial['amount']) * 100, 1);
						$h .= '<td class="td-min-content text-end '.($eOperationInitial['vatRate'] !== $calculatedVatRate ? 'color-danger' : '').'">';
							$h .= $calculatedVatRate;
						$h .= '</td>';

						$h .= '<td class="text-end td-min-content highlight-stick-right td-vertical-align-top">';
							$h .= \util\TextUi::money($eOperationInitial['amount'] + $eOperation['amount']);
						$h .= '</td>';

						$h .= '<td class="text-end td-min-content highlight-stick-left td-vertical-align-top">';
							$h .= \util\TextUi::money($eOperationInitial['amount']);
						$h .= '</td>';

						$h .= '<td class="text-end td-min-content highlight-stick-right td-vertical-align-top">';
							$h .= \util\TextUi::money($eOperation['amount']);
						$h .= '</td>';

					$h .= '</tr>';

				}
			$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getCheck(\farm\Farm $eFarm, array $check): string {

		$taxes = $check['taxes'];
		$sales = $check['sales'];
		$deposits = $check['deposits'];

		$hasIncoherence = FALSE;

		$h = '<div class="tab-panel selected" data-tab="check">';
			$h .= '<h3>'.s("Contrôles de cohérence").'</h3>';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead>';
					$h .= '<th class="text-center td-min-content">'.s("Taux").'</th>';
					$h .= '<th class="text-end highlight-stick-right">'.s("Montant (HT)").'</th>';
					$h .= '<th class="text-end highlight-stick-right">'.s("Montant de TVA<br />(calculé)").'</th>';
					$h .= '<th class="text-end highlight-stick-right">'.s("TVA collectée<br />(compte {value})", \account\AccountSetting::COLLECTED_VAT_CLASS).'</th>';
					$h .= '<th class="text-center">'.s("Contrôle de cohérence").'</th>';
				$h .= '</thead>';

				$h .= '<tbody>';

					$totalCalculated = 0;
					$totalCollected = 0;
					foreach($sales as $sale) {

						$collectedVat = $taxes[\account\AccountSetting::COLLECTED_VAT_CLASS][(string)$sale['vatRate']]['amount'] ?? 0;

						$h .= '<tr>';
							$h .= '<th class="text-center td-min-content text-end">'.round($sale['vatRate'], 1).'</th>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($sale['amount'], precision: 0).'</td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($sale['tax'], precision: 0).'</td>';
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($collectedVat, precision: 0).'</td>';
							$h .= '<td class="text-center">';

								if(round($collectedVat) === round($sale['tax'])) {

									$h .= '<span class="color-success">'.\Asset::icon('check').'</span>';

								} else if((round($collectedVat) - round($sale['tax'])) < 2) {

									$h .= '<span class="color-warning">'.\Asset::icon('exclamation-triangle').'</span>';
									$hasIncoherence = TRUE;

								} else {

									$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').'</span>';
									$hasIncoherence = TRUE;

								}
							$h .= '</td>';
						$h .= '</tr>';

						$totalCalculated += $sale['tax'];
						$totalCollected = $collectedVat;
					}

					$h .= '<tr class="row-bold">';
						$h .= '<th class="text-center td-min-content text-end">'.s("Totaux").'</th>';
						$h .= '<td class="text-end highlight-stick-right"></td>';
						$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($totalCalculated, precision: 0).'</td>';
						$h .= '<td class="text-end highlight-stick-right">'.\Asset::icon('1-circle').' '.\util\TextUi::money($totalCollected, precision: 0).'</td>';
						$h .= '<td class="text-center">';
							if(round($totalCollected) === round($totalCalculated)) {

								$h .= '<span class="color-success">'.\Asset::icon('check').'</span>';

							} else if((round($totalCollected) - round($totalCalculated)) < 2) {

								$h .= '<span class="color-warning">'.\Asset::icon('exclamation-triangle').'</span>';
								$hasIncoherence = TRUE;

							} else {

								$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').'</span>';
								$hasIncoherence = TRUE;

							}
						$h .= '</td>';
					$h .= '</tr>';

				$h .= '</tbody>';

			$h .= '</table>';

			if($hasIncoherence) {

				$h .= '<div class="util-warning-outline">'.s("Il y a une incohérence entre les opération de vente et les opérations de TVA enregistrées dans votre journal : la somme de la TVA sur le compte {value} ne correspond pas avec le montant de TVA qui est calculé.", \account\AccountSetting::COLLECTED_VAT_CLASS).'</div>';

			} else {

				$h .= '<div class="util-success">'.s("Félicitations ! Vos écritures de TVA sont cohérentes avec vos écritures de ventes, vous pouvez passer à l'étape suivante.").'</div>';

			}

			$abs44566 = 0;
			$immo44562 = 0;
			foreach($taxes as $account => $taxesByRate) {

				if(mb_strpos($account, \account\AccountSetting::VAT_BUY_CLASS_PREFIX) === FALSE) {
					continue;
				}

				switch(mb_substr($account, 4, 1)) {
					case '2':
						foreach($taxesByRate as $tax) {
							$immo44562 += $tax['amount'];
						}
						break;

					case '6':
						foreach($taxesByRate as $tax) {
							$abs44566 += $tax['amount'];
						}
						break;

				}

			}

			$vatCredit = $deposits[\account\AccountSetting::VAT_CREDIT_CLASS_ACCOUNT];
			$vatDeposit = $deposits[\account\AccountSetting::VAT_DEPOSIT_CLASS_PREFIX];

			$vatBalance = $totalCollected - $abs44566 - $immo44562 - $vatCredit;
			$h .= '<h3>'.s("Récapitulatif").'</h3>';

			$h .= '<table class="vat-summary-table tr-even tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th></th>';
						$h .= '<th class="text-center">'.s("Compte ou calcul").'</th>';
						$h .= '<th class="text-end highlight-stick-right">'.s("Montant").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tr>';
					$h .= '<th>'.s("TVA déductible sur biens et services").'</th>';
					$h .= '<td class="text-center">'.\account\AccountSetting::VAT_BUY_CLASS_ACCOUNT.'</td>';
					$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($abs44566).'</td>';
					$h .= '<td>'.\Asset::icon('2-circle').'</td>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<th>'.s("TVA sur immobilisations").'</th>';
					$h .= '<td class="text-center">'.\account\AccountSetting::VAT_ASSET_CLASS_ACCOUNT.'</td>';
					$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($immo44562).'</td>';
					$h .= '<td>'.\Asset::icon('3-circle').'</td>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<th>'.s("Crédit de TVA à reporter").'</th>';
					$h .= '<td class="text-center">'.\account\AccountSetting::VAT_CREDIT_CLASS_ACCOUNT.'</td>';
					$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($vatCredit).'</td>';
					$h .= '<td>'.\Asset::icon('4-circle').'</td>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<th>';
					if($vatBalance > 0) {
						$h .= s("<b>TVA à payer</b> ou TVA à déduire");
					} else {
						$h .= s("TVA à payer ou <b>TVA à déduire</b>");
					}
					$h .= '</th>';
					$h .= '<td class="text-center">';
						$h .= '+ '.\Asset::icon('1-circle').' - '.\Asset::icon('2-circle').' - '.\Asset::icon('3-circle').' - '.\Asset::icon('4-circle');
					$h .= '</td>';
					$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($vatBalance).'</td>';
					$h .= '<td>'.\Asset::icon('5-circle').'</td>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<th>'.s("Acomptes de TVA").'</th>';
					$h .= '<td class="text-center">'.\account\AccountSetting::VAT_DEPOSIT_CLASS_PREFIX.'</td>';
					$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($vatDeposit).'</td>';
					$h .= '<td>'.\Asset::icon('6-circle').'</td>';
				$h .= '</tr>';

				$h .= '<tr class="row-bold">';
					$h .= '<th>'.s("Total : ");
					if($vatBalance - $vatDeposit < 0) {
						$h .= s("Crédit de TVA");
					} else {
						$h .= s("TVA à payer");
					}
					$h .= '</th>';
					$h .= '<td class="text-center">+ '.\Asset::icon('5-circle').' -  '.\Asset::icon('6-circle').'</td>';
					$h .= '<th class="text-end highlight-stick-right">'.\util\TextUi::money($vatBalance - $vatDeposit).'</th>';
				$h .= '<td></td>';
				$h .= '</tr>';
			$h .= '</table>';

		$h .= '</div>';


		return $h;

	}
}

?>
