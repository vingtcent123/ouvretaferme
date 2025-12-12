<?php
namespace shop;

class ShopUi {

	public function __construct() {

		\Asset::css('shop', 'shop.css');
		\Asset::js('shop', 'shop.js');

	}

	public function join(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/shop/:doJoin', ['id' => 'shop-join']);

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= '<div class="util-block-help">';
				$h .= s("Pour rejoindre une boutique collective, munissez-vous du code que l'administrateur de la boutique vous a donné et saisissez-le dans le formulaire ci-dessous.");
			$h .= '</div>';

			$h .= $form->group(
				s("Code d'invitation"),
				$form->text('key')
			);

			$h .= $form->group(
				content: $form->submit(s("Valider"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-shop-join',
			title: s("Rejoindre une boutique collective"),
			body: $h
		);

	}

	public function checkJoin(\farm\Farm $eFarm, Shop $eShop, bool $hasJoin): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/shop/:doJoin', ['id' => 'shop-join']);

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->hidden('key', $eShop->getSharedKey());

			if($hasJoin === FALSE) {
				$h .= '<div class="util-info">';
					$h .= s("Nous avons trouvé une boutique correspondant au code d'invitation que vous avez saisi !");
				$h .= '</div>';
			}

			$h .= '<h3>'.encode($eShop['name']).'</h3>';

			if($eShop['description']) {
				$h .= '<div class="util-block-gradient">'.new \editor\EditorUi()->value($eShop['description']).'</div>';
			}

			if($hasJoin) {
				$h .= '<div class="util-block util-block-dark bg-success">'.s("Vous avez déjà rejoint cette boutique !").'</div>';
			} else {
				$h .= $form->hidden('do', TRUE);
				$h .= $form->submit(s("Rejoindre la boutique"));
			}

		$h .= $form->close();

		return $h;

	}

	public function create(Shop $eShop): \Panel {

		$eFarm = $eShop['farm'];

		$form = new \util\FormUi();

		if($eShop['shared'] === FALSE) {

			$eShop->merge([
				'name' => s("Boutique de {value}", $eFarm['name']),
				'fqn' => toFqn($eFarm['name']),
			]);

		}

		if($eShop['shared'] === NULL) {

			$h = '<div class="util-block-help">';
				$h .= s("Vous devez choisir si vous souhaitez créer une boutique en ligne dédiée à la vente de votre seule production, ou si cette boutique en ligne sera partagée avec d'autres producteurs. Le choix que vous faites maintenant ne pourra pas être modifié par la suite, mais vous pouvez toujours créer autant de boutiques que vous le souhaitez.");
			$h .= '</div>';

			$h .= '<div class="util-buttons mb-3">';

				$h .= '<a href="/shop/:create?farm='.$eShop['farm']['id'].'&shared=0" class="util-button">';
					$h .= '<div>';
						$h .= '<h4>'.s("Créer une boutique personnelle").'</h4>';
						$h .= '<div class="util-button-text">'.s("Vous serez le seul producteur à vendre sur cette boutique.").'</div>';
					$h .= '</div>';
					$h .= \Asset::icon('person-fill-lock');
				$h .= '</a>';

				$h .= '<a href="/shop/:create?farm='.$eShop['farm']['id'].'&shared=1" class="util-button">';
					$h .= '<div>';
						$h .= '<h4>'.s("Créer une boutique collective").'</h4>';
						$h .= '<div class="util-button-text">'.s("Vous pourrez inviter d'autres producteurs à vendre leur production sur cette boutique.").'</div>';
					$h .= '</div>';
					$h .= \Asset::icon('people-fill');
				$h .= '</a>';

			$h .= '</div>';

			$h .= '<h3>'.s("Vous avez un code d'invitation ?").'</h3>';

			$h .= '<div>';

				$h .= '<a href="/shop/:join?farm='.$eShop['farm']['id'].'&shared=1" class="btn btn-outline-primary">';
					$h .= s("Rejoindre une boutique collective");
				$h .= '</a>';

			$h .= '</div>';

		} else {

			$h = $form->openAjax('/shop/:doCreate', ['id' => 'shop-create']);

				$h .= $form->asteriskInfo();

				$h .= $form->hidden('farm', $eFarm['id']);
				$h .= $form->hidden('shared', $eShop['shared']);

				if($eShop['shared']) {

					$content = '<h3>'.s("Démarrer une boutique collective").'</h3>';
					$content .= '<p>'.s("Une boutique collective permet à plusieurs producteurs de vendre sur la même boutique, et de partager ses créneaux de commercialisation.").'</p>';
					$content .= '<p>'.s("Nous vous recommandons de bien travailler le mode de fonctionnement de votre collectif avant d'engager la création d'une boutique en commun, à la fois en termes organisationnels, logistiques et financiers afin que votre projet soit un succès.").'</p>';
					$content .= '<p>'.s("Vous devez tenir compte des éléments suivants pour créer une boutique collective :").'</p>';
					$content .= '<ul>';
						$content .='<li>'.s("Une boutique collective est administrée uniquement par la ferme qui l'a créée").'</li>';
						$content .='<li>'.s("La ferme à l'origine de la création d'une boutique collective ne peut pas y vendre sa production").'</li>';
					$content .= '</ul>';
					$content .= '<p>'.s("Nous vous recommandons donc de <link>créer une ferme dédiée</link> pour créer une boutique collective</b> !", ['link' => '<a href="/farm/farm:create">']).'</p>';

					$h .= $form->group(content: '<div class="util-block-help">'.$content.'</div>');

				}

				$h .= $form->dynamicGroups($eShop, ['name*', 'type*', 'fqn*', 'email'.($eShop['shared'] ? '*' : '')], [
					'type*' => self::getTypeDescriber($eFarm, 'create')
				]);

				$h .= $this->getOpeningFields($form, $eShop);

				$h .= $form->dynamicGroups($eShop, ['description'], [
					'type*' => self::getTypeDescriber($eFarm, 'create')
				]);

				$submit = '';

				if($eShop['shared']) {
					$submit .= '<div class="util-block-help">';
						$submit .= '<h3>'.s("J'ai bien noté que").'</h3>';
						$submit .= '<ul>';
							$submit .='<li>'.s("La ferme {farm} est à l'origine de cette boutique collective et que seuls les utilisateurs enregistrés comme exploitants de cette ferme pourront l'administrer", ['farm' => '<b>'.encode($eShop['farm']['name']).'</b>']).'</li>';
							$submit .='<li>'.s("La ferme {farm} ne pourra pas vendre sa production dans cette boutique collective étant donné qu'elle en est à l'origine", ['farm' => '<b>'.encode($eShop['farm']['name']).'</b>']).'</li>';
						$submit .= '</ul>';
					$submit .= '</div>';
				}

				$submit .= $form->submit(s("Créer la boutique"));

				$h .= $form->group(
						content: $submit
				);

			$h .= $form->close();

		}

		return new \Panel(
			id: 'panel-shop-create',
			title: $eShop['shared'] ? s("Nouvelle boutique collective") : s("Nouvelle boutique personnelle"),
			body: $h
		);

	}

	public function update(Shop $eShop, \farm\Farm $eFarm, \Collection $cCustomize, \selling\Sale $eSaleExample): string {

		$h = '<div class="tabs-h" id="selling-configure" onrender="'.encode('Lime.Tab.restore(this, "settings", '.(get_exists('tab') ? '"'.GET('tab', ['settings', 'payment', 'points'], 'settings').'"' : 'null').')').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="settings" onclick="Lime.Tab.select(this)">'.s("Général").'</a>';
				$h .= '<a class="tab-item" data-tab="payment" onclick="Lime.Tab.select(this)">';
					$h .= s("Moyens de paiement");
					if($eShop['shared'] === FALSE) {
						$h .= ' <span class="tab-item-count">'.$eShop->countPayments().'</span>';
					}
				$h .= '</a>';
				$h .= '<a class="tab-item" data-tab="terms" onclick="Lime.Tab.select(this)">'.s("Conditions de vente").'</a>';
				$h .= '<a class="tab-item" data-tab="mail" onclick="Lime.Tab.select(this)">'.s("E-mails").'</a>';
				$h .= '<a class="tab-item" data-tab="customize" onclick="Lime.Tab.select(this)">'.s("Personnalisation").'</a>';
			$h .= '</div>';

			$h .= '<div class="tab-panel selected" data-tab="settings">';
				$h .= $this->updateGeneral($eShop, $eFarm);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="payment">';
				$h .= $this->updatePayment($eShop, $eFarm);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="terms">';
				$h .= $this->updateTerms($eShop);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="mail">';
				$h .= $this->updateMail($eShop, $cCustomize, $eSaleExample);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="customize">';
				$h .= $this->updateCustomize($eShop);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function updateGeneral(Shop $eShop, \farm\Farm $eFarm): string {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/shop/configuration:doUpdate');

		$h .= $form->hidden('id', $eShop['id']);

		$h .= $form->dynamicGroups($eShop, ['name', 'type', 'fqn', 'email'], [
			'type' => self::getTypeDescriber($eFarm, 'update')
		]);

		$h .= $this->getOpeningFields($form, $eShop);

		$update = ['approximate', 'outOfStock', 'orderMin', 'shipping', 'shippingUntil', 'limitCustomers', 'hasPoint', 'comment', 'commentCaption', 'description'];

		if($eShop['shared']) {
			array_delete($update, 'shipping');
			array_delete($update, 'shippingUntil');
			$update[] = 'sharedGroup';
			$update[] = 'sharedCategory';
		}

		$h .= $form->dynamicGroups($eShop, $update, [
			'comment' => function(\PropertyDescriber $d) {
				$d->attributes['callbackRadioAttributes'] = fn() => ['onclick' => 'ShopManage.changeComment(this)'];
			},
			'commentCaption' => $eShop['comment'] ? NULL : function(\PropertyDescriber $d) {
				$d->group['class'] = 'hide';
			}
		]);

		$h .= $form->group(
			self::p('logo')->label,
			new \media\ShopLogoUi()->getCamera($eShop, size: '20rem')
		);

		$h .= '<br/>';

		$h .= $form->group(
			content: $form->submit(s("Mettre à jour la boutique"))
		);

		$h .= $form->close();

		return $h;

	}

	public function updateInactivePoint(Shop $eShop): string {

		$h = '<div class="util-block-help">';
			$h .= '<h4>'.s("Le choix du mode de livraison est désactivé sur votre boutique").'</h4>';
			$h .= '<p>'.s("Vos clients n'ont actuellement pas besoin de choisir de mode de livraison lorsqu'ils commandent sur la boutique, c'est à vous de les informer de la façon dont ils peuvent retirer leurs commandes.").'</p>';
			$h .= '<a data-ajax="/shop/:doUpdatePoint" post-id="'.$eShop['id'].'" post-has-point="1" data-confirm="'.s("Souhaitez-vous réellement réactiver le choix du mode de livraison sur votre boutique ?").'" class="btn btn-secondary">'.s("Réactiver le choix du mode de livraison").'</a>';
		$h .= '</div>';

		return $h;

	}

	protected function updatePayment(Shop $eShop, \farm\Farm $eFarm): string {

		if($eShop['hasPayment']) {
			return $this->updateActivePayment($eShop, $eFarm);
		} else {
			return $this->updateInactivePayment($eShop);
		}

	}

	protected function updateActivePayment(Shop $eShop, \farm\Farm $eFarm): string {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/shop/configuration:doUpdatePayment');

		$h .= $form->hidden('id', $eShop['id']);

		$h .= $form->group(content: '<h3>'.s("Paiement en direct avec le producteur").'</h3>');

		$h .= $form->dynamicGroup($eShop, 'paymentOffline');
		$h .= $form->dynamicGroup($eShop, 'paymentOfflineHow', function(\PropertyDescriber $d) use($eShop) {
			$d->group['class'] = $eShop['paymentOffline'] ? '' : 'hide';
		});

		$h .= $form->group(content: '<h3>'.s("Paiement par virement bancaire").'</h3>');

		$content = '<p>'.s("Le paiement par virement bancaire fonctionne selon le principe suivant :").'</p>';
		$content .= '<ol>';
			$content .= '<li>'.s("Vos clients passent leurs commandes sans les régler immédiatement").'</li>';
			$content .= '<li>'.s("Chaque début de mois, vous leur éditez une facture que vous pouvez envoyer automatiquement par e-mail").'</li>';
			$content .= '<li>'.s("Vous faites le rapprochement entre votre compte bancaire habituel et les factures éditées pour vérifier que tous vos clients ont bien réglé leur facture").'</li>';
		$content .= '</ol>';
		$content .= '<p>'.s("Le paiement par virement bancaire bancaire est surtout intéressant pour vos clients qui passent plusieurs commandes dans le mois et pour vous éviter les commissions liées au paiement par carte bancaire.").'</p>';

		$h .= $form->group(content: '<div class="util-block-help">'.$content.'</div>');

		$h .= $form->dynamicGroup($eShop, 'paymentTransfer');
		$h .= $form->dynamicGroup($eShop, 'paymentTransferHow', function(\PropertyDescriber $d) use($eShop) {
			$d->group['class'] = $eShop['paymentTransfer'] ? '' : 'hide';
		});

		$h .= '<br/>';

		$h .= $form->group(content: '<h3>'.s("Paiement en ligne avec {icon} Stripe", ['icon' => \Asset::icon('stripe')]).'</h3>');

		if($eShop['stripe']->empty()) {

			$content = '<p>'.s("Pour utiliser les options suivantes, vous avez besoin de configurer préalablement votre compte de paiement en ligne sur Stripe.").'</p>';
			$content .= '<a href="/payment/stripe:manage?farm='.$eShop['farm']['id'].'" class="btn btn-secondary" target="_blank">'.s("Configurer le paiement en ligne").'</a>';

			$h .= $form->group(content: '<div class="util-block-util-block-help">'.$content.'</div>');

		} else {
			$h .= $form->group(content: '<div class="util-block-help">'.\payment\StripeFarmUi::getWarning().'</div>');

			if($eShop['shared']) {
				$alert = '<div class="util-block util-block-dark bg-danger">';
					$alert .= '<h4>'.s("Attention !").'</h4>';
					$alert .= '<p>'.s("Le compte Stripe configuré sur {value} recevra l'intégralité des paiements réalisés par les clients et ce sera la responsabilité du propriétaire de ce compte Stripe de répartir le produit des ventes entre les membres du collectif.", '<u>'.encode($eFarm['name']).'</u>').'</p>';
				$alert .= '</div>';

				$h .= $form->group(content: $alert);
			}

		}

		$h .= $form->dynamicGroup($eShop, 'paymentCard');

		$h .= '<br/>';

		$h .= $form->group(
			content: $form->submit(s("Mettre à jour le paiement"))
		);

		$hasPayment = '<div class="util-block-important mt-2" id="shop-payment-disabled">';
			$hasPayment .= '<h4>'.s("Désactiver le choix du moyen de paiement").'</h4>';
			$hasPayment .= '<p>'.s("Vos clients doivent choisir explicitement un moyen de paiement sur votre boutique, parmi ceux que vous avez activés. Vous pouvez <link>désactiver la page de choix du moyen de paiement</link> si vous n'acceptez que le paiement en direct et que vous souhaitez simplifier l'interface de commande en ligne pour vos clients.", ['link' => '<a data-ajax="/shop/:doUpdatePayment" post-id="'.$eShop['id'].'" post-has-payment="0" data-confirm="'.s("Souhaitez-vous réellement désactiver la page de choix du moyen de paiement sur votre boutique ?").'">']).'</p>';
		$hasPayment .= '</div>';

		$h .= $form->group(content: $hasPayment);

		$h .= $form->close();

		return $h;

	}

	protected function updateInactivePayment(Shop $eShop): string {

		$h = '<div class="util-block-help">';
			if($eShop->isPersonal()) {
				$h .= '<h4>'.s("Le choix du moyen de paiement est désactivé sur votre boutique").'</h4>';
				$h .= '<p>'.s("Vos clients n'ont actuellement pas besoin de choisir de moyen de paiement lorsqu'ils commandent sur la boutique, c'est à vous de les informer de la façon dont ils peuvent régler leurs commandes.").'</p>';
				$h .= '<a data-ajax="/shop/:doUpdatePayment" post-id="'.$eShop['id'].'" post-has-payment="1" data-confirm="'.s("Souhaitez-vous réellement réactiver la page de choix du moyen de paiement sur votre boutique ?").'" class="btn btn-secondary">'.s("Réactiver le choix du moyen de paiement").'</a>';
			} else {
				$h .= '<h4>'.s("Le choix du moyen de paiement n'est pas activable sur les boutiques collectives").'</h4>';
				$h .= '<p>'.s("C'est à vous d'informer vos clients de la façon dont ils peuvent régler leurs commandes.").'</p>';
			}
		$h .= '</div>';

		$form = new \util\FormUi();

		$h .= '<br/>';

		$h .= '<h3>'.s("Définir un moyen de paiement par défaut pour la boutique").'</h3>';

		$h .= self::getPaymentMethodInfo();

		$h .= $form->openAjax('/shop/configuration:doUpdatePaymentMethod');
			$h .= $form->hidden('id', $eShop['id']);
			$h .= $form->dynamicGroup($eShop, 'paymentMethod');
			$h .= $form->group(content: $form->submit(s("Enregistrer")));
		$h .= $form->close();

		return $h;

	}

	public static function getPaymentMethodInfo(): string {
		return '<p class="util-helper">'.s("Vous pouvez choisir un moyen de paiement qui sera appliqué aux ventes que vous réaliserez dans cette boutique. Attention, les moyens de paiement par défaut des clients sont prioritaires par rapport au moyen de paiement choisi pour la boutique.").'</p>';
	}

	protected function updateTerms(Shop $eShop): string {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/shop/configuration:doUpdateTerms');

		$h .= $form->hidden('id', $eShop['id']);

		$content = '<div class="util-block-help">';
			$content .= '<h4>'.s("Les conditions générales de vente").'</h4>';
			$content .= '<p>'.s("Dès lors que vous ajoutez des conditions générales de vente à votre boutique, celles-ci sont proposées en lecture à vos clients au moment où ils valident leur commande. Vous avez également la possibilité de demander à vos clients de les accepter explicitement.").'</p>';
		$content .= '</div>';

		if($eShop['terms'] === NULL) {
			$content .= '<div class="util-empty">';
				$content .= '<p>'.s("Actuellement, vous n'avez pas ajouté de conditions générales de vente à votre boutique.").'</p>';
			$content .= '</div>';
		} else {
			$content .= '<div class="util-success">';
				$content .= '<p>'.\Asset::icon('check-lg').' '.s("Vous avez configuré des conditions générales de vente d'une longueur de {value} caractères.", mb_strlen(strip_tags($eShop['terms']))).'</p>';
			$content .= '</div>';
		}

		$h .= $form->group(content: $content);
		$h .= $form->dynamicGroups($eShop, ['terms', 'termsField']);

		$h .= '<br/>';

		$h .= $form->group(
			content: $form->submit(s("Mettre à jour les conditions de vente"))
		);

		$h .= $form->close();

		return $h;

	}

	public function updateMail(Shop $eShop, \Collection $cCustomize, \selling\Sale $eSaleExample): string {

		$h = '';

		$form = new \util\FormUi();

		$h .= '<h2>'.s("Configuration des e-mails").'</h2>';

		$h .= $form->openAjax('/shop/configuration:doUpdateEmail');

		$h .= $form->hidden('id', $eShop['id']);
		$h .= $form->dynamicGroups($eShop, ['emailNewSale', 'emailEndDate']);

		$h .= $form->group(
			content: $form->submit(s("Enregistrer les modifications"))
		);

		$h .= $form->close();

		$h .= '<br/>';

		$h .= '<h2>'.s("Personnalisation des e-mails").'</h2>';

		$form = new \util\FormUi();

		$h .= '<div class="util-block-help">';
			$h .= '<p>';
				$h .= s("Vous pouvez personnaliser le contenu des e-mails envoyés à vos clients lorsqu'ils commandent dans votre boutique, en fonction du mode de livraison choisi.");
				if($eShop['hasPayment']) {
					$h .= ' '.s("Le contenu des e-mails envoyés dépend aussi du moyen de paiement sélectionné par le client, si vous avez activé différentes moyens de paiement sur votre boutique.");
				}
				$h .= '</p>';
			if($eShop['hasPayment']) {
				$h .= $form->open(attributes: ['action' => '/shop/configuration:update', 'method' => 'get']);
					$h .= $form->hidden('id', $eShop['id']);
					$h .= $form->inputGroup(
						$form->addon(s("Moyen de paiement")).
						$form->select('paymentMethod', [
							NULL => s("Direct avec le producteur"),
							\payment\MethodLib::ONLINE_CARD => s("Carte bancaire avec Stripe"),
							\payment\MethodLib::TRANSFER => s("Virement bancaire")
						], $eSaleExample['paymentMethod']->notEmpty() ? $eSaleExample['paymentMethod']['fqn'] : NULL, attributes: ['mandatory' => TRUE]).
						$form->submit(s("Afficher"))
					);
				$h .= $form->close();
			}
		$h .= '</div>';

		$h .= '<br/>';

		$h .= '<div style="'.($eShop['hasPoint'] ? '' : 'opacity: 0.33').'">';

			$h .= '<div class="util-title">';
				$h .= '<h3>'.s("Confirmation de commande livrée en point retrait").'</h3>';
				$h .= '<a href="/mail/customize:create?farm='.$eShop['farm']['id'].'&type='.\mail\Customize::SHOP_CONFIRMED_PLACE.'&shop='.$eShop['id'].'" class="btn btn-outline-primary">'.s("Personnaliser l'e-mail").'</a>';
			$h .= '</div>';

			$eSaleExample['shopPoint'] = $eSaleExample['shopPoints'][Point::PLACE];

			[$title, , $html] = new MailUi()->getSaleConfirmed($eSaleExample, $eSaleExample['cItem'], $eShop->isShared(), $cCustomize[\mail\Customize::SHOP_CONFIRMED_PLACE]['template'] ?? NULL);
			$h .= new \mail\CustomizeUi()->getMailExample($title, $html);

			$h .= '<div class="util-title">';
				$h .= '<h3>'.s("Confirmation de commande livrée à domicile").'</h3>';
				$h .= '<a href="/mail/customize:create?farm='.$eShop['farm']['id'].'&type='.\mail\Customize::SHOP_CONFIRMED_HOME.'&shop='.$eShop['id'].'" class="btn btn-outline-primary">'.s("Personnaliser l'e-mail").'</a>';
			$h .= '</div>';

			$eSaleExample['shopPoint'] = $eSaleExample['shopPoints'][Point::HOME];

			[$title, , $html] = new MailUi()->getSaleConfirmed($eSaleExample, $eSaleExample['cItem'], $eShop->isShared(), $cCustomize[\mail\Customize::SHOP_CONFIRMED_HOME]['template'] ?? NULL);
			$h .= new \mail\CustomizeUi()->getMailExample($title, $html);

		$h .= '</div>';
		$h .= '<div style="'.($eShop['hasPoint'] ? 'opacity: 0.33' : '').'">';

			$h .= '<div class="util-title">';
				$h .= '<h3>'.s("Confirmation de commande livrée lorsque le choix du mode de livraison est désactivé").'</h3>';
				$h .= '<a href="/mail/customize:create?farm='.$eShop['farm']['id'].'&type='.\mail\Customize::SHOP_CONFIRMED_NONE.'&shop='.$eShop['id'].'" class="btn btn-outline-primary">'.s("Personnaliser l'e-mail").'</a>';
			$h .= '</div>';

			$eSaleExample['shopPoint'] = new Point();

			[$title, , $html] = new MailUi()->getSaleConfirmed($eSaleExample, $eSaleExample['cItem'], $eShop->isShared(),$cCustomize[\mail\Customize::SHOP_CONFIRMED_NONE]['template'] ?? NULL);
			$h .= new \mail\CustomizeUi()->getMailExample($title, $html);

		$h .= '</div>';

		if($eShop['opening'] === Shop::FREQUENCY) {

			$h .= '<div class="util-title">';
				$h .= '<h3>'.s("Commande annulée").'</h3>';
				$h .= '<div class="btn disabled">'.s("Non personnalisable").'</div>';
			$h .= '</div>';

			[$title, , $html] = new MailUi()->getSaleCanceled($eSaleExample);
			$h .= new \mail\CustomizeUi()->getMailExample($title, $html);

		}

		if($eSaleExample->isPaymentOnline()) {

			$h .= '<div class="util-title">';
				$h .= '<h3>'.s("Paiement par carte bancaire échoué").'</h3>';
				$h .= '<div class="btn disabled">'.s("Non personnalisable").'</div>';
			$h .= '</div>';

			[$title, , $html] = new MailUi()->getCardSaleFailed($eSaleExample, test: TRUE);
			$h .= new \mail\CustomizeUi()->getMailExample($title, $html);

		}

		return $h;

	}


	public function updateCustomize(Shop $eShop): string {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/shop/configuration:doCustomize', ['id' => 'shop-customize']);

		$h .= $form->hidden('id', $eShop['id']);

		$h .= $form->dynamicGroups($eShop, ['customColor', 'customBackground', 'customFont', 'customTitleFont']);

		$h .= $form->group(
				content: $form->submit(s("Enregistrer les modifications"))
		);

		$h .= $form->close();

		$h .= '<h3 class="shop-preview-title">'.s("Prévisualisation de boutique").'</h3>';
		$h .= '<iframe id="shop-preview" src="'.ShopUi::url($eShop).'?"></iframe>';

		return $h;

	}

	protected function getOpeningFields(\util\FormUi $form, Shop $eShop): string {

		$h = '<div class="shop-write-opening">';
			$h .= $form->dynamicGroups($eShop, ['opening', 'openingFrequency', 'openingDelivery']);
		$h .= '</div>';

		return $h;

	}

	protected function getTypeDescriber(\farm\Farm $eFarm, string $for) {

		return function(\PropertyDescriber $d) use($eFarm, $for) {

			$d->values = $eFarm->getConf('hasVat') ?
				[
					Shop::PRIVATE => s("Utiliser les prix particuliers").' <span class="util-annotation">'.s("/ affichage TTC sur la boutique").'</span>',
					Shop::PRO => s("Utiliser les prix professionnels").' <span class="util-annotation">'.s("/ affichage HT sur la boutique").'</span>',
				] :
				[
					Shop::PRIVATE => s("Utiliser les prix particuliers"),
					Shop::PRO => s("Utiliser les prix professionnels"),
				];

			if($for === 'update') {
				$d->after = \util\FormUi::info(s("Le changement de grille tarifaire sera pris en compte pour les prochaines ventes que vous créerez"));
			}

		};

	}

	public function getEmbedScript(Shop $eShop, string $mode) {

		return '<script type="text/javascript">(function() { const element = document.createElement("script"); element.src = "'.self::domain().'/embed-'.$mode.'.js?id='.$eShop['id'].'"; document.getElementsByTagName("head")[0].appendChild(element); })()</script><div id="otf-'.$mode.'"></div>';

	}

	public function displayWebsiteLimited(Shop $eShop, Date $eDate, \farm\Farm $eFarm, \website\Website $eWebsite): string {

		\Asset::css('website', 'manage.css');

		$h = '<h2>'.s("Option 1").'</h2>';
		$h .= '<h3 style="text-transform: uppercase">'.s("Intégration simple").'</h3>';
		$h .= '<div class="util-block mb-3">';

		$h .= '<h3>'.s("Intégration").'</h3>';

		$h .= '<h4>'.s("Code à copier sur un site internet créé avec Ouvretaferme").'</h4>';

		$h .= '<div class="website-code">';

			if($eWebsite->empty()) {

				$h .= '<p class="util-empty">'.s("Vous n'avez pas encore créé votre site internet avec {siteName} !").'</p>';

				$h .= '<a href="/website/manage?id='.$eFarm['id'].'" class="btn btn-primary">'.s("Créer mon site internet").'</a>';

			} else {

				$h .= '<dl class="util-presentation util-presentation-1">';
					$h .= '<dt>'.s("Site internet").'</dt>';
					$h .= '<dd>'.\website\WebsiteUi::link($eWebsite).'</dd>';
				$h .= '</dl>';

				$h .= '<br/>';

				$h .= '<code>@shop='.$eShop['id'].'</code>';

			}

		$h .= '</div>';

		$h .= '<h4>'.s("Code à copier sur un autre site internet").'</h4>';

		$h .= '<div class="website-code">';
			$h .= '<code>';
				$h .= encode($this->getEmbedScript($eShop, 'limited'));
			$h .= '</code>';
		$h .= '</div>';

		$h .= '<h3>'.s("Comportement").'</h3>';
		$h .= '<p>'.s("Un bloc contenant le nom de votre boutique et la date de la prochaine vente sera affiché sur votre site internet. Si une vente est ouverte, un lien pour commander en ligne sera affiché à votre client.").'</p>';

		$h .= '<h3>'.s("Rendu").'</h3>';

		if($eDate->notEmpty()) {

			$h .= '<div>';
				$h .= $this->getEmbedScript($eShop, 'limited');
			$h .= '</div>';

		} else {
			$h .= '<p>'.s("Créez une première vente pour visualiser le rendu.").'</p>';
		}

		$h .= '</div>';

		return $h;

	}

	public function displayWebsiteFull(Shop $eShop, Date $eDate, \farm\Farm $eFarm, \website\Website $eWebsite): string {

		\Asset::css('website', 'manage.css');

		$h = '<h2>'.s("Option 2").'</h2>';
		$h .= '<h3 style="text-transform: uppercase">'.s("Intégration complète").'</h3>';

		$h .= '<div class="util-block mb-3">';
			$h .= '<h3>'.s("Intégration").'</h3>';

			if($eShop['embedUrl'] === NULL) {

				$h .= '<p class="util-empty">'.s("Vous devez d'abord configurer l'intégration avant d'utiliser cette fonctionnalité.").'</p>';
				$h .= '<div>';
					$h .= '<a href="/shop/:updateEmbed?id='.$eShop['id'].'" class="btn btn-secondary">'.s("Configurer l'intégration").'</a>';
				$h .= '</div>';

			} else {

				$h .= '<dl class="util-presentation util-presentation-1">';
					$h .= '<dt>'.s("Site internet").'</dt>';
					$h .= '<dd>'.encode($eShop['embedUrl']).'</dd>';
					$h .= '<dt>'.s("Accès à cette boutique").'</dt>';
					$h .= '<dd>'.self::p('embedOnly')->values[$eShop['embedOnly']].'</dd>';
				$h .= '</dl>';

				$h .= '<div class="mt-1">';
					$h .= '<a href="/shop/:updateEmbed?id='.$eShop['id'].'" class="btn btn-secondary">'.s("Modifier l'intégration").'</a> ';
					$h .= '<a data-ajax="/shop/:doUpdateEmbed" post-id="'.$eShop['id'].'" class="btn btn-outline-secondary" data-confirm="'.s("Voulez-vous réellement supprimer cette intégration ?").'">'.s("Supprimer l'intégration").'</a>';
				$h .= '</div>';

				$h .= '<br/>';

				$h .= '<h4>'.s("Code à copier sur un site internet créé avec Ouvretaferme").'</h4>';

				$h .= '<div class="website-code">';

					if($eWebsite->empty()) {

						$h .= '<p class="util-empty">'.s("Vous n'avez pas encore créé votre site internet avec {siteName} !").'</p>';

						$h .= '<a href="/website/manage?id='.$eFarm['id'].'" class="btn btn-primary">'.s("Créer mon site internet").'</a>';

					} else {

						$h .= '<dl class="util-presentation util-presentation-1">';
							$h .= '<dt>'.s("Site internet").'</dt>';
							$h .= '<dd>'.\website\WebsiteUi::link($eWebsite).'</dd>';
						$h .= '</dl>';

						$h .= '<br/>';

						$h .= '<code>@fullShop='.$eShop['id'].'</code>';

					}

				$h .= '</div>';

				$h .= '<h4>'.s("Code à copier sur un autre site internet").'</h4>';

				$h .= '<div class="website-code">';
					$h .= '<code>';
						$h .= encode($this->getEmbedScript($eShop, 'full'));
					$h .= '</code>';
				$h .= '</div>';

			}

			$h .= '<br/>';

			$h .= '<h3>'.s("Comportement").'</h3>';
			$h .= '<p>'.s("La page d'accueil de votre boutique sera pleinement visible sur votre site internet. Si le client s'engage dans un processus de vente, il sera redirigé vers {siteName} pour finaliser sa commande et sera invité à revenir vers votre site Internet si vous avez indiqué l'adresse la page sur laquelle vous allez faire l'intégration de cette boutique.").'</p>';

			$h .= '<h4>'.s("Les limites de cette intégration").'</h4>';
			$h .= '<ul>';
				$h .= '<li>'.s("À moins que votre client n'ait activé les cookies tiers, il ne verra pas sur votre site sa commande en cours ou les réductions auxquelles il a droit, mais celles-ci seront bien prises en compte sur la page de confirmation de commande.").'</li>';
				$h .= '<li>'.s("Cette intégration ne fonctionnera pas avec les boutiques dont l'accès a été limité à certains clients seulement.").'</li>';
				$h .= '<li>'.s("Cette intégration peut ne pas fonctionner si vous l'intégrez dans une iframe.").'</li>';
			$h .= '</ul>';

			$h .= '<br/>';

			$h .= '<h3>'.s("Rendu").'</h3>';

			if($eDate->notEmpty()) {

				$h .= '<div>';
					$h .= $this->getEmbedScript($eShop, 'full');
				$h .= '</div>';

			} else {
				$h .= '<p>'.s("Créez une première vente pour visualiser le rendu.").'</p>';
			}
		$h .= '</div>';

		return $h;

	}

	public function updateEmbed(Shop $eShop): \Panel {

		$form = new \util\FormUi();

		$h = '<p class="util-info">'.s("Vous devez indiquer l'adresse du site internet sur lequel vous souhaitez intégrer votre boutique. C'est cette adresse qui sera utilisé par {siteName} pour rediriger vos clients sur votre site pendant le processus de prise de commande.").'</p>';

		$h .= $form->openAjax('/shop/:doUpdateEmbed');

		$h .= $form->hidden('id', $eShop['id']);
		$h .= $form->dynamicGroups($eShop, ['embedUrl', 'embedOnly']);

		$h .= $form->group(
			content: $form->submit(s("Configurer"))
		);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-shop-embed',
			title: s("Configuration de l'intégration"),
			body: $h
		);

	}

	public function displayInvite(Shop $eShop): \Panel {

		$h = '<div class="util-block-help">';
			$h .= '<p>'.s("Pour inviter des producteurs sur cette boutique collective, communiquez-leur un code d'invitation que vous aurez généré.").'</p>';
			$h .= '<h4>'.s("Un producteur invité pourra :").'</h4>';
			$h .= '<ul>';
				$h .= '<li>'.s("Proposer son catalogue de produits sur la boutique").'</li>';
				$h .= '<li>'.s("Consulter les commandes de la boutique").'</li>';
			$h .= '</ul>';
			$h .= '<h4>'.s("Un producteur invité ne pourra pas :").'</h4>';
			$h .= '<ul>';
				$h .= '<li>'.s("Organiser de nouvelles ventes sur la boutique").'</li>';
				$h .= '<li>'.s("Configurer ou supprimer la boutique").'</li>';
			$h .= '</ul>';
		$h .= '</div>';

		$h .= '<h2>'.s("Code d'invitation").'</h2>';

		if($eShop->hasSharedKey()) {

			$h .= '<div class="input-group mb-1">';
				$h .= '<div class="form-control" id="invite-key">'.$eShop->getSharedKey().'</div>';
				$h .= '<a onclick="doCopy(this)" data-selector="#invite-key" data-message="'.s("Copié !").'" class="btn btn-primary">'.\Asset::icon('clipboard').' '.s("Copier").'</a>';
			$h .= '</div>';

			if($eShop['sharedHashExpiresAt'] !== NULL) {

				if($eShop->isSharedKeyExpired()) {
					$h .= '<div class="util-block util-block-dark bg-danger">'.s("Ce code a expiré et n'est plus utilisable.").'</div>';
				} else {
					$h .= '<div class="util-block-gradient">'.\Asset::icon('exclamation-circle').' '.s("Ce code expirera le {value}.", \util\DateUi::textual($eShop['sharedHashExpiresAt'])).'</div>';
				}

			}

			$h .= '<br/>';

			$h .= '<h3>'.s("Renouveler le code d'invitation").'</h3>';

		}

		$h .= '<a data-ajax="/shop/:doRegenerateSharedHash" post-id="'.$eShop['id'].'" class="btn btn-secondary">'.s("Générer un nouveau code").'</a>';

		return new \Panel(
			id: 'panel-shop-invite',
			title: s("Inviter des producteurs sur la boutique"),
			body: $h
		);

	}

	public static function link(Shop $eShop, bool $newTab = FALSE): string {
		return '<a href="'.self::url($eShop).'" '.($newTab ? 'target="_blank"' : '').'>'.encode($eShop['name']).'</a>';
	}

	public static function url(Shop $eShop, bool $showProtocol = TRUE): string {

		$eShop->expects(['fqn']);

		return self::domain($showProtocol).'/'.$eShop['fqn'];

	}

	public static function domain(bool $showProtocol = TRUE): string {

		return $showProtocol ? match(LIME_ENV) {
			'dev' => 'http://'.ShopSetting::$domain,
			'prod' => SERVER('REQUEST_SCHEME', default: 'https').'://'.ShopSetting::$domain
		} : ShopSetting::$domain;

	}

	public static function dateUrl(Shop $eShop, Date $eDate, ?string $page = NULL): string {
		return self::url($eShop).'/'.$eDate['id'].($page !== NULL ? '/'.$page : '');
	}

	public static function basketUrl(Shop $eShop, Date $eDate): string {
		return self::dateUrl($eShop, $eDate, 'panier');
	}

	public static function paymentUrl(Shop $eShop, Date $eDate): string {
		return self::dateUrl($eShop, $eDate, 'paiement');
	}

	public static function confirmationUrl(Shop $eShop, Date $eDate, \Collection $cSale = new \Collection()): string {
		$url = self::dateUrl($eShop, $eDate, 'confirmation');
		if($cSale->notEmpty()) {
			$url .= '?sales[]='.implode('&sales[]=', $cSale->getIds());
		}
		return $url;
	}

	public static function userUrl(Shop $eShop, Date $eDate, string $page): string {
		return self::dateUrl($eShop, $eDate, $page);
	}

	public static function adminUrl(\farm\Farm $eFarm, Shop $eShop): string {
		return '/ferme/'.$eFarm['id'].'/boutique/'.$eShop['id'];
	}

	public static function adminDateUrl(\farm\Farm $eFarm, Date $eDate): string {
		if($eDate['deliveryDate'] === NULL) {
			return '/ferme/'.$eFarm['id'].'/boutique/'.$eDate['shop']['id'];
		} else {
			return '/ferme/'.$eFarm['id'].'/date/'.$eDate['id'];
		}
	}

	public static function getLogo(Shop $eShop, string $size): string {

		$eShop->expects(['id', 'logo']);

		if($eShop['logo'] === NULL) {
			return '';
		}

		$ui = new \media\ShopLogoUi();

		$class = 'shop-logo-view media-rectangle-view'.' ';

		$format = $ui->convertToFormat($size);

		$style = 'background-image: url('.$ui->getUrlByElement($eShop, $format).');';

		return '<div class="'.$class.'" style="'.$ui->getSquareCss($size).'; '.$style.'"></div>';

	}

	public function getWidgetCollection(\Collection $cShop) {

		$h = '<div class="shop-widget-wrapper stick-xs">';
		foreach($cShop as $eShop) {
			$eShop->expects(['date']);
			$h .= $this->getWidget($eShop, $eShop['date']);
		}
		$h .= '</div>';

		return $h;

	}

	public function getWidget(Shop $eShop, Date $eDate) {

		$h = '<div class="shop-widget">';
			$h .= '<div class="shop-widget-farm">';
				$h .= \farm\FarmUi::getVignette($eShop['farm'], '1.75rem').'';
				$h .= \farm\FarmUi::websiteLink($eShop['farm']);
			$h .= '</div>';
			$h .= '<h4>';
				$h .= ShopUi::link($eShop);
			$h .= '</h4>';
			$h .= $this->getDateHeader($eDate);
			if($eDate->acceptOrder()) {
				$h .= '<div class="shop-widget-order">';
					$h .= '<a href="'.ShopUi::url($eShop).'" class="btn btn-secondary">'.s("Commander en ligne").'</a>';
				$h .= '</div>';
			}
		$h .= '</div>';

		return $h;

	}

	public function getHeader(Shop $eShop, \Collection $cDate): string {

		$h = '<div class="shop-header shop-header-'.($eShop['logo'] ? 'with' : 'without').'-logo">';

			if($eShop['logo']) {
				$h .= '<div class="shop-header-image">';
					$h .= self::getLogo($eShop, '8rem');
				$h .= '</div>';
			}

			$h .= '<div class="shop-header-content">';

				$h .= '<h1>';
					$h .= encode($eShop['name']);
				$h .= '</h1>';

				if($eShop['embedOnly'] === FALSE) {

					if($eShop['description'] !== NULL) {
						$h .= '<div class="shop-header-description">';
							$h .= new \editor\EditorUi()->value($eShop['description']);
							if(substr_count($eShop['description'], '<p>') + substr_count($eShop['description'], '<ul>') > 1) {
								$h .= '<div class="shop-header-description-full-link">';
									$h .= '<a '.attr('onclick', 'this.parentElement.parentElement.classList.add("shop-header-description-full")').'>'.\Asset::icon('chevron-right').' '.s("Lire la suite").'</a>';
								$h .= '</div>';
							}
						$h .= '</div>';
					}

				}

				if($cDate->empty()) {

					$h .= '<div class="shop-header-flow">';

						if($eShop->canWrite()) {
							$h .= '<div class="util-block-help">';
								$h .= '<p>';
									$h .= match($eShop['opening']) {
										Shop::FREQUENCY => s("Votre boutique n'est pas encore ouverte car vous n'avez pas encore configuré de livraison !"),
										Shop::ALWAYS => s("Votre boutique n'est pas encore ouverte car vous n'avez pas choisi les produits disponibles à la vente !")
									};
								$h .= '</p>';
								$h .= '<a href="'.\Lime::getUrl().''.ShopUi::adminUrl($eShop['farm'], $eShop).'" class="btn btn-secondary">'.s("Configurer ma boutique").'</a>';
							$h .= '</div>';
						} else {
							$h .= '<div class="util-info">'.s("Cette boutique n'est pas encore ouverte, revenez un peu plus tard !").'</div>';
						}
					$h .= '</div>';

				}

			$h .= '</div>';


		$h .= '</div>';

		return $h;

	}

	public function getDateHeader(Date $eDate, string $for = 'next', string $cssPrefix = 'shop'): string {

		if($eDate['deliveryDate'] === NULL) {
			$h = '<div>';
				$h .= new \shop\DateUi()->getOrderPeriod($eDate);
			$h .= '</div>';
		} else {

			$h = '<div class="'.$cssPrefix.'-header-date">';

				$h .= '<div class="'.$cssPrefix.'-header-block">';
					$h .= new \shop\DateUi()->getDeliveryPeriod($eDate, $for, $cssPrefix);
				$h .= '</div>';
				$h .= '<div class="'.$cssPrefix.'-header-block '.$cssPrefix.'-header-period">';
					$h .= '<div>'.new \shop\DateUi()->getOrderPeriod($eDate).'</div>';
				$h .= '</div>';

			$h .= '</div>';

		}

		return $h;

	}

	public function toggle(Shop $eShop) {

		if($eShop->canWrite()) {

			$switch = [
				'id' => 'shop-switch-'.$eShop['id'],
				'data-ajax' => $eShop->canWrite() ? '/shop/:doUpdateStatus' : NULL,
				'post-id' => $eShop['id'],
				'post-status' => ($eShop['status'] === Shop::OPEN) ? Shop::CLOSED : Shop::OPEN
			];

		} else {
			$switch = ['disabled' => TRUE];
		}

		return \util\TextUi::switch($switch, $eShop['status'] === Shop::OPEN, s("Ouverte"), s("Fermée"));

	}

	public function getDetails(Shop $eShop): string {

		$h = '<div class="util-block stick-xs" style="margin-bottom: 2rem">';

			if($eShop['shared']) {

				$h .= \Asset::icon('people-fill').'  '.s("Boutique collective administrée par {value}", '<b>'.encode($eShop['farm']['name']).'</b>');

				$h .= '<div class="util-block-separator"></div>';

			}

			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>';
					$h .= s("Lien de la boutique");
				$h .= '</dt>';
				$h .= '<dd class="util-presentation-fill">';
					$h .= '<a href="'.ShopUi::url($eShop).'" id="shop-url">'.ShopUi::url($eShop).'</a>';
					$h .= '  <a onclick="doCopy(this)" data-selector="#shop-url" data-message="'.s("Copié !").'" class="btn btn-sm btn-outline-primary">'.\Asset::icon('clipboard').' '.s("Copier").'</a>';
				$h .= '</dd>';

				$h .= '<dt style="align-self: center">';
					$h .= s("État de la boutique");
				$h .= '</dt>';
				$h .= '<dd>';
					$h .= $this->toggle($eShop);
				$h .= '</dd>';

				$h .= '<dt>';
					$h .= s("Grille tarifaire");
				$h .= '</dt>';
				$h .= '<dd>';
					$h .= ShopUi::p('type')->values[$eShop['type']];
				$h .= '</dd>';

				switch($eShop['opening']) {

					case Shop::FREQUENCY :

						if($eShop['openingFrequency'] !== NULL) {
							$h .= '<dt>';
								$h .= s("Fréquence des livraisons");
							$h .= '</dt>';
							$h .= '<dd>';
								$h .= self::p('openingFrequency')->values[$eShop['openingFrequency']];
							$h .= '</dd>';
						}

						break;

					case Shop::ALWAYS :

						$eShop->expects(['eDate']);

						if($eShop['eDate']->notEmpty()) {

							$h .= '<dt>';
								$h .= s("Prise des commandes");
							$h .= '</dt>';
							$h .= '<dd>';
								$h .= new DateUi()->getOrderHours($eShop['eDate']);
							$h .= '</dd>';

						}
						break;

				}

				if($eShop['shared'] === FALSE) {

					$h .= '<dt>';
						$h .= s("Moyens de paiement");
					$h .= '</dt>';
					$h .= '<dd>';

						if($eShop['hasPayment']) {

							$modes = [];

							if($eShop['paymentOffline']) {
								$modes[] = s("En direct");
							}

							if($eShop['paymentTransfer']) {
								$modes[] = s("Virement bancaire");
							}

							if($eShop['paymentCard']) {
								$modes[] = \Asset::icon('stripe').' '.s("Stripe");
							}

							$h .= implode(' / ', $modes);

						}

					$h .= '</dd>';

				}

				if($eShop['cCustomer']->notEmpty()) {

					$h .= '<dt>';
						$h .= s("Clients autorisés");
					$h .= '</dt>';
					$h .= '<dd>';

						$customers = $eShop['cCustomer']->toArray(fn($eCustomer) => encode($eCustomer->getName()));
						$h .= implode(' / ', $customers);

					$h .= '</dd>';

				}

			$h .= '</dl>';
		$h .= '</div>';

		return $h;
	}

	public static function p(string $property): \PropertyDescriber {

		$d = Shop::model()->describer($property, [
			'email' => s("Adresse e-mail de contact pour les clients"),
			'hasPoint' => s("Activer le choix du mode de livraison"),
			'description' => s("Description de la boutique"),
			'type' => s("Grille tarifaire"),
			'fqn' => s('Adresse internet'),
			'opening' => s("Mode de prise des commandes"),
			'openingFrequency' => s("Fréquence des livraisons"),
			'openingDelivery' => s("Modalités de livraison après la commande"),
			'name' => s("Nom de la boutique"),
			'logo' => s("Image de présentation"),
			'paymentCard' => s("Activer le choix du paiement en ligne avec {icon} Stripe", ['icon' => \Asset::icon('stripe')]),
			'paymentOffline' => s("Activer le choix du paiement en direct"),
			'paymentOfflineHow' => s("Modalités du paiement en direct"),
			'paymentTransfer' => s("Activer le choix du paiement par virement bancaire"),
			'paymentTransferHow' => s("Modalités du paiement par virement bancaire"),
			'paymentMethod' => s("Moyen de paiement"),
			'sharedGroup' => s("Groupage des produits sur la boutique"),
			'sharedCategory' => s("Conserver les catégories créées par les producteurs à l'affichage des produits"),
			'orderMin' => s("Montant minimal de commande"),
			'limitCustomers' => s("Limiter l'accès à cette boutique à certains clients seulement"),
			'shipping' => s("Frais de livraison par commande"),
			'shippingUntil' => s("Montant minimal de commande au delà duquel les frais de livraison sont offerts"),
			'embedUrl' => s("Adresse de votre site internet"),
			'embedOnly' => s("Comment vos clients peuvent-ils accéder à votre boutique pour commander ?"),
			'terms' => s("Vos conditions générales de vente"),
			'termsField' => s("Demander à vos clients d'accepter explicitement les conditions générales de vente avec une case à cocher"),
			'approximate' => s("Indiquer aux clients que le montant de leur commande affiché sur le site est approximatif s'il y a des produits qui nécessitent une pesée"),
			'outOfStock' => s("Affichage des produits en rupture de stock"),
			'comment' => s("Permettre à vos clients de laisser un commentaire lors d'une commande"),
			'commentCaption' => s("Instructions à communiquer à vos clients pour remplir le commentaire"),
			'customBackground' => s("Couleur d'arrière plan"),
			'customColor' => s("Couleur contrastante"),
			'customFont' => s("Police pour le texte"),
			'customTitleFont' => s("Police pour le titre principal des pages"),
			'emailNewSale' => fn($eShop) => $eShop->isShared() ? s("Envoyer à chaque producteur un e-mail à chaque nouvelle commande ou modification de commande d'un client") : s("Recevoir un e-mail à chaque nouvelle commande ou modification de commande d'un de vos clients"),
			'emailEndDate' => fn($eShop) => $eShop->isShared() ? s("Envoyer à chaque producteur un e-mail de synthèse lorsque les prises de commande d'une livraison se terminent") : s("Recevoir un e-mail de synthèse lorsque les prises de commande d'une livraison se terminent")
		]);

		switch($property) {

			case 'fqn':
				$d->field = function(\util\FormUi $form, Shop $eShop) use($d) {

					\Asset::css('shop', 'manage.css');
					\Asset::js('shop', 'manage.js');

					$auto = '<label title="Garder un nom choisi automatiquement en fonction du nom de la boutique ?">'.$form->inputCheckbox('fqnAuto', 1, [
							'checked' => TRUE,
							'onclick' => 'ShopManage.changeFqnAuto(this)',
						]).'&nbsp;'.s("Automatique ?").'</label>';
					$attributes = ['class' => 'disabled'];

						$h ='<div class="shop-write-fqn form-info-ancestor">';

							$h .= $form->inputGroup(
									'<div class="input-group-addon">'.ShopUi::domain().'/</div>'.
									$form->text('fqn', $eShop['fqn'] ?? '', $attributes)
								);
							$h .=	$auto;

						$h .= '</div>';

					return $h;
				};
				$d->after = \util\FormUi::info(s("Uniquement des chiffres, des lettres ou des tirets"));
				$d->labelAfter = \util\FormUi::info(s("Nous vous recommandons de ne pas modifier l'adresse internet de votre boutique dès lors que vous l'avez communiquée publiquement afin d'éviter de perturber votre clientèle."));
				break;

			case 'hasPoint' :
				$d->field = 'yesNo';

				$label = s("Choisissez si vos clients doivent choisir explicitement un mode de livraison sur votre boutique, parmi ceux que vous avez définis. Si vous désactivez ce choix, vous simplifiez l'interface de commande en ligne mais vous devez communiquer l'information directement à vos clients.");

				$d->label .= \util\FormUi::info($label);
				break;

			case 'description' :

				$label = s("Nous vous recommandons de préciser dans la description :");
				$label .= '<ul class="mt-1">';
					$label .= '<li>'.s("une courte présentation de votre activité").'</li>';
					$label .= '<li>'.s("les modalités pour récupérer les commandes").'</li>';
					$label .= '<li>'.s("les moyens de paiements que vous autorisez").'</li>';
				$label .= '</ul>';
				$label .= '<p>'.s("Veuillez noter que seul le premier paragraphe sera visible immédiatement sur les petits écrans de smartphone.").'</p>';

				$d->label .= \util\FormUi::info($label);
				break;

			case 'hasPayment' :
				$d->field = 'switch';
				$d->attributes += [
					'onchange' => 'Product.changeType(input, "'.$property.'")'
				];
				$d->attributes = [
					'labelOn' => s("Activé"),
					'labelOff' => s("Désactivé"),
				];
				break;

			case 'type' :
				$d->values = [
					Shop::PRO => s("Professionnels"),
					Shop::PRIVATE => s("Particuliers")
				];
				break;

			case 'email' :
				$d->placeholder = fn(Shop $eShop) => $eShop['shared'] ? '' : $eShop['farm']['legalEmail'];
				$d->after = fn(\util\FormUi $form, Shop $eShop) => \util\FormUi::info(
					$eShop['shared'] ?
						s("Cette adresse e-mail est utilisée comme expéditeur des e-mails envoyés aux clients pour les confirmations de commande, choisissez de préférence une adresse e-mail commune à tous les producteurs.") :
						s("Cette adresse e-mail est utilisée comme expéditeur des e-mails envoyés aux clients pour les confirmations de commande.")
				);
				break;

			case 'opening':

				$frequency = '<h4>'.s("Par jour de livraison").'</h4>';
				$frequency .= '<ul>';
					$frequency .= '<li>'.s("Vous définissez des jours de livraison pour lesquels vous choisissez la période de prise de commandes").'</li>';
					$frequency .= '<li>'.s("Une commande par client et par jour de livraison").'</li>';
					$frequency .= '<li>'.s("Les clients peuvent modifier leur commande pendant la période de prise de commandes").'</li>';
				$frequency .= '</ul>';
				$frequency .= '<p>'.\Asset::icon('arrow-right').' <i>'.s("Idéal pour un point de vente hebdomadaire, une AMAP, une mercuriale pour vos clients professionnels ou si vous faites des tournées à jours fixes...").'</i></p>';

				$calendar = '<h4>'.s("En continu").'</h4>';
				$calendar .= '<ul>';
					$calendar .= '<li>'.s("Les prises de commandes sont toujours ouvertes").'</li>';
					$calendar .= '<li>'.s("Vous indiquez vous-même aux clients les modalités de livraison").'</li>';
					$calendar .= '<li>'.s("Les clients ne peuvent pas modifier les commandes réalisées").'</li>';
				$calendar .= '</ul>';
				$calendar .= '<p>'.\Asset::icon('arrow-right').' <i>'.s("Idéal pour expédier vos produits par transporteur ou vendre sans interruption sur une longue période...").'</i></p>';

				$d->values = [
					Shop::FREQUENCY => $frequency,
					Shop::ALWAYS => $calendar,
				];

				$d->labelAfter = fn(Shop $e) => $e->exists() ? \util\FormUi::info(s("Pensez mettre à jour les e-mails de confirmation de commande sur l'onglet d'à côté si vous changez le mode de prise de commande.", ['link' => '<a href="/shop/configuration:update?id='.$e['id'].'">'])) : NULL;

				break;

			case 'openingFrequency':
				$d->placeholder = s("Autre");
				$d->values = [
					Shop::BIMONTHLY => s("Bimensuelle"),
					Shop::MONTHLY => s("Mensuelle"),
					Shop::WEEKLY => s("Hebdomadaire"),
				];
				break;

			case 'openingDelivery' :

				$label = '<p>'.s("Les modalités de livraison seront indiquées aux clients au moment de la prise de commande.").'</p>';

				$d->label .= \util\FormUi::info($label);
				break;

			case 'paymentCard':
			case 'paymentOffline' :
			case 'paymentTransfer' :
				$d->field = 'yesNo';
				$d->attributes = function(\util\FormUi $form, Shop $eShop) use($property) {

					$eShop->expects(['stripe']);

					$attributes = [];

					if($property === 'paymentCard' and $eShop['stripe']->empty()) {
						$attributes['disabled'] = 'disabled';
					}

					$attributes['callbackRadioAttributes'] = function() {
						return [
							'onclick' => 'ShopManage.updatePayment(this)'
						];
					};

					return $attributes;

				};
				break;

			case 'paymentOfflineHow' :
				$d->placeholder = s("Exemple : Règlement en espèces ou par chèque au moment du retrait des commandes.");
				$d->after = \util\FormUi::info(s("Indiquez ici comment vous pouvez être payé par vos clients s'ils choisissent de régler leurs commandes en direct avec vous.<br/>Si vous n'autorisez que le paiement en direct sur cette boutique, nous vous suggérons de <link>désactiver la page de choix du moyen de paiement</link> pour simplifier l'interface pour vos clients.", ['link' => '<a href="'.LIME_REQUEST.'#shop-payment-disabled">']));
				break;

			case 'paymentTransferHow' :
				$d->placeholder = s("Exemple : Règlement des commandes par virement bancaire chaque début de mois à réception de facture.");
				$d->after = \util\FormUi::info(s("Indiquez ici comment vous comptez facturer vos clients pour qu'ils vous règlent par virement."));
				break;

			case 'paymentMethod' ;
				$d->values = fn(Shop $e) => $e['cPaymentMethod'] ?? $e->expects(['cPaymentMethod']);
				$d->placeholder = s("Non défini");
				break;

			case 'orderMin' :
				$d->append = '€ '.\selling\CustomerUi::getTaxes(\selling\Customer::PRIVATE);
				$d->after = \util\FormUi::info(s("Les clients ne peuvent pas valider leur commande tant que ce minimum n'est pas atteint."));
				break;

			case 'shipping' :
				$d->append = '€ '.\selling\CustomerUi::getTaxes(\selling\Customer::PRIVATE);
				break;

			case 'shippingUntil' :
				$d->append = '€ '.\selling\CustomerUi::getTaxes(\selling\Customer::PRIVATE);
				$d->after = \util\FormUi::info(s("Les frais de livraison ne sont pas facturés si la commande des clients excède ce montant."));
				break;

			case 'termsField' :
				$d->field = 'yesNo';
				break;

			case 'outOfStock' :
				$d->values = [
					Shop::SHOW => s("Montrer ces produits aux clients assortis d'une mention <i>Rupture de stock</i>"),
					Shop::HIDE => s("Cacher ces produits aux clients")
				];
				break;

			case 'approximate' :
				$d->field = fn(\util\FormUi $form, Shop $e, string $field) => $e['paymentCard'] ? '<div class="util-info">'.s("Ce paramètre est configurable uniquement si vous avez désactivé le paiement par carte bancaire sur votre boutique.").'</div>' : $form->yesNo($field, $e['approximate']);
				$d->after = fn(\util\FormUi $form, Shop $e) => $e['paymentCard'] ? '' : \util\FormUi::info(s("Les clients seront informés que le montant que vous leur facturerez sera probablement différent de celui affiché sur la boutique si des produits nécessitent une pesée, c'est-à-dire dont l'unité de vente est le kg ou le gramme."));
				break;

			case 'limitCustomers' :
				$d->after = \util\FormUi::info(s("Seuls les clients que vous aurez choisis pourront accéder à cette boutique."));
				$d->autocompleteDefault = fn(Shop $e) => $e['cCustomer'] ?? $e->expects(['cCustomer']);
				$d->autocompleteBody = function(\util\FormUi $form, Shop $e) {
					return [
						'farm' => $e['farm']['id'],
					];
				};
				new \selling\CustomerUi()->query($d, TRUE);
				$d->group = ['wrapper' => 'limitCustomers'];
				break;

			case 'customFont':
				$d->field = 'select';
				$d->placeholder = s("Par défaut");
				$d->values = \website\WebsiteSetting::CUSTOM_FONTS;
				break;

			case 'customBackground':
				$d->placeholder = s("Par défaut");
				break;

			case 'customColor':
				$d->placeholder = s("Par défaut");
				break;

			case 'customTitleFont':
				$d->field = 'select';
				$d->placeholder = s("Par défaut");
				$d->values = \website\WebsiteSetting::CUSTOM_TITLE_FONTS;
				break;

			case 'embedUrl':
				$d->attributes = [
					'mandatory' => TRUE
				];
				$d->placeholder = 'https://www.lesitedemaferme.fr/';
				break;

			case 'embedOnly':
				$d->field = 'radio';
				$d->values = [
					TRUE => s("Uniquement depuis votre site internet"),
					FALSE => s("Depuis votre site internet et depuis {siteName}")
				];
				$d->attributes = [
					'mandatory' => TRUE
				];
				break;

			case 'sharedCategory' :
				$d->field = 'yesNo';
				break;

			case 'sharedGroup':
				$d->values = [
					Shop::FARM => s("Grouper par producteur"),
					Shop::DEPARTMENT => s("Grouper par rayon"),
					Shop::PRODUCT => s("Ne pas grouper les produits")
				];
				$d->field = 'select';
				$d->attributes = [
					'mandatory' => TRUE
				];
				break;

			case 'comment' :
			case 'emailNewSale' :
			case 'emailEndDate' :
				$d->field = 'yesNo';
				break;

			case 'commentCaption' :
				$d->placeholder = s("Tapez vos instructions...");
				break;

		}

		return $d;

	}
}
?>
