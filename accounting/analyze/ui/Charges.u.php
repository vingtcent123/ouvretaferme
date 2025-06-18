<?php
namespace analyze;

class ChargesUi {

	public function __construct() {
		\Asset::css('analyze', 'analyze.css');
		\Asset::js('analyze', 'analyze.js');
	}

	public function get(\company\Company $eCompany, \accounting\FinancialYear $eFinancialYear, \Collection $cOperation, \Collection $cAccount): string {

		if($cOperation->empty() === TRUE) {

			$h = '<div class="util-info">';
				$h .= s("Le suivi des charges sera disponible lorsque vous aurez attribué des écritures à vos opérations bancaires pour cet exercice.");
			$h .= '</div>';

			return $h;
		}

		$h = '<div class="analyze-chart-table">';
			$h .= $this->getChart($cOperation, $cAccount);
			$h .= $this->getTable($cOperation, $cAccount);
		$h .= '</div>';

		return $h;
	}

	protected function getChart(\Collection $cOperation, \Collection $cAccount): string {

		\Asset::jsUrl('https://cdn.jsdelivr.net/npm/chart.js');

		$total = array_reduce($cOperation->getArrayCopy(), function ($sum, $element) {
			$sum += $element['total'];
			return $sum;
		});

		$values = [];
		$labels = [];
		foreach($cAccount as $eAccount) {
			if($cOperation->offsetExists($eAccount['class']) === FALSE) {
				continue;
			}
			$values[] = 100 * ($cOperation->offsetGet($eAccount['class'])['total'] / $total);
			$labels[] = $this->formatAccountLabel($eAccount);
		}

		$h = '<div class="analyze-pie-canvas">';
			$h .= '<canvas '.attr('onrender', 'Analyze.createPie(this, '.json_encode($values).', '.json_encode($labels).')').'</canvas>';
		$h .= '</div>';

		return $h;
	}

	protected  function formatAccountLabel(\accounting\Account $eAccount): string {
		return encode(mb_ucfirst(mb_strtolower($eAccount['description'])));
	}

	protected function getTable(\Collection $cOperation, \Collection $cAccount): string {

		$h = '<div class="util-overflow-sm">';

			$h .= '<table class="tr-even tr-hover">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>';
							$h .= s("Compte");
						$h .= '</th>';
						$h .= '<th class="text-end">';
							$h .= s("Montant");
						$h .= '</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cAccount as $eAccount) {
					$h .= '<tr>';
						$h .= '<td>';
							$h .= $this->formatAccountLabel($eAccount);
						$h .= '</td>';
						$h .= '<td class="text-end">';
							$h .= \util\TextUi::money($cOperation[$eAccount['class']]['total'] ?? 0);
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
