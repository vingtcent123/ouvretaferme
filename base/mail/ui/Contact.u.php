<?php
namespace mail;

class ContactUi {

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
