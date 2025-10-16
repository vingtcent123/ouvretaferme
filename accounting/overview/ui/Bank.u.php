<?php
namespace overview;

class BankUi {

	public function __construct() {
		\Asset::css('overview', 'analyze.css');
		\Asset::js('overview', 'analyze.js');
	}

	public function get(\Collection $ccOperationBank, \Collection $ccOperationCash): string {

		if($ccOperationBank->empty() === TRUE) {

			$h = '<div class="util-info">';
				$h .= s("Le suivi de la trésorerie sera disponible lorsque vous aurez attribué des écritures à vos opérations bancaires pour cet exercice.");
			$h .= '</div>';

			return $h;
		}

		$accountLabelBank = $ccOperationBank->getKeys();
		$selected = first($accountLabelBank);
		$h = '<div class="tabs-h" id="analyze-bank" onrender="'.encode('Lime.Tab.restore(this, "bank-'.encode($selected).'")').'">';

			$h .= '<div class="tabs-item">';
			foreach($accountLabelBank as $accountLabel) {
				$h .= '<a class="tab-item analyze-tab analyze-tab-ban '.($selected === $accountLabelBank ? 'selected' : '').' text-center" data-tab="bank-'.encode($accountLabel).'" onclick="Lime.Tab.select(this)">'.s("Banque<br />{value}", '<div class="analyze-account">'.encode($accountLabel).'</div>').'</a>';
			}
			$accountLabelCash = $ccOperationCash->getKeys();
			foreach($accountLabelCash as $accountLabel) {
				$h .= '<a class="tab-item analyze-tab analyze-tab-ks text-center" data-tab="cash-'.encode($accountLabel).'" onclick="Lime.Tab.select(this)">'.s("Caisse<br />{value}", '<div class="analyze-account">'.encode($accountLabel).'</div>').'</a>';
			}
			$h .= '</div>';

			foreach($ccOperationBank as $accountLabel => $cOperationBank) {
				$h .= '<div class="tab-panel" data-tab="bank-'.encode($accountLabel).'">';

					$h .= '<div class="analyze-chart-table">';

						$h .= $this->getChart($cOperationBank);
						$h .= $this->getTable($cOperationBank);

					$h .= '</div>';
				$h .= '</div>';
			}

			foreach($ccOperationCash as $accountLabel => $cOperationCash) {
				$h .= '<div class="tab-panel" data-tab="cash-'.encode($accountLabel).'">';

					if($cOperationCash->notEmpty()) {

						$h .= '<div class="analyze-chart-table">';

						$h .= $this->getChart($cOperationCash);
						$h .= $this->getTable($cOperationCash);

						$h .= '</div>';

					} else {

						$h .= '<div class="util-info">';
							$h .= s("Il n'y a aucun mouvement de caisse avec le compte {value} pour cet exercice.", encode($accountLabel));
						$h .= '</div>';
					}

				$h .= '</div>';
			}
		$h .= '</div>';

		return $h;
	}

	protected function getValues(\Collection $cOperation): array {

		$credit = [];
		$debit = [];
		$total = [];
		$labels = [];

		foreach($cOperation as $eOperation) {
			$labels[] = \util\DateUi::textual($eOperation['month'], \util\DateUi::MONTH_YEAR);
			$credit[] = $eOperation['credit'];
			$debit[] = $eOperation['debit'];
			$total[] = $eOperation['total'];
		}

		return [[$debit, $credit, $total], $labels];
	}

	protected function getChart(\Collection $cOperation): string {

		\Asset::jsUrl('https://cdn.jsdelivr.net/npm/chart.js');

		[$values, $labels] = $this->getValues($cOperation);

		$h = '<div class="analyze-line">';
			$h .= '<canvas '.\attr('onrender', 'Analyze.create3Lines(this, '.json_encode($labels).', '.json_encode($values).', '.json_encode([s("Recettes"), s("Dépenses"), s("Solde")]).')').'</canvas>';
		$h .= '</div>';

		return $h;
	}

	protected function getTable($cOperation): string {

		$h = '<div class="util-overflow-sm">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>';
							$h .= s("Mois");
						$h .= '</th>';
						$h .= '<th class="text-end">';
							$h .= s("Recettes");
						$h .= '</th>';
						$h .= '<th class="text-end">';
							$h .= s("Dépenses");
						$h .= '</th>';
						$h .= '<th class="text-end">';
							$h .= s("Solde");
						$h .= '</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cOperation as $eOperation) {
					$h .= '<tr>';
						$h .= '<td>';
							$h .= \util\DateUi::textual($eOperation['month'].'-01', \util\DateUi::MONTH_YEAR);
						$h .= '</td>';
						$h .= '<td class="text-end">';
							$h .= \util\TextUi::money($eOperation['debit']);
						$h .= '</td>';
						$h .= '<td class="text-end">';
							$h .= \util\TextUi::money(abs($eOperation['credit']));
						$h .= '</td>';
						$h .= '<td class="text-end">';
							$h .= \util\TextUi::money($eOperation['total']);
						$h .= '</td>';
					$h .= '</tr>';
				}

				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;
	}

}
?>
