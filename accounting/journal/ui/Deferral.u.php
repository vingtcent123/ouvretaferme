<?php
namespace journal;

class DeferralUi {

	public function __construct() {

		\Asset::js('journal', 'deferral.js');

	}

	public function set(\farm\Farm $eFarm, Operation $eOperation,\account\FinancialYear $eFinancialYear, string $field): \Panel {

		$eDeferral = new Deferral();

		$h = '<div class="util-block stick-xs bg-background-light mt-1 mb-1">';

			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>'.s("Écriture").'</dt>';
				$h .= '<dd>'.encode($eOperation['description']).'</dd>';

				$h .= '<dt>'.s("Montant").'</dt>';
				$h .= '<dd>'.\util\TextUi::money($eOperation['amount']).'</dd>';

				$h .= '<dt>'.s("Date").'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($eOperation['date']).'</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		if($field === 'dates') {
			$datesAttributes = [];
			$amountAttributes = ['disabled' => TRUE];
		} else {
			$datesAttributes = ['disabled' => TRUE];
			$amountAttributes = [];
		}

		$form = new \util\FormUi();

		$h .= $form->openAjax(\company\CompanyUi::urlJournal($eFarm).'/deferral:doSet', ['id' => 'journal-deferral-set', 'autocomplete' => 'off']);

		$h .= $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('id', $eOperation['id']);
		$h .= $form->hidden('operationAmount', $eOperation['amount']);
		$h .= $form->hidden('financialYear', $eFinancialYear['id']);
		$h .= $form->hidden('financialYearEndDate', $eFinancialYear['endDate']);
		$h .= $form->hidden('field', $field);

		$h .= $form->group(s("Dates de consommation"), s("du {startDate} au {endDate}", [
			'startDate' => $form->date('startDate', $eOperation['date'], $datesAttributes),
			'endDate' => $form->date('endDate', NULL, $datesAttributes + ['min' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' + 1 day'))]),
		]));
		$h .= $form->dynamicGroup($eDeferral, 'amount', function($d) use($amountAttributes) {
			$d->attributes = $amountAttributes;
		});

		$h .= $form->group(
			content: $form->submit(s("Enregister"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-journal-deferral-set',
			title: s("Enregistrer une charge constatée d'avance"),
			body: $h
		);
	}

	public function listForOpening(\Collection $cDeferral): string {

		if($cDeferral->count() === 0) {
			return '';
		}

		$h = '<h3>'.s("Écritures des charges et produits constatés d'avance").'</h3>';

		$h .= '<div class="stick-sm util-overflow-sm">';

			$h .= '<table class="financialYear-item-table tr-even tr-hover">';

				$h .= '<thead>';

					$h .= '<tr>';

						$h .= '<th>'.s("Type").'</th>';
						$h .= '<th>'.s("Compte").'</th>';
						$h .= '<th>'.s("Libellé du compte").'</th>';
						$h .= '<th class="text-end">'.s("Débit").'</th>';
						$h .= '<th class="text-end">'.s("Crédit").'</th>';

					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cDeferral as $eDeferral) {

						if($eDeferral['type'] === Deferral::CHARGE) {
							$class = \account\AccountSetting::PREPAID_EXPENSE_CLASS;
						} else {
							$class = \account\AccountSetting::ACCRUED_EXPENSE_CLASS;
						}


						$h .= '<tr>';

							$h .= '<td>'.encode(self::p('type')->values[$eDeferral['type']]).'</td>';
							$h .= '<td>'.encode($eDeferral['operation']['accountLabel']).'</td>';
							$h .= '<td>'.encode($eDeferral['operation']['description']).'</td>';
							$h .= '<td class="text-end">';
							$h .= match($eDeferral['operation']['type']) {
								\journal\Operation::CREDIT => '',
								\journal\Operation::DEBIT => \util\TextUi::money($eDeferral['amount']),
							};
							$h .='</td>';
							$h .= '<td class="text-end">';
							$h .= match($eDeferral['operation']['type']) {
								\journal\Operation::DEBIT => '',
								\journal\Operation::CREDIT => \util\TextUi::money($eDeferral['amount']),
							};
						$h .='</td>';

						$h .= '</tr>';

							// Contrepassation
							$h .= '<tr>';

								$h .= '<td>'.encode(self::p('type')->values[$eDeferral['type']]).'</td>';
								$h .= '<td>'.\account\ClassLib::pad($class).'</td>';
								$h .= '<td>'.self::getTranslation(NULL, $eDeferral['type']).' ('.encode($eDeferral['operation']['description']).')</td>';
								$h .= '<td class="text-end">';
								$h .= match($eDeferral['operation']['type']) {
									\journal\Operation::DEBIT => '',
									\journal\Operation::CREDIT => \util\TextUi::money($eDeferral['amount']),
								};
								$h .='</td>';
								$h .= '<td class="text-end">';
								$h .= match($eDeferral['operation']['type']) {
									\journal\Operation::CREDIT => '',
									\journal\Operation::DEBIT => \util\TextUi::money($eDeferral['amount']),
								};
								$h .='</td>';

						$h .= '</tr>';

					}
				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';


		return $h;

	}

	public function listForClosing(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cOperation): string {

		$h = '<h3 class="mt-2">'.s("Charges et Produits constatés d'avance (CCA et PCA)").'</h3>';

		if($cOperation->empty()) {

			$h .= '<div class="util-info">'.s("Aucune opération ne peut être reportée (charge ou produit).").'</div>';

		} else {

			$countDeferral = $cOperation->find(fn($e) => $e['deferral'] !== NULL)->count();
			$totalDeferral = $countDeferral;

			$h .= '<div class="util-block-help">';
				$h .= s("Toutes les écritures de charge et de produit de cet exercice comptable ont été listées ci-après. Si vous souhaitez que certaines d'entre elles soient en partie reportées au prochain exercice, vous pouvez modifier leur période de consommation ou le montant à reporter");
			$h .= '</div>';

			$h .= '<div class="stick-sm util-overflow-sm mb-1">';

				$h .= '<table class="financial-year-cca-table tr-even tr-hover" data-type="deferral">';

					$h .= '<thead>';

						$h .= '<tr>';

							$h .= '<th>'.s("Type").'</th>';
							$h .= '<th>'.s("Date").'</th>';
							$h .= '<th>'.s("Compte").'</th>';
							$h .= '<th>'.s("Libellé").'</th>';
							$h .= '<th class="text-end">'.s("Montant HT").'</th>';
							$h .= '<th>'.s("Période de<br />consommation").'</th>';
							$h .= '<th class="text-end">'.s("À reporter").'</th>';
							$h .= '<th></th>';

						$h .= '</tr>';

					$h .= '</thead>';

					$h .= '<tbody>';

						foreach($cOperation as $eOperation) {

							if(($eOperation['deferral'] ?? NULL) !== NULL) {

								$isDeferral = TRUE;
								$countDeferral--;

								$period = s("{startDate} - {endDate}", [
									'startDate' => \util\DateUi::numeric($eOperation['date'], \util\DateUi::DATE),
									'endDate' => \util\DateUi::numeric($eOperation['deferral']['endDate'], \util\DateUi::DATE),
								]);
								$amount = \util\TextUi::money($eOperation['deferral']['amount']);

								if($eOperation['deferral']->canDelete()) {

									$confirm = match((int)substr($eOperation['accountLabel'], 0, 1)) {
										\account\AccountSetting::CHARGE_ACCOUNT_CLASS => s("Voulez-vous vraiment supprimer cette charge constatée d'avance ?"),
										\account\AccountSetting::PRODUCT_ACCOUNT_CLASS => s("Voulez-vous vraiment supprimer ce produit constaté d'avance ?"),
									};

									$action = '<a data-ajax="'.\company\CompanyUi::urlJournal($eFarm).'/deferral:doDelete" post-id="'.$eOperation['deferral']['id'].'" title="'.s("Supprimer").'" data-confirm="'.$confirm.'" class="btn btn-outline-danger">'.\Asset::icon('trash').'</a>';

								} else {

									$action = '';

								}

							} else {

								$isDeferral = FALSE;

								$period = '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/deferral:set?operation='.$eOperation['id'].'&financialYear='.$eFinancialYear['id'].'&field=dates">'.s("modifier").'</a>';
								$amount = '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/deferral:set?operation='.$eOperation['id'].'&financialYear='.$eFinancialYear['id'].'&field=amount">'.s("modifier").'</a>';
								$action = '';

							}

							if($countDeferral === 0 and $isDeferral === FALSE and $totalDeferral > 0) {

								$class = 'tr-border-top '.($totalDeferral > 0 ? 'hide' : '');
								$countDeferral = NULL;

							} else if($isDeferral === FALSE and $totalDeferral > 0) {

								$class = 'hide';

							} else {

								$class = '';

							}

							$h .= '<tr id="'.$eOperation['id'].'" class="'.$class.'">';

								$h .= '<td>'.match((int)substr($eOperation['accountLabel'], 0, 1)) {
									\account\AccountSetting::PRODUCT_ACCOUNT_CLASS => s("Produit"),
									\account\AccountSetting::CHARGE_ACCOUNT_CLASS => s("Charge"),
								}.'</td>';
								$h .= '<td>'.\util\DateUi::numeric($eOperation['date'], \util\DateUi::DATE).'</td>';
								$h .= '<td>'.encode($eOperation['accountLabel']).'</td>';
								$h .= '<td>'.encode($eOperation['description']).'</td>';
								$h .= '<td class="text-end">'.\util\TextUi::money($eOperation['amount']).'</td>';
								$h .= '<td>'.$period.'</td>';
								$h .= '<td class="text-end">'.$amount.'</td>';
								$h .= '<td class="td-min-content">'.$action.'</td>';

							$h .= '</tr>';

						}

					$h .= '</tbody>';

				$h .= '</table>';

				if($cOperation->count() > $totalDeferral and $totalDeferral > 0) {
					$h .= '<a style="width: 100%" class="btn btn-outline-secondary" onclick="FinancialYear.displayOperations(this, \'deferral\')">'.\Asset::icon('caret-down').' '.s("Afficher toutes les charges").'</a>';
				}

			$h .= '</div>';

		}

		return $h;

	}

	public static function getTranslation(?string $status, string $type): string {

		if($type === Deferral::CHARGE) {
			return match($status) {
				Deferral::RECORDED => s("Report CCA"),
				Deferral::DEFERRED => s("Réintégration CCA"),
				default => s("Charges constatées d'avance"),
			};
		}

		return match($status) {
			Deferral::RECORDED => s("Report PCA"),
			Deferral::DEFERRED => s("Réintégration PCA"),
			default => s("Produit constaté d'avance"),
		};

	}
	public static function p(string $property): \PropertyDescriber {

		$d = Operation::model()->describer($property, [
			'type' => s("Type"),
			'operation' => s("Écriture"),
			'startDate' => s("Date de début"),
			'endDate' => s("Date de fin"),
			'amount' => s("Montant à reporter (HT)"),
			'initialFinancialYear' => s("Exercice comptable d'origine"),
			'destinationFinancialYear' => s("Exercice comptable de report"),
			'status' => s("Statut"),
			'createdAt' => s("Enregistré le"),
		]);

		switch($property) {

			case 'amount' :
				$d->attributes['min'] = 0;
				break;

			case 'status':
				$d->values = [
					Deferral::PLANNED => s("Planifiée"),
					Deferral::RECORDED => s("Constatée"),
					Deferral::DEFERRED => s("Reportée"),
					Deferral::CANCELLED => s("Annulée"),
				];
				break;

			case 'type':
				$d->values = [
					Deferral::CHARGE => s("Charge"),
					Deferral::PRODUCT => s("Produit"),
				];
				break;

		}

		return $d;

	}

}

?>
