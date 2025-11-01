<?php
namespace journal;

class PdfUi {

	public function __construct() {

		\Asset::css('pdf', 'pdf.css');
		\Asset::css('company', 'company.css');

	}

	public static function filenameJournal(\farm\Farm $eFarm): string {

		return s("{date}-{siret}-journal", ['date' => date('Y-m-d'), 'siret' => $eFarm['siret']]);

	}

	public static function filenameVat(\farm\Farm $eFarm, string $type): string {

		$typeText = $type === 'sell' ? s("ventes") : s("achats");
		return s("{date}-{siret}-tva-{type}", ['date' => date('Y-m-d'), 'siret' => $eFarm['siret'], 'type' => $typeText]);

	}

	public static function filenameVatStatement(\farm\Farm $eFarm): string {

		return s("{date}-{siret}-declaration-tva", ['date' => date('Y-m-d'), 'siret' => $eFarm['siret']]);

	}

	public static function urlJournal(\farm\Farm $eFarm): string {

		return \company\CompanyUi::urlJournal($eFarm).'/operations:pdf';

	}

	public static function getJournalTitle(): string {

		return s("Journal");

	}

	public function getJournal(\Collection $cOperation): string {

		$h = '<style>@page {	size: A4; margin: calc(var(--margin-bloc-height) + 2cm) 1cm 1cm; }</style>';

		$h .= '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';
				$h .= '<table class="">';

					$h .= '<thead>';
						$h .= '<tr class="row-header row-upper">';
							$h .= '<th>'.s("Date de l'écriture").'</th>';
							$h .= '<th>'.s("Pièce comptable").'</th>';
							$h .= '<th colspan="2">'.s("Compte (Libellé et classe)").'</th>';
							$h .= '<th>'.s("Tiers").'</th>';
							$h .= '<th class="text-end">'.s("Débit (D)").'</th>';
							$h .= '<th class="text-end">'.s("Crédit (C)").'</th>';
						$h .= '</tr>';
					$h .= '</thead>';

					$h .= '<tbody>';

					foreach($cOperation as $eOperation) {

						$h .= '<tr class="border-top">';

							$h .= '<td rowspan="2">';
								$h .= \util\DateUi::numeric($eOperation['date']);
							$h .= '</td>';

							$h .= '<td rowspan="2">';
								$h .= encode($eOperation['document']);
							$h .= '</td>';

							$h .= '<td>';
								if($eOperation['accountLabel'] !== NULL) {
									$h .= encode($eOperation['accountLabel']);
								} else {
									$h .= encode(str_pad($eOperation['account']['class'], 8, 0));
								}
							$h .= '</td>';

							$h .= '<td>';
								$h .= encode($eOperation['account']['description']);
							$h .= '</td>';

							$h .= '<td>';
								if($eOperation['thirdParty']->exists() === TRUE) {
									$h .= encode($eOperation['thirdParty']['name']);
								}
							$h .= '</td>';

							$h .= '<td class="text-end">';
								$debitDisplay = match($eOperation['type']) {
									Operation::DEBIT => \util\TextUi::money($eOperation['amount']),
									default => '',
								};
								$h .= $debitDisplay;
							$h .= '</td>';

							$h .= '<td class="text-end">';
								$creditDisplay = match($eOperation['type']) {
									Operation::CREDIT => \util\TextUi::money($eOperation['amount']),
									default => '',
								};
								$h .= $creditDisplay;
							$h .= '</td>';

						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td class="no-border" colspan="5">';
							$h .= encode($eOperation['description']);
							$h .= '</td>';
						$h .= '</tr>';
					}

					$h .= '</tbody>';
				$h .= '</table>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public static function filenameBook(\farm\Farm $eFarm): string {

		return s("{date}-{siret}-grand-livre", ['date' => date('Y-m-d'), 'siret' => $eFarm['siret']]);

	}

	public static function urlBook(\farm\Farm $eFarm): string {

		return \company\CompanyUi::urlJournal($eFarm).'/book:pdf';

	}

	public static function getBookTitle(): string {

		return s("Grand livre");

	}


	public static function getVatDeclarationTitle(): string {

		return s("Déclaration de TVA");

	}

	public static function getVatTitle(string $type): string {

		if($type === \pdf\PdfElement::JOURNAL_TVA_BUY) {
			return s("Journal de TVA - Achats");
		}

		return s("Journal de TVA - Ventes");

	}

	public function getBook(
		\farm\Farm $eFarm,
		\Collection $cOperation,
		\account\FinancialYear $eFinancialYear,
	): string {


		$h = '<style>@page {	size: A4; margin: calc(var(--margin-bloc-height) + 2cm) 1cm 1cm; }</style>';

		if(get_exists('test') === TRUE) {
			$h .= \pdf\PdfUi::getHeader(s("Grand livre"), $eFinancialYear);
		}

		$h .= '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';

				$h .= '<table>';

					$h .= '<thead>';
						$h .= BookUi::getBookTheadContent();
					$h .= '</thead>';

					$h .= BookUi::getBookTbody($eFarm, $cOperation, $eFinancialYear, new \Search());

				$h .= '</table>';

			$h .= '</div>';

		$h .= '</div>';

		if(get_exists('test') === TRUE) {
			$h .= \pdf\PdfUi::getFooter();
		}
		return $h;

	}

}
?>
