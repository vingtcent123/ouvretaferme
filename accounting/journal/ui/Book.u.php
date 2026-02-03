<?php
namespace journal;

class BookUi {

	public function __construct() {
		\Asset::css('journal', 'journal.css');
		\Asset::css('company', 'company.css');
	}

	public function getBookTitle(\farm\Farm $eFarm): string {


		$h = \farm\FarmUi::getSelectedFinancialYear($eFarm);
		$h .= '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Le Grand livre");
			$h .= '</h1>';

		$h .= '<div>';
			$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#book-search")').' class="btn btn-primary">'.\Asset::icon('filter').' '.s("Configurer la synthèse").'</a> ';
		$h .= '</div>';

			/*$h .= '<div>';
				$h .= '<a href="'.PdfUi::urlBook($eFarm).'" data-ajax-navigation="never" class="btn btn-primary">'.\Asset::icon('file-pdf').'&nbsp;'.s("Télécharger en PDF").'</a>';
			$h .= '</div>';*/
		$h .= '</div>';

		return $h;

	}

	public function getSearch(\Search $search, \account\FinancialYear $eFinancialYear): string {

		$h = '<div id="book-search" class="util-block-search '.($search->empty(['ids', 'financialYear']) === TRUE ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Numéro de compte").'</legend>';
					$h .= $form->text('accountLabel', $search->get('accountLabel') !== '' ? $search->get('accountLabel') : '', ['placeholder' => s("Numéro de compte")]);
				$h .= '</fieldset>';
				$h .= '<div class="util-search-submit">';
					$h .= $form->submit(s("Chercher"));
					$h .= '<a href="'.$url.'" class="btn">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public static function getBookTheadContent(): string {

		$h = '<tr>';
			$h .= '<th>'.s("Date").'</th>';
			$h .= '<th class="hide-sm-down">'.s("Pièce").'</th>';
			$h .= '<th>'.s("Description").'</th>';
			$h .= '<td class="text-end highlight-stick-right hide-md-up">'.s("Montant").'</td>';
			$h .= '<th class="text-end highlight-stick-right hide-sm-down">'.s("Débit (D)").'</th>';
			$h .= '<th class="text-end highlight-stick-left hide-sm-down">'.s("Crédit (C)").'</th>';
		$h .= '</tr>';

		return $h;

	}

	public static function getBookTbody(
		\farm\Farm $eFarm,
		\Collection $cOperation,
		\account\FinancialYear $eFinancialYear,
		\Search $search
	): string {

		$h = '';

		$totalDebit = 0;
		$totalCredit = 0;
		$debit = 0;
		$credit = 0;
		$currentClass = NULL;
		$currentAccountLabel = NULL;

		foreach($cOperation as $eOperation) {

			if(
				$currentAccountLabel !== NULL and $currentClass !== NULL and
				($eOperation['class'] !== $currentClass  or $eOperation['accountLabel'] !== $currentAccountLabel)
			) {

				$h .= self::getSubTotal($currentAccountLabel, $debit, $credit);
				$totalCredit += $credit;
				$totalDebit += $debit;

			}

			if($currentAccountLabel === NULL or $currentClass === NULL or $eOperation['class'] !== $currentClass  or $eOperation['accountLabel'] !== $currentAccountLabel) {
				$debit = 0;
				$credit = 0;
				$currentClass = $eOperation['class'];
				$currentAccountLabel = $eOperation['accountLabel'];

				$h .= '<tr class="tr-header">';
					$h .= '<td class="hide-sm-down"></td>';
					$h .= '<td colspan="2">';
					$h .= s("{class} - {description}", [
							'class' => $currentAccountLabel,
							'description' => $eOperation['account']['description'],
						]);
					$h .= '</td>';
					$h .= '<td class="highlight-stick-right hide-md-up"></td>';
					$h .= '<td class="highlight-stick-right hide-sm-down"></td>';
					$h .= '<td class="highlight-stick-left hide-sm-down"></td>';
				$h .= '</tr>';

				$trClass = 'tr-border-bottom';

			} else {

				$trClass = 'tr-border-bottom';

			}

			$h .= '<tr class="'.$trClass.'">';

				$h .= '<td class="td-vertical-align-top">';
					$h .= \util\DateUi::numeric($eOperation['date']);
				$h .= '</td>';

				$h .= '<td class="td-vertical-align-top hide-sm-down">';
					$query = [
						'document' => $eOperation['document'],
						'financialYear' => $eFinancialYear['id'],
					];
					$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?'.http_build_query($query).'" title="'.s("Voir les écritures liées à cette pièce comptable").'">'.encode($eOperation['document']).'</a>';
				$h .= '</td>';

				$h .= '<td>';
					$h .= encode($eOperation['description']);
				$h .= '</td>';

				$h .= '<td class="text-end highlight-stick-right hide-md-up">';
					if($eOperation['type'] === Operation::DEBIT) {
						$h .= \util\TextUi::money($eOperation['amount']);
					} else {
						$h .= \util\TextUi::money(-1 * $eOperation['amount']);
					}
				$h .= '</td>';

				$h .= '<td class="text-end highlight-stick-right hide-sm-down">';
					$h .= match($eOperation['type']) {
						Operation::DEBIT => \util\TextUi::money($eOperation['amount']),
						default => '',
					};
				$h .= '</td>';

				$h .= '<td class="text-end highlight-stick-left hide-sm-down">';
					$h .= match($eOperation['type']) {
						Operation::CREDIT => \util\TextUi::money($eOperation['amount']),
						default => '',
					};
				$h .= '</td>';

			$h .= '</tr>';

			$debit += $eOperation['type'] === OperationElement::DEBIT ? $eOperation['amount'] : 0;
			$credit += $eOperation['type'] === OperationElement::CREDIT ? $eOperation['amount'] : 0;

		}

		// Dernier groupe
		$h .= self::getSubTotal($currentAccountLabel, $debit, $credit);
		$totalCredit += $credit;
		$totalDebit += $debit;

		$balance = abs($totalDebit - $totalCredit);

		// On n'affiche pas le solde s'il y a un filtre
		if($search->get('accountLabel') === FALSE) {

			$h .= '<tr>';

				$h .= '<td class="hide-sm-down"></td>';
				$h .= '<td colspan="2" class="text-end">';
					$h .= '<strong>'.s("Solde").'</strong>';
				$h .= '</td>';
				$h .= '<td class="text-end highlight-stick-right hide-md-up">';
					$h .= '<strong>'.\util\TextUi::money($totalDebit - $totalCredit).'</strong>';
				$h .= '</td>';
				$h .= '<td class="text-end highlight-stick-right hide-sm-down">';
					$h .= '<strong>'.($totalDebit > $totalCredit ? \util\TextUi::money($balance) : '').'</strong>';
				$h .= '</td>';
					$h .= '<td class="text-end highlight-stick-lefthide-sm-down">';
					$h .= '<strong>'.($totalDebit <= $totalCredit ? \util\TextUi::money($balance) : '').'</strong>';
				$h .= '</td>';
			$h .= '</tr>';

		}

		return $h;

	}

	public function getBook(
		\farm\Farm $eFarm,
		\Collection $cOperation,
		\account\FinancialYear $eFinancialYear,
		\Search $search
	): string {

		if($cOperation->empty() === TRUE) {
			return '<div class="util-empty">'. s("Aucune écriture ne correspond à vos critères de recherche.") .'</div>';
		}

		$h = '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="td-vertical-top tr-hover no-background">';

				$h .= '<thead class="thead-sticky">';
					$h .= self::getBookTheadContent();
				$h .= '</thead>';

				$h .= '<tbody>';
					$h .= self::getBookTbody($eFarm, $cOperation, $eFinancialYear, $search);
				$h .= '</tbody>';


			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	private static function getSubTotal(string $class, float $debit, float $credit): string {

		$h = '<tr>';

			$h .= '<td class="hide-sm-down"></td>';
			$h .= '<td colspan="2" class="text-end">';
				$h .= '<strong>'.s("Total pour le compte {class}", [
						'class' => $class,
				]).'</strong>';
			$h .= '</td>';

			$h .= '<td class="text-end highlight-stick-right hide-md-up">';
				$h .= '<strong>'.\util\TextUi::money($debit - $credit).'</strong>';
			$h .= '</td>';
			$h .= '<td class="text-end highlight-stick-right hide-sm-down">';
				$h .= '<strong>'.\util\TextUi::money($debit).'</strong>';
			$h .= '</td>';
			$h .= '<td class="text-end highlight-stick-left hide-sm-down">';
				$h .= '<strong>'.\util\TextUi::money($credit).'</strong>';
			$h .= '</td>';
		$h .= '</tr>';

		$h .= '<tr class="hide-sm-down">';

			$h .= '<td class="hide-sm-down"></td>';
			$h .= '<td colspan="2" class="text-end">';
			if(($debit - $credit) < 0) {

				$h .= '<strong>'.s("Solde créditeur pour le compte {class}", [
						'class' => $class,
				]).'</strong>';

			} else if(($debit - $credit) > 0) {

				$h .= '<strong>'.s("Solde débiteur pour le compte {class}", [
						'class' => $class,
				]).'</strong>';

			} else {

				$h .= '<strong>'.s("Solde nul pour le compte {class}", [
						'class' => $class,
				]).'</strong>';

			}
			$h .= '</td>';

			$h .= '<td class="text-end highlight-stick-right hide-md-up">';
			$h .= '</td>';
			$h .= '<td class="text-end highlight-stick-right hide-sm-down">';
				if($debit - $credit < 0) {
					$h .= '<strong>'.\util\TextUi::money(abs($debit - $credit)).'</strong>';
				}
			$h .= '</td>';
			$h .= '<td class="text-end highlight-stick-left hide-sm-down">';
				if($debit - $credit > 0) {
				$h .= '<strong>'.\util\TextUi::money($debit - $credit).'</strong>';
				}
			$h .= '</td>';
		$h .= '</tr>';

		return $h;
	}
}

?>
