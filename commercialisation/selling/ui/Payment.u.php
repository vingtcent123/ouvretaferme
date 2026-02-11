<?php
namespace selling;

class PaymentUi {

	public static function getList(\Collection $cPayment): array {

		$payments = [];

		foreach($cPayment as $ePayment) {

			if($ePayment['method']->empty()) {
				continue;
			}

			$payments[] = encode($ePayment['method']['name']);

		}

		return $payments;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Payment::model()->describer($property, [
			'method' => s("Moyen de paiement"),
			'status' => s("État du paiement"),
			'paidAt' => s("Date de paiement"),
			'amountIncludingVat' => s("Montant réglé"),
		]);

		switch($property) {

			case 'method' :
				$d->values = fn(Payment $e) => $e['cMethod'] ?? $e->expects(['cMethod']);
				break;

			case 'status' :
				$d->values = [
					Sale::PAID => s("Payé"),
					Sale::NOT_PAID => s("Non payé"),
					Sale::PARTIAL_PAID => s("Payé partiellement"),
					Sale::FAILED => s("Paiement en échec"),
					Sale::NEVER_PAID => s("Ne sera pas payé"),
				];
				$d->field = 'switch';
				$d->attributes = [
					'labelOn' => $d->values[Sale::PAID],
					'labelOff' => $d->values[Sale::NOT_PAID],
					'valueOn' => Sale::PAID,
					'valueOff' => Sale::NOT_PAID,
				];
				break;

			case 'amountIncludingVat' :
				$d->type = 'float';
				$d->append = fn(\util\FormUi $form, $e) => $form->addon($e['farm']->getConf('hasVat') ? s("€ TTC") : s("€"));
				$d->attributes = [
					'onfocus' => 'this.select()',
				];
				break;

			case 'accountingDifference' :
				$d->values = [
					Payment::AUTOMATIC => s("Écriture de régularisation créée automatiquement"),
					Payment::NOTHING => s("Ne créer aucune écriture"),
				];
				$d->attributes['mandatory'] = TRUE;
				break;
		}

		return $d;
	}
}
