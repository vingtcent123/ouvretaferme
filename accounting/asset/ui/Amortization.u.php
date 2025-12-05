<?php
namespace asset;

Class AmortizationUi {

	public function __construct() {
		\Asset::js('asset', 'asset.js');
		\Asset::css('company', 'company.css');
	}

	private static function getAmortizationLine(\farm\Farm $eFarm, array $amortization): string {

		$isTotalLine = match($amortization['economicMode']) {
			AssetElement::LINEAR, AssetElement::WITHOUT => FALSE,
			default => TRUE,
		};

		if($isTotalLine === TRUE) {

			$class = 'row-header';
			$default = '0.00';

		} else {

			$class = '';
			$default = '';

		}

		if(GET('id', 'int') === $amortization['id']) {
			$class .= 'row-highlight';
		}

		if($amortization['id'] !== NULL and $amortization['id'] !== '') {

			$link = \company\CompanyUi::urlFarm($eFarm).'/immobilisation/'.$amortization['id'].'/';
			$description = '<a href="'.$link.'">'.encode($amortization['description']).'</a>';

		} else {

			$description = encode($amortization['description']);

		}

		$h = '<tr name="asset-'.$amortization['id'].'" class="'.$class.'">';
			$h .= '<td>'.$description.'</td>';
			$h .= '<td>';
				if($amortization['acquisitionDate'] !== NULL) {
					$h .= \util\DateUi::numeric($amortization['acquisitionDate'], \util\DateUi::DATE);
				}
			$h .= '</td>';

			$h .= '<td class="td-min-content">';
				if($amortization['economicMode'] and $amortization['fiscalMode']) {

					$h .= match($amortization['economicMode']) {
						Asset::LINEAR => 'L',
						Asset::DEGRESSIVE => 'D',
						Asset::WITHOUT => 'S',
						default => '',
					};
					$h .= '/';
					$h .= match($amortization['fiscalMode']) {
						Asset::LINEAR => 'L',
						Asset::DEGRESSIVE => 'D',
						Asset::WITHOUT => 'S',
						default => '',
					};
				}
			$h .= '</td>';
			$h .= '<td>'.encode($amortization['duration']).'</td>';

			$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['acquisitionValue'], $default, 2) .'</td>';

			$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['economic']['startFinancialYearValue'], $default, 2).'</td>';
			$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['economic']['currentFinancialYearAmortization'], $default, 2).'</td>';
			$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['economic']['endFinancialYearValue'], $default, 2).'</td>';

			$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['grossValueDiminution'], $default, 2).'</td>';
			$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['netFinancialValue'], '0.00', 2).'</td>';

			$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['excess']['startFinancialYearValue'], $default, 2).'</td>';
			$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['excess']['currentFinancialYearAmortization'], $default, 2).'</td>';
			$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['excess']['currentFinancialYearRecovery'] ?? 0, $default, 2).'</td>';
			$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['excess']['endFinancialYearValue'], $default, 2).'</td>';

			$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['fiscalNetValue'], '0.00', 2).'</td>';

		$h .= '</tr>';

		return $h;
	}

	public static function addTotalLine(array &$total, array $line): void {

		$total['acquisitionValue'] += $line['acquisitionValue'];
		$total['economic']['startFinancialYearValue'] += $line['economic']['startFinancialYearValue'];
		$total['economic']['currentFinancialYearAmortization'] += $line['economic']['currentFinancialYearAmortization'];
		$total['economic']['endFinancialYearValue'] += $line['economic']['endFinancialYearValue'];
		$total['grossValueDiminution'] += $line['grossValueDiminution'];
		$total['netFinancialValue'] += $line['netFinancialValue'];
		$total['excess']['startFinancialYearValue'] += $line['excess']['startFinancialYearValue'];
		$total['excess']['currentFinancialYearAmortization'] += $line['excess']['currentFinancialYearAmortization'];
		$total['excess']['endFinancialYearValue'] += $line['excess']['endFinancialYearValue'];
		$total['fiscalNetValue'] += $line['fiscalNetValue'];

	}

	public static function getDepreciationTable(\farm\Farm $eFarm, array $amortizations): string {

		$highlightedAssetId = GET('id', 'int');

		$h = '<div class="stick-sm util-overflow-sm">';

			$h .= '<table id="asset-list" class="tr-even td-vertical-top tr-hover table-bordered" '.($highlightedAssetId !== NULL ? ' onrender="DepreciationList.scrollTo('.$highlightedAssetId.');"' : '').'>';

			$h .= '<thead class="thead-sticky">';
				$h .= '<tr class="row-bold">';
					$h .= '<th colspan="4" class="text-center">'.s("Caractéristiques").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Valeur acquisition").'</th>';
					$h .= '<th colspan="3" class="text-center">'.s("Amortissements économiques").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Dimin. de val. brut.").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("VNC fin").'</th>';
					$h .= '<th colspan="4" class="text-center">'.s("Amortissements dérogatoires").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("VNF fin").'</th>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<th class="text-center">'.s("Libellé").'</th>';
					$h .= '<th class="text-center">'.s("Date").'</th>';
					$h .= '<th colspan="2" class="text-center border-bottom">'.s("Mode E/F et durée").'</th>';
					$h .= '<th class="text-center">'.s("Début exercice").'</th>';
					$h .= '<th class="text-center">'.s("Dotation exercice").'</th>';
					$h .= '<th class="text-center">'.s("Fin exercice").'</th>';
					$h .= '<th class="text-center">'.s("Début exercice").'</th>';
					$h .= '<th class="text-center">'.s("Dotation exercice").'</th>';
					$h .= '<th class="text-center">'.s("Reprise exercice").'</th>';
					$h .= '<th class="text-center">'.s("Fin exercice").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$emptyLine = [
				'description' => '',
				'id' => '',
				'acquisitionDate' => NULL,
				'economicMode' => '',
				'fiscalMode' => '',
				'duration' => '',
				'acquisitionValue' => 0,
				'economic' => [
					'startFinancialYearValue' => 0,
					'currentFinancialYearAmortization' => 0,
					'endFinancialYearValue' => 0,
				],
				'grossValueDiminution' => 0,
				'netFinancialValue' => 0,
				'excess' => [
					'startFinancialYearValue' => 0,
					'currentFinancialYearAmortization' => 0,
					'endFinancialYearValue' => 0,
				],
				'fiscalNetValue' => 0,
			];
			$total = $emptyLine;
			$generalTotal = $emptyLine;
			$generalTotal['description'] = s("Totaux");

			$currentAccountLabel = NULL;

			$h .= '<tbody>';

				foreach($amortizations as $amortization) {

					if($currentAccountLabel !== NULL and $amortization['accountLabel'] !== $currentAccountLabel) {

						$h .= self::getAmortizationLine($eFarm, $total);
						self::addTotalLine($generalTotal, $total);
						$total = $emptyLine;

					}
					$currentAccountLabel = $amortization['accountLabel'];
					$total['description'] = $amortization['accountLabel'].' '.$amortization['accountDescription'];

					$h .= self::getAmortizationLine($eFarm, $amortization);
					self::addTotalLine($total, $amortization);

				}
				self::addTotalLine($generalTotal, $total);
				$h .= self::getAmortizationLine($eFarm, $total);
				$h .= self::getAmortizationLine($eFarm, $generalTotal);

			$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;


	}

	public static function p(string $property): \PropertyDescriber {

		$d = \journal\Operation::model()->describer($property, [
			'asset' => s("Immobilisation"),
			'amount' => s("Montant (HT)"),
			'type' => s("Type d'amortissement"),
			'date' => s("Date"),
			'financialYear' => s("Exercice comptable"),
		]);

		switch($property) {

			case 'type':
				$d->values = [
					Amortization::EXCESS => s("Dérogatoire"),
					Amortization::ECONOMIC => s("Économique"),
				];
				break;

		}

		return $d;

	}
}

?>
