<?php
namespace journal;

Class BalanceUi {

	public function __construct() {
	}

	public function getTitle(): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("La balance comptable");
			$h .= '</h1>';

			$h .= '<div>';
				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#balance-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getSearch(\Search $search, \account\FinancialYear $eFinancialYear): string {

		$h = '<div id="balance-search" class="util-block-search '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

		$form = new \util\FormUi();
		$url = LIME_REQUEST_PATH;

		$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

		$startDate = $search->get('startDate');
		if($startDate === '') {
			$startDate = $eFinancialYear['startDate'];
		}
		$endDate = $search->get('endDate');
		if($endDate === '') {
			$endDate = $eFinancialYear['endDate'];
		}

		$h .= '<div>';
			$h .= $form->addon(s("Du")).$form->date('startDate', $startDate, ['placeholder' => s("Début de période"), 'min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']]);
			$h .= $form->addon(s("au")).$form->date('endDate', $endDate, ['placeholder' => s("Fin de période"), 'min' => $eFinancialYear['startDate'], 'max' => $eFinancialYear['endDate']]);
			$h .= $form->addon(s("Précision du compte")).$form->number('precision', $search->get('precision') ?? 3, ['min' => 1, 'max' => 5]).$form->addon(s("chiffres"));
			$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
			$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
		$h .= '</div>';

		$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function display(array $balance): string {

		$h = '';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead class="thead-sticky">';
				$h .= '<tr>';
					$h .= '<th rowspan="2" class="td-vertical-align-middle">'.s("N° de compte").'</th>';
					$h .= '<th rowspan="2" class="td-vertical-align-middle hide-sm-down">'.s("Libellé").'</th>';
					$h .= '<th colspan="2" class="text-center">'.s("Totaux").'</th>';
					$h .= '<th colspan="2" class="text-center">'.s("Soldes").'</th>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<th class="text-center">'.s("Débit").'</th>';
					$h .= '<th class="text-center">'.s("Crédit").'</th>';
					$h .= '<th class="text-center">'.s("Débit").'</th>';
					$h .= '<th class="text-center">'.s("Crédit").'</th>';
				$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				$totalDebit = 0;
				$totalCredit = 0;
				$balanceDebit = 0;
				$balanceCredit = 0;

				foreach($balance as $line) {

					$h .= '<tr>';
						$h .= '<td class="text-end">';
							$h .= encode($line['account']);
							if(isset($line['accountDetail'])) {
								$h .= '<br /><span class="color-muted font-xs">'.encode($line['accountDetail']).'</span>';
							}
						$h .= '</td>';
						$h .= '<td class="hide-sm-down">'.encode($line['label']).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($line['debit']).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($line['credit']).'</td>';
						$h .= '<td class="text-end">'.($line['debit'] > $line['credit'] ? \util\TextUi::money($line['debit'] - $line['credit']) : '').'</td>';
						$h .= '<td class="text-end">'.($line['credit'] > $line['debit'] ? \util\TextUi::money($line['credit'] - $line['debit']) : '').'</td>';
					$h .= '</tr>';

					$totalDebit += $line['debit'];
					$totalCredit += $line['credit'];

					if($line['debit'] > $line['credit']) {
						$balanceDebit += $line['debit'] - $line['credit'];
					} else {
						$balanceCredit += $line['credit'] - $line['debit'];
					}

				}

					$h .= '<tr class="row-bold">';
						$h .= '<td class="hide-sm-down"></td>';
						$h .= '<td class="text-end">'.s("Totaux").'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($totalDebit).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($totalCredit).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($balanceDebit).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($balanceCredit).'</td>';
					$h .= '</tr>';

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}


}
