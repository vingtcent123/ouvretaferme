<?php
namespace mail;

class EmailUi {

	public function getList(\Collection $cEmail, array $hide = []) {

		if($cEmail->empty()) {
			return '<div class="util-empty">'.s("Il n'y a aucun e-mail à afficher.").'</div>';
		}

		$h = '<p class="util-info">'.s("Vous pouvez consulter la liste des e-mails envoyés il y a moins d'un mois.").'</p>';

		$h .= '<div class="util-overflow-sm stick-xs">';

			$h .= '<table class="tr-even">';

				$h .= '<thead>';
					$h .= '<tr>';
						if(in_array('customer', $hide) === FALSE) {
							$h .= '<th class="email-item-customer">'.s("Client").'</th>';
						}
						$h .= '<th>'.s("Titre").'</th>';
						$h .= '<th class="text-center">'.s("Envoyé").'</th>';
						$h .= '<th class="text-center">'.s("État").'</th>';
						$h .= '<th class="text-center">'.s("Lu").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

				foreach($cEmail as $eEmail) {

					$attachments = count(unserialize($eEmail['attachments']));

					$h .= '<tr>';

						if(in_array('customer', $hide) === FALSE) {
							$h .= '<td>';
								$h .= CustomerUi::link($eEmail['customer']);
								$h .= '<div class="util-annotation">';
									$h .= CustomerUi::getCategory($eEmail['customer']);
								$h .= '</div>';
							$h .= '</td>';
						}

						$h .= '<td>';
							$h .= '<span>'.encode($eEmail['subject']).'</span>';
							if($attachments > 0) {
								$h .= '<span class="util-badge bg-primary ml-1">'.p("{value} pièce jointe", "{value} pièces jointes", $attachments).'</span>';
							}
						$h .= '</td>';

						$h .= '<td class="text-center">';
							$h .= \util\DateUi::numeric($eEmail['sentAt']);
						$h .= '</td>';

						if($eEmail->isBlocked()) {

							$h .= '<td class="text-center color-danger"><b>'.s("BLOQUÉ").'</b></td>';
							$h .= '<td class="text-center">'.\Asset::icon('x-circle-fill').'</td>';

							$h .= '<td class="color-danger">';
								$h .= match($eEmail['status']) {
									Email::ERROR_BOUNCE => s("L'adresse e-mail n'est pas joignable"),
									Email::ERROR_BLOCKED => s("L'e-mail a été bloqué par le fournisseur"),
									default => '',
								};
							$h .= '</td>';

						} else {

							$h .= '<td class="text-center color-success"><b>'.s("REÇU").'</b></td>';

							if($eEmail['openedAt'] !== NULL) {
								$h .= '<td class="text-center color-success"><b>'.\Asset::icon('check-circle-fill').'</b></td>';
							} else {
								$h .= '<td class="text-center"><b>'.\Asset::icon('question-circle-fill').'</b></td>';
							}

							$h .= '<td></td>';

						}

					$h .= '</tr>';

				}


			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}
	
}
?>
