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

	public static function filenameBalance(\company\Company $eCompany): string {

		return s("{date}-{company}-bilan-comptable", ['date' => date('Y-m-d'), 'company' => $eCompany['siret']]);

	}
	public static function urlBalance(\company\Company $eCompany, \accounting\FinancialYear $eFinancialYear): string {

		return \company\CompanyUi::urlOverview($eCompany).'/balance:pdf';

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

					$h .= new BalanceUi()->displaySubCategoryBody($balance['asset'], s("Total de l'actif"));

				$h .= '</table>';

				$h .= '<table id="balance-liabilities" class="tr-even table-bordered">';

					$h .= '<thead>';

						$h .= '<tr class="row-header row-upper">';
							$h .= '<th class="text-center">'.s("PASSIF").'</th>';
							$h .= '<th class="text-center">'.s("Brut").'</th>';
							$h .= '<th class="text-center">'.s("Amort prov.").'</th>';
							$h .= '<th class="text-center">'.s("Net").'</th>';
							$h .= '<th class="text-center">'.s("% passif").'</th>';
						$h .= '</tr>';

					$h .= new BalanceUi()->displaySubCategoryBody($balance['liability'], s("Total du passif"));

				$h .= '</table>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

}

?>
