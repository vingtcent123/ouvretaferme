<?php

namespace overview;

Class ResultUi {

	public function __construct() {
		\Asset::css('overview', 'analyze.css');
		\Asset::js('overview', 'analyze.js');
	}

	public function getByMonth(\account\FinancialYear $eFinancialYear, \Collection $cOperation): string {

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


	protected function getChart(\account\FinancialYear $eFinancialYear, \Collection $cOperation): string {

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
			$h .= '<canvas '.\attr('onrender', 'Analyze.createDoubleBar(this, "'.s("Charges").'", '.json_encode($charges).', "'.s("Produits").'", '.json_encode($products).', '.json_encode($labels).')').'</canvas>';
		$h .= '</div>';

		return $h;
	}

	protected function getTable(\account\FinancialYear $eFinancialYear, \Collection $cOperation): string {

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
