<?php
namespace journal;

class BookUi {

	public function __construct() {
		\Asset::css('journal', 'journal.css');
		\Asset::css('company', 'company.css');
	}

	public function getBookTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

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

				$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

					$h .= '<div>';
					$h .= $form->text('accountLabel', $search->get('accountLabel') !== '' ? $search->get('accountLabel') : '', ['placeholder' => s("Compte")]);
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public static function getBookTheadContent(): string {

		$h = '<tr>';
			$h .= '<th>'.s("Date").'</th>';
			$h .= '<th>'.s("Pièce").'</th>';
			$h .= '<th>'.s("Description").'</th>';
			$h .= '<th class="text-end highlight-stick-right">'.s("Débit (D)").'</th>';
			$h .= '<th class="text-end highlight-stick-left">'.s("Crédit (C)").'</th>';
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

				$h .= '<tr class="row-header">';
					$h .= '<td colspan="3">';
					$h .= s("{class} - {description}", [
							'class' => $currentAccountLabel,
							'description' => $eOperation['account']['description'],
						]);
					$h .= '</td>';
					$h .= '<td class="highlight-stick-right">';
					$h .= '</td>';
					$h .= '<td class="highlight-stick-left">';
					$h .= '</td>';
				$h .= '</tr>';

				$trClass = 'tr-border-bottom';

			} else {

				$trClass = 'tr-border-bottom';

			}

			$h .= '<tr class="'.$trClass.'">';

				$h .= '<td>';
					$h .= \util\DateUi::numeric($eOperation['date']);
				$h .= '</td>';

				$h .= '<td>';
					$h .= '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal?document='.encode($eOperation['document']).'&financialYear='.$eFinancialYear['id'].'" title="'.s("Voir les écritures liées à cette pièce comptable").'">'.encode($eOperation['document']).'</a>';
				$h .= '</td>';

				$h .= '<td>';
					$h .= encode($eOperation['description']);
				$h .= '</td>';

				$h .= '<td class="text-end highlight-stick-right">';
					$h .= match($eOperation['type']) {
						Operation::DEBIT => \util\TextUi::money($eOperation['amount']),
						default => '',
					};
				$h .= '</td>';

				$h .= '<td class="text-end highlight-stick-left">';
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

				$h .= '<td colspan="3" class="text-end">';
					$h .= '<strong>'.s("Solde").'</strong>';
				$h .= '</td>';
				$h .= '<td class="text-end highlight-stick-right">';
					$h .= '<strong>'.($totalDebit > $totalCredit ? \util\TextUi::money($balance) : '').'</strong>';
				$h .= '</td>';
					$h .= '<td class="text-end highlight-stick-left">';
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
			if($search->empty(['ids', 'financialYear'])) {
				return '<div class="util-info">'. s("Aucune écriture n'a encore été enregistrée") .'</div>';
			} else {
				return '<div class="util-info">'. s("Aucune écriture ne correspond à vos critères de recherche.") .'</div>';
			}
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

			$h .= '<td colspan="3" class="text-end">';
				$h .= '<strong>'.s("Total pour le compte {class}", [
						'class' => $class,
				]).'</strong>';
			$h .= '</td>';
			$h .= '<td class="text-end highlight-stick-right">';
				$h .= '<strong>'.\util\TextUi::money($debit).'</strong>';
			$h .= '</td>';
			$h .= '<td class="text-end highlight-stick-left">';
				$h .= '<strong>'.\util\TextUi::money($credit).'</strong>';
			$h .= '</td>';
		$h .= '</tr>';

		return $h;
	}
}

?>
