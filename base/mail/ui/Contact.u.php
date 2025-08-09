<?php
namespace mail;

class ContactUi {

	public function __construct() {

		\Asset::js('mail', 'contact.js');
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

		$h = '<div id="contact-search" class="util-block-search stick-xs '.($search->empty(['source', 'cShop']) ? 'hide' : '').'">';

			$h .= $form->openAjax(\farm\FarmUi::urlCommunicationsMailing($eFarm), ['method' => 'get', 'id' => 'form-search']);
				$h .= '<div>';

					if($search->get('source') === NULL) {

						$h .= $form->text('email', $search->get('email'), ['placeholder' => s("Adresse e-mail")]);
						$h .= $form->inputGroup(
							$form->checkbox('newsletter', 'yes', ['checked' => $search->get('newsletter') === 'yes', 'callbackLabel' => fn($input) => $input.'  '.$form->addon(s("Uniquement inscrits à la newsletter")), 'callbackFieldAttributes' => ['class' => 'bg-white']])
						);

					}

					$h .= $form->select('shop', $search->get('cShop'), $search->get('shop'), ['placeholder' => s("Clients d'une boutique")]);

					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.\farm\FarmUi::urlCommunicationsMailing($eFarm).'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function query(\PropertyDescriber $d, bool $multiple = FALSE) {

		$d->prepend ??= \Asset::icon('at');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Tapez une adresse e-mail");
		$d->multiple = $multiple;
		$d->group += ['wrapper' => 'contact'];

		$d->autocompleteUrl = '/mail/contact:query';
		$d->autocompleteResults = function(Contact $e) {
			return self::getAutocomplete($e);
		};

	}

	public static function getAutocomplete(Contact $eContact): array {

		\Asset::css('media', 'media.css');
		
		$html = '<div>';
			$html .= encode($eContact['email']).'<br/>';

			if($eContact['cCustomer']->notEmpty()) {

				$html .= '<small class="color-muted">';

					$position = 0;

					foreach($eContact['cCustomer'] as $eCustomer) {
						if($position++ > 0) {
							$html .= ' / ';
						}
						$html .= $eCustomer->getName();
					}

				$html .= '</small>';

			}

		$html .= '</div>';

		//$te$eCustomer['name'].' / '.$eCustomer->getTextCategory(short: TRUE);
		
		return [
			'value' => $eContact['email'],
			'itemHtml' => $html,
			'itemText' => $eContact['email']
		];

	}

	public static function getAutocompleteCreate(\farm\Farm $eFarm): array {

		$item = \Asset::icon('plus-circle');
		$item .= '<div>'.s("Ajouter un nouveau contact").'</div>';

		return [
			'type' => 'link',
			'link' => '/mail/contact:create?farm='.$eFarm['id'],
			'itemHtml' => $item
		];

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

		if($cContact->count() > 0) {

			$h .= '<div class="mb-1">';
				$h .= '<a data-ajax="/mail/contact:export?farm='.$eFarm['id'].'&'.http_build_query($_GET).'" data-ajax-method="get" class="btn btn-secondary" id="contact-export-link">';
					if($search->empty(['source', 'cShop'])) {
						$h .= s("Récupérer les adresses e-mail");
					} else {
						$h .= s("Récupérer les adresses e-mail de cette recherche");
					}
				$h .= '</a>';
			$h .= '</div>';
			$h .= '<div id="contact-export" '.($search->get('source') === 'shop' ? 'onrender="Contact.loadExport()"' : '').'></div>';

		}

		$h .= '<div class="stick-md util-overflow-sm">';

			$h .= '<table class="contact-item-table tr-even">';

				$h .= '<thead class="thead-sticky">';

					$h .= '<tr>';
						$h .= '<th rowspan="2">'.$search->linkSort('email', s("Adresse e-mail")).'</th>';
						$h .= '<th rowspan="2" class="text-center hide-sm-down">'.$search->linkSort('createdAt', s("Depuis"), SORT_DESC).'</th>';
						$h .= '<th rowspan="2" class="text-center">'.s("Consentement<br/>pour recevoir<br/>des e-mails").'</th>';
						$h .= '<th rowspan="2" class="text-center highlight-stick-right">'.s("Envoyer<br/>des e-mails").'</th>';
						$h .= '<th rowspan="2" class="text-center highlight-stick-left">'.s("Newsletter").'</th>';
						$h .= '<th rowspan="2" class="hide-xl-down">'.$search->linkSort('lastSent', s("Dernier<br/>e-mail<br/>envoyé"), SORT_DESC).'</th>';
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
							if($eContact['optIn'] === NULL) {
								$h .= \Asset::icon('question-circle');
							} else if($eContact['optIn'] === TRUE) {
								$h .= '<div class="color-success">'.\Asset::icon('check-circle').' '.s("Acceptation").'</div>';
							} else if($eContact['optIn'] === FALSE) {
								$h .= '<div class="color-danger">'.\Asset::icon('x-circle').' '.s("Refus").'</div>';
							}
						$h .= '</td>';

						if($eContact['optIn'] === FALSE) {
							$h .= '<td class="text-center highlight-stick-alone" colspan="2">';
								$h .= '<div class="color-muted">'.s("Impossible").' <small>'.s("(refus du client)").'</small></div>';
							$h .= '</td>';
						} else if($eContact['activeCustomer'] === FALSE) {
							$h .= '<td class="text-center highlight-stick-alone" colspan="2">';
								$h .= '<div class="color-muted">'.s("Impossible").' <small>'.s("(client désactivé)").'</small></div>';
							$h .= '</td>';
						} else {

							$h .= '<td class="text-center highlight-stick-right">';
								$h .= $this->toggleActive($eContact);
							$h .= '</td>';
							$h .= '<td class="text-center highlight-stick-left">';
								$h .= $this->toggleNewsletter($eContact);
							$h .= '</td>';

						}

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
			'id' => 'contact-active-switch-'.$eContact['id'],
			'disabled' => $eContact->canWrite() === FALSE,
			'data-ajax' => $eContact->canWrite() ? '/mail/contact:doUpdateActive' : NULL,
			'post-id' => $eContact['id'],
			'post-active' => $eContact['active'] ? FALSE : TRUE
		], $eContact['active']);

	}

	public function toggleNewsletter(Contact $eContact) {

		return \util\TextUi::switch([
			'id' => 'contact-newsletter-switch-'.$eContact['id'],
			'disabled' => $eContact->canWrite() === FALSE,
			'data-ajax' => $eContact->canWrite() ? '/mail/contact:doUpdateNewsletter' : NULL,
			'post-id' => $eContact['id'],
			'post-newsletter' => $eContact['newsletter'] ? FALSE : TRUE
		], $eContact['newsletter']);

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
