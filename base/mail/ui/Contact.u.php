<?php
namespace mail;

class ContactUi {

	public function __construct() {

		\Asset::css('mail', 'contact.css');

	}

	public function create(Contact $eContact): \Panel {

		$form = new \util\FormUi();

		$eFarm = $eContact['farm'];

		$h = '';

		$h .= $form->openAjax('/mail/contact:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicGroup($eContact, 'email*');

			$h .= $form->group(
				content: $form->submit(s("Ajouter le contact"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-contact-create',
			title: s("Ajouter un contact"),
			body: $h
		);

	}

	public function getSearch(\farm\Farm $eFarm, \Search $search): string {

		$form = new \util\FormUi();

		$h = '<div id="contact-search" class="util-block-search stick-xs '.($search->empty() ? 'hide' : '').'">';

			$h .= $form->openAjax(\farm\FarmUi::urlCommunicationsMailing($eFarm), ['method' => 'get', 'id' => 'form-search']);
				$h .= '<div>';
					$h .= $form->text('email', $search->get('email'), ['placeholder' => s("Adresse e-mail")]);
					$h .= $form->select('optIn', [
						'no' => s("Refus de consentement")
					], $search->get('optIn'), ['placeholder' => s("Tous consentements")]);
					$h .= $form->select('category', [
						\selling\Customer::PRO => s("Clients professionnels"),
						\selling\Customer::PRIVATE => s("Clients particuliers")
					], $search->get('category'), ['placeholder' => s("Clientèle")]);
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.\farm\FarmUi::urlCommunicationsMailing($eFarm).'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getExport(\farm\Farm $eFarm, \Collection $cContact) {

		$h = '<div class="util-block">';

			$h .= '<h3>'.s("Liste des e-mails").'</h3>';

			$h .= '<p class="util-danger">'.s("Les clients pour qui vous avez désactivé l'envoi des e-mails ainsi que ceux qui ont refusé vos communications ne sont pas présents dans cette liste. En envoyant des e-mails non sollicités ou en refusant de désabonner les clients qui le souhaitent, vous engagez votre propre responsabilité et vous exposez à un bannissement à vie de {siteName}. <b>Si vous faites le choix de ne pas respecter le cadre légal concernant votre politique de gestion des e-mails, n'utilisez pas {siteName}.</b>").'</p>';

			$emails = $cContact->getColumn('email');

			if($emails) {
				$h .= '<code id="contact-emails">'.implode(', ', array_map('encode', $emails)).'</code>';
				$h .= '<a onclick="doCopy(this)" data-selector="#contact-emails" data-message="'.s("Copié !").'" class="btn btn-secondary mb-1 mt-1">'.s("Copier la liste dans le presse-papier").'</a>';
			} else {
				$h .= '<p class="util-info">'.s("Aucune adresse e-mail ne correspond aux critères.").'</p>';
			}

			$h .= '<br/><br/>';

			$h .= '<h3>'.s("Lien à donner à vos clients pour se désabonner de vos communications").'</h3>';
			$h .= '<code>'.\Lime::getUrl().\farm\FarmUi::url($eFarm).'/optIn</code>';

		$h .= '</div>';

		return $h;

	}

	public function getList(\farm\Farm $eFarm, \Collection $cContact, int $nContact, int $page, \Search $search) {

		$h = '';

		if($cContact->empty()) {

			$h .= '<div class="util-empty">'.s("Il n'y a aucun contact à afficher.").'</div>';
			return $h;

		}
/*
		$h .= '<p class="util-info">'.s("Les préférences d'envoi des e-mails ne s'appliquent qu'aux campagnes de communications que vous faites auprès de vos clients. Les e-mails directement liés aux commandes et à la facturation sont toujours envoyés.").'</p>';
*/

		if($cContact->count() > 0) {

			$h .= '<div class="mb-1">';
				$h .= '<a data-ajax="/mail/contact:export?farm='.$eFarm['id'].'&'.http_build_query($_GET).'" data-ajax-method="get" class="btn btn-secondary">';
					if($search->empty()) {
						$h .= s("Récupérer les adresses e-mail");
					} else {
						$h .= s("Récupérer les adresses e-mail de cette recherche");
					}
				$h .= '</a>';
			$h .= '</div>';
			$h .= '<div id="contact-export"></div>';

		}

		$h .= '<div class="stick-md util-overflow-xs">';

			$h .= '<table class="contact-item-table tr-even">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th rowspan="2">'.$search->linkSort('email', s("Adresse e-mail")).'</th>';
						$h .= '<th rowspan="2" class="text-center hide-sm-down">'.$search->linkSort('createdAt', s("Depuis"), SORT_DESC).'</th>';
						$h .= '<th rowspan="2" class="text-center">'.s("Envoyer<br/>des e-mails").'</th>';
						$h .= '<th rowspan="2" class="text-center">'.s("Consentement pour<br/>recevoir des e-mails").'</th>';
						$h .= '<th rowspan="2" class="hide-xl-down">'.$search->linkSort('lastSent', s("Dernier e-mail<br/>envoyé il y a"), SORT_DESC).'</th>';
						$h .= '<th colspan="4" class="text-center hide-md-down">'.s("Statistiques depuis le 14 juin 2025 *").'</th>';
						$h .= '<th rowspan="2"></th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="text-center highlight-stick-right hide-md-down">'.$search->linkSort('sent', s("Envoyés"), SORT_DESC).'</th>';
						$h .= '<th class="text-center highlight-stick-both hide-md-down">'.$search->linkSort('delivered', s("Reçus"), SORT_DESC).'</th>';
						$h .= '<th class="text-center highlight-stick-both hide-md-down">'.$search->linkSort('opened', s("Lus"), SORT_DESC).'</th>';
						$h .= '<th class="text-center highlight-stick-left hide-md-down">'.$search->linkSort('blocked', s("Bloqués"), SORT_DESC).'</th>';
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cContact as $eContact) {

					$h .= '<tr>';

						$h .= '<td>';
							$h .= '<div class="contact-item-email">'.encode($eContact['email']).'</div>';

							if($eContact['cCustomer']->notEmpty()) {

								$h .= '<div class="util-annotation">';

									foreach($eContact['cCustomer'] as $position => $eCustomer) {

										if($position > 0) {
											$h .= ' / ';
										}

										$h .= '<a href="/client/'.$eCustomer['id'].'">'.encode($eCustomer->getName()).'</a>';

									}
								$h .= '</div>';

							}

						$h .= '</td>';

						$h .= '<td class="text-center hide-sm-down">';
							$h .= \util\DateUi::numeric($eContact['createdAt'], \util\DateUi::DATE);
						$h .= '</td>';

						$h .= '<td class="text-center">';
							if($eContact['optIn'] === FALSE) {
								$h .= '<div class="color-muted">'.s("Impossible").'</div>';
							} else {
								$h .= $this->toggleActive($eContact);
							}
						$h .= '</td>';

						$h .= '<td class="text-center">';
							if($eContact['optIn'] === NULL) {
								$h .= \Asset::icon('question-circle');
							} else if($eContact['optIn'] === TRUE) {
								$h .= '<div class="color-success">'.\Asset::icon('check-circle').' '.s("Acceptation").'</div>';
							} else if($eContact['optIn'] === FALSE) {
								$h .= '<div class="color-danger">'.\Asset::icon('x-circle').' '.s("Refus").'</div>';
							}
						$h .= '</td>';

						$h .= '<td class="hide-xl-down">';

							if($eContact['lastSent'] !== NULL) {
								$h .= \util\DateUi::secondToDuration(time() - strtotime($eContact['lastSent']), \util\DateUi::AGO, maxNumber: 1);
							} else {
								$h .= '/';
							}

						$h .= '</td>';

						$h .= '<td class="contact-item-stat highlight-stick-right hide-md-down">';
							$h .= '<span style="font-size: 1.25rem">'.$eContact['sent'].'</span>';
						$h .= '</td>';

						$h .= '<td class="contact-item-stat highlight-stick-both hide-md-down">';
							if($eContact['sent'] > 0) {
								$h .= '<span>'.$eContact['delivered'].'</span>';
								$h .= '<div class="contact-item-stat-percent">'.s("{value} %", round($eContact['delivered'] / $eContact['sent'] * 100)).'</div>';
							}
						$h .= '</td>';

						$h .= '<td class="contact-item-stat highlight-stick-both hide-md-down">';
							if($eContact['sent'] > 0) {
								$h .= '<span>'.$eContact['opened'].'</span>';
								$h .= '<div class="contact-item-stat-percent">'.s("{value} %", round($eContact['opened'] / $eContact['sent'] * 100)).'</div>';
							}
						$h .= '</td>';

						$h .= '<td class="contact-item-stat highlight-stick-left hide-md-down">';
							if($eContact['sent'] > 0) {
								$blocked = $eContact['failed'] + $eContact['spam'];
								$h .= '<span '.($blocked > 0 ? 'class="color-danger"' : '').'>'.$blocked.'</span>';
								$h .= '<div class="contact-item-stat-percent">'.s("{value} %", round($blocked / $eContact['sent'] * 100)).'</div>';
							}
						$h .= '</td>';

						$h .= '<td class="td-min-content">';
							$h .= '<a data-ajax="/mail/contact:doDelete" post-id="'.$eContact['id'].'" data-confirm="'.s("Vous allez supprimer un contact. Veuillez noter que, même supprimé, un contact est automatiquement recréé dès qu'un e-mail liés à ses commandes doit lui être envoyé. Continuer ?").'" class="btn btn-danger">'.\Asset::icon('trash').'</a>';
						$h .= '</td>';

					$h .= '</tr>';

				}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		if($nContact !== NULL and $page !== NULL) {
			$h .= \util\TextUi::pagination($page, $nContact / 100);
		}

		$h .= '<div class="util-info">';
			$h .= s("* Les statistiques d'e-mails reçus, lus et bloqués sont des estimations qui ne sont pas fiables à 100 %.");
		$h .= '</div>';

		return $h;

	}

	public function toggleActive(Contact $eContact) {

		return \util\TextUi::switch([
			'id' => 'contact-switch-'.$eContact['id'],
			'disabled' => $eContact->canWrite() === FALSE,
			'data-ajax' => $eContact->canWrite() ? '/mail/contact:doUpdateActive' : NULL,
			'post-id' => $eContact['id'],
			'post-active' => $eContact['active'] ? FALSE : TRUE
		], $eContact['active']);

	}

	public function getOpt(Contact $eContact): string {

		$h = '<h3>'.s("Préférences de communication par e-mail").'</h3>';
		$h .= '<p class="util-info">'.s("Les préférences d'envoi des e-mails ne s'appliquent qu'aux campagnes de communications que vous faites auprès de {value}. Les e-mails directement liés aux commandes et à la facturation sont toujours envoyés.", ['value' => '<u>'.encode($eContact['email']).'</u>']).'</p>';

		$h .= '<table class="mb-3">';
			$h .= '<tr>';
				$h .= '<td style="white-space: nowrap"><b>'.s("Envoyer des communications par e-mail à ce client").'</b></td>';
				$h .= '<td>';
					if($eContact->getOptIn() === FALSE) {
						$h .= s("Impossible");
					} else {
						$h .= $this->toggleActive($eContact);
					}
				$h .= '</td>';
			$h .= '</tr>';
			$h .= '<tr>';
				$h .= '<td style="white-space: nowrap"><b>'.s("Consentement donné par le client pour recevoir vos communications par e-mail").'</b></td>';
				$h .= '<td>';
					$h .= match($eContact->getOptIn()) {
						NULL => \Asset::icon('question-circle').' '.s("Pas de consentement explicite"),
						TRUE => '<span class="color-success">'.\Asset::icon('check-circle-fill', ['class' => 'asset-icon-lg']).' '.s("Acceptation explicite de vos communications").'</span>',
						FALSE => '<span class="color-danger">'.\Asset::icon('x-circle-fill', ['class' => 'asset-icon-lg']).' '.s("Refus explicite de vos communications").'</span>'
					};
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

	public static function p(string $property): \PropertyDescriber {

		$d = Contact::model()->describer($property, [
			'email' => s("Adresse e-mail"),
		]);

		switch($property) {


		}

		return $d;

	}

}
?>
