<?php
namespace overview;

class BalanceUi {

	public function __construct() {
		\Asset::css('company', 'company.css');
	}

	public function extractLabelsFromCategories(array $categories): array {

		$accountLabels = [];
		foreach($categories as $subcategories) {
			foreach(array_column($subcategories['categories'], 'accounts') as $labelsList) {
				$accountLabels = array_merge($accountLabels, $labelsList);
			}
		}

		return $accountLabels;
	}

	private function displayLine(string $text, float $value, ?float $amort, float $net, ?float $total): string {

		$h = '<td>'.($total === NULL ? '<span class="ml-1">'.$text.'</span>' : $text).'</td>';
		$h .= '<td class="text-end">'.(new OverviewUi()->number($value, valueIfEmpty: '', decimals: 0)).'</td>';

		if($amort !== NULL) {
			$h .= '<td class="text-end">'.(new OverviewUi()->number(abs($amort), valueIfEmpty: '', decimals: 0)).'</td>';
			$h .= '<td class="text-end">'.(new OverviewUi()->number($net, valueIfEmpty: '', decimals: 0)).'</td>';
		}

		$h .= '<td class="text-center">'.($total ? (new OverviewUi()->number(round($net / $total * 100), valueIfEmpty: '', decimals: 0)) : '').'</td>';

		return $h;

	}

	public function displaySubCategoryBody(array $balance, string $totalText, string $type): string {

		$h = '<tbody>';

			foreach($balance as $line) {

				$class = match($line['type']) {
					'line' => '',
					'subcategory' => 'row-bold',
					'category' => 'row-upper row-emphasis row-bold text-end',
					'total' => 'row-upper row-emphasis row-bold text-end',
				};

				$label = match($line['type']) {
					'line' => $line['label'],
					'subcategory' => $line['label'],
					'category' => s("Total {category}", ['category' => $line['label']]),
					'total' => $totalText,
				};

				if($line['type'] === 'line' and $line['value'] === 0 and $line['valueAmort'] === 0 and $line['net'] === 0) {
					continue;
				}
				$h .= '<tr class="'.$class.'">';
					$h .= $this->displayLine(
						$label,
						$line['value'],
						$type === 'actif' ? $line['valueAmort'] : NULL,
						$line['net'],
						$line['total']
					);
				$h .= '</tr>';
			}

		$h .= '</tbody>';

		return $h;

	}

	public function displaySummarizedBalance(array $balance): string {

		if(empty($balance) === TRUE) {
			return '<div class="util-info">'.s("Il n'y a rien à afficher.").'</div>';
		}

		$h = '<div class="util-overflow-sm">';

			$h .= '<table id="balance-assets" class="tr-even tr-hover table-bordered">';

				$h .= '<thead class="thead-sticky">';

					$h .= '<tr class="row-header row-upper">';
						$h .= '<td class="text-center">'.s("ACTIF").'</td>';
						$h .= '<td class="text-center">'.s("Brut").'</td>';
						$h .= '<td class="text-center">'.s("Amort prov.").'</td>';
						$h .= '<td class="text-center">'.s("Net").'</td>';
						$h .= '<td class="text-center">'.s("% actif").'</td>';
					$h .= '</tr>';

					$h .= $this->displaySubCategoryBody($balance['actif'], s("Total de l'actif"), 'actif');

			$h .= '</table>';

			$h .= '<table id="balance-liabilities" class="table-sticky tr-even tr-hover table-bordered">';

				$h .= '<thead class="thead-sticky">';

					$h .= '<tr class="row-header row-upper">';
						$h .= '<td class="text-center">'.s("PASSIF").'</td>';
						$h .= '<td class="text-center">'.s("Brut").'</td>';
						$h .= '<td class="text-center">'.s("% passif").'</td>';
					$h .= '</tr>';

					$h .= $this->displaySubCategoryBody($balance['passif'], s("Total du passif"), 'passif');

			$h .= '</table>';

		$h .= '</div>';

		return $h;
	}


	public function displayDetailedSubCategoryBody(array $balance, string $type): string {

		$totalText = match($type) {
			'actif' => s("Total de l'actif"),
			'passif' => s("Total du passif"),
		};

		$h = '<tbody>';

		foreach($balance as $line) {

			$class = match($line['type']) {
				'line' => '',
				'subcategory' => 'row-bold',
				'category' => 'row-upper row-emphasis row-bold text-end',
				'total' => 'row-upper row-emphasis row-bold text-end',
			};

			$label = match($line['type']) {
				'line' => $line['accountClass'].' '.$line['label'],
				'subcategory' => $line['label'],
				'category' => s("Total {category}", ['category' => $line['label']]),
				'total' => $totalText,
			};

			$h .= '<tr class="'.$class.'">';
			$h .= $this->displayLine(
				$label,
				$line['value'],
				$type === 'actif' ? $line['valueAmort'] : NULL,
				$line['net'],
				$line['total']
			);
			$h .= '</tr>';
		}

		$h .= '</tbody>';

		return $h;

	}
	public function displayDetailedBalance(array $balance): string {

		if(empty($balance) === TRUE) {
			return '<div class="util-info">'.s("Il n'y a rien à afficher pour le moment.").'</div>';
		}

		$h = '<div class="util-overflow-sm">';

			$h .= '<table id="balance-assets" class="tr-even tr-hover table-bordered">';

				$h .= '<thead class="thead-sticky">';

					$h .= '<tr class="row-header row-upper">';
						$h .= '<td class="text-center">'.s("ACTIF").'</td>';
						$h .= '<td class="text-center">'.s("Brut").'</td>';
						$h .= '<td class="text-center">'.s("Amort prov.").'</td>';
						$h .= '<td class="text-center">'.s("Net").'</td>';
						$h .= '<td class="text-center">'.s("% actif").'</td>';
					$h .= '</tr>';

					$h .= $this->displayDetailedSubCategoryBody($balance['actif'],'actif');

			$h .= '</table>';

			$h .= '<table id="balance-liabilities" class="table-sticky tr-even tr-hover table-bordered">';

				$h .= '<thead class="thead-sticky">';

					$h .= '<tr class="row-header row-upper">';
						$h .= '<td class="text-center">'.s("PASSIF").'</td>';
						$h .= '<td class="text-center">'.s("N").'</td>';
						$h .= '<td class="text-center">'.s("% passif").'</td>';
					$h .= '</tr>';

					$h .= $this->displayDetailedSubCategoryBody($balance['passif'], 'passif');

			$h .= '</table>';

		$h .= '</div>';

		return $h;
	}

	public function displayPdfLink(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, string $type): string {

		$h = '<div class="text-end mb-1">';
			$h .= '<a href="'.\overview\PdfUi::urlBalance($eFarm, $eFinancialYear).'?type='.$type.'" data-ajax-navigation="never" class="btn btn-primary">';
				$h .= \Asset::icon('file-pdf').'&nbsp;'.s("Télécharger en PDF");
			$h .= '</a>';
		$h .= '</div>';

		return $h;
	}
}

?>
