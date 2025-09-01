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

						$type = self::p('type')->values[$eHistory['type']];
						if($eHistory['type'] === History::MEMBERSHIP) {
							$type .= ' '.s('(année {year})', ['year' => $eHistory['membership']]);
						}
						$h .= '<tr>';
							$h .= '<td>'.\util\DateUi::numeric($eHistory['paidAt'] ?? $eHistory['updatedAt'], \util\DateUi::DATE_HOUR_MINUTE).'</td>';
							$h .= '<td>'.$type.'</td>';
							$h .= '<td class="text-end">'.\util\TextUi::money($eHistory['amount']).'</td>';
							$h .= '<td>'.self::p('paymentStatus')->values[$eHistory['paymentStatus']].'</td>';
							$h .= '<td class="text-end">';
								$h .= '<a href="/association/pdf:document?id='.$eHistory['id'].'" data-ajax-navigation="never" class="btn btn-outline-secondary">'.\Asset::icon('download').'  '.s("Reçu").'</a> ';
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
			'paymentStatus' => s("Statut"),
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

		}

		return $d;

	}

}
