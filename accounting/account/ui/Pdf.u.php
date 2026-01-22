<?php
namespace account;

class PdfUi {

	public function getFilename(\account\FinancialYear $eFinancialYear, string $type): string {

		return $this->getName($eFinancialYear, $type);

	}

	public function getName(\account\FinancialYear $eFinancialYear, string $type): string {

		return match($type) {
			FinancialYearDocumentLib::BALANCE_SHEET => s("bilan-provisoire-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::OPENING => s("bilan-ouverture-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::OPENING_DETAILED => s("bilan-ouverture-detaille-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::CLOSING => s("bilan-cloture-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::CLOSING_DETAILED => s("bilan-cloture-detaille-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::INCOME_STATEMENT => s("compte-de-resultat-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED => s("compte-de-resultat-detaille-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::SIG => s("sig-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::ASSET_AMORTIZATION => s("immobilisations-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::ASSET_ACQUISITION => s("acquisitions-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::BALANCE => s("balance-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::BALANCE_DETAILED => s("balance-detaillee-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
		};

	}

	public function getTitle(string $type, bool $isTemporary): string {

		return match($type) {
			FinancialYearDocumentLib::BALANCE_SHEET => s("Bilan provisoire"),
			FinancialYearDocumentLib::OPENING => s("Bilan d'ouverture"),
			FinancialYearDocumentLib::OPENING_DETAILED => s("Bilan d'ouverture détaillé"),
			FinancialYearDocumentLib::CLOSING => s("Bilan de clôture"),
			FinancialYearDocumentLib::CLOSING_DETAILED => s("Bilan de clôture détaillé"),
			FinancialYearDocumentLib::INCOME_STATEMENT => $isTemporary ? s("Compte de résultat provisoire") : s("Compte de résultat"),
			FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED => $isTemporary ? s("Compte de résultat provisoire avec synthèse") : s("Compte de résultat avec synthèse"),
			FinancialYearDocumentLib::SIG => $isTemporary ? s("Soldes intermédiaires de gestion provisoires") : s("Soldes intermédiaires de gestion"),
			FinancialYearDocumentLib::ASSET_AMORTIZATION => $isTemporary ? s("Amortissements provisoires") : s("Amortissements"),
			FinancialYearDocumentLib::ASSET_ACQUISITION => $isTemporary ? s("Acquisitions en cours") : s("Acquisitions"),
			FinancialYearDocumentLib::BALANCE => $isTemporary ? s("Balance provisoire") : s("Balance"),
			FinancialYearDocumentLib::BALANCE_DETAILED => $isTemporary ? s("Balance détaillée provisoire") : s("Balance détaillée"),
		};

	}

	public static function getHeader(\farm\Farm $eFarm, string $title, \account\FinancialYear $eFinancialYear): string {

		$date = \util\DateUi::numeric(date('Y-m-d H:i'));

		$h = '<tr>';
			$h .= '<td colspan="99">';
				$h .= '<div class="pdf-document-wrapper">';

					$h .= '<div class="pdf-farm-title">';

						$h .= '<div style="display: flex; gap: 0.25cm; align-items: center;">';
							if($eFarm['vignette'] !== NULL) {
								if(LIME_ENV === 'dev') {
									$h .= '<img src="https://media.ouvretaferme.org/'.new \media\FarmLogoUi()->getBasenameByHash($eFarm['logo'], 's').'" style="max-height: 1.25cm"/>';
								} else {
									$h .= \farm\FarmUi::getLogo($eFarm, '1.25cm');
								}
							}
							$h .= '<div class="pdf-farm-subtitle">';
								$h .= encode($eFarm['legalName'] ?? $eFarm['name']);
								if($eFarm['siret'] !== NULL) {
									$h .= '<div>'.s("SIRET {value}", $eFarm['siret']).'</div>';
								}
							$h .= '</div>';
						$h .= '</div>';

						$h .= '<div class="pdf-farm-site">';
							$h .= \Asset::image('main', 'logo.png');
							$h .= '<div class="color-muted font-xs">'.s("Généré le {value}", $date).'</div>';
						$h .= '</div>';
					$h .= '</div>';
					$h .= '<div style="background-color: var(--background); border-radius: 0.15cm; text-align: center; padding: 0.5cm 0; margin: 0.25cm 0;">';
						$h .= '<h2 style="font-weight: bold; margin: 0;">';
							$h .= $title;
						$h .= '</h2>';
						$h .= '<div>';
							$h .= s("Exercice {exercice} - {startDate} au {endDate}", ['exercice' => $eFinancialYear->getLabel(), 'startDate' => \util\DateUi::numeric($eFinancialYear['startDate'], \util\DateUi::DATE), 'endDate' => \util\DateUi::numeric($eFinancialYear['endDate'], \util\DateUi::DATE)]);
						$h .= '</div>';
					$h .= '</div>';
				$h .= '</div>';
			$h .= '</td>';
		$h .= '</tr>';
		return $h;

	}

	public function getPdfPage(\farm\Farm $eFarm, FinancialYear $eFinancialYear, string $type, string $content): string {

		$h = '<style>
	@page {
		margin-top: 1cm;
		margin-left: 1cm;
		margin-right: 1cm;
	}
  html {
		margin: 0;
		padding: 0;
    -webkit-print-color-adjust: exact;
    font-family: "Open Sans", sans-serif;
    font-weight: 400;
    color: #212529;
    line-height: 1;
    font-size: 0.25cm !important;
  }
      thead {
      display: table-header-group;
    }
    
    tfoot {
      display: table-footer-group;
    }
    
    table {
      border-collapse: collapse;
    }
    
    body,
    table {
      border: none;
      width: 100%;
    };
    
	.pdf-document-header {
		overflow: hidden;
		margin: 0.5cm auto;
		border-radius: 0.15cm;
		border: 1px solid var(--secondary);
		background-color: #20516029;
		width: 19cm;
		padding: .25cm 0 .5cm;
		font-size: 12px;
		align-content: center;
		text-align: center;
	}
	.pdf-farm-subtitle {
    font-weight: bold;
    font-size: 0.6cm;
    margin-left: 0.2cm;
	}
	.pdf-farm-subtitle div {
		font-size: 0.4cm;
		font-weight: normal;
	}
	.pdf-farm-title {
		display: flex;
		justify-content: space-between;
		gap: 0.5cm;
		align-items: center;
	}
	.pdf-farm-site {
		text-align: end;
	}
	.pdf-farm-site img {
		width: auto;
		height: 0.75cm;
		margin-bottom: 0.125cm;
	}
	.text-center {
		text-align: center;
	}
	.text-end {
		text-align: right;
	}
	thead {
	  display: table-header-group;
	  break-inside: avoid;
	}
	thead > tr > td {
		background-color: white !important;
		color: black;
	}
	
	.pdf-document-wrapper {
		display: grid;
		grid-template-rows: auto 1fr auto;
	}
	
	table.pdf-table-bordered tr:first-child {
		border: 0;
	}
	table.pdf-table-bordered {
		page-break-after: always;
		border-collapse: collapse; 
		margin: 0 auto 1cm;
		max-width: 18.5cm;
	}
	
	table.pdf-table-bordered tbody td,
	table.pdf-table-bordered th {
		padding: 0.1cm 0.2cm;
	}
	
	table.pdf-table-bordered th {
		font-weight: normal;
		vertical-align: bottom;
	}
	
	table.pdf-table-bordered thead tr th:first-child,
	table.pdf-table-bordered tbody tr th:first-child,
	table.pdf-table-bordered tbody tr td:first-child {
		border-left: 1px solid black;
	}
	table.pdf-table-bordered thead tr th:last-child,
	table.pdf-table-bordered tbody tr th:last-child,
	table.pdf-table-bordered tbody tr td:last-child {
		border-right: 1px solid black;
	}
	table.pdf-table-bordered tbody tr:last-child td {
		border-bottom: 1px solid black;
	}
	.pdf-table-bordered thead tr,
	tr.overview_group-title,
	tr.overview_group-total,
	tr.pdf-tr-title {
		background-color: var(--accounting) !important;
		color: white;
	}
	tr.pdf-tr-total-general {
		text-transform: uppercase;
	}
	tr.pdf-tr-total-general > td {
		text-align: right;
	}
	
	table.pdf-table-bordered tr:nth-child(odd) td {
		background-color: #0001;
	}
	
	.pdf-document-content a.nav-item {
		display: none;
	}
	
	tr.pdf-tr-muted {
		font-style: italic;
		color: var(--muted);
	}
	tr.pdf-tr-muted.pdf-tr-ml-1 > td:first-child {
		padding-left: 0.5cm;
	}
	.print-spacer th {
    height: 80mm;
    padding: 0;
    border: none;
  }

</style>';

		$h .= $content;

		return $h;
	}

	public static function getFooter(): string {

		$h = '<div class="footer-container" style="text-align: center; font-size: 0.25cm; width: 100%;">';
			$h .= '<span class="pageNumber"></span> / <span class="totalPages"></span>';
		$h .= '</div>';

		return $h;
	}

}

?>
