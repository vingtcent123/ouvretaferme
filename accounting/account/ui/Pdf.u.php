<?php
namespace account;

class PdfUi {

	public function __construct() {


	}

	public function getFilename(\account\FinancialYear $eFinancialYear, string $type): string {

		return $this->getName($eFinancialYear, $type);

	}

	public function getName(\account\FinancialYear $eFinancialYear, string $type): string {

		return match($type) {
			FinancialYearDocumentLib::BALANCE_SHEET => s("bilan-privisoire-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::OPENING => s("bilan-ouverture-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::OPENING_DETAILED => s("bilan-ouverture-detaille-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::CLOSING => s("bilan-cloture-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::CLOSING_DETAILED => s("bilan-cloture-detaille-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::INCOME_STATEMENT => s("compte-de-resultat-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED => s("compte-de-resultat-detaille-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::SIG => s("sig-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::ASSET_AMORTIZATION => s("immobilisations-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
			FinancialYearDocumentLib::ASSET_ACQUISITION => s("acquisitions-{startDate}-{endDate}", ['startDate' => $eFinancialYear['startDate'], 'endDate' => $eFinancialYear['endDate']]),
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
		};

	}

	public static function getHeader(\farm\Farm $eFarm, string $title, \account\FinancialYear $eFinancialYear): string {

		$borderColor = '#D5D5D5';
		$h = '<style>
        html {
          -webkit-print-color-adjust: exact;
          font-family: "Open Sans", sans-serif;
          font-weight: 400;
          color: #212529;
          line-height: 1;
        }
        body.template-pdf {
        	line-height: 1;
        }
				.pdf-document-header {
					display: grid;
					grid-column-gap: 1rem;
					grid-template-columns: 2fr 1fr;
					overflow: hidden;
					margin: 1cm auto;
					border-radius: 0.15cm;
					border: 1px solid '.$borderColor.';
					background-color: #F5F7F5FF;
					width: 19cm;
					height: 3cm;
					font-size: 12px;
					align-content: center;
				}
				.pdf-document-header > div > h3 {
					margin: 0.4cm auto 0.25cm;
				}
				.pdf-document-header > div > h4 {
					margin-bottom: 0.1cm;
				}
				.pdf-document-header > div > * {
					text-align: center;
					margin: auto;
				}
				.pdf-document-header-details > table {
			    width: 100%;
			    line-height: 1.4;
				}
        .td-content {
        	background-color: white;
        	border: 1px solid #d5d5d5;
        	text-align: center;
        	width: 40%;
        }
				.pdf-document-title {
			    align-content: center;
			    font-weight: bold;
			    text-align: center;
			    margin: auto;
			    font-size: 0.6cm;
				}
        </style>';
		$h .= '<div class="pdf-document-header">';

			$h .= '<div>';
				$h .= '<h3 class="pdf-document-title">'.$title.'</h3>';
				$h .= '<h4>'.encode($eFarm['legalName'] ?? $eFarm['name']).'</h4>';
				if($eFarm['siret'] !== NULL) {
					$h .= '<div>'.encode($eFarm['siret']).'</div>';
				}
			$h .= '</div>';

			$h .= '<div class="pdf-document-header-details">';

				$h .= '<table>';
					$h .= '<tr>';
						$h .= '<td style="text-align: end">'.s("Devise").'</td>';
						$h .= '<td class="td-content">'.s("EURO").'</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td></td>';
						$h .= '<td style="text-align: center; font-weight: bold;">'.s("EXERCICE").'</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td style="text-align: end">'.s("Du").'</td>';
						$h .= '<td class="td-content">'.\util\DateUi::numeric($eFinancialYear['startDate'], \util\DateUi::DATE).'</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td style="text-align: end">'.s("Au").'</td>';
						$h .= '<td class="td-content">'.\util\DateUi::numeric($eFinancialYear['endDate'], \util\DateUi::DATE).'</td>';
					$h .= '</tr>';
				$h .= '</table>';

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public static function getFooter(): string {

		$date = \util\DateUi::numeric(date('Y-m-d H:i:s'));

		$h = '<style>
        html {
          -webkit-print-color-adjust: exact;
          font-family: "Open Sans", sans-serif;
          font-weight: 400;
          color: #212529;
          line-height: 1;
        }
        .footer-container {
					display: grid;
					grid-column-gap: 1rem;
					grid-template-columns: 1fr 1fr 1fr;
					margin: 0 auto; 
					width: 18cm; 
					font-size: 12px; 
        }
        a {
        	color: inherit;
        	text-decoration: none;
        }
      </style>';
		$h .= '<div class="footer-container">';
			$h .= '<span>'.$date.'</span>';
			$h .= '<span class="pageNumber" style="text-align: center;"></span>';
			$h .= '<span style="text-align: end;"><a href="'.\Lime::getUrl().'">'.\Lime::getName().'</a></span>';
		$h .= '</div>';

		return $h;
	}


}

?>
