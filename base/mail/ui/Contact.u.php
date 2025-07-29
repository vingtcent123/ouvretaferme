<?php
namespace mail;

class ContactUi {

	public function __construct() {

		\Asset::css('mail', 'contact.css');

	}

	public function getSearch(\farm\Farm $eFarm, \Search $search): string {

		$form = new \util\FormUi();

		$h = '<div id="contact-search" class="util-block-search stick-xs '.($search->empty() ? 'hide' : '').'">';

			$h .= $form->openAjax(\farm\FarmUi::urlCommunicationsMailing($eFarm), ['method' => 'get', 'id' => 'form-search']);
				$h .= $form->hidden('category', $search->get('category'));
				$h .= '<div>';
					$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom du produit")]);
					$h .= $form->text('plant', $search->get('plant'), ['placeholder' => s("Espèce")]);
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.\farm\FarmUi::urlCommunicationsMailing($eFarm).'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getList(\farm\Farm $eFarm, \Collection $cContact, array $contacts, \Search $search) {

		$h = '';

		if($cContact->empty()) {

			$h .= '<div class="util-empty">'.s("Il n'y a aucun contact à afficher.").'</div>';
			return $h;

		}

		$h .= '<div class="contact-item-wrapper stick-md">';

		$h .= '<table class="contact-item-table tr-even">';

			$h .= '<thead>';

				$h .= '<tr>';
					$h .= '<th rowspan="2">'.$search->linkSort('email', s("Adresse e-mail")).'</th>';
					$h .= '<th rowspan="2">'.s("Client").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Opt-in").'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Opt-out").'</th>';
					$h .= '<th rowspan="2">'.s("Dernier e-mail envoyé").'</th>';
					$h .= '<th colspan="4" class="text-center hide-md-down">'.s("Statistiques").'</th>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<th class="text-center highlight-stick-right hide-md-down">'.s("Envoyés").'</th>';
					$h .= '<th class="text-center highlight-stick-both hide-md-down">'.s("Reçus").'</th>';
					$h .= '<th class="text-center highlight-stick-both hide-md-down">'.s("Ouverts").'</th>';
					$h .= '<th class="text-center highlight-stick-left hide-md-down">'.s("Bloqués").'</th>';
				$h .= '</tr>';

			$h .= '</thead>';

			$h .= '<tbody>';

			foreach($cContact as $eContact) {

				$h .= '<tr>';

					$h .= '<td class="contact-item-email">';
						$h .= encode($eContact['email']);

						if($eContact['optIn'] === FALSE) {

							$h .= '<div>';
								$h .= s("Refus de recevoir vos communications par e-mail");
							$h .= '</div>';

						}

					$h .= '</td>';

					$h .= '<td class="contact-item-email">';
					$h .= '</td>';

					$h .= '<td class="text-center">';
						if($eContact['optIn'] === NULL) {
							$h .= \Asset::icon('question-circle');
						} else if($eContact['optIn'] === TRUE) {
							$h .= \Asset::icon('check-circle', ['class' => 'color-success']);
						} else if($eContact['optIn'] === FALSE) {
							$h .= \Asset::icon('x-circle', ['class' => 'color-danger']);
						}
					$h .= '</td>';

					$h .= '<td class="text-center">';
						if($eContact['optOut'] === TRUE) {
							$h .= \Asset::icon('check-circle', ['class' => 'color-success']);
						} else if($eContact['optOut'] === FALSE) {
							$h .= \Asset::icon('x-circle', ['class' => 'color-danger']);
						}
					$h .= '</td>';

					$h .= '<td>';
						if($eContact['lastSent'] !== NULL) {

							$h .= \util\DateUi::ago($eContact['lastSent']);

							// afficher lastEmail

						}
					$h .= '</td>';

					$h .= '<td class="contact-item-stat highlight-stick-right">';
						$h .= '<span style="font-size: 1.25rem">'.$eContact['sent'].'</span>';
					$h .= '</td>';

					$h .= '<td class="contact-item-stat highlight-stick-both">';
						if($eContact['sent'] > 0) {
							$h .= '<span>'.$eContact['delivered'].'</span>';
							$h .= '<div class="contact-item-stat-percent">'.s("{value} %", round($eContact['delivered'] / $eContact['sent'] * 100)).'</div>';
						}
					$h .= '</td>';

					$h .= '<td class="contact-item-stat highlight-stick-both">';
						if($eContact['sent'] > 0) {
							$h .= '<span>'.$eContact['opened'].'</span>';
							$h .= '<div class="contact-item-stat-percent">'.s("{value} %", round($eContact['opened'] / $eContact['sent'] * 100)).'</div>';
						}
					$h .= '</td>';

					$h .= '<td class="contact-item-stat highlight-stick-left">';
						if($eContact['sent'] > 0) {
							$blocked = $eContact['failed'] + $eContact['spam'];
							$h .= '<span '.($blocked > 0 ? 'class="color-danger"' : '').'>'.$blocked.'</span>';
							$h .= '<div class="contact-item-stat-percent">'.s("{value} %", round($blocked / $eContact['sent'] * 100)).'</div>';
						}
					$h .= '</td>';

				$h .= '</tr>';

			}

