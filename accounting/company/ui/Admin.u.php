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

	public function displayFarms(\Collection $cFarm, \Search $search): string {

		if($cFarm->empty()) {
			return '<div class="util-empty">'.s("Il n'y a aucune ferme à afficher...").'</div>';
		}

		$h = '<div class="util-overflow-xs stick-sm">';

			$h .= '<table class="tr-even tr-hover farm-admin-table">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="text-center td-min-content" rowspan="2">#</th>';
						$h .= '<th class="td-min-content" rowspan="2"></th>';
						$h .= '<th rowspan="2">'.s("Nom").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.$search->linkSort('nProduct', s("Produits"), SORT_DESC).'</th>';
						$h .= '<th class="text-center" colspan="3">'.s("Exercices comptables").'</th>';
						$h .= '<th colspan="3" class="text-center">'.s("Banque").'</th>';
						$h .= '<th class="text-center"  rowspan="2">'.$search->linkSort('cash', s("Opérations<br/>de caisse"), SORT_DESC).'</th>';
						$h .= '<th class="text-center"  rowspan="2">'.s("Rapprochements").'<br />'.$search->linkSort('suggestion-validated', \Asset::icon('check'), SORT_DESC).' / '.$search->linkSort('suggestion-rejected', \Asset::icon('x'), SORT_DESC).'</th>';
						$h .= '<th colspan="2" class="text-center">'.s("Écritures").'</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.$search->linkSort('nFinancialYear', s("Exercices"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort('nAccountImport', s("Imports FEC"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort('nFinancialDocument', s("Documents"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort('nBankAccount', s("Comptes"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort('nBankImport', s("Imports"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort('nCashflow', s("Opérations"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort('nOperation', s("Écritures"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort('nAsset', s("Immos"), SORT_DESC).'</th>';
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
						$h .= '<td class="text-center">'.encode($eFarm['nFinancialYear']).'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['nAccountImport']).'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['nFinancialDocument']).'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['nBankAccount']).'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['nBankImport']).'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['nCashflow']).'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['nCash']).'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['suggestion-'.\preaccounting\Suggestion::VALIDATED] ?? 0).' / '.encode($eFarm['suggestion-'.\preaccounting\Suggestion::REJECTED] ?? 0).'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['nOperation']).'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['nAsset']).'</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;
	}
	public static function t(string $key): string {

		return match($key) {
			'hasAccounting' => s("Précomptabilité"),
			'hasFinancialYears' => s("Comptabilité"),
		};

	}
}
