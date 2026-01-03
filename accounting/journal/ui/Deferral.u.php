<?php
namespace journal;

class DeferralUi {

	public function __construct() {

		\Asset::js('journal', 'deferral.js');
		\Asset::css('journal', 'deferral.css');

	}

	public function set(\farm\Farm $eFarm, Operation $eOperation, string $field): \Panel {

		$eDeferral = new Deferral();

		$h = '<div class="util-block stick-xs bg-background-light mt-1 mb-1">';

			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>'.s("Dates de l'exercice courant").'</dt>';
				$h .= '<dd>'.s("du {startDate} au {endDate}", ['startDate' => \util\DateUi::numeric($eOperation['financialYear']['startDate']), 'endDate' => \util\DateUi::numeric($eOperation['financialYear']['endDate'])]).'</dd>';

				$h .= '<dt>'.s("Date de l'écriture").'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($eOperation['date']).'</dd>';

				$h .= '<dt>'.s("Libellé de l'écriture").'</dt>';
				$h .= '<dd>'.encode($eOperation['description']).'</dd>';

				$h .= '<dt>'.s("Montant").'</dt>';
				$h .= '<dd>'.\util\TextUi::money($eOperation['amount']).'</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		if($field === 'dates') {
			$datesAttributes = [];
			$amountAttributes = ['disabled' => TRUE, 'max' => $eOperation['amount']];
		} else {
			$datesAttributes = ['disabled' => TRUE];
			$amountAttributes = ['max' => $eOperation['amount']];
		}

		$form = new \util\FormUi();

		$h .= $form->openAjax(\company\CompanyUi::urlJournal($eFarm).'/deferral:doSet', ['id' => 'journal-deferral-set', 'autocomplete' => 'off']);

		$h .= $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('id', $eOperation['id']);
		$h .= $form->hidden('operationAmount', $eOperation['amount']);
		$h .= $form->hidden('financialYear', $eOperation['financialYear']['id']);
		$h .= $form->hidden('financialYearEndDate', $eOperation['financialYear']['endDate']);
		$h .= $form->hidden('field', $field);

		$h .= $form->group(
			s("Dates de consommation"),
			$form->inputGroup($form->addon(s("du")).
				$form->date('startDate', $eOperation['date'], $datesAttributes).
				$form->addon("au").
				$form->date('endDate', NULL, $datesAttributes + ['min' => date('Y-m-d', strtotime($eOperation['financialYear']['endDate'].' + 1 day'))]))
		);
		$h .= $form->dynamicGroup($eDeferral, 'amount', function($d) use($amountAttributes) {
			$d->attributes = $amountAttributes;
		});

		$h .= $form->group(
			content: $form->submit(s("Enregister"))
		);

		$h .= $form->close();

		$isCharge = \account\AccountLabelLib::isChargeClass($eOperation['accountLabel']);
		if($isCharge) {
			$title = s("Enregistrer une charge constatée d'avance");
		} else {
			$title = s("Enregistrer un produit constaté d'avance");
		}

