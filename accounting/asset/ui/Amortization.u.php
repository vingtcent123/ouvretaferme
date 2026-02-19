<?php
namespace asset;

Class AmortizationUi {

	public function __construct() {
		\Asset::js('asset', 'asset.js');
		\Asset::css('company', 'company.css');
	}

	private static function getAmortizationLine(\farm\Farm $eFarm, array $amortization, bool $showExcessColumns, bool $showGrossValueDiminutionColumn): string {

		$isTotalLine = match($amortization['economicMode']) {
			AssetElement::LINEAR, AssetElement::WITHOUT => FALSE,
			default => TRUE,
		};

		if($isTotalLine === TRUE) {

			$class = 'tr-header';
			$default = '0.00';

		} else {

			$class = '';
			$default = '';

		}

		if(GET('id', 'int') === $amortization['id']) {
			$class .= 'row-highlight';
		}

		if($amortization['id'] !== NULL and $amortization['id'] !== '') {

			$link = \farm\FarmUi::urlConnected($eFarm).'/immobilisation/'.$amortization['id'].'/';
			$description = '<a href="'.$link.'">'.encode($amortization['description']).'</a>';

		} else {

			$description = encode($amortization['description']);

		}

		$h = '<tr name="asset-'.$amortization['id'].'" class="'.$class.'">';
			$h .= '<td>'.$description.'</td>';
			$h .= '<td class="text-center">';
				if($amortization['acquisitionDate'] !== NULL) {
					$h .= \util\DateUi::numeric($amortization['acquisitionDate'], \util\DateUi::DATE);
				}
			$h .= '</td>';
			$h .= '<td class="text-center">';
				if($isTotalLine === FALSE and $amortization['startDate'] !== NULL) {
					$h .= \util\DateUi::numeric($amortization['startDate'], \util\DateUi::DATE);
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
			$h .= '<td class="text-end">'.encode($amortization['duration']).'</td>';

			$h .= '<td class="util-unit text-end">';
				if($isTotalLine) {
					$h .= new AssetUi()->number($amortization['valueStart'], $default, 2) ;
				} else if($amortization['startDate'] < $eFarm['eFinancialYear']['startDate']) {
					$h .= new AssetUi()->number($amortization['acquisitionValue'], $default, 2) ;
				}
			$h .= '</td>';
			$h .= '<td class="util-unit text-end">';
				if($isTotalLine) {
					$h .= new AssetUi()->number($amortization['valueAcquisition'], $default, 2) ;
				} else if($amortization['startDate'] >= $eFarm['eFinancialYear']['startDate']) {
					$h .= new AssetUi()->number($amortization['acquisitionValue'], $default, 2) ;
				}
			$h .= '</td>';

			$h .= '<td class="util-unit text-end">';
				if(round($amortization['economic']['startFinancialYearValue'], 2) > 0) {
					$h .= new AssetUi()->number($amortization['economic']['startFinancialYearValue'], $default, 2);
				}
			$h .= '</td>';
			$h .= '<td class="util-unit text-end">';
				if(round($amortization['economic']['currentFinancialYearAmortization'], 2) > 0) {
					$h .= new AssetUi()->number($amortization['economic']['currentFinancialYearAmortization'], $default, 2);
				}
			$h .= '</td>';
			$h .= '<td class="util-unit text-end">';
				if(round($amortization['economic']['endFinancialYearValue'], 2) > 0) {
					$h .= new AssetUi()->number($amortization['economic']['endFinancialYearValue'], $default, 2);
				}
			$h .= '</td>';

			if($showGrossValueDiminutionColumn) {
				$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['grossValueDiminution'], $default, 2).'</td>';
			}
			$h .= '<td class="util-unit text-end">'.(round($amortization['netFinancialValue'], 2) > 0 ? new AssetUi()->number($amortization['netFinancialValue'], '0.00', 2) : '').'</td>';

			if($showExcessColumns) {
				$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['excess']['startFinancialYearValue'], $default, 2).'</td>';
				$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['excess']['currentFinancialYearAmortization'], $default, 2).'</td>';
				$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['excess']['currentFinancialYearRecovery'] ?? 0, $default, 2).'</td>';
				$h .= '<td class="util-unit text-end">'.new AssetUi()->number($amortization['excess']['endFinancialYearValue'], $default, 2).'</td>';
				$h .= '<td class="util-unit text-end">'.(round($amortization['fiscalNetValue'], 2) > 0 ? new AssetUi()->number($amortization['fiscalNetValue'], '0.00', 2) : '').'</td>';
			}


		$h .= '</tr>';

		return $h;
	}

	public static function addTotalLine(array &$total, array $line, \account\FinancialYear $eFinancialYear): void {

		$isTotalLine = match($line['economicMode']) {
			AssetElement::LINEAR, AssetElement::WITHOUT => FALSE,
			default => TRUE,
		};

		if($isTotalLine) {
			$total['valueAcquisition'] += $line['valueAcquisition'] ?? 0;
			$total['valueStart'] += $line['valueStart'] ?? 0;
		} else if($line['startDate'] >= $eFinancialYear['startDate']) {
			$total['valueAcquisition'] += $line['acquisitionValue'];
		} else if($line['startDate'] < $eFinancialYear['startDate']) {
			$total['valueStart'] += $line['acquisitionValue'];
		}

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

	public static function getPdfTHead(string $header, array $amortizations, ?string $type): string {

		$showExcessColumns = array_find($amortizations, fn($amortization) => $amortization['excess']['currentFinancialYearRecovery'] > 0) !== NULL;
		$showGrossValueDiminutionColumn = array_find($amortizations, fn($amortization) => $amortization['grossValueDiminution'] > 0) !== NULL;

		$h = $header;
		$h .= '<tr class="tr-bold" style="border-bottom: 1px solid black;">';
			$h .= '<th colspan="99" class="text-center">'.match($type) {
				'asset' => s("Immobilisations"),
				'grant' => s("Subventions"),
			}.'</th>';
		$h .= '</tr>';

		$h .= '<tr class="tr-bold">';
			$h .= '<th colspan="5" class="text-center">'.s("Caractéristiques").'</th>';
			$h .= '<th colspan="2" class="text-center">'.s("Valeur").'</th>';
			$h .= '<th colspan="3" class="text-center">'.s("Amortissements économiques").'</th>';
			if($showGrossValueDiminutionColumn) {
				$h .= '<th rowspan="2" class="text-center">'.s("Dimin. de val. brut.").'</th>';
			}
			$h .= '<th rowspan="2" class="text-center">'.s("VNC fin").'</th>';
			if($showExcessColumns) {
				$h .= '<th colspan="4" class="text-center">'.s("Amortissements dérogatoires").'</th>';
				$h .= '<th rowspan="2" class="text-center">'.s("VNF fin").'</th>';
			}
		$h .= '</tr>';
		$h .= '<tr>';
			$h .= '<th class="text-center">'.s("Libellé").'</th>';
			$h .= '<th class="text-center">'.s("Date acquisition").'</th>';
			$h .= '<th class="text-center">'.s("Date mise en service").'</th>';
			$h .= '<th class="text-center">'.s("Mode E/F").'</th>';
			$h .= '<th class="text-center">'.s("Durée").'</th>';
			$h .= '<th class="text-center">'.s("Début exercice").'</th>';
			$h .= '<th class="text-end">'.s("Début").'</th>';
			$h .= '<th class="text-end">'.s("Acquisition").'</th>';
			$h .= '<th class="text-center">'.s("Dotation exercice").'</th>';
			$h .= '<th class="text-center no-border-right">'.s("Cumulé").'</th>';
			if($showExcessColumns) {
				$h .= '<th class="text-center">'.s("Début exercice").'</th>';
				$h .= '<th class="text-center">'.s("Dotation exercice").'</th>';
				$h .= '<th class="text-center">'.s("Reprise exercice").'</th>';
				$h .= '<th class="text-center no-border-right">'.s("Cumulé").'</th>';
			}
		$h .= '</tr>';

		return $h;
	}

	public function getTBody(\farm\Farm $eFarm, array $amortizations): string {

		$showExcessColumns = array_find($amortizations, fn($amortization) => $amortization['excess']['currentFinancialYearRecovery'] > 0) !== NULL;
		$showGrossValueDiminutionColumn = array_find($amortizations, fn($amortization) => $amortization['grossValueDiminution'] > 0) !== NULL;

		$emptyLine = [
			'description' => '',
			'id' => '',
			'acquisitionDate' => NULL,
			'economicMode' => '',
			'fiscalMode' => '',
			'duration' => '',
			'acquisitionValue' => 0,
			// Décomposition de acquisitionValue en : valeur au début de l'exercice et valeur d'acquisition en cours d'exercice
			'valueAcquisition' => 0,
			'valueStart' => 0,
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

		$h = '';

		foreach($amortizations as $amortization) {

			if($currentAccountLabel !== NULL and $amortization['accountLabel'] !== $currentAccountLabel) {

				$h .= self::getAmortizationLine($eFarm, $total, $showExcessColumns, $showGrossValueDiminutionColumn);
				self::addTotalLine($generalTotal, $total, $eFarm['eFinancialYear']);
				$total = $emptyLine;

			}
			$currentAccountLabel = $amortization['accountLabel'];
			$total['description'] = $amortization['accountLabel'].' '.$amortization['accountDescription'];

			$h .= self::getAmortizationLine($eFarm, $amortization, $showExcessColumns, $showGrossValueDiminutionColumn);
			self::addTotalLine($total, $amortization, $eFarm['eFinancialYear']);

		}

		self::addTotalLine($generalTotal, $total, $eFarm['eFinancialYear']);
		$h .= self::getAmortizationLine($eFarm, $total, $showExcessColumns, $showGrossValueDiminutionColumn);
		$h .= self::getAmortizationLine($eFarm, $generalTotal, $showExcessColumns, $showGrossValueDiminutionColumn);

		return $h;
	}

	public function getDepreciationTable(\farm\Farm $eFarm, array $amortizations): string {

		$highlightedAssetId = GET('id', 'int');

		$showExcessColumns = array_find($amortizations, fn($amortization) => $amortization['excess']['currentFinancialYearRecovery'] > 0) !== NULL;
		$showDimValBrut = array_find($amortizations, fn($amortization) => $amortization['grossValueDiminution'] > 0) !== NULL;

		$h = '<div class="stick-sm util-overflow-sm">';

			$h .= '<table id="asset-list" class="tr-even td-vertical-top tr-hover table-bordered" '.($highlightedAssetId !== NULL ? ' onrender="DepreciationList.scrollTo('.$highlightedAssetId.');"' : '').'>';

			$h .= '<thead class="thead-sticky">';
				$h .= '<tr class="tr-bold">';
					$h .= '<th colspan="5" class="text-center">'.s("Caractéristiques").'</th>';
					$h .= '<th colspan="2" class="text-center">'.s("Valeur acquisition").'</th>';
					$h .= '<th colspan="3" class="text-center">'.s("Amortissements économiques").'</th>';
					if($showDimValBrut) {
						$h .= '<th rowspan="2" class="text-center">'.s("Dimin. de val. brut.").'</th>';
					}
					$h .= '<th rowspan="2" class="text-center">'.s("VNC fin").'</th>';
					if($showExcessColumns) {
						$h .= '<th colspan="4" class="text-center">'.s("Amortissements dérogatoires").'</th>';
						$h .= '<th rowspan="2" class="text-center">'.s("VNF fin").'</th>';
					}
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<th class="text-center">'.s("Libellé").'</th>';
					$h .= '<th class="text-center">'.s("Date Acquisition").'</th>';
					$h .= '<th class="text-center">'.s("Date mise en service").'</th>';
					$h .= '<th class="text-center border-bottom">'.s("Mode E/F").'</th>';
					$h .= '<th class="text-center border-bottom">'.s("Durée").'</th>';
					$h .= '<th class="text-center">'.s("Début").'</th>';
					$h .= '<th class="text-center">'.s("Acquisition<br />ou apport").'</th>';
					$h .= '<th class="text-center">'.s("Début exercice").'</th>';
					$h .= '<th class="text-center">'.s("Dotation exercice").'</th>';
					$h .= '<th class="text-center">'.s("Cumulé").'</th>';
					if($showExcessColumns) {
						$h .= '<th class="text-center">'.s("Début exercice").'</th>';
						$h .= '<th class="text-center">'.s("Dotation exercice").'</th>';
						$h .= '<th class="text-center">'.s("Reprise exercice").'</th>';
						$h .= '<th class="text-center">'.s("Cumulé").'</th>';
					}
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';

				$h .= $this->getTBody($eFarm, $amortizations);

			$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = \journal\Operation::model()->describer($property, [
			'asset' => s("Immobilisation"),
			'amount' => s("Montant HT"),
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
