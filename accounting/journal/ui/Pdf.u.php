<?php
namespace journal;

class PdfUi {

	public function __construct() {

		\Asset::css('pdf', 'pdf.css');
		\Asset::css('company', 'company.css');

	}

	public static function filenameJournal(\farm\Farm $eFarm): string {

		return s("{date}-{company}-journal", ['date' => date('Y-m-d'), 'company' => $eFarm['siret']]);

	}

	public static function filenameVat(\farm\Farm $eFarm, string $type): string {

		$typeText = $type === 'sell' ? s("ventes") : s("achats");
		return s("{date}-{company}-tva-{type}", ['date' => date('Y-m-d'), 'company' => $eFarm['siret'], 'type' => $typeText]);

	}

	public static function filenameVatStatement(\farm\Farm $eFarm): string {

		return s("{date}-{company}-declaration-tva", ['date' => date('Y-m-d'), 'company' => $eFarm['siret']]);

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

		// TODO SIRET eu lieu de ID
		return s("{date}-{company}-grand-livre", ['date' => date('Y-m-d'), 'company' => $eFarm['id']]);

	}

	public static function urlBook(\farm\Farm $eFarm): string {

		return \company\CompanyUi::urlJournal($eFarm).'/book:pdf';

	}

	public static function getBookTitle(): string {

		return s("Grand livre");

	}

	public static function urlVatDeclaration(\farm\Farm $eFarm, VatDeclaration $eVatDeclaration): string {

		return \company\CompanyUi::urlJournal($eFarm).'/vatDeclaration:pdf?id='.$eVatDeclaration['id'];

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

					$h .= BookUi::getBookTbody($eFarm, $cOperation, $eFinancialYear);

				$h .= '</table>';

			$h .= '</div>';

		$h .= '</div>';

		if(get_exists('test') === TRUE) {
			$h .= \pdf\PdfUi::getFooter();
		}
		return $h;

	}

	public function getVat(
		\farm\Farm $eFarm,
		\Collection $cccOperation,
		\account\FinancialYear $eFinancialYear,
	): string {


		$h = '<style>@page {	size: A4; margin: calc(var(--margin-bloc-height) + 2cm) 1cm 1cm; }</style>';

		if(get_exists('test') === TRUE) {
			$h .= \pdf\PdfUi::getHeader(\journal\PdfUi::getVatTitle(\pdf\PdfElement::JOURNAL_TVA_SELL), $eFinancialYear);
		}

		$h .= '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';

				$h .= new VatUi()->getTables($eFarm, $cccOperation, new \Search(), 'pdf');

			$h .= '</div>';

		$h .= '</div>';

		if(get_exists('test') === TRUE) {
			$h .= \pdf\PdfUi::getFooter();
		}
		return $h;

	}


	public function getVatDeclaration(
		\farm\Farm $eFarm,
		\Collection $cOperation,
		\account\FinancialYear $eFinancialYear,
		VatDeclaration $eVatDeclaration,
	): string {

		$regime = \account\FinancialYearUi::p('taxSystem')->values[$eFinancialYear['taxSystem']].' (Déclaration '.strtolower(\account\FinancialYearUi::p('vatFrequency')->values[$eFinancialYear['vatFrequency']]).')';

		$h = '<style>@page {	size: A4; margin: calc(var(--margin-bloc-height) + 2cm) 1cm 1cm; } table {width: inherit; } .row-bordered { border-bottom: 1px solid black; }</style>';

		if(get_exists('test') === TRUE) {
			$h .= \pdf\PdfUi::getHeader(\journal\PdfUi::getVatDeclarationTitle(), $eFinancialYear);
		}

		$h .= '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';

				$h .= '<div class="util-block stick-xs bg-background-light mt-1 mb-1">';

					$h .= '<dl class="util-presentation util-presentation-2">';

						$h .= '<dt>'.s("Exploitation").'</dt>';
						$h .= '<dd>'.encode($eFarm['legalName']).'</dd>';

						$h .= '<dt>'.s("Exercice").'</dt>';
						$h .= '<dd>'.\account\FinancialYearUi::getYear($eFinancialYear).'</dd>';

						$h .= '<dt>'.s("SIRET").'</dt>';
						$h .= '<dd>'.encode($eFarm['siret']).'</dd>';

						$h .= '<dt>'.s("Période").'</dt>';
						$h .= '<dd>'.s("du {startDate} au {endDate}", ['startDate' => \util\DateUi::numeric($eVatDeclaration['startDate']), 'endDate' => \util\DateUi::numeric($eVatDeclaration['endDate'])]).'</dd>';

						$h .= '<dt>'.s("Régime").'</dt>';
						$h .= '<dd>'.$regime.'</dd>';

						$h .= '<dt>'.s("Date de validation").'</dt>';
						$h .= '<dd>'.\util\DateUi::numeric($eVatDeclaration['createdAt'], \util\DateUi::DATE).'</dd>';

						$h .= '<dt></dt>';
						$h .= '<dd></dd>';

						$h .= '<dt>'.s("N° de déclaration").'</dt>';
						$h .= '<dd>'.$eVatDeclaration['id'].'</dd>';

					$h .= '</dl>';
				$h .= '</div>';

				$h .= '<h3>'.s("Résumé").'</h3>';

				$adjustements = $cOperation->reduce(fn($e, $n) => $e->isVatAdjustement($eFinancialYear['lastPeriod']) ? $n + 1 : $n, 0);
				if($adjustements > 0) {
					$h .= '<p>'.p("{value} ligne de régularisation incluse.", "{value} lignes de régularisation incluses.", $adjustements).'</p>';
				}

				$h .= '<table>';
					$h .= '<tr class="row-bordered">';
						$h .= '<th class="text-end">'.s("TVA collectée").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eVatDeclaration['collectedVat']).'</td>';
					$h .= '</tr>';
					$h .= '<tr class="row-bordered">';
						$h .= '<th class="text-end">'.s("TVA déductible").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eVatDeclaration['deductibleVat']).'</td>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="text-end">'.s("TVA due").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eVatDeclaration['dueVat']).'</td>';
					$h .= '</tr>';
				$h .= '</table>';

				$h .= '<h3>'.s("Lignes de TVA").'</h3>';

				$h .= new VatDeclarationUi()->getPdfTable($cOperation);

			$h .= '</div>';

			$h .= '<div class="mt-2">';
				$h .= '<i>'.s("Document généré automatiquement par {siteName}. Ce document n'est pas une transmission officielle à l'administration fiscale.").'</i>';
			$h .= '</div>';

		$h .= '</div>';

		if(get_exists('test') === TRUE) {
			$h .= \pdf\PdfUi::getFooter();
		}
		return $h;

	}

}
?>
