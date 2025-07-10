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
			'endDate' => $form->date('endDate', NULL, $datesAttributes),
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
