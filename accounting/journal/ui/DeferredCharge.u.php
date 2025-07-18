<?php
namespace journal;

class DeferredChargeUi {

	public function __construct() {

		\Asset::js('journal', 'deferredCharge.js');

	}

	public function set(\farm\Farm $eFarm, Operation $eOperation,\account\FinancialYear $eFinancialYear, string $field): \Panel {

		$eDeferredCharge = new DeferredCharge();

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

		$h .= $form->openAjax(\company\CompanyUi::urlJournal($eFarm).'/deferredCharge:doSet', ['id' => 'journal-deferredCharge-set', 'autocomplete' => 'off']);

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
		$h .= $form->dynamicGroup($eDeferredCharge, 'amount', function($d) use($amountAttributes) {
			$d->attributes = $amountAttributes;
		});

		$h .= $form->group(
			content: $form->submit(s("Enregister"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-journal-deferredCharge-set',
			title: s("Enregistrer une charge constatée d'avance"),
			body: $h
		);
	}

	public function list(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \Collection $cOperationCharges): string {

		$h = '<h3 class="mt-2">'.s("Charges constatées d'avance (CCA)").'</h3>';

		if($cOperationCharges->empty()) {

			$h .= '<div class="util-info">'.s("Aucune charge ne peut être reportée.").'</div>';

		} else {

			$countDeferred = $cOperationCharges->find(fn($e) => $e['deferredCharge'] !== NULL)->count();
			$totalDeferred = $countDeferred;

			$h .= '<div class="util-block-help">';
				$h .= s("Toutes les écritures de charge de cet exercice comptable ont été listées ci-après. Si vous souhaitez que certaines d'entre elles soient en partie reportées au prochain exercice, vous pouvez modifier leur période de consommation ou le montant à reporter");
			$h .= '</div>';

			$h .= '<div class="stick-sm util-overflow-sm mb-1">';

				$h .= '<table class="financial-year-cca-table tr-even tr-hover">';

					$h .= '<thead>';

						$h .= '<tr>';

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

						foreach($cOperationCharges as $eOperation) {

							if(($eOperation['deferredCharge'] ?? NULL) !== NULL) {

								$isDeferred = TRUE;
								$countDeferred--;

								$period = s("{startDate} - {endDate}", [
									'startDate' => \util\DateUi::numeric($eOperation['date'], \util\DateUi::DATE),
									'endDate' => \util\DateUi::numeric($eOperation['deferredCharge']['endDate'], \util\DateUi::DATE),
								]);
								$amount = \util\TextUi::money($eOperation['deferredCharge']['amount']);

								if($eOperation['deferredCharge']->canDelete()) {

									$action = '<a data-ajax="'.\company\CompanyUi::urlJournal($eFarm).'/deferredCharge:doDelete" post-id="'.$eOperation['deferredCharge']['id'].'" title="'.s("Supprimer").'" data-confirm="'.s("Voulez-vous vraiment supprimer cette charge constatée d'avance ?").'" class="btn btn-outline-danger">'.\Asset::icon('trash').'</a>';

								} else {

									$action = '';

								}

							} else {

								$isDeferred = FALSE;

								$period = '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/deferredCharge:set?operation='.$eOperation['id'].'&financialYear='.$eFinancialYear['id'].'&field=dates">'.s("modifier").'</a>';
								$amount = '<a href="'.\company\CompanyUi::urlJournal($eFarm).'/deferredCharge:set?operation='.$eOperation['id'].'&financialYear='.$eFinancialYear['id'].'&field=amount">'.s("modifier").'</a>';
								$action = '';

							}

							if($countDeferred === 0 and $isDeferred === FALSE and $totalDeferred > 0) {

								$class = 'tr-border-top hide';
								$countDeferred = NULL;

							} else if($isDeferred === FALSE) {

								$class = 'hide';

							} else {

								$class = '';

							}

							$h .= '<tr id="'.$eOperation['id'].'" class="'.$class.'">';

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

				if($cOperationCharges->count() > $totalDeferred) {
					$h .= '<a style="width: 100%" class="btn btn-outline-secondary" onclick="FinancialYear.displayCharges(this)">'.\Asset::icon('caret-down').' '.s("Afficher toutes les charges").'</a>';
				}

			$h .= '</div>';

		}

		return $h;

	}

	public static function getTranslation(string $type): string {

		return match($type) {
			DeferredCharge::RECORDED => s("Report CCA"),
			DeferredCharge::DEFERRED => s("Réintégration CCA"),
		};

	}
	public static function p(string $property): \PropertyDescriber {

		$d = Operation::model()->describer($property, [
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
					DeferredCharge::PLANNED => s("Planifiée"),
					DeferredCharge::RECORDED => s("Constatée"),
					DeferredCharge::DEFERRED => s("Reportée"),
					DeferredCharge::CANCELLED => s("Annulée"),
				];
				break;

		}

		return $d;

	}

}

?>
