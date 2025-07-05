<?php
namespace mail;

class EmailUi {

	public function get(Email $e): \Panel {

		$h = '<div class="util-block stick-xs bg-background-light">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Pour").'</dt>';
				$h .= '<dd>'.encode($e['to']).'</dd>';
				$h .= '<dt>'.s("Envoyé le").'</dt>';
				$h .= '<dd>'.\util\DateUi::numeric($e['createdAt']).'</dd>';
				$h .= '<dt>'.s("Titre").'</dt>';
				$h .= '<dd>'.encode($e['subject']).'</dd>';
				$h .= '<dt>'.s("État").'</dt>';
				$h .= '<dd>'.($e->isBlocked() ? s("Bloqué") : s("Reçu")).'</dd>';
			$h .= '</dl>';
		$h .= '</div>';

		$h .= $e['html'];

		return new \Panel(
			id: 'panel-email-show',
			title: s("E-mail envoyé"),
			body: $h
		);

	}

	public function getList(\Collection $cEmail, array $hide = []) {

		if($cEmail->empty()) {
			return '<div class="util-empty">'.s("Il n'y a aucun e-mail à afficher.").'</div>';
		}

		$h = '<p class="util-info">'.s("Vous pouvez consulter la liste des e-mails envoyés il y a moins de trois mois.").'</p>';

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
							$h .= '<a href="/mail/email?id='.$eEmail['id'].'">'.encode($eEmail['subject']).'</a>';
							if($attachments > 0) {
								$h .= '<span class="util-badge bg-primary ml-1">'.p("{value} pièce jointe", "{value} pièces jointes", $attachments).'</span>';
							}
						$h .= '</td>';

						$h .= '<td class="text-center">';
							if($eEmail['sentAt'] !== NULL) {
								$h .= \util\DateUi::numeric($eEmail['sentAt']);
							}
						$h .= '</td>';

						if($eEmail->isBlocked()) {

							$h .= '<td class="text-center color-danger"><b>'.s("BLOQUÉ").'</b></td>';
							$h .= '<td class="text-center">'.\Asset::icon('x-circle-fill', ['class' => 'asset-icon-lg']).'</td>';

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
								$h .= '<td class="text-center color-success"><b>'.\Asset::icon('check-circle-fill', ['class' => 'asset-icon-lg']).'</b></td>';
							} else {
								$h .= '<td class="text-center"><b>'.\Asset::icon('question-circle-fill', ['class' => 'asset-icon-lg']).'</b></td>';
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
