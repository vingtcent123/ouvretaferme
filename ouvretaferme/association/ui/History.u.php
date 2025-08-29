<?php
namespace association;

class HistoryUi {

	public function display(\Collection $cHistory): string {

		$h = '<div class="util-overflow-md stick-xs">';

			$h .= '<table class="tr-even">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th>'.s("Année").'</th>';
						$h .= '<th>'.s("Montant").'</th>';
						$h .= '<th>'.s("Statut").'</th>';
						$h .= '<th>'.s("Date").'</th>';
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cHistory as $eHistory) {

						$h .= '<tr>';
							$h .= '<td>'.encode($eHistory['membership']).'</td>';
							$h .= '<td>'.\util\TextUi::money($eHistory['amount']).'</td>';
							$h .= '<td>'.self::p('paymentStatus')->values[$eHistory['paymentStatus']].'</td>';
							$h .= '<td>'.\util\DateUi::numeric($eHistory['paidAt'] ?? $eHistory['createdAt']).'</td>';
						$h .= '</tr>';
					}

				$h .= '</tbody>';
			$h .= '</table>';
			
		$h .= '</div>';

		return $h;

	}
	public static function p(string $property): \PropertyDescriber {

		$d = History::model()->describer($property, [
			'paymentStatus' => s("Statut"),
			'membership' => s("Année"),
			'amount' => s("Montant"),
			'paidAt' => s("Adhésion du"),
		]);

		switch($property) {

			case 'paymentStatus' :
				$d->values = [
					History::INITIALIZED => s("Paiement initialisé"),
					History::SUCCESS => s("Payé"),
					History::FAILURE => s("Paiement en échec"),
					History::EXPIRED => s("Paiement expiré"),
				];
				break;

		}

		return $d;

	}

}
