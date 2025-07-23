<?php
namespace overview;

class PdfUi {

	public function __construct() {

		\Asset::css('pdf', 'pdf.css');

	}

	public static function getBalanceOpeningTitle(): string {

		return s("Bilan d'ouverture");

	}

	public static function getTitle(): string {

		return s("Bilan comptable");

	}

	public static function filenameBalance(\farm\Farm $eFarm): string {

		// TODO SIRET
		return s("{date}-{farm}-bilan-comptable", ['date' => date('Y-m-d'), 'farm' => $eFarm['id']]);

	}
	public static function urlBalance(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear): string {

		return \company\CompanyUi::urlOverview($eFarm).'/statements:pdfBalances';

	}

	public function getSummarizedBalance(array $balance): string {

		$h = '<style>@page {	size: A4; margin: calc(var(--margin-bloc-height) + 2cm) 1cm 1cm; }</style>';

		$h .= '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';

				$h .= '<table id="balance-assets" class="tr-even table-bordered">';

					$h .= '<thead>';
						$h .= '<tr class="row-header row-upper">';
						$h .= '<th class="text-center">'.s("ACTIF").'</th>';
						$h .= '<th class="text-center">'.s("Brut").'</th>';
						$h .= '<th class="text-center">'.s("Amort prov.").'</th>';
						$h .= '<th class="text-center">'.s("Net").'</th>';
						$h .= '<th class="text-center">'.s("% actif").'</th>';
					$h .= '</tr>';

					$h .= new BalanceUi()->displaySubCategoryBody($balance['actif'], s("Total de l'actif"), 'actif');

				$h .= '</table>';

				$h .= '<table id="balance-liabilities" class="tr-even table-bordered">';

					$h .= '<thead>';

						$h .= '<tr class="row-header row-upper">';
							$h .= '<th class="text-center">'.s("PASSIF").'</th>';
							$h .= '<th class="text-center">'.s("Brut").'</th>';
							$h .= '<th class="text-center">'.s("% passif").'</th>';
						$h .= '</tr>';

					$h .= new BalanceUi()->displaySubCategoryBody($balance['passif'], s("Total du passif"), 'passif');

				$h .= '</table>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

}

?>
