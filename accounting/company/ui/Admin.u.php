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

	public function displayFarms(\Collection $cData, \Collection $cFarm, \Search $search): string {

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
						$h .= '<th class="text-center" rowspan="2">'.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_PRODUCTS, s("Produits"), SORT_DESC).'</th>';
						$h .= '<th class="text-center" colspan="3">'.s("Exercices comptables").'</th>';
						$h .= '<th colspan="3" class="text-center">'.s("Banque").'</th>';
						$h .= '<th class="text-center"  rowspan="2">'.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_CASH_OPERATIONS, s("Opérations<br/>de caisse"), SORT_DESC).'</th>';
						$h .= '<th class="text-center"  rowspan="2">'.s("Rapprochements").'<br />'.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_RECONCILIATION_OK, \Asset::icon('check'), SORT_DESC).' / '.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_RECONCILIATION_KO, \Asset::icon('x'), SORT_DESC).'</th>';
						$h .= '<th colspan="2" class="text-center">'.s("Écritures").'</th>';
						$h .= '<th class="text-center" rowspan="2">'.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_VAT_DECLARATION, s("CA3 <br /> CA12"), SORT_DESC).'</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_FINANCIAL_YEARS, s("Exercices"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_FINANCIAL_YEAR_FEC, s("Imports FEC"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_FINANCIAL_YEAR_DOCUMENTS, s("Documents"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_BANK_ACCOUNTS, s("Comptes"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_BANK_IMPORTS, s("Imports"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_BANK_OPERATIONS, s("Opérations"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_JOURNAL_OPERATIONS, s("Écritures"), SORT_DESC).'</th>';
						$h .= '<th class="text-center">'.$search->linkSort(\data\DataSetting::TYPE_ACCOUNTING_JOURNAL_ASSETS, s("Immos"), SORT_DESC).'</th>';
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
						foreach([
							\data\DataSetting::TYPE_ACCOUNTING_PRODUCTS,
							\data\DataSetting::TYPE_ACCOUNTING_FINANCIAL_YEARS,
							\data\DataSetting::TYPE_ACCOUNTING_FINANCIAL_YEAR_FEC,
							\data\DataSetting::TYPE_ACCOUNTING_FINANCIAL_YEAR_DOCUMENTS,
							\data\DataSetting::TYPE_ACCOUNTING_BANK_ACCOUNTS,
							\data\DataSetting::TYPE_ACCOUNTING_BANK_IMPORTS,
							\data\DataSetting::TYPE_ACCOUNTING_BANK_OPERATIONS,
							\data\DataSetting::TYPE_ACCOUNTING_CASH_OPERATIONS,
							] as $type
						) {
							$h .= '<td class="text-center">'.encode($eFarm['cFarmData'][$cData[$type]['id']]['value'] ?? '-').'</td>';

						}
						$h .= '<td class="text-center">'.encode($eFarm['cFarmData'][$cData[\data\DataSetting::TYPE_ACCOUNTING_RECONCILIATION_OK]['id']]['value'] ?? '-').' / '.encode($eFarm['cFarmData'][$cData[\data\DataSetting::TYPE_ACCOUNTING_RECONCILIATION_KO]['id']]['value'] ?? '-').'</td>';
							$h .= '<td class="text-center">'.encode($eFarm['cFarmData'][$cData[\data\DataSetting::TYPE_ACCOUNTING_JOURNAL_OPERATIONS]['id']]['value'] ?? '-').'</td>';
							$h .= '<td class="text-center">'.encode($eFarm['cFarmData'][$cData[\data\DataSetting::TYPE_ACCOUNTING_JOURNAL_ASSETS]['id']]['value'] ?? '-').'</td>';
						$h .= '<td class="text-center">'.encode($eFarm['cFarmData'][$cData[\data\DataSetting::TYPE_ACCOUNTING_VAT_DECLARATION]['id']]['value'] ?? '-').'</td>';
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
