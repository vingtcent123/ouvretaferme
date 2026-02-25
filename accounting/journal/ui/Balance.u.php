<?php
namespace journal;

Class BalanceUi {

	public function __construct() {
	}

	public function getTitle(\farm\Farm $eFarm, \account\FinancialYearDocument $eFinancialYearDocument, string $type): string {


		$h = \farm\FarmUi::getSelectedFinancialYear($eFarm);
		$h .= '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/etats-financiers/" class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("La balance");
			$h .= '</h1>';

			$h .= '<div style="display: flex; gap: 1rem; flex-wrap: wrap;">';

				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#balance-search")').' class="btn btn-primary">'.\Asset::icon('filter').' '.s("Configurer la synthèse").'</a> ';

				$h .= new \account\FinancialYearDocumentUi()->getPdfLink($eFarm, $eFinancialYearDocument, $type);

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getSearch(\Search $search, \account\FinancialYear $eFinancialYear): string {

		$h = '<div id="balance-search" class="util-block-search '.($search->empty(['ids', 'financialYear']) === TRUE ? 'hide' : '').'">';

		$form = new \util\FormUi();
		$url = LIME_REQUEST_PATH;

		$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);

			$h .= '<fieldset>';
				$h .= '<legend>'.s("Période").'</legend>';
				$h .= $form->inputGroup($form->addon(s('Du'))
					.$form->date('startDate', $search->get('startDate'), ['placeholder' => s("Début de période"), 'min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']])
					.$form->addon(s('au'))
					.$form->date('endDate', $search->get('endDate'), ['placeholder' => s("Fin de période"), 'min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']]));
			$h .= '</fieldset>';
			$h .= '<fieldset>';
				$h .= '<legend>'.s("Numéro de compte").'</legend>';
				$h .= $form->text('accountLabel', $search->get('accountLabel'), ['placeholder' => s("Numéro de compte")]);
			$h .= '</fieldset>';
			$h .= '<fieldset>';
				$h .= '<legend>'.s("Précision du numéro de compte").'</legend>';
				$h .= $form->inputGroup($form->number('precision', $search->get('precision') !== '' ? $search->get('precision') : 3, ['min' => 2, 'max' => 8]).$form->addon(s("chiffres")));
			$h .= '</fieldset>';
			$h .= '<fieldset>';
				$h .= '<legend>'.s("Synthèse par compte").'</legend>';
				$h .= $form->select('summary', [
					0 => s("Cacher"),
					1 => s("Afficher"),
				], (int)$search->get('summary'), ['mandatory' => TRUE]);
			$h .= '</fieldset>';
			$h .= '<div class="util-search-submit">';
				$h .= $form->submit(s("Chercher"));
				$h .= '<a href="'.$url.'" class="btn">'.\Asset::icon('x-lg').'</a>';
			$h .= '</div>';

		$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getTabs(\farm\Farm $eFarm, ?string $selectedTab): string {

		$tabs = [
			NULL => s("Balance générale"),
			'customer' => s("Balance clients"),
		];
		if($eFarm['eFinancialYear']->isAccrualAccounting()) {
			$tabs['supplier'] = s("Balance fournisseurs");
		}

		$h = '<div class="tabs-item">';

			foreach($tabs as $key => $translation) {
				$isSelected = ((empty($key) === TRUE and empty($selectedTab)) or $key === $selectedTab);
				$h .= '<a class="tab-item'.($isSelected ? ' selected' : '').'" data-tab="'.$key.'" href="'.\company\CompanyUi::urlJournal($eFarm).'/balance?tab='.encode($key).'">'.$translation.'</a>';

			}

		$h .= '</div>';

		return $h;
	}

	public function displayThirdParty(\farm\Farm $eFarm, \Collection $cOperation, string $type): string {

		if($cOperation->empty()) {
			return '<div class="util-empty">'.s("Il n'y a pas encore d'écriture à afficher").'</div>';
		}

		$h = '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead class="thead-sticky">';
					$h .= '<tr>';

						$h .= '<th>'.s("Numéro de compte").'</th>';
						$h .= '<th>'.s("Tiers").'</th>';
						$h .= '<th class="text-end t-highlight">'.s("Débit").'</th>';
						$h .= '<th class="text-end t-highlight">'.s("Crédit").'</th>';
						$h .= '<th class="text-end t-highlight">'.s("Solde").'</th>';
						$h .= '<th></th>';

					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cOperation as $eOperation) {

					$h .= '<tr name="operation-'.$eOperation['accountLabel'].'">';

						$h .= '<td>';
							$h .= encode($eOperation['accountLabel']);
						$h .= '</td>';

						$h .= '<td>';
							$h .= encode($eOperation['thirdParty']['name']);
						$h .= '</td>';

						$h .= '<td class="text-end t-highlight td-vertical-align-top">';
							$h .= \util\TextUi::money($eOperation[Operation::DEBIT]);
						$h .= '</td>';

						$h .= '<td class="text-end t-highlight td-vertical-align-top">';
							$h .= \util\TextUi::money($eOperation[Operation::CREDIT]);
						$h .= '</td>';

						$h .= '<td class="text-end t-highlight td-vertical-align-top">';
							$h .= \util\TextUi::money($eOperation[Operation::DEBIT] - $eOperation[Operation::CREDIT]);
						$h .= '</td>';

						$h .= '<td class="td-min-content">';
							$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary btn-xs">'.\Asset::icon('gear-fill').'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<div class="dropdown-title">'.encode($eOperation['accountLabel']).' '.encode($eOperation['thirdParty']['name']).'</div>';
									$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?accountLabel='.$eOperation['accountLabel'].'&thirdParty='.$eOperation['thirdParty']['id'].'" class="dropdown-item">'.s("Voir le détail des écritures").'</a>';
								$h .= '</div>';
							$h .= '</div>';
						$h .= '</td>';

					$h .= '</tr>';

				}

				$h .= '</tbody>';

			$h .= '</table>';
		$h .= '</div>';
		return $h;


	}

	public function getPdfTHead(string $header, \account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearPrevious): string {

		$h = $header;
		$h .= '<tr>';
			$h .= '<th rowspan="2" class="td-vertical-align-middle td-min-content text-center">'.s("Numéro<br />de compte").'</th>';
			$h .= '<th rowspan="2" class="td-vertical-align-middle">'.s("Libellé").'</th>';
			$h .= '<th colspan="2" class="text-center">'.s("Totaux").'</th>';
			$h .= '<th colspan="2" class="text-center">'.s("Soldes").'</th>';
			if($eFinancialYearPrevious->notEmpty()) {
				$h .= '<th class="text-center" rowspan="2">';
					$h .= s("Variation");
				$h .= '</th>';
			}
		$h .= '</tr>';
		$h .= '<tr>';
			$h .= '<th class="text-center" style="border: 0;">'.s("Débit").'</th>';
			$h .= '<th class="text-center">'.s("Crédit").'</th>';
			$h .= '<th class="text-center">';
				$h .= s("Exercice {value}", $eFinancialYear->getLabel());
			$h .= '</th>';
			if($eFinancialYearPrevious->notEmpty()) {
				$h .= '<th class="text-center" style="border-right: none;">';
					$h .= s("Exercice {value}", $eFinancialYearPrevious->getLabel());
				$h .= '</th>';
			}
		$h .= '</tr>';

		return $h;

	}

	public function getTBody(\account\FinancialYear $eFinancialYearPrevious, array $balance, array $balancePrevious, \Search $search, string $for): string {

		$hasPrevious = $eFinancialYearPrevious->notEmpty();
		$classes = array_unique(array_merge(array_keys($balance), array_keys($balancePrevious)));
		asort($classes, SORT_STRING);

		$hasSummary = (bool)$search->get('summary');

		$totalDebitCurrent = 0;
		$totalCreditCurrent = 0;
		$totalDebitPrevious = 0;
		$totalCreditPrevious = 0;

		$lastClass = NULL;

		$h = '';

		foreach($classes as $class) {

			$lineCurrent = $balance[$class] ?? ['debit' => 0.0, 'credit' => 0.0];
			$linePrevious = $balancePrevious[$class] ?? ['debit' => 0.0, 'credit' => 0.0];

			if($hasSummary) {

				if($lastClass !== NULL and $lastClass !== (int)mb_substr($class, 0, 1)) {
					$h .= $this->displaySummary($this->getSumByClass($lastClass, $balance, $balancePrevious), $lastClass, $hasPrevious);
				}
			}

			$h .= '<tr>';
				$h .= '<td class="text-end">';
					$h .= encode($lineCurrent['account'] ?? $linePrevious['account']);
				$h .= '</td>';
				$h .= '<td class="text-center">';
					$h .= '<span class="color-muted font-xs">'.encode($lineCurrent['accountDetail'] ?? $linePrevious['accountDetail'] ?? '').'</span>';
				$h .= '</td>';
				$h .= '<td class="hide-sm-down">'.encode($lineCurrent['accountLabel'] ?? $linePrevious['accountLabel']).'</td>';

				$h .= '<td class="text-end t-highlight">'.($lineCurrent['debit'] !== 0.0 ? \util\TextUi::money($lineCurrent['debit']) : '').'</td>';
				$h .= '<td class="text-end t-highlight">'.($lineCurrent['credit'] !== 0.0 ? \util\TextUi::money($lineCurrent['credit']) : '').'</td>';

				$h .= '<td class="text-end t-highlight">'.($lineCurrent['debit'] > $lineCurrent['credit'] ? \util\TextUi::money($lineCurrent['debit'] - $lineCurrent['credit']) : '').'</td>';
				$h .= '<td class="text-end t-highlight">'.($lineCurrent['credit'] > $lineCurrent['debit'] ? \util\TextUi::money($lineCurrent['credit'] - $lineCurrent['debit']) : '').'</td>';

				if($hasPrevious) {
					$h .= '<td class="text-end t-highlight">'.($linePrevious['debit'] > $linePrevious['credit'] ? \util\TextUi::money($linePrevious['debit'] - $linePrevious['credit']) : '').'</td>';
					$h .= '<td class="text-end t-highlight">'.($linePrevious['credit'] > $linePrevious['debit'] ? \util\TextUi::money($linePrevious['credit'] - $linePrevious['debit']) : '').'</td>';
				}
			$h .= '</tr>';

			$totalDebitCurrent += $lineCurrent['debit'];
			$totalCreditCurrent += $lineCurrent['credit'];

			$totalDebitPrevious += $linePrevious['debit'];
			$totalCreditPrevious += $linePrevious['credit'];

			if($hasSummary) {
				$lastClass = (int)mb_substr($class, 0, 1);
			}

		}

		if($hasSummary) {
			$h .= $this->displaySummary($this->getSumByClass($lastClass, $balance, $balancePrevious), $lastClass, $hasPrevious);
		}

		$h .= '<tr class="tr-bold">';
			$h .= '<td></td>';
			$h .= '<td></td>';
			$h .= '<td class="text-end hide-sm-down">'.s("Totaux").'</td>';
			$h .= '<td class="text-end t-highlight">'.\util\TextUi::money($totalDebitCurrent).'</td>';
			$h .= '<td class="text-end t-highlight">'.\util\TextUi::money($totalCreditCurrent).'</td>';
			$h .= '<td class="text-end t-highlight">'.($totalDebitCurrent > $totalCreditCurrent ? \util\TextUi::money(round($totalDebitCurrent - $totalCreditCurrent, 2)) : '').'</td>';
			$h .= '<td class="text-end t-highlight">'.($totalCreditCurrent > $totalDebitCurrent ? \util\TextUi::money(round($totalCreditCurrent - $totalDebitCurrent, 2)) : '').'</td>';
			if($hasPrevious) {
				$h .= '<td class="text-end t-highlight">'.($totalDebitPrevious > $totalCreditPrevious ? \util\TextUi::money(round($totalDebitPrevious - $totalCreditPrevious, 2)) : '').'</td>';
				$h .= '<td class="text-end t-highlight">'.($totalCreditPrevious > $totalDebitPrevious ? \util\TextUi::money(round($totalCreditPrevious - $totalDebitPrevious, 2)) : '').'</td>';
			}
		$h .= '</tr>';

		return $h;
	}

	private function getSumByClass(int $class, array $balance, array $balancePrevious): array {

		$total = [
			'totalDebit' => 0,
			'totalCredit' => 0,
			'totalDebitPrevious' => 0,
			'totalCreditPrevious' => 0,
		];

		foreach($balance as $currentClass => $values) {
			if((int)mb_substr($currentClass, 0, 1) !== $class) {
				continue;
			}
			$total['totalDebit'] += $values['debit'] ?? 0;
			$total['totalCredit'] += $values['credit'] ?? 0;
		}

		foreach($balancePrevious as $currentClass => $values) {
			if((int)mb_substr($currentClass, 0, 1) !== $class) {
				continue;
			}
			$total['totalDebitPrevious'] += $values['debit'] ?? 0;
			$total['totalCreditPrevious'] += $values['credit'] ?? 0;
		}

		return [
			'class' => $class,
			'totalDebit' => $total['totalDebit'],
			'totalCredit' => $total['totalCredit'],
			'balanceDebit' => $total['totalDebit'] > $total['totalCredit'] ? $total['totalDebit'] - $total['totalCredit'] : 0,
			'balanceCredit' => $total['totalCredit'] > $total['totalDebit'] ? $total['totalCredit'] - $total['totalDebit'] : 0,
			'balanceDebitPrevious' => $total['totalDebitPrevious'] > $total['totalCreditPrevious'] ? $total['totalDebitPrevious'] - $total['totalCreditPrevious'] : 0,
			'balanceCreditPrevious' => $total['totalCreditPrevious'] > $total['totalDebitPrevious'] ? $total['totalCreditPrevious'] - $total['totalDebitPrevious'] : 0,
		];
	}

	public function getPdfTBody(\account\FinancialYear $eFinancialYearPrevious, array $balance, array $balancePrevious, string $type): string {

		$hasPrevious = $eFinancialYearPrevious->notEmpty();
		$classes = array_unique(array_merge(array_keys($balance), array_keys($balancePrevious)));
		asort($classes, SORT_STRING);

		$hasSummary = FALSE;

		$totalDebitCurrent = 0;
		$totalCreditCurrent = 0;

		$totalDebitPrevious = 0;
		$totalCreditPrevious = 0;

		$h = '';
		$lastClass = NULL;

		foreach($classes as $class) {

			$lineCurrent = $balance[$class] ?? ['debit' => 0.0, 'credit' => 0.0];
			$linePrevious = $balancePrevious[$class] ?? ['debit' => 0.0, 'credit' => 0.0];

			if($hasSummary) {

				if($lastClass !== NULL and $lastClass !== (int)mb_substr($class, 0, 1)) {
					$h .= $this->displaySummaryPdf($this->getSumByClass($lastClass, $balance, $balancePrevious), $hasPrevious);
				}
			}

			$h .= '<tr>';

				if($type === \account\FinancialYearDocumentLib::BALANCE) {
					$h .= '<td class="text-end">';
						$h .= encode($lineCurrent['account'] ?? $linePrevious['account']);
					$h .= '</td>';
				} else {
					$h .= '<td class="text-end">';
						$h .= encode($lineCurrent['accountDetail'] ?? $linePrevious['accountDetail']);
					$h .= '</td>';
				}

				$h .= '<td>'.encode($lineCurrent['accountLabel'] ?? $linePrevious['accountLabel']).'</td>';

				$h .= '<td class="text-end">'.($lineCurrent['debit'] !== 0.0 ? \util\TextUi::money($lineCurrent['debit'], precision: 0) : '').'</td>';
				$h .= '<td class="text-end">'.($lineCurrent['credit'] !== 0.0 ? \util\TextUi::money($lineCurrent['credit'], precision: 0) : '').'</td>';

				if(in_array((int)mb_substr($class, 0, 1), \account\AccountSetting::ASSET_CLASSES)) {
					$calculatedBalanceCurrent = $lineCurrent['debit'] - $lineCurrent['credit'];
				} else {
					$calculatedBalanceCurrent = $lineCurrent['credit'] - $lineCurrent['debit'];
				}
				$h .= '<td class="text-end">'.\util\TextUi::money($calculatedBalanceCurrent, precision: 0).'</td>';

				if($hasPrevious) {
					if(in_array((int)mb_substr($class, 0, 1), \account\AccountSetting::ASSET_CLASSES)) {
						$calculatedBalancePrevious = $linePrevious['debit'] - $linePrevious['credit'];
					} else {
						$calculatedBalancePrevious = $linePrevious['credit'] - $linePrevious['debit'];
					}
					$h .= '<td class="text-end">'.\util\TextUi::money($calculatedBalancePrevious, precision: 0).'</td>';
					$h .= '<td class="text-center">';
						if(
							($calculatedBalancePrevious > 0 and $calculatedBalanceCurrent > 0) or
							($calculatedBalancePrevious < 0 and $calculatedBalanceCurrent < 0)
						) {
							$variation = round(($calculatedBalanceCurrent - $calculatedBalancePrevious) / abs($calculatedBalancePrevious) * 100);
							if($variation !== 0.0) {
								if($variation > 0) {
									$h .= '+';
								}
								$h .= round(($calculatedBalanceCurrent - $calculatedBalancePrevious) / abs($calculatedBalancePrevious) * 100).'%';
							} else {
								$h .= '=';
							}
						}
					$h .='</td>';
				}
			$h .= '</tr>';

			$totalDebitCurrent += $lineCurrent['debit'];
			$totalCreditCurrent += $lineCurrent['credit'];

			$totalDebitPrevious += $linePrevious['debit'];
			$totalCreditPrevious += $linePrevious['credit'];

			if($hasSummary) {
				$lastClass = (int)mb_substr($class, 0, 1);
			}

		}
		if($hasSummary) {
			$h .= $this->displaySummaryPdf($this->getSumByClass($lastClass, $balance, $balancePrevious), $hasPrevious);
		}

		$h .= '<tr>';
			$h .= '<td></td>';
			$h .= '<td><b>'.s("Totaux").'<b></td>';
			$h .= '<td class="text-end"><b>'.\util\TextUi::money($totalDebitCurrent, precision: 0).'<b></td>';
			$h .= '<td class="text-end"><b>'.\util\TextUi::money($totalCreditCurrent, precision: 0).'<b></td>';
			$balanceTotalCurrent = round($totalDebitCurrent - $totalCreditCurrent) === 0.0 ? 0.0 : round($totalDebitCurrent - $totalCreditCurrent);
			$h .= '<td class="text-end"><b>'.\util\TextUi::money($balanceTotalCurrent).'<b></td>';
			if($hasPrevious) {
				$balanceTotalPrevious = round($totalDebitPrevious - $totalCreditPrevious) === 0.0 ? 0.0 : round($totalDebitPrevious - $totalCreditPrevious);
				$h .= '<td class="text-end"><b>'.\util\TextUi::money($balanceTotalPrevious).'<b></td>';
					$h .= '<td class="text-end">';
						if(
							($balanceTotalPrevious > 0 and $balanceTotalCurrent > 0) or
							($balanceTotalPrevious < 0 and $balanceTotalCurrent < 0)
						) {
							$h .= round(($balanceTotalCurrent - $balanceTotalPrevious) / abs($balanceTotalPrevious) * 100, 2).'%';
						}
					$h .='</td>';
			}
		$h .= '</tr>';

		return $h;
	}

	public function list(\account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearPrevious, array $balance, array $balancePrevious, \Search $search, array $searches): string {

		$h = '';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead class="thead-sticky">';
				$h .= '<tr>';
					$h .= '<th rowspan="2" colspan="2" class="td-vertical-align-middle td-min-content">'.s("Numéro de compte").'</th>';
					$h .= '<th rowspan="2" class="td-vertical-align-middle hide-sm-down">'.s("Libellé").'</th>';
					$h .= '<th colspan="2" class="text-center">'.s("Totaux").'</th>';
					$h .= '<th colspan="2" class="text-center">';
						$h .= s("Soldes exercice {value}", $eFinancialYear->getLabel());
						if($searches['current']->get('startDate') !== '' and $searches['current']->get('endDate') !== '' ) {
							$h .= '<br /><small>'.s("(du {startDate} au {endDate})", ['startDate' => \util\DateUi::numeric($searches['current']->get('startDate')), 'endDate' => \util\DateUi::numeric($searches['current']->get('endDate'))]).'</small>';
						}
					$h .= '</th>';
					if($eFinancialYearPrevious->notEmpty()) {
						$h .= '<th colspan="2" class="text-center">';
						$h .= s("Soldes exercice {value}", $eFinancialYearPrevious->getLabel());
						if(array_key_exists('previous', $searches) and $searches['previous']->get('startDate') !== '' and $searches['previous']->get('endDate') !== '' ) {
							$h .= '<br /><small>'.s("(du {startDate} au {endDate})", ['startDate' => \util\DateUi::numeric($searches['previous']->get('startDate')), 'endDate' => \util\DateUi::numeric($searches['previous']->get('endDate'))]).'</small>';
						}
						$h .= '</th>';
					}
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<th class="text-center t-highlight">'.s("Débit").'</th>';
					$h .= '<th class="text-center t-highlight">'.s("Crédit").'</th>';
					$h .= '<th class="text-center t-highlight">'.s("Débit").'</th>';
					$h .= '<th class="text-center t-highlight">'.s("Crédit").'</th>';
					if($eFinancialYearPrevious->notEmpty()) {
						$h .= '<th class="text-center t-highlight">'.s("Débit").'</th>';
						$h .= '<th class="text-center t-highlight">'.s("Crédit").'</th>';
					}
				$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					$h .= $this->getTBody($eFinancialYearPrevious, $balance, $balancePrevious, $search, 'web');

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	private function displaySummary(array $classes, bool $hasPrevious): string {

		$h = '<tr class="tr-bold">';

		$h .= '<td class="hide-sm-down text-end">'.encode($classes['class']).'</td>';
		$h .= '<td class="text-start">';
		$h .= match($classes['class']) {
			1 => s("Comptes de capitaux"),
			2 => s("Comptes d'immobilisation"),
			3 => s("Comptes de stocks et encours"),
			4 => s("Comptes de tiers"),
			5 => s("Comptes financiers"),
			6 => s("Comptes de charges"),
			7 => s("Comptes de produits"),
		};
		$h .= '</td>';
		$h .= '<td class="text-end t-highlight">'.\util\TextUi::money($classes['totalDebit']).'</td>';
		$h .= '<td class="text-end t-highlight">'.\util\TextUi::money($classes['totalCredit']).'</td>';
		$h .= '<td class="text-end t-highlight">'.($classes['balanceDebit'] ? \util\TextUi::money($classes['balanceDebit']) : '').'</td>';
		$h .= '<td class="text-end t-highlight">'.($classes['balanceCredit'] ? \util\TextUi::money($classes['balanceCredit']) : '').'</td>';
		if($hasPrevious) {
			$h .= '<td class="text-end t-highlight">'.($classes['balanceDebitPrevious'] ? \util\TextUi::money($classes['balanceDebitPrevious']) : '').'</td>';
			$h .= '<td class="text-end t-highlight">'.($classes['balanceCreditPrevious'] ? \util\TextUi::money($classes['balanceCreditPrevious']) : '').'</td>';
		}
		$h .= '</tr>';

		return $h;

	}

	private function displaySummaryPdf(array $classes, bool $hasPrevious): string {

		$h = '<tr>';

			$h .= '<td class="text-end">'.encode($classes['class']).'</td>';
			$h .= '<td></td>';
			$h .= '<td>';
			$h .= match($classes['class']) {
				1 => s("Comptes de capitaux"),
				2 => s("Comptes d'immobilisation"),
				3 => s("Comptes de stocks et encours"),
				4 => s("Comptes de tiers"),
				5 => s("Comptes financiers"),
				6 => s("Comptes de charges"),
				7 => s("Comptes de produits"),
			};
			$h .= '</td>';
			$h .= '<td class="text-end">'.\util\TextUi::money($classes['totalDebit']).'</td>';
			$h .= '<td class="text-end">'.\util\TextUi::money($classes['totalCredit']).'</td>';
			$h .= '<td class="text-end">'.($classes['balanceDebit'] ? \util\TextUi::money($classes['balanceDebit']) : '').'</td>';
			$h .= '<td class="text-end">'.($classes['balanceCredit'] ? \util\TextUi::money($classes['balanceCredit']) : '').'</td>';
			if($hasPrevious) {
				$h .= '<td class="text-end">'.($classes['balanceDebitPrevious'] ? \util\TextUi::money($classes['balanceDebitPrevious']) : '').'</td>';
				$h .= '<td class="text-end">'.($classes['balanceCreditPrevious'] ? \util\TextUi::money($classes['balanceCreditPrevious']) : '').'</td>';
			}
		$h .= '</tr>';

		return $h;

	}


}
