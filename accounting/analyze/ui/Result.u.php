<?php

namespace analyze;

Class ResultUi {

	public function __construct() {
		\Asset::css('analyze', 'analyze.css');
		\Asset::js('analyze', 'analyze.js');
	}

	public function get(array $result, \Collection $cAccount): string {

		if(empty($result) === TRUE) {

			$h = '<div class="util-info">';
				$h .= s("Le compte de résultat sera disponible lorsque vous aurez créé des écritures pour cet exercice.");
			$h .= '</div>';

			return $h;
		}

		$h = '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="tr-even td-vertical-top tr-hover">';
	
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Comptes").'</th>';
						$h .= '<th>'.s("Libellé").'</th>';
						$h .= '<th>'.s("Montant").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';
		
				$h .= '<tbody>';
		
					$h .= $this->getByClass(\Setting::get('accounting\productAccountClass'), $result, $cAccount);
					$h .= $this->getByClass(\Setting::get('accounting\chargeAccountClass'), $result, $cAccount);
					$h .= $this->getTotalLine(array_sum(array_column($result, 'credit')) - array_sum(array_column($result, 'debit')));
		
				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	private function getByClass(int $class, array $result, \Collection $cAccount): string {

		$debit = 0;
		$credit = 0;

		$h = '';

		foreach($result as $accountClass => $line) {

			$currentClass = mb_substr($accountClass, 0, 1);
			if((int)$currentClass !== $class) {
				continue;
			}

			$h .= '<tr>';
				$h .= '<td>'.encode($accountClass).'</td>';
				$h .= '<td>'.encode($cAccount->offsetGet($accountClass)['description']).'</td>';
				$h .= '<td class="text-end">'.number_format(abs($line['credit'] - $line['debit']), thousands_separator: ' ').'</td>';
			$h .= '</tr>';

			$debit += $line['debit'];
			$credit += $line['credit'];


		}

		$h .= $this->getSubTotalLine($class, $debit, $credit);

		return $h;

	}

	private function getTotalLine(float $total): string {

		$h = '<tr class="row-highlight row-bold">';
			$h .= '<td>12</td>';
			$h .= '<td class="text-end">'.s("Résultat (Produits - Charges)").'</td>';
			$h .= '<td class="text-end">'.number_format($total, thousands_separator: ' ').'</td>';
		$h .= '</tr>';

		return $h;

	}

	private function getSubTotalLine(int $class, float $debit, float $credit): string {

		$h = '<tr class="row-highlight row-bold">';
		$h .= '<td></td>';
		$h .= '<td class="text-end">'.match($class) {
				\Setting::get('accounting\chargeAccountClass') => s("Total Charges"),
				\Setting::get('accounting\productAccountClass') => s("Total Produits")
			}.'</td>';
		$h .= '<td class="text-end">'.number_format(abs($credit - $debit), thousands_separator: ' ').'</td>';
		$h .= '</tr>';

		return $h;

	}

	public function getByMonth(\company\Company $eCompany, \accounting\FinancialYear $eFinancialYear, \Collection $cOperation): string {

		if($cOperation->empty() === TRUE) {

			$h = '<div class="util-info">';
			$h .= s("Le suivi du résultat sera disponible lorsque vous aurez créé des écritures pour cet exercice.");
			$h .= '</div>';

			return $h;
		}

		$h = '<div class="analyze-chart-table">';
		$h .= $this->getChart($eFinancialYear, $cOperation);
		$h .= $this->getTable($eFinancialYear, $cOperation);
		$h .= '</div>';

		return $h;
	}


	protected function getChart(\accounting\FinancialYear $eFinancialYear, \Collection $cOperation): string {

		\Asset::jsUrl('https://cdn.jsdelivr.net/npm/chart.js');

		$charges = [];
		$products = [];
		$labels = [];
		for(
			$date = date('Y-m', strtotime($eFinancialYear['startDate']));
			$date <= $eFinancialYear['endDate'];
			$date = date("Y-m", strtotime("+1 month", strtotime($date)))
		) {
			$eOperation = $cOperation->offsetExists($date) ? $cOperation->offsetGet($date) : new \journal\Operation();
			$labels[] = \util\DateUi::textual($date.'-01', \util\DateUi::MONTH_YEAR);
			$charges[] = $eOperation['charge'] ?? 0;
			$products[] = $eOperation['product'] ?? 0;
		}

		$h = '<div class="analyze-bar">';
			$h .= '<canvas '.attr('onrender', 'Analyze.createDoubleBar(this, "'.s("Charges").'", '.json_encode($charges).', "'.s("Produits").'", '.json_encode($products).', '.json_encode($labels).')').'</canvas>';
		$h .= '</div>';

		return $h;
	}

	protected function getTable(\accounting\FinancialYear $eFinancialYear, \Collection $cOperation): string {

		$h = '<div class="util-overflow-sm">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>';
							$h .= s("Mois");
						$h .= '</th>';
						$h .= '<th class="text-end">';
							$h .= s("Produits");
						$h .= '</th>';
						$h .= '<th class="text-end">';
							$h .= s("Charges");
						$h .= '</th>';
						$h .= '<th class="text-end">';
							$h .= s("Resultat");
						$h .= '</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					for(
						$date = date('Y-m', strtotime($eFinancialYear['startDate']));
						$date <= $eFinancialYear['endDate'];
						$date = date("Y-m", strtotime("+1 month", strtotime($date)))
					) {

						$eOperation = $cOperation->offsetExists($date) ? $cOperation->offsetGet($date) : new \journal\Operation();

						$h .= '<tr>';
							$h .= '<td>';
								$h .= \util\DateUi::textual($date.'-01', \util\DateUi::MONTH_YEAR);
							$h .= '</td>';
							$h .= '<td class="text-end">';
								$h .= \util\TextUi::money($eOperation['product'] ?? 0);
							$h .= '</td>';
							$h .= '<td class="text-end">';
								$h .= \util\TextUi::money($eOperation['charge'] ?? 0);
							$h .= '</td>';
							$h .= '<td class="text-end">';
								$h .= \util\TextUi::money(($eOperation['product'] ?? 0) - ($eOperation['charge'] ?? 0));
							$h .= '</td>';
						$h .= '</tr>';
					}

				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;
	}

}
