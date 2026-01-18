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
					overflow: hidden;
					margin: 0.5cm auto;
					border-radius: 0.15cm;
					border: 1px solid var(--secondary);
					background-color: #20516029;
					width: 19cm;
					padding: .25cm 0 .5cm;
					font-size: 12px;
					align-content: center;
				}
        .td-content {
        	background-color: white;
        	border: 1px solid #d5d5d5;
        	text-align: center;
        	width: 40%;
        }
        .pdf-document-title-container {
			    display: flex;
				  align-items: center;
				  justify-content: center;
				  margin: auto;
        }
				.pdf-farm-subtitle {
			    font-weight: bold;
			    font-size: 0.6cm;
				}
				.pdf-farm-subtitle div {
					font-size: 0.4cm;
					font-weight: normal;
					margin-top: 0.2cm;
				}
				.pdf-farm-title {
					display: flex;
					justify-content: center;
					gap: 0.5cm;
					align-items: center;
				}
				.pdf-farm-header {
					display: flex;
					justify-content: space-between;
					margin-top: 0.5cm;
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
        </style>';

		$date = \util\DateUi::numeric(date('Y-m-d H:i'));

		$h .= '<div class="pdf-document-wrapper">';
			$h .= '<div class="pdf-farm-header">';

				$h .= '<div class="pdf-farm-title">';

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
		$h .= '</div>';

		$h .= '<div class="pdf-document-header">';

			$h .= '<div>';
				$h .= '<div class="text-center">';
					$h .= '<h2 style="font-weight: bold">';
						$h .= $title;
					$h .= '</h2>';
					$h .= '<div>';
						$h .= s("Exercice {exercice} - {startDate} au {endDate}", ['exercice' => $eFinancialYear->getLabel(), 'startDate' => \util\DateUi::numeric($eFinancialYear['startDate'], \util\DateUi::DATE), 'endDate' => \util\DateUi::numeric($eFinancialYear['endDate'], \util\DateUi::DATE)]);
					$h .= '</div>';
				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getPdfPage(\farm\Farm $eFarm, FinancialYear $eFinancialYear, string $type, string $content): string {

		$h = '<table>';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<td>';
						$h .= \account\PdfUi::getHeader($eFarm, new \account\PdfUi()->getTitle($type, $eFinancialYear->isClosed() === FALSE), $eFinancialYear);
					$h .= '</td>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';
				$h .= '<tr>';
					$h .= '<td>';
						$h .= $content;
					$h .= '</td>';
				$h .= '</tr>';

			$h .= '</tbody>';

		$h .= '</table>';

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
