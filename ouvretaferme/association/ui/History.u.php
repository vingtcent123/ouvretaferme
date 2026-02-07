<?php
namespace association;

class HistoryUi {

	public function display(\Collection $cHistory): string {

		$h = '<div class="util-overflow-md stick-xs">';

			$h .= '<table class="tr-even">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th>'.s("Type").'</th>';
						$h .= '<th class="text-end">'.s("Montant").'</th>';
						$h .= '<th>'.s("Statut").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cHistory as $eHistory) {

						$h .= '<tr>';
							$h .= '<td>'.\util\DateUi::numeric($eHistory['paidAt'] ?? $eHistory['updatedAt'], \util\DateUi::DATE_HOUR_MINUTE).'</td>';
							$h .= '<td>';
								$h .= match($eHistory['type']) {
									History::DONATION => s("Don"),
									History::MEMBERSHIP => s("Adhésion pour l'année {value}", $eHistory['membership'])
								};
							$h .= '</td>';
							$h .= '<td class="text-end">';
								if($eHistory['amount'] === 0.0) {
									$h .= s("Offert");
								} else {
									$h .= \util\TextUi::money($eHistory['amount']);
								}
							$h .= '</td>';
							$h .= '<td>';
								$h .= self::p('status')->values[$eHistory['status']];
								if($eHistory['onlineCheckoutId'] !== NULL) {
									$h .= '<br />';
									$h .= '<div class="util-annotation">'.self::p('paymentStatus')->values[$eHistory['paymentStatus']].'</div>';
								}
							$h .= '</td>';
							$h .= '<td class="text-end">';
								if($eHistory->canReadDocument()) {
									$h .= '<a href="/association/pdf:document?id='.$eHistory['id'].'" data-ajax-navigation="never" class="btn btn-outline-secondary">'.\Asset::icon('download').'  '.s("Reçu").'</a> ';
								}
							$h .= '</td>';
						$h .= '</tr>';
					}

				$h .= '</tbody>';
			$h .= '</table>';
			
		$h .= '</div>';

		return $h;

	}
	public static function p(string $property): \PropertyDescriber {

		$d = History::model()->describer($property, [
			'paymentStatus' => s("Statut de paiement"),
			'status' => s("Statut"),
			'membership' => s("Année"),
			'amount' => s("Montant"),
			'paidAt' => s("Payé le"),
			'type' => s("Type"),
		]);

		switch($property) {

			case 'type' :
				$d->values = [
					History::DONATION => s("Don"),
					History::MEMBERSHIP => s("Adhésion"),
				];
				break;

			case 'paymentStatus' :
				$d->values = [
					History::INITIALIZED => s("Paiement initialisé"),
					History::SUCCESS => s("Paiement terminé"),
					History::FAILURE => s("Paiement en échec"),
					History::EXPIRED => s("Paiement expiré"),
				];
				break;

			case 'status' :
				$d->values = [
					History::PROCESSING => s("En attente"),
					History::VALID => s("Valide"),
					History::INVALID => s("Invalide"),
				];
				break;

		}

		return $d;

	}

}