		return new \Panel(
			id: 'panel-journal-deferral-set',
			title: $title,
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
						$h .= '<th>'.s("Numéro de compte").'</th>';
						$h .= '<th>'.s("Libellé du compte").'</th>';
						$h .= '<th class="text-end highlight-stick-right">'.s("Débit").'</th>';
						$h .= '<th class="text-end highlight-stick-left">'.s("Crédit").'</th>';

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
							$h .= '<td class="text-end highlight-stick-right">';
							$h .= match($eDeferral['operation']['type']) {
								\journal\Operation::CREDIT => '',
								\journal\Operation::DEBIT => \util\TextUi::money($eDeferral['amount']),
							};
							$h .='</td>';
							$h .= '<td class="text-end highlight-stick-left">';
							$h .= match($eDeferral['operation']['type']) {
								\journal\Operation::DEBIT => '',
								\journal\Operation::CREDIT => \util\TextUi::money($eDeferral['amount']),
							};
						$h .='</td>';

						$h .= '</tr>';

							// Contrepassation
							$h .= '<tr>';

								$h .= '<td>'.encode(self::p('type')->values[$eDeferral['type']]).'</td>';
								$h .= '<td>'.\account\AccountLabelLib::pad($class).'</td>';
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

			$h .= '<div class="util-empty">'.s("Aucune opération ne peut être reportée (charge ou produit).").'</div>';

		} else {

			$countDeferral = $cOperation->find(fn($e) => $e['deferral'] !== NULL)->count();
			$totalDeferral = $countDeferral;

			$h .= '<div class="util-info">';
				$h .= s("Toutes les écritures de charge et de produit de cet exercice comptable ont été listées ci-après. Si vous souhaitez que certaines d'entre elles soient en partie reportées au prochain exercice, vous pouvez indiquer leur période de consommation ou le montant à reporter.<br />Dans le cas d'une <b>charge</b> constatée d'avance, le compte de contrepartie utilisé sera le <b>{chargeClass}</b> et pour un <b>produit</b> constaté d'avance, le compte de contrepartie sera le <b>{productClass}</b>.", ['chargeClass' => \account\AccountSetting::PREPAID_EXPENSE_CLASS, 'productClass' => \account\AccountSetting::ACCRUED_EXPENSE_CLASS]);
			$h .= '</div>';

			$h .= '<div class="stick-sm util-overflow-sm mb-1">';

				$h .= '<table class="financial-year-cca-table tr-even tr-hover" data-type="deferral">';

					$h .= '<thead>';

						$h .= '<tr>';

							$h .= '<th>'.s("Type").'</th>';
							$h .= '<th>'.s("Date").'</th>';
							$h .= '<th>'.s("Numéro de compte").'</th>';
							$h .= '<th>'.s("Libellé").'</th>';
							$h .= '<th class="text-end highlight-stick-right">'.s("Montant HT").'</th>';
							$h .= '<th>'.s("Période de<br />consommation").'</th>';
							$h .= '<th class="text-end highlight-stick-right">'.s("Montant à reporter").'</th>';
							$h .= '<th></th>';

						$h .= '</tr>';

					$h .= '</thead>';

					$h .= '<tbody>';

						foreach($cOperation as $eOperation) {

							$eDeferral = $eOperation['deferral'] ?? new Deferral();

							if($eDeferral->notEmpty()) {

								$isDeferral = TRUE;
								$countDeferral--;

								$period = s("{startDate} - {endDate}", [
									'startDate' => \util\DateUi::numeric($eDeferral['startDate'], \util\DateUi::DATE),
									'endDate' => \util\DateUi::numeric($eDeferral['endDate'], \util\DateUi::DATE),
								]);
								if(date('Y-m-d', strtotime($eDeferral['financialYear']['endDate'].' + 1 YEAR')) < $eDeferral['endDate']) {
									$period = '<span class="color-warning" data-dropdown="bottom" data-dropdown-hover="true">'.$period.' '.\Asset::icon('exclamation-triangle').'</span>';
									$period .= '<div class="dropdown-list bg-primary">';
										$period .= '<span class="dropdown-item">'.s("La période de consommation est peut-être erronée").'</span>';
									$period .= '</div>';
								}
								$amount = \util\TextUi::money($eOperation['deferral']['amount']);

								if($eOperation['deferral']->acceptDelete()) {

									if(\account\AccountLabelLib::isFromClass($eOperation['accountLabel'], \account\AccountSetting::CHARGE_ACCOUNT_CLASS)) {
										$confirm = s("Voulez-vous vraiment supprimer cette charge constatée d'avance ?");
									} else {
										$confirm = s("Voulez-vous vraiment supprimer ce produit constaté d'avance ?");
									}

									$action = '<a data-ajax="'.\company\CompanyUi::urlJournal($eFarm).'/deferral:doDelete" post-id="'.$eOperation['deferral']['id'].'" title="'.s("Supprimer").'" data-confirm="'.$confirm.'" class="btn btn-outline-danger">'.\Asset::icon('trash').'</a>';

								} else {

									$action = '';

								}

							} else if($eOperation->acceptDeferral()) {

								$isDeferral = FALSE;

								$period = '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/deferral:set?id='.$eOperation['id'].'&field=dates">'.s("modifier").'</a>';
								$amount = '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/deferral:set?id='.$eOperation['id'].'&field=amount">'.s("modifier").'</a>';
								$action = '';

							} else {

								$isDeferral = FALSE;
								$period = '';
								$amount = '';
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
								$h .= '<td>';
									$h .= '<div data-dropdown="bottom" data-dropdown-hover="true">';
										$h .= encode($eOperation['accountLabel']);
									$h .= '</div>';
									$h .= '<div class="dropdown-list bg-primary">';
										$h .= '<span class="dropdown-item">'.encode($eOperation['account']['class']).' '.encode($eOperation['account']['description']).'</span>';
									$h .= '</div>';
								$h .= '</td>';
								$h .= '<td>'.encode($eOperation['description']).'</td>';
								$h .= '<td class="text-end highlight-stick-right">'.\util\TextUi::money($eOperation['amount']).'</td>';
								$h .= '<td class="deferral-td-period">'.$period.'</td>';
								$h .= '<td class="text-end highlight-stick-right">'.$amount.'</td>';
								$h .= '<td class="td-min-content">'.$action.'</td>';

							$h .= '</tr>';

						}

					$h .= '</tbody>';

				$h .= '</table>';

				if($cOperation->count() > $totalDeferral and $totalDeferral > 0) {
					$h .= '<a style="width: 100%" class="btn btn-outline-secondary" onclick="FinancialYear.displayOperations(this, \'deferral\')">'.\Asset::icon('caret-down').' '.s("Afficher toutes les opérations").'</a>';
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
			'financialYear' => s("Exercice comptable"),
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
