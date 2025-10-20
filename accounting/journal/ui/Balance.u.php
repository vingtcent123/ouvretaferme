<?php
namespace journal;

Class BalanceUi {

	public function __construct() {
	}

	public function getTitle(): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("La balance");
			$h .= '</h1>';

			$h .= '<div>';
				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#balance-search")').' class="btn btn-primary">'.\Asset::icon('filter').' '.s("Configurer la synthèse").'</a> ';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getSearch(\Search $search, \account\FinancialYear $eFinancialYear): string {

		$h = '<div id="balance-search" class="util-block-search '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

		$form = new \util\FormUi();
		$url = LIME_REQUEST_PATH;

		$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

		$h .= '<div>';
		$h .= $form->inputGroup($form->addon(s('Du'))
			.$form->date('startDate', $search->get('startDate'), ['placeholder' => s("Début de période"), 'min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']])
			.$form->addon(s('au'))
			.$form->date('endDate', $search->get('endDate'), ['placeholder' => s("Fin de période"), 'min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']]));
			$h .= $form->inputGroup($form->addon(s("Précision du compte en chiffres")).$form->number('precision', $search->get('precision') !== '' ? $search->get('precision') : 3, ['min' => 2, 'max' => 8]));
		$h .= '</div>';
		$h .= '<div class="mb-1">';
			$h .= $form->checkbox('summary', 1, ['checked' => $search->get('summary'), 'callbackLabel' => fn($input) => $input.' '.s("Afficher la synthèse par classe de compte")]);
		$h .= '</div>';
		$h .= '<div>';
			$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
			$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
		$h .= '</div>';

		$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function display(\account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearPrevious, array $balance, array $balancePrevious, \Search $search, array $searches): string {

		$hasPrevious = $eFinancialYearPrevious->notEmpty();
		$classes = array_unique(array_merge(array_keys($balance), array_keys($balancePrevious)));
		asort($classes, SORT_STRING);

		$hasSummary = (bool)$search->get('summary');

		$defaultClass = ['total-debit' => 0.0, 'total-credit' => 0.0, 'current-debit' => 0.0, 'current-credit' => 0, 'previous-debit' => 0, 'previous-credit' => 0];
		$classesTotal = [
			1 => $defaultClass,
			2 => $defaultClass,
			3 => $defaultClass,
			4 => $defaultClass,
			5 => $defaultClass,
			6 => $defaultClass,
			7 => $defaultClass,
		];

		$h = '';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead class="thead-sticky">';
				$h .= '<tr>';
					$h .= '<th rowspan="2" class="td-vertical-align-middle td-min-content">'.s("N° de compte").'</th>';
					$h .= '<th rowspan="2" class="td-vertical-align-middle hide-sm-down">'.s("Libellé").'</th>';
					$h .= '<th colspan="2" class="text-center">'.s("Totaux").'</th>';
					$h .= '<th colspan="2" class="text-center">';
						$h .= s("Soldes exercice {value}", \account\FinancialYearUi::getYear($eFinancialYear));
						if($searches['current']->get('startDate') !== '' and $searches['current']->get('endDate') !== '' ) {
							$h .= '<br /><small>'.s("(du {startDate} au {endDate})", ['startDate' => \util\DateUi::numeric($searches['current']->get('startDate')), 'endDate' => \util\DateUi::numeric($searches['current']->get('endDate'))]).'</small>';
						}
					$h .= '</th>';
					if($hasPrevious) {
						$h .= '<th colspan="2" class="text-center">';
						$h .= s("Soldes exercice {value}", \account\FinancialYearUi::getYear($eFinancialYearPrevious));
						if(array_key_exists('previous', $searches) and $searches['previous']->get('startDate') !== '' and $searches['previous']->get('endDate') !== '' ) {
							$h .= '<br /><small>'.s("(du {startDate} au {endDate})", ['startDate' => \util\DateUi::numeric($searches['previous']->get('startDate')), 'endDate' => \util\DateUi::numeric($searches['previous']->get('endDate'))]).'</small>';
						}
						$h .= '</th>';
					}
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<th class="text-center highlight-stick-right">'.s("Débit").'</th>';
					$h .= '<th class="text-center highlight-stick-left">'.s("Crédit").'</th>';
					$h .= '<th class="text-center highlight-stick-right">'.s("Débit").'</th>';
					$h .= '<th class="text-center highlight-stick-left">'.s("Crédit").'</th>';
					if($hasPrevious) {
						$h .= '<th class="text-center highlight-stick-right">'.s("Débit").'</th>';
						$h .= '<th class="text-center highlight-stick-left">'.s("Crédit").'</th>';
					}
				$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					$totalDebitCurrent = 0;
					$totalCreditCurrent = 0;
					$balanceDebitCurrent = 0;
					$balanceCreditCurrent = 0;
					$totalDebitPrevious = 0;
					$totalCreditPrevious = 0;
					$balanceDebitPrevious = 0;
					$balanceCreditPrevious = 0;

					$lastClass = NULL;

					foreach($classes as $class) {

						$lineCurrent = $balance[$class] ?? ['debit' => 0.0, 'credit' => 0.0];
						$linePrevious = $balancePrevious[$class] ?? ['debit' => 0.0, 'credit' => 0.0];

						if($hasSummary) {

							$classesTotal[(int)mb_substr($class, 0, 1)]['total-debit'] += $lineCurrent['debit'];
							$classesTotal[(int)mb_substr($class, 0, 1)]['total-credit'] += $lineCurrent['credit'];
							$classesTotal[(int)mb_substr($class, 0, 1)]['current-debit'] += max($lineCurrent['debit'] - $lineCurrent['credit'], 0);
							$classesTotal[(int)mb_substr($class, 0, 1)]['current-credit'] += max($lineCurrent['credit'], $lineCurrent['debit'], 0);
							$classesTotal[(int)mb_substr($class, 0, 1)]['previous-debit'] += max($linePrevious['debit'] - $linePrevious['credit'], 0);
							$classesTotal[(int)mb_substr($class, 0, 1)]['previous-credit'] += max($linePrevious['credit'], $linePrevious['debit'], 0);

							if($lastClass !== NULL and $lastClass !== (int)mb_substr($class, 0, 1)) {
								$h .= $this->displaySummary($classesTotal, $lastClass, $hasPrevious);
							}
						}

						$h .= '<tr>';
							$h .= '<td class="text-end">';
								$h .= encode($class);
								if(isset($lineCurrent['accountDetail'])) {
									$h .= '<span class="color-muted font-xs">'.encode($lineCurrent['accountDetail']).'</span>';
								} else if(isset($linePrevious['accountDetail'])) {
									$h .= '<span class="color-muted font-xs">'.encode($linePrevious['accountDetail']).'</span>';
								}
							$h .= '</td>';
							$h .= '<td class="hide-sm-down">'.encode($lineCurrent['label'] ?? $linePrevious['label']).'</td>';

							$h .= '<td class="text-end highlight-stick-right">'.($lineCurrent['debit'] !== 0.0 ? \util\TextUi::money($lineCurrent['debit']) : '').'</td>';
							$h .= '<td class="text-end highlight-stick-left">'.($lineCurrent['credit'] !== 0.0 ? \util\TextUi::money($lineCurrent['credit']) : '').'</td>';

							$h .= '<td class="text-end highlight-stick-right">'.($lineCurrent['debit'] > $lineCurrent['credit'] ? \util\TextUi::money($lineCurrent['debit'] - $lineCurrent['credit']) : '').'</td>';
							$h .= '<td class="text-end highlight-stick-left">'.($lineCurrent['credit'] > $lineCurrent['debit'] ? \util\TextUi::money($lineCurrent['credit'] - $lineCurrent['debit']) : '').'</td>';

							if($hasPrevious) {
								$h .= '<td class="text-end highlight-stick-right">'.($linePrevious['debit'] > $linePrevious['credit'] ? \util\TextUi::money($linePrevious['debit'] - $linePrevious['credit']) : '').'</td>';
								$h .= '<td class="text-end highlight-stick-left">'.($linePrevious['credit'] > $linePrevious['debit'] ? \util\TextUi::money($linePrevious['credit'] - $linePrevious['debit']) : '').'</td>';
							}
						$h .= '</tr>';

						$totalDebitCurrent += $lineCurrent['debit'];
						$totalCreditCurrent += $lineCurrent['credit'];

						if($lineCurrent['debit'] > $lineCurrent['credit']) {
							$balanceDebitCurrent += $lineCurrent['debit'] - $lineCurrent['credit'];
						} else {
							$balanceCreditCurrent += $lineCurrent['credit'] - $lineCurrent['debit'];
						}
						if($linePrevious['debit'] > $linePrevious['credit']) {
							$balanceDebitPrevious += $linePrevious['debit'] - $linePrevious['credit'];
						} else {
							$balanceCreditPrevious += $linePrevious['credit'] - $linePrevious['debit'];
						}

						if($hasSummary) {
							$lastClass = (int)mb_substr($class, 0, 1);
						}

					}

					if($hasSummary) {
						$h .= $this->displaySummary($classesTotal, $lastClass, $hasPrevious);
					}

					$h .= '<tr class="row-bold">';
						$h .= '<td class="hide-sm-down"></td>';
						$h .= '<td class="text-end">'.s("Totaux").'</td>';
						$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($totalDebitCurrent).'</td>';
						$h .= '<td class="text-end highlight-stick-left">'.\util\TextUi::money($totalCreditCurrent).'</td>';
						$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($balanceDebitCurrent).'</td>';
						$h .= '<td class="text-end highlight-stick-left">'.\util\TextUi::money($balanceCreditCurrent).'</td>';
						if($hasPrevious) {
							$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($balanceDebitPrevious).'</td>';
							$h .= '<td class="text-end highlight-stick-left">'.\util\TextUi::money($balanceCreditPrevious).'</td>';
						}
					$h .= '</tr>';

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	private function displaySummary(array $classes, int $class, bool $hasPrevious): string {

		$h = '<tr class="row-bold">';

		$h .= '<td class="hide-sm-down text-end">'.encode($class).'</td>';
		$h .= '<td class="text-start">';
		$h .= match($class) {
			1 => s("Comptes de bilan"),
			2 => s("Comptes d'immobilisation"),
			3 => s("Comptes de stocks et encours"),
			4 => s("Comptes de tiers"),
			5 => s("Comptes financiers"),
			6 => s("Comptes de charges"),
			7 => s("Comptes de produits"),
		};
		$h .= '</td>';
		$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($classes[$class]['total-debit']).'</td>';
		$h .= '<td class="text-end highlight-stick-left">'.\util\TextUi::money($classes[$class]['total-credit']).'</td>';
		$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($classes[$class]['current-debit']).'</td>';
		$h .= '<td class="text-end highlight-stick-left">'.\util\TextUi::money($classes[$class]['current-credit']).'</td>';
		if($hasPrevious) {
			$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($classes[$class]['previous-debit']).'</td>';
			$h .= '<td class="text-end highlight-stick-left">'.\util\TextUi::money($classes[$class]['previous-credit']).'</td>';
		}
		$h .= '</tr>';

		return $h;

	}


}
