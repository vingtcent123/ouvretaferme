<?php
namespace journal;

class VatDeclarationUi {

	public function __construct() {
	}

	public function create(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cOperation, array $vatByType): \Panel {

		$form = new \util\FormUi();

		$dialogOpen = $form->openAjax(
			\company\CompanyUi::urlJournal($eFarm).'/vatDeclaration:doCreate',
			[
				'id' => 'journal-vat-create',
				'class' => 'panel-dialog container',
			],
		);

		$h = '';
		if($eFinancialYear['lastPeriod'] === NULL) {

			$periode = s("Il n'y a pas encore de période échue valide pour cette année");

		} else {

			$periode = s("du {startDate} au {endDate}", [
				'startDate' => \util\DateUi::numeric($eFinancialYear['lastPeriod']['start'], \util\DateUi::DATE),
				'endDate' => \util\DateUi::numeric($eFinancialYear['lastPeriod']['end'], \util\DateUi::DATE),
			]);

		}
		$regime = \account\FinancialYearUi::p('taxSystem')->values[$eFinancialYear['taxSystem']].' ('.\account\FinancialYearUi::p('vatFrequency')->values[$eFinancialYear['vatFrequency']].')';

		$h .= '<div class="util-block stick-xs bg-background-light mt-1 mb-1">';

			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>'.s("Période").'</dt>';
				$h .= '<dd>'.$periode.'</dd>';

				$h .= '<dt>'.s("Régime").'</dt>';
				$h .= '<dd>'.$regime.'</dd>';

				$h .= '<dt>'.s("Exercice comptable").'</dt>';
				$h .= '<dd>'.\account\FinancialYearUi::getYear($eFinancialYear).'</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		$h .= $form->hidden('company', $eFarm['id']);
		$h .= $form->hidden('financialYear', $eFinancialYear['id']);

		$h .= '<ul>';
			$h .= '<li>'.s("Écritures concernées : {value} lignes", $cOperation->count()).'</li>';
			$regul = 0;
			if($regul > 0) {
				$h .= '<li>'.p("dont régularisation : {value} ligne", "dont régularisations : {value} lignes (TODO)", 0).'</li>';
			} else {
				$h .= '<li>'.s("sans régularisation (TODO)", 0).'</li>';
			}
		$h .= '</ul>';

		$h .= '<h3>'.s("Liste des écritures").'</h3>';
		$h .= new JournalUi()->getTableContainer($eFarm, $cOperation, $eFinancialYear, hide: ['cashflow', 'actions', 'document']);

		$h .= '<h3>'.s("Résumé").'</h3>';

		$h .= '<div class="stick-sm util-overflow-sm table-container">';

			$h .= '<table class="tr-even td-vertical-top tr-hover">';

				$h .= '<tr>';
					$h .= '<th>'.s("TVA collectée").'</th>';
					$h .= '<th>'.s("TVA déductible").'</th>';
					$h .= '<th>'.s("TVA due").'</th>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<td>'.\util\TextUi::money($vatByType['collectedVat']).'</td>';
					$h .= '<td>'.\util\TextUi::money($vatByType['deductibleVat']).'</td>';
					$h .= '<td>'.\util\TextUi::money($vatByType['dueVat']).'</td>';
				$h .= '</tr>';

			$h .= '</table>';
		$h .= '</div>';

		$dialogClose = $form->close();

		$footer = '</div>'
			.'<div class="create-operation-button-add">'.$form->submit(s("Créer ma déclaration")).'</div>'
		.'</div>';

		return new \Panel(
			id: 'panel-journal-vat-create',
			title: '<div id="panel-journal-vat-create-title">'.s("Créer une déclaration de TVA").'</div>',
			dialogOpen: $dialogOpen,
			dialogClose: $dialogClose,
			body: $h,
			footer: $footer,
		);

	}

	public function getPdfTable(\Collection $cOperation): string {

		$h = '<div class="stick-sm util-overflow-sm table-container">';

			$h .= '<table class="tr-even td-vertical-top">';

				$h .= '<thead>';

					$h .= '<tr>';

						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th>'.s("Tiers").'</th>';
						$h .= '<th>'.s("Libellé").'</th>';
						$h .= '<th>'.s("Compte").'</th>';
						$h .= '<th>'.s("TVA").'</th>';
						$h .= '<th>'.s("HT").'</th>';
						$h .= '<th>'.s("TVA").'</th>';

					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cOperation as $eOperation) {

						if($eOperation['operation']->exists() === TRUE) {

							$label = encode($eOperation['operation']['description']);
							$amountWithoutVat = \util\TextUi::money($eOperation['operation']['amount']);


							if($eOperation['thirdParty']->exists() === TRUE) {
								$thirdParty = encode($eOperation['thirdParty']['name']);
							} else {
								$thirdParty = '-';
							}

						} else {

							$label = encode($eOperation['description']);
							$amountWithoutVat = '-';

							if($eOperation['thirdParty']->exists() === TRUE) {
								$thirdParty = encode($eOperation['thirdParty']['name']);
							} else {
								$thirdParty = '-';
							}
						}

						$account = encode($eOperation['accountLabel']);
						if(mb_substr($account, 0, mb_strlen(\Setting::get('account\vatBuyClassPrefix'))) === \Setting::get('account\vatBuyClassPrefix')) {
							$type = s("Déductible");
						} else if(mb_substr($account, 0, mb_strlen(\Setting::get('account\vatSellClassPrefix'))) === \Setting::get('account\vatSellClassPrefix')) {
							$type = s("Collectée");
						} else {
							$type = '-';
						}

						$h .= '<tr>';
							$h .= '<td>'.\util\DateUi::numeric($eOperation['date']).'</td>';
							$h .= '<td>'.$thirdParty.'</td>';
							$h .= '<td>'.$label.'</td>';
							$h .= '<td>'.$account.'</td>';
							$h .= '<td>'.$type.'</td>';
							$h .= '<td class="text-end">'.$amountWithoutVat.'</td>';
							$h .= '<td class="text-end">'.\util\TextUi::money($eOperation['amount']).'</td>';
						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Operation::model()->describer($property, [
			'startDate' => s("Début"),
			'endDate' => s("Fin"),
			'type' => s("Type de déclaration"),
		]);

		switch($property) {

			case 'type' :
				$d->values = [
					VatDeclarationElement::STATEMENT => s("Déclaration initiale"),
					VatDeclarationElement::ADJUSTMENT => s("Régularisation"),
					VatDeclarationElement::AMENDMENT => s("Déclaration rectificative"),
				];
				break;

		}

		return $d;

	}

}

?>