			$h .= '</tbody>';

		$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function toggleOptOut(Contact $eContact) {

		return \util\TextUi::switch([
			'id' => 'contact-switch-'.$eContact['id'],
			'disabled' => $eContact->canWrite() === FALSE,
			'data-ajax' => $eContact->canWrite() ? '/mail/contact:doUpdateOptOut' : NULL,
			'post-id' => $eContact['id'],
			'post-opt-out' => $eContact['optOut'] ? FALSE : TRUE
		], $eContact['optOut']);

	}

	public function getOpt(Contact $eContact): string {

		$h = '<h3>'.s("Préférences de communication par e-mail").'</h3>';
		$h .= '<p class="util-info">'.s("Les préférences de communication ne s'appliquent qu'aux campagnes de communications que vous faites auprès de {value}. Les e-mails directement liés aux commandes et à la facturation sont toujours envoyés.", ['value' => '<u>'.encode($eContact['email']).'</u>']).'</p>';

		$h .= '<table class="mb-3">';
			$h .= '<tr>';
				$h .= '<td style="white-space: nowrap"><b>'.s("Opt-in").'</b></td>';
				$h .= '<td class="hide-xs-down">';
					$h .= '<div class="util-annotation">'.s("Consentement donné par le client pour recevoir vos communications par e-mail").'</div>';
				$h .= '</td>';
				$h .= '<td>';
					$h .= match($eContact->getOptIn()) {
						NULL => s("Pas de consentement explicite"),
						TRUE => '<span class="color-success">'.\Asset::icon('check-circle-fill', ['class' => 'asset-icon-lg']).' '.s("Acceptation explicite de vos communications").'</span>',
						FALSE => '<span class="color-danger">'.\Asset::icon('x-circle-fill', ['class' => 'asset-icon-lg']).' '.s("Refus explicite de vos communications").'</span>'
					};
				$h .= '</td>';
			$h .= '</tr>';
			$h .= '<tr>';
				$h .= '<td style="white-space: nowrap"><b>'.s("Opt-out").'</b></td>';
				$h .= '<td class="hide-xs-down">';
					$h .= '<div class="util-annotation">'.s("Envoyer des communications par e-mail à ce client").'</div>';
				$h .= '</td>';
				$h .= '<td>';
					if($eContact->getOptIn() === FALSE) {
						$h .= s("Non configurable");
					} else {
						$h .= $this->toggleOptOut($eContact);
					}
				$h .= '</td>';
			$h .= '</tr>';
		$h .= '</table>';

		if($eContact->getOptIn() === FALSE) {
			$h .= '<div class="util-box-danger mb-2">'.s("Ce client a refusé explicitement de recevoir des e-mails de communication de votre part. Outrepasser ce refus de consentement conduira à l'exclusion de votre ferme de la plateforme {siteName}.").'</div>';
		}

		return $h;

	}

	public function updateOptIn(\Collection $cContact): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/mail/contact:doUpdateOptIn');

			$h .= '<h3>'.s("Recevoir les communications des producteurs").'</h3>';

			$h .= '<p class="util-info">';
				$h .= s("Vos producteurs sont susceptibles de vous envoyer des communications par e-mail, selon une fréquence et un contenu qu'ils choisissent eux-mêmes. Vous pouvez choisir de recevoir ces communications ou les refuser.");
			$h .= '</p>';

			foreach($cContact as $eContact) {

				$h .= $form->group(
					\farm\FarmUi::link($eContact['farm'], TRUE),
					$form->yesNo('farms['.$eContact['farm']['id'].']', $eContact['optIn'] ?? TRUE, [
						'yes' => s("Oui, les recevoir"),
						'no' => s("Ne rien recevoir")
					])
				);

			}

			$h .= $form->group(
				content: $form->submit(s("Enregistrer mes préférences"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-contact-email',
			title: s("Préférences de communication par e-mail"),
			body: $h
		);

	}

}
?>
