<?php
namespace company;

Class AdminUi {

	public function __construct() {
		\Asset::css('farm', 'admin.css');
	}

	public function displayStats(array $farms): string {

		$h = '<ul class="util-summarize">';

		foreach($farms as $key => $farm) {

			$h .= '<li>';
				$h .= '<div>';
					$h .= '<h5>'.self::t($key).'</h5>';
					$h .= '<div>'.$farm.'</div>';
				$h .= '</div>';
			$h .= '</li>';

		}

		$h .= '</ul>';

		return $h;

	}

	public function displayFarms(\Collection $cFarm, int $nFarm, int $page): string {

		if($nFarm === 0) {
			return '<div class="util-empty">'.s("Il n'y a aucune ferme à afficher...").'</div>';
		}

		$h = '<div class="util-overflow-xs stick-sm">';

			$h .= '<table class="tr-even farm-admin-table">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="text-center td-min-content">#</th>';
						$h .= '<th class="td-min-content"></th>';
						$h .= '<th>'.s("Nom").'</th>';
						$h .= '<th class="text-center">'.s("Produits").'</th>';
						$h .= '<th class="text-center">'.s("Opérations bancaires").'</th>';
						$h .= '<th class="text-center">'.s("Écritures comptables").'</th>';
						$h .= '<th class="text-center">'.s("Rapprochements<br />({iconValidated}/{iconRejected})", ['iconValidated' => \Asset::icon('check'), 'iconRejected' => \Asset::icon('x')]).'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cFarm as $eFarm) {

					$h .= '<tr id="farm-admin-'.$eFarm['id'].'" class="farm-admin-'.$eFarm['status'].'">';
						$h .= '<td class="text-center">'.$eFarm['id'].'</td>';
						$h .= '<td class="farm-admin-vignette">';
							if($eFarm['vignette'] !== NULL) {
								$h .= \Asset::image(new \media\FarmVignetteUi()->getUrlByElement($eFarm, 's'));
							}
						$h .= '</td>';
						$h .= '<td>';
							$h .= '<a href="/farm/admin/?id='.$eFarm['id'].'">';
								if($eFarm['hasFinancialYears']) {
									$h .= '<span class="font-xl">'.\Asset::icon('calculator-fill').'</span> ';
								}
								$h .= encode($eFarm['name']);
							$h .= '</a>';
						$h .= '</td>';
						$h .= '<td class="text-center">'.encode($eFarm['nProduct']).'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['nCashflow']).'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['nOperation']).'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['cSuggestion'][\preaccounting\Suggestion::VALIDATED]['count'] ?? 0).' / '.encode($eFarm['cSuggestion'][\preaccounting\Suggestion::REJECTED]['count'] ?? 0).'</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		$h .= \util\TextUi::pagination($page, $nFarm / 100);

		return $h;
	}
	public static function t(string $key): string {

		return match($key) {
			'hasAccounting' => s("Précomptabilité"),
			'hasFinancialYears' => s("Comptabilité"),
		};

	}
}
