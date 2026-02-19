<?php
namespace account;

class PdfUi {

	public function __construct() {
		\Asset::css('account', 'pdf.css');
	}
	public function getFilename(\account\FinancialYear $eFinancialYear, string $type): string {

		return $this->getName($eFinancialYear, $type);

	}

	public function getName(\account\FinancialYear $eFinancialYear, string $type): string {

		return match($type) {
			'fec-attestation' => s("attestation-fec"),
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

	public function getHeader(\farm\Farm $eFarm, string $title, \account\FinancialYear $eFinancialYear): string {

		$date = \util\DateUi::numeric(date('Y-m-d H:i'));

		$h = '<tr>';
			$h .= '<td colspan="99">';
				$h .= '<div class="pdf-document-wrapper">';

					$h .= '<div class="pdf-farm-title">';

						$h .= '<div>';
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
					$h .= '<div class="pdf-farm-document-title">';
						$h .= '<h2>';
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

	public static function getFooter(): string {

		$h = '<div class="footer-container" style="text-align: center; font-size: 0.25cm; width: 100%;">';
			$h .= '<span class="pageNumber"></span> / <span class="totalPages"></span>';
		$h .= '</div>';

		return $h;
	}

}

?>
