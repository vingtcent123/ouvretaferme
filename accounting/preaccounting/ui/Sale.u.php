<?php
namespace preaccounting;

Class SaleUi {

	public function toDate(string $fecDate): string {

		if(mb_strlen($fecDate) !== 8) {
			throw new \Exception("Unknown date format.");
		}

		return mb_substr($fecDate, 0, 4).'-'.mb_substr($fecDate, 4, 2).'-'.mb_substr($fecDate, 6, 2);
	}

	public function list(\farm\Farm $eFarm, array $operations, ?int $hasInvoice, \Collection $cInvoice): string {

		$nOperations = count($operations);
		if($nOperations > 100) {
			$operations = array_slice($operations, 0, 100);
		}

		$showEcritLib = count(array_filter($operations, fn($operation) => $operation[AccountingLib::EXTRA_FEC_COLUMN_ORIGIN] === 'register')) > 0;

		$h = '<table class="tr-hover tr-even">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th rowspan="2">'.s("Date").'</th>';
					$h .= '<th colspan="2" class="text-center">'.s("Journal").'</th>';
					$h .= '<th rowspan="2">'.s("Numéro de <br />compte").'</th>';
					$h .= '<th rowspan="2">'.s("Libellé du <br />compte").'</th>';
					$h .= '<th colspan="2" class="text-center">'.s("Pièce justificative").'</th>';
					if($showEcritLib) {
						$h .= '<th rowspan="2">'.s("Libellé de l'écriture").'</th>';
					}
					$h .= '<th rowspan="2" class="highlight-stick-right text-end">'.s("Débit").'</th>';
					$h .= '<th rowspan="2" class="highlight-stick-left text-end">'.s("Crédit").'</th>';
					$h .= '<th rowspan="2">'.s("Date de <br />paiement").'</th>';
					$h .= '<th rowspan="2">'.s("Moyen de <br />paiement").'</th>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<th>'.s("Code").'</th>';
					$h .= '<th>'.s("Libellé").'</th>';
					$h .= '<th>'.s("Numéro").'</th>';
					$h .= '<th>'.s("Date").'</th>';
				$h .= '</tr>';

			$h .= '</thead>';

			$h .= '<tbody>';

				foreach($operations as $operation) {

					$h .= '<tr>';
						$h .= '<td>';
						if($operation[AccountingLib::FEC_COLUMN_DATE]) {
							$h .= \util\DateUi::numeric($this->toDate($operation[AccountingLib::FEC_COLUMN_DATE]));
						}
						$h .= '</td>';
						$h .= '<td>'.encode($operation[AccountingLib::FEC_COLUMN_JOURNAL_CODE]).'</td>';
						$h .= '<td>'.encode($operation[AccountingLib::FEC_COLUMN_JOURNAL_TEXT]).'</td>';
						$h .= '<td>';
							if($operation[AccountingLib::FEC_COLUMN_ACCOUNT_LABEL] === '') {
								$h .= '<span class="color-danger" title="'.s("Information manquante").'"><b>'.\Asset::icon('three-dots').'</b></span>';
							} else {
								$h .= encode($operation[AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]);
							}
						$h .= '</td>';
						$h .= '<td>'.encode($operation[AccountingLib::FEC_COLUMN_ACCOUNT_DESCRIPTION]).'</td>';
						$h .= '<td>';
							if($operation[AccountingLib::EXTRA_FEC_COLUMN_ORIGIN] === 'invoice' and $cInvoice->offsetExists($operation[AccountingLib::FEC_COLUMN_DOCUMENT])) {
								$eInvoice = $cInvoice[$operation[AccountingLib::FEC_COLUMN_DOCUMENT]];
								$url = \farm\FarmUi::urlSellingInvoices($eFarm).'?name='.$eInvoice['document'].'&customer='.urlencode($eInvoice['customer']['name']);
							} else if($operation[AccountingLib::EXTRA_FEC_COLUMN_ORIGIN] === 'sale') {
								$url = \farm\FarmUi::urlSellingSalesAll($eFarm).'?document='.$operation[AccountingLib::FEC_COLUMN_DOCUMENT];
							} else {
								$url = NULL;
							}
							if($url) {
								$h .= '<a href="'.$url.'">';
									$h .= encode($operation[AccountingLib::FEC_COLUMN_DOCUMENT]);
								$h .= '</a>';
							} else {
								$h .= encode($operation[AccountingLib::FEC_COLUMN_DOCUMENT]);
							}
						$h .= '</td>';
						$h .= '<td>';
						if($operation[AccountingLib::FEC_COLUMN_DOCUMENT_DATE]) {
							$h .= \util\DateUi::numeric($this->toDate($operation[AccountingLib::FEC_COLUMN_DOCUMENT_DATE]));
						}
						$h .= '</td>';

						if($showEcritLib) {
							$h .= '<td>';
							if($operation[AccountingLib::FEC_COLUMN_DESCRIPTION]) {
								$h .= encode($operation[AccountingLib::FEC_COLUMN_DESCRIPTION]);
							}
							$h .= '</td>';
						}

						$h .= '<td class="highlight-stick-right text-end">';
						if($operation[AccountingLib::FEC_COLUMN_DEBIT]) {
							$h .= \util\TextUi::money($operation[AccountingLib::FEC_COLUMN_DEBIT]);
						}
						$h .= '</td>';
						$h .= '<td class="highlight-stick-left text-end">';
						if($operation[AccountingLib::FEC_COLUMN_CREDIT]) {
							$h .= \util\TextUi::money($operation[AccountingLib::FEC_COLUMN_CREDIT]);
						}
						$h .= '</td>';
						$h .= '<td>';
						if($operation[AccountingLib::FEC_COLUMN_PAYMENT_DATE]) {
							$h .= \util\DateUi::numeric($this->toDate(($operation[AccountingLib::FEC_COLUMN_PAYMENT_DATE])));
						}
						$h .= '<td>'.encode($operation[AccountingLib::FEC_COLUMN_PAYMENT_METHOD]).'</td>';
					$h .= '</tr>';
				}
			$h .= '</tbody>';

		$h .= '</table>';

		if($nOperations > 100) {
			$h .= '<div class="util-info">';
				if($hasInvoice === NULL) {
					$h .= s("Seules les 100 premières ventes et factures sont affichées.");
				} else if($hasInvoice === 0) {
					$h .= s("Seules les 100 premières ventes non facturées sont affichées.");
				} else if($hasInvoice === 1) {
					$h .= s("Seules les 100 premières factures sont affichées.");
				}
				$h .= '<br/>'.s("Vous pouvez télécharger le fichier {fec} pour consulter l'intégralité des données.", ['fec' => '<span class="util-badge bg-primary">FEC</span>']);
			$h .= '</div>';
		}

		return $h;

	}

}
