<?php
namespace shop;

class ShopUi {

	public function __construct() {

		\Asset::css('shop', 'shop.css');
		\Asset::js('shop', 'shop.js');

	}

	public function create(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();

		$eShop = new Shop([
			'farm' => $eFarm,
			'name' => s("Boutique de {value}", $eFarm['name']),
			'fqn' => toFqn($eFarm['name']),
		]);

		$h = '';

		$h .= $form->openAjax('/shop/:doCreate', ['id' => 'shop-create']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicGroups($eShop, ['name*', 'fqn', 'email*', 'frequency', 'description']);

		$h .= $form->group(
				content: $form->submit(s("Créer la boutique"))
		);

		$h .= $form->close();

		return new \Panel(
			title: s("Créer une boutique"),
			body: $h
		);

	}

	public function update(Shop $eShop, \Collection $cCustomize, \selling\Sale $eSaleExample): string {

		$h = '<div class="tabs-h" id="selling-configure" onrender="'.encode('Lime.Tab.restore(this, "settings")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="settings" onclick="Lime.Tab.select(this)">'.s("Général").'</a>';
				$h .= '<a class="tab-item" data-tab="payment" onclick="Lime.Tab.select(this)">'.s("Paiement").'</a>';
				$h .= '<a class="tab-item" data-tab="terms" onclick="Lime.Tab.select(this)">'.s("Conditions de vente").'</a>';
				$h .= '<a class="tab-item" data-tab="mail" onclick="Lime.Tab.select(this)">'.s("E-mails").'</a>';
			$h .= '</div>';

			$h .= '<div class="tab-panel selected" data-tab="settings">';
				$h .= $this->updateGeneral($eShop);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="payment">';
				$h .= $this->updatePayment($eShop);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="terms">';
				$h .= $this->updateTerms($eShop);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="mail">';
				$h .= $this->updateMail($eShop, $cCustomize, $eSaleExample);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function updateGeneral(Shop $eShop): string {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/shop/configuration:doUpdate');

		$h .= $form->hidden('id', $eShop['id']);

		$h .= $form->dynamicGroups($eShop, ['name', 'fqn', 'email', 'frequency', 'orderMin', 'shipping', 'shippingUntil', 'description']);

		$h .= $form->group(
			self::p('logo')->label,
			(new \media\ShopLogoUi())->getCamera($eShop, size: '20rem')
		);

		$h .= '<br/>';

		$h .= $form->group(
			content: $form->submit(s("Mettre à jour la boutique"))
		);

		$h .= $form->close();

		return $h;

	}

	protected function updatePayment(Shop $eShop): string {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/shop/configuration:doUpdatePayment');

		$h .= $form->hidden('id', $eShop['id']);

		$h .= $form->group(content: '<h3>'.s("Paiement en direct avec le producteur").'</h3>');

		$h .= $form->dynamicGroup($eShop, 'paymentOffline');
		$h .= $form->dynamicGroup($eShop, 'paymentOfflineHow', function(\PropertyDescriber $d) use ($eShop) {
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
		$h .= $form->dynamicGroup($eShop, 'paymentTransferHow', function(\PropertyDescriber $d) use ($eShop) {
			$d->group['class'] = $eShop['paymentTransfer'] ? '' : 'hide';
		});

		$h .= '<br/>';

		$h .= $form->group(content: '<h3>'.s("Paiement en ligne avec {icon} Stripe", ['icon' => \Asset::icon('stripe')]).'</h3>');

		if($eShop['stripe']->empty()) {

			$content = '<p>'.s("Pour utiliser les options suivantes, vous avez besoin de configurer préalablement votre compte de paiement en ligne sur Stripe.").'</p>';
			$content .= '<a href="/payment/stripe:manage?farm='.$eShop['farm']['id'].'" class="btn btn-secondary" target="_blank">'.s("Configurer le paiement en ligne").'</a>';

			$h .= $form->group(content: '<div class="util-block-requirement">'.$content.'</div>');

		} else {
			$h .= $form->group(content: '<div class="util-block-help">'.\payment\StripeFarmUi::getWarning().'</div>');
		}

		$h .= $form->dynamicGroup($eShop, 'paymentCard');

		$h .= '<br/>';

		$h .= $form->group(
			content: $form->submit(s("Mettre à jour le paiement"))
		);

		$h .= $form->close();

		return $h;

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
			$content .= '<div class="util-info">';
				$content .= '<p>'.s("Actuellement, vous n'avez pas ajouté de conditions générales de ventes à votre boutique.").'</p>';
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

		$h .= '<div class="util-block-help">';
			$h .= '<h4>'.s("Personnalisation des e-mails").'</h4>';
			$h .= '<p>'.s("Vous pouvez personnaliser le contenu des e-mails envoyés à vos clients lorsqu'ils commandent dans votre boutique. Le contenu des e-mails envoyés dépend aussi du moyen de paiement sélectionné par le client, si vous avez activé différentes moyens de paiement sur votre boutique.").'</p>';
			$h .= $form->open(attributes: ['action' => '/shop/configuration:update', 'method' => 'get']);
				$h .= $form->hidden('id', $eShop['id']);
				$h .= $form->inputGroup(
					$form->addon(s("Moyen de paiement")).
					$form->select('paymentMethod', [
						\selling\Sale::OFFLINE => s("Direct avec le producteur"),
						\selling\Sale::ONLINE_CARD => s("Carte bancaire"),
						\selling\Sale::TRANSFER => s("Virement bancaire")
					], $eSaleExample['paymentMethod'], attributes: ['mandatory' => TRUE]).
					$form->submit(s("Afficher"))
				);
			$h .= $form->close();
		$h .= '</div>';

		$h .= '<br/>';

		$h .= '<div class="util-action">';
			$h .= '<h3>'.s("Confirmation de commande livrée en point retrait").'</h3>';
			$h .= '<a href="/mail/customize:create?farm='.$eShop['farm']['id'].'&type='.\mail\Customize::SHOP_CONFIRMED_PLACE.'&shop='.$eShop['id'].'" class="btn btn-outline-primary">'.s("Personnaliser l'e-mail").'</a>';
		$h .= '</div>';

		$eSaleExample['shopPoint'] = $eSaleExample['shopPoints'][Point::PLACE];

		[$title, , $html] = (new MailUi())->getSaleConfirmed($eSaleExample, $eSaleExample['cItem'], $cCustomize[\mail\Customize::SHOP_CONFIRMED_PLACE]['template'] ?? NULL);
		$h .= (new \mail\CustomizeUi())->getMailExample($title, $html);

		$h .= '<div class="util-action">';
			$h .= '<h3>'.s("Confirmation de commande livrée à domicile").'</h3>';
			$h .= '<a href="/mail/customize:create?farm='.$eShop['farm']['id'].'&type='.\mail\Customize::SHOP_CONFIRMED_HOME.'&shop='.$eShop['id'].'" class="btn btn-outline-primary">'.s("Personnaliser l'e-mail").'</a>';
		$h .= '</div>';

		$eSaleExample['shopPoint'] = $eSaleExample['shopPoints'][Point::HOME];

		[$title, , $html] = (new MailUi())->getSaleConfirmed($eSaleExample, $eSaleExample['cItem'], $cCustomize[\mail\Customize::SHOP_CONFIRMED_HOME]['template'] ?? NULL);
		$h .= (new \mail\CustomizeUi())->getMailExample($title, $html);


		$h .= '<div class="util-action">';
			$h .= '<h3>'.s("Commande annulée").'</h3>';
			$h .= '<div class="btn btn-disabled">'.s("Non personnalisable").'</div>';
		$h .= '</div>';

		[$title, , $html] = (new MailUi())->getSaleCanceled($eSaleExample);
		$h .= (new \mail\CustomizeUi())->getMailExample($title, $html);

		if($eSaleExample['paymentMethod'] === \selling\Sale::ONLINE_CARD) {

			$h .= '<div class="util-action">';
				$h .= '<h3>'.s("Paiement par carte bancaire échoué").'</h3>';
				$h .= '<div class="btn btn-disabled">'.s("Non personnalisable").'</div>';
			$h .= '</div>';

			[$title, , $html] = (new MailUi())->getCardSaleFailed($eSaleExample, test: TRUE);
			$h .= (new \mail\CustomizeUi())->getMailExample($title, $html);

		}

		return $h;

	}

	public function displayWebsite(Shop $eShop, Date $eDate, \farm\Farm $eFarm, \website\Website $eWebsite): \Panel {

		if($eWebsite->empty()) {

			$h = '<p class="util-info">'.s("Vous ne pouvez intégrer la boutique sur votre site internet uniquement si vous avez créé celui-ci avec <i>{siteName}</i>, ce qui n'est pas encore le cas !").'</p>';

			$h .= '<a href="/website/manage?id='.$eFarm['id'].'" class="btn btn-primary">'.s("Créer mon site internet").'</a>';

		} else {

			$h = '<p>'.s("Vous souhaitez mettre en avant cette boutique sur le site internet de votre ferme afin de permettre à vos clients de passer facilement leurs commandes :").'</p>';

			$h .= '<dl class="util-presentation util-presentation-1">';
				$h .= '<dt>'.s("Ferme").'</dt>';
				$h .= '<dd>'.encode($eFarm['name']).'</dd>';
				$h .= '<dt>'.s("Site internet").'</dt>';
				$h .= '<dd>'.\website\WebsiteUi::link($eWebsite).'</dd>';
			$h .= '</dl>';

			$h .= '<br/>';

			$h .= '<h3>'.s("Intégration").'</h3>';
			$h .= '<p>'.s("Copiez ce morceau de code sur n'importe quelle page de votre site pour y mettre en avant cette boutique :").'</p>';
			$h .= '<code>@shop='.$eShop['id'].'</code>';

			if($eDate->notEmpty()) {

				$h .= '<br/>';

				$h .= '<h3>'.s("Rendu").'</h3>';
				$h .= '<p>'.s("En copiant le morceau de code ci-dessus sur une page de votre site, voici quel sera le rendu :").'</p>';
				$h .= '<br/>';
				$h .= (new \website\WidgetUi())->getShop($eShop, $eDate);

			}

		}

		return new \Panel(
			title: s("Intégrer la boutique sur votre site internet"),
			body: $h
		);

	}
	public function displayEmails(Shop $eShop, array $emails): \Panel {

		$eShop->expects(['farm' => ['name', 'url']]);

		$h = '<p class="util-info">'.s("Cette page vous permet de récupérer les adresses e-mail des clients de votre boutique. Les clients qui ont supprimé leur compte ou qui ont refusé vos communications ne sont pas présents dans cette liste. En envoyant des e-mails non sollicités ou en refusant de désabonner les clients qui le souhaitent, vous engagez votre propre responsabilité.").'</p>';

		$h .= '<h3>'.s("Liste des e-mails").'</h3>';

		if($emails) {
			$h .= '<code id="shop-emails">'.implode(', ', array_map('encode', $emails)).'</code>';
			$h .= '<a onclick="doCopy(this)" data-selector="#shop-emails" data-message="'.s("Copié !").'" class="btn btn-secondary mb-1 mt-1">'.s("Copier la liste dans le presse-papier").'</a>';
		} else {
			$h .= '<p class="util-info">'.s("Vous n'avez encore collecté aucune adresse e-mail pour vos communucations.").'</p>';
		}

		$h .= '<br/>';

		$h .= '<h3>'.s("Lien à donner à vos clients pour se désabonner de vos communications").'</h3>';
		$h .= '<code>'.\Lime::getUrl().\farm\FarmUi::url($eShop['farm']).'/optIn</code>';


		return new \Panel(
			title: s("Obtenir les adresses e-mail des clients"),
			body: $h
		);

	}

	public static function link(Shop $eShop, bool $newTab = FALSE): string {
		return '<a href="'.self::url($eShop).'" '.($newTab ? 'target="_blank"' : '').'>'.encode($eShop['name']).'</a>';
	}

	public static function url(Shop $eShop, bool $showProtocol = TRUE): string {

		$eShop->expects(['fqn']);

		return self::baseUrl($showProtocol).'/'.$eShop['fqn'];

	}

	public static function baseUrl(bool $showProtocol = TRUE): string {

		$domain = \Setting::get('shop\domain');

		return $showProtocol ? match(LIME_ENV) {
			'dev' => 'http://'.$domain,
			'prod' => SERVER('REQUEST_SCHEME', default: 'https').'://'.$domain
		} : $domain;

	}

	public static function dateUrl(Shop $eShop, Date $eDate, ?string $page = NULL, bool $showDomain = FALSE): string {
		$dateUrl = '/'.$eDate['id'].($page !== NULL ? '/'.$page : '');
		return ($showDomain ? self::url($eShop) : '/'.$eShop['fqn']).$dateUrl;
	}

	public static function adminUrl(\farm\Farm $eFarm, Shop $eShop): string {
		return \farm\FarmUi::url($eFarm).'/boutiques?shop='.$eShop['id'];
	}

	public static function adminDateUrl(\farm\Farm $eFarm, Shop $eShop, Date $eDate): string {
		return \farm\FarmUi::url($eFarm).'/boutique/'.$eShop['id'].'/date/'.$eDate['id'];
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
			$eShop->expects(['eDate']);
			$h .= $this->getWidget($eShop, $eShop['eDate']);
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
			$h .= '<div class="shop-widget-order">';
				if($eDate['isOrderable']) {
					$h .= '<a href="'.ShopUi::url($eShop).'" class="btn btn-transparent">'.s("Commander en ligne").'</a>';
				}
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getHeader(Shop $eShop, \Collection $cDate, Date $eDateSelected): string {

		$h = '<div class="shop-header">';

			$h .= '<div class="shop-header-image">';
				$h .= self::getLogo($eShop, '10rem');
			$h .= '</div>';

			$h .= '<div class="shop-header-content">';

				$h .= '<h1>';
					$h .= encode($eShop['name']);
				$h .= '</h1>';

				if($eShop['description'] !== NULL) {
					$h .= '<div class="shop-header-description">';
						$h .= (new \editor\EditorUi())->value($eShop['description']);
						if(substr_count($eShop['description'], '<p>') + substr_count($eShop['description'], '<ul>') > 1) {
							$h .= '<div class="shop-header-description-full-link">';
								$h .= '<a '.attr('onclick', 'this.parentElement.parentElement.classList.add("shop-header-description-full")').'>'.\Asset::icon('chevron-right').' '.s("Lire la suite").'</a>';
							$h .= '</div>';
						}
					$h .= '</div>';
				}

			$h .= '</div>';


			if($cDate->notEmpty()) {

				if($cDate->count() > 1) {
					$h .= '<div class="shop-header-flow">';
						$h .= (new \shop\DateUi())->getDeliveryPeriods($eShop, $cDate, $eDateSelected);
					$h .= '</div>';
				}

			} else {

				$h .= '<div class="shop-header-flow">';

					if($eShop->canWrite()) {
						$h .= '<div class="util-block-help">';
							$h .= '<p>'.s("Pour activer votre boutique, vous devez créer une première date avec les produits que vous souhaitez vendre !").'</p>';
							$h .= '<a href="'.\Lime::getUrl().''.ShopUi::adminUrl($eShop['farm'], $eShop).'" class="btn btn-secondary">'.s("Configurer ma boutique").'</a>';
						$h .= '</div>';
					} else {
						$h .= '<div class="util-info">'.s("Cette boutique n'est pas encore ouverte, revenez un peu plus tard !").'</div>';
					}
				$h .= '</div>';

			}


		$h .= '</div>';

		return $h;

	}

	public function getDateHeader(Date $eDate, string $for = 'next'): string {

		$h = '<div class="shop-header-date">';

			$h .= '<div class="shop-header-block">';
				$h .= (new \shop\DateUi())->getDeliveryPeriod($eDate, $for);
			$h .= '</div>';
			$h .= '<div class="shop-header-block shop-header-period">';
				$h .= '<div>'.(new \shop\DateUi())->getOrderPeriod($eDate).'</div>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function toggle(Shop $eShop) {

		return \util\TextUi::switch([
			'id' => 'shop-switch-'.$eShop['id'],
			'data-ajax' => $eShop->canWrite() ? '/shop/:doUpdateStatus' : NULL,
			'post-id' => $eShop['id'],
			'post-status' => ($eShop['status'] === Shop::OPEN) ? Shop::CLOSED : Shop::OPEN
		], $eShop['status'] === Shop::OPEN, s("Ouverte"), s("Fermée"));

	}

	public function getDetails(Shop $eShop): string {

		$h = '<div class="util-block" style="margin-bottom: 2rem">';
			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>';
					$h .= s("État de la boutique");
				$h .= '</dt>';
				$h .= '<dd>';
					$h .= $this->toggle($eShop);
				$h .= '</dd>';

				$h .= '<dt>';
					$h .= s("Fréquence des ventes");
				$h .= '</dt>';
				$h .= '<dd>';
					$h .= self::p('frequency')->values[$eShop['frequency']];
				$h .= '</dd>';

				$h .= '<dt>';
					$h .= s("Moyens de paiement");
				$h .= '</dt>';
				$h .= '<dd>';

					$modes = [];

					if($eShop['paymentOffline']) {
						$modes[] = s("En direct");
					}

					if($eShop['paymentTransfer']) {
						$modes[] = s("Virement bancaire");
					}

					if($eShop['paymentCard']) {
						$modes[] = s("Carte bancaire");
					}

					$h .= implode(' / ', $modes);
				$h .= '</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		return $h;
	}

	public static function p(string $property): \PropertyDescriber {

		$d = Shop::model()->describer($property, [
			'email' => s("Adresse e-mail de contact pour les clients"),
			'description' => s("Description de la boutique"),
			'fqn' => s('Adresse internet'),
			'frequency' => s("Fréquence d'ouverture des ventes"),
			'name' => s("Nom de la boutique"),
			'logo' => s("Image de présentation"),
			'paymentCard' => s("Activer le paiement en ligne par carte bancaire"),
			'paymentOffline' => s("Activer le paiement en direct"),
			'paymentOfflineHow' => s("Modalités du paiement en direct"),
			'paymentTransfer' => s("Activer le paiement par virement bancaire"),
			'paymentTransferHow' => s("Modalités du paiement par virement bancaire"),
			'orderMin' => s("Montant minimal de commande"),
			'shipping' => s("Frais de livraison par commande"),
			'shippingUntil' => s("Montant minimal de commande au delà duquel les frais de livraison sont offerts"),
			'terms' => s("Conditions générales de vente"),
			'termsField' => s("Demander à vos clients d'accepter explicitement les conditions générales de vente avec une case à cocher"),
		]);

		switch($property) {

			case 'fqn':
				$d->field = function(\util\FormUi $form, Shop $eShop) use ($d) {

					\Asset::css('shop', 'manage.css');
					\Asset::js('shop', 'manage.js');

					$auto = '<label title="Garder un nom choisi automatiquement en fonction du nom de la boutique ?">'.$form->inputCheckbox('fqnAuto', 1, [
							'checked' => TRUE,
							'onclick' => 'ShopManage.changeFqnAuto(this)',
						]).'&nbsp;'.s("Automatique ?").'</label>';
					$attributes = ['class' => 'disabled'];

						$h ='<div class="shop-write-fqn form-info-ancestor">';

							$h .= $form->inputGroup(
									'<div class="input-group-addon">'.\Lime::getProtocol().'://'.\Setting::get('shop\domain').'</div>'.
									$form->text('fqn', $eShop['fqn'] ?? '', $attributes)
								);
							$h .=	$auto;

						$h .= '</div>';

					return $h;
				};
				$d->after = \util\FormUi::info(s("Uniquement des chiffres, des lettres ou des tirets"));
				$d->labelAfter = \util\FormUi::info(s("Nous vous recommandons de ne pas modifier l'adresse internet de votre boutique dès lors que vous l'avez communiquée publiquement afin d'éviter de perturber votre clientèle."));
				break;

			case 'description' :

				$label = s("Nous vous recommandons de préciser dans la description :");
				$label .= '<ul class="mt-1">';
					$label .= '<li>'.s("une courte présentation de votre activité").'</li>';
					$label .= '<li>'.s("les conditions pour récupérer sa commande").'</li>';
					$label .= '<li>'.s("les moyens de paiements que vous autorisez").'</li>';
				$label .= '</ul>';
				$label .= '<p>'.s("Veuillez noter que seul le premier paragraphe sera visible immédiatement sur les petits écrans de smartphone.").'</p>';

				$d->label .= \util\FormUi::info($label);
				break;

			case 'email' :
				$d->after = \util\FormUi::info(s("Cette adresse e-mail est utilisé comme expéditeur des e-mails envoyés aux clients pour les confirmation de commande."));
				break;

			case 'frequency':
				$d->values = [
					Shop::BIMONTHLY => s("Bimensuelle"),
					Shop::MONTHLY => s("Mensuelle"),
					Shop::OTHER => s("Autre"),
					Shop::WEEKLY => s("Hebdomadaire"),
				];
				break;

			case 'paymentCard':
			case 'paymentOffline' :
			case 'paymentTransfer' :
				$d->field = 'yesNo';
				$d->attributes = function(\util\FormUi $form, Shop $eShop) use ($property) {

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
				$d->after = \util\FormUi::info(s("Indiquez ici comment vous pouvez être payé par vos clients s'ils choisissent de régler leurs commandes en direct avec vous."));
				break;

			case 'paymentTransferHow' :
				$d->placeholder = s("Exemple : Règlement des commandes par virement bancaire chaque début de mois à réception de facture.");
				$d->after = \util\FormUi::info(s("Indiquez ici comment vous comptez facturer vos clients pour qu'ils vous règlent par virement."));
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

		}

		return $d;

	}
}
?>
