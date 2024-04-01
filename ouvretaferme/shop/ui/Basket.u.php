<?php
namespace shop;

class BasketUi {

	/**
	 * Basket steps
	 */
	const STEP_SUMMARY = 1;
	const STEP_DELIVERY = 2;
	const STEP_PAYMENT = 3;
	const STEP_CONFIRMATION = 4;

	public function __construct() {

		\Asset::css('shop', 'basket.css');
		\Asset::js('shop', 'basket.js');

	}

	public function getHeader(Shop $eShop, Date $eDate, ?string $currentContent = NULL, ?int $currentStep = NULL): string {

		$h = '<div class="util-vignette util-vignette-xs mb-0">';

			$h .= '<div class="hide-xs-down">';
				$h .= '<a href="'.ShopUi::url($eShop).'">'.ShopUi::getLogo($eShop, '10rem').'</a>';
			$h .= '</div>';

			$h .= '<div>';
				$h .= '<div class="util-action">';
					$h .= '<h1>';
						$h .= encode($eShop['name']);
					$h .= '</h1>';
				$h .= '</div>';
				$h .= '<div class="util-action-subtitle">';
					$h .= s("Livraison du {value}", \util\DateUi::getDayName(date('N', strtotime($eDate['deliveryDate']))).' '.\util\DateUi::textual($eDate['deliveryDate']));
				$h .= '</div>';

				if($currentStep !== NULL) {
					$h .= '<div class="basket-flow">';
						$h .= '<h4>'.s("Les étapes de votre commande").'</h4>';
						$h .= $this->getNav($eShop, $eDate, $currentStep);
					$h .= '</div>';
				}
				if($currentContent !== NULL) {
					$h .= $currentContent;
				}
			$h .= '</div>';

		$h .= '</div>';

		if($currentStep !== \shop\BasketUi::STEP_CONFIRMATION and $eDate->isOrderSoonExpired()) {
			$h .= '<br/><span class="color-danger">'.\Asset::icon('exclamation-circle').' '.s("Attention, il ne vous reste plus que quelques minutes pour finaliser votre commande. Après {value}, votre panier sera supprimé et vous ne pourrez pas terminer votre achat.", \util\DateUi::numeric($eDate['orderEndAt'], \util\DateUi::TIME_HOUR_MINUTE)).'</span>';
		}

		return $h;

	}

	public function getNav(Shop $eShop, Date $eDate, int $currentStep): string {

		$steps = $this->getSteps($eShop, $eDate);

		$h = '<nav id="shop-order-nav">';
			$h .= '<ol>';

			$getCurrent = FALSE;

			foreach($steps as [$step, $text, $url]) {

				if($getCurrent === FALSE and $currentStep === $step) {
					$getCurrent = TRUE;
				}

				if(
					$currentStep === self::STEP_CONFIRMATION or
					$step === self::STEP_CONFIRMATION
				) {
					$attribute = '';
				} else {
					$attribute = 'href="'.$url.'"';
				}

				$h .= '<li '.($currentStep === $step ? 'current' : '').'">';

					if($getCurrent === FALSE ) {

						$h .= '<a class="btn '.($getCurrent === FALSE ? 'btn-secondary' : 'btn-transparent').'" '.$attribute.'>';
							$h .= $text;
						$h .= '</a>';

					} else if($currentStep === $step) {

						$h .= '<span class="btn btn-transparent">';
							$h .= $text;
						$h .= '</span>';

					} else {

						$h .= '<span class="btn btn-secondary btn-readonly" style="opacity: 0.5">';
							$h .= $text;
						$h .= '</a>';

					}

				$h .= '</li>';

			}

			$h .= '</ol>';
		$h .= '</nav>';

		return $h;

	}

	private function getSteps(Shop $eShop, Date $eDate): array {

		$steps = [];

		$steps[] = [
			self::STEP_SUMMARY,
			s("Panier"),
			ShopUi::dateUrl($eShop, $eDate, 'panier')
		];

		$steps[] = [
			self::STEP_PAYMENT,
			s("Paiement"),
			ShopUi::dateUrl($eShop, $eDate, 'paiement')
		];

		$steps[] = [
			self::STEP_CONFIRMATION,
			s("Confirmation"),
			ShopUi::dateUrl($eShop, $eDate, 'confirmation')
		];

		return $steps;

	}

	public function getSummary(Shop $eShop, Date $eDate, \selling\Sale $eSaleExisting, array $basket, bool $isModifying): string {

		\Asset::css('shop', 'product.css');

		$eDate->expects(['cProduct']);

		$cProduct = $eDate['cProduct'];

		$updateBasket = FALSE;

		$h = '<div class="util-action">';
			$h .= '<h2>';
				$h .= s("Mon panier").' (<span id="shop-basket-articles">'.count($basket).'</span>)';
			$h .= '</h2>';
			if($eSaleExisting->empty()) {
				$h .= '<a href="'.ShopUi::dateUrl($eShop, $eDate).'" class="btn btn-outline-primary hide-xs-down">'.\Asset::icon('chevron-left').' '.s("Retourner sur la boutique").'</a>';
			} else {
				$h .= '<a href="'.ShopUi::dateUrl($eShop, $eDate).''.($isModifying ? '?modify=1' : '').'" class="btn btn-outline-secondary"><span class="hide-xs-down">'.s("Modifier les produits").'</span><span class="hide-sm-up">'.s("Modifier").'</span></a>';
			}
		$h .= '</div>';

		$h .= '<table id="shop-basket-summary-list" class="tr-even tr-bordered stick-xs">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th colspan="2">'.s("Produit").'</th>';
					$h .= '<th class="text-center">'.s("Quantité").'</th>';
					$h .= '<th class="text-end hide-xs-down">'.s("Prix unitaire").'</th>';
					$h .= '<th class="text-end">'.s("Total").'</th>';
					$h .= '<th class="hide-xs-down"></th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$total = 0;

			$h .= '<tbody>';
				foreach($basket as $productId => $product) {

					$eProduct = $cProduct->offsetGet($productId);
					$eProductSelling = $eProduct['product'];

					$deleteAttributes = [
						'data-confirm' => s("Souhaitez-vous réellement supprimer ce produit ?"),
						'onclick' => 'BasketManage.deleteProduct('.$eDate['id'].', '.$eProductSelling['id'].')',
						'title' => s("Supprimer cet article"),
					];

					$unitPrice = \util\TextUi::money($eProduct['price']).'&nbsp;/&nbsp;'.\main\UnitUi::getSingular($eProductSelling['unit'], short: TRUE, by: TRUE);

					$h .= '<tr>';
						$h .= '<td class="td-min-content">';
							if($eProductSelling['vignette'] !== NULL) {
								$h .= \selling\ProductUi::getVignette($eProductSelling, '3rem');
							}
						$h .= '</td>';
						$h .= '<td class="basket-summary-product">';

							$h .= encode($eProductSelling->getName());
							$h .= '<div class="hide-sm-up"><small>'.$unitPrice.'</small></div>';

						$h .= '</td>';
						$h .= '<td class="text-center">';
							$h .= ProductUi::quantityOrder($eDate, $eProductSelling, $cProduct->offsetGet($productId), $product['quantity']);
						$h .= '</td>';
						$h .= '<td class="text-end hide-xs-down">'.$unitPrice.'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eProduct['price'] * $product['quantity']).'</td>';
						$h .= '<td class="hide-xs-down text-center">';
							$h .= '<a '.attrs($deleteAttributes).'>'.\Asset::icon('trash').'</a>';
						$h .= '</td>';
					$h .= '</tr>';

					if(array_key_exists('warning', $product) and $product['warning'] === 'quantity') {
						$updateBasket = TRUE;
					}

					$total += $eProduct['price'] * $product['quantity'];

				}

				$h .= '<tfoot>';
					$h .= '<tr '.($updateBasket ? 'onrender="BasketManage.updateBasketFromSummary('.$eDate['id'].');"' : 'onrender="BasketManage.showWarnings('.$eDate['id'].');"').'>';
						$h .= '<td class="hide-xs-down"></td>';
						$h .= '<td class="text-end" colspan="3"><b>'.s("Total du panier").'</b></td>';
						$h .= '<td class="text-end"><b>'.\util\TextUi::money($total).'</b></td>';
						$h .= '<td class="hide-xs-down"></td>';
					$h .= '</tr>';
				$h .= '</tfoot>';

			$h .= '</tbody>';

		$h .= '</table>';
		$h .= '<p id="quantity-warning" class="util-warning hide">'.s("* Certains produits n'étant plus disponibles en quantité suffisante, la quantité de votre commande a été modifiée.").'</p>';
		$h .= '<br/>';

		return $h;

	}

	public function getAuthenticateForm(\user\User $eUser, \user\Role $eRole): string {

		$h = '<div class="util-block">';
			$h .= '<h4>'.s("Votre panier est enregistré !").'</h4>';
			$h .= '<p>'.s("Pour confirmer votre commande, veuillez vous connecter si vous avez déjà un compte. Si vous êtes un nouveau client, saisissez quelques informations qui permettront à votre producteur de vous reconnaître !").'</p>';
		$h .= '</div>';

		$h .= '<div class="shop-identification">';
			$h .= '<div>';
				$h .= '<h2>'.s("Déjà inscrit ?").'</h2>';
				$h .= (new \user\UserUi())->logInBasic();
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<h2>'.s("Nouveau client ?").'</h2>';
				$h .= (new \user\UserUi())->signUp($eUser, $eRole, LIME_REQUEST);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getOrder(\selling\Sale $eSale): string {

		$h = '<div class="util-action">';
			$h .= '<h2>'.s("Ma commande").'</h2>';
			$h .= '<a '.attr('onclick', 'BasketManage.modify('.$eSale['shopDate']['id'].', '.$this->getJsonBasket($eSale).', \'home\')').' class="btn btn-outline-primary">'.\Asset::icon('chevron-left').' '.s("Modifier la commande").'</a>';
		$h .= '</div>';
		$h .= '<div class="util-block mb-2">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Nom").'</dt>';
				$h .= '<dd>'.encode($eSale['customer']['name']).'</dd>';
				$h .= '<dt>'.s("Montant").'</dt>';
				$h .= '<dd>'.\util\TextUi::money($eSale['priceIncludingVat']).'</dd>';
			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	public function getAccount(\user\User $eUser): string {

		$h = '<div class="util-action">';
			$h .= '<h2>'.s("Mon compte client").'</h2>';
			$h .= '<a href="/user/settings:updateUser" class="btn btn-outline-primary">'.s("Mettre à jour").'</a>';
		$h .= '</div>';
		$h .= '<div class="util-block mb-2">';
			$h .= '<dl class="util-presentation util-presentation-'.($eUser['phone'] ? 3 : 2).'">';
				$h .= '<dt>'.s("Nom").'</dt>';
				$h .= '<dd>'.encode($eUser['firstName'].' '.$eUser['lastName']).'</dd>';
				$h .= '<dt>'.s("Adresse-mail").'</dt>';
				$h .= '<dd>'.encode($eUser['email']).'</dd>';

				if($eUser['phone']) {
					$h .= '<dt>'.s("Numéro téléphone").'</dt>';
					$h .= '<dd>'.encode($eUser['phone']).'</dd>';
				}
			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	public function getSubmitBasket(Shop $eShop, Date $eDate, \user\User $eUserOnline, Point $ePointSelected): string {

		$class = (
			$ePointSelected->notEmpty() and (
				($ePointSelected['type'] === Point::HOME and $eUserOnline->hasAddress()) or
				($ePointSelected['type'] === Point::PLACE)
			)
		) ? '' : 'hide';

		$h = '<div id="shop-basket-submit" class="'.$class.'" onrender="BasketManage.checkBasketButtons('.$eDate['id'].');">';

			$h .= '<h2>'.s("Valider ma commande de <span>{value}</span>", ['span' => '<span id="shop-basket-price">']).'</h2>';

			$h .= '<div class="shop-basket-submit-order-error color-danger hide">'.s("Vous n'avez pas atteint le minimum de commande pour ce mode de livraison, nous vous remercions de bien vouloir compléter vos achats !").'</div>';
			$h .= '<div class="shop-basket-submit-order-valid">';

				$h .= $this->getTermsBasket($eShop);

				$h .= '<div class="basket-buttons hide" data-ref="basket-create">';
					$h .= '<a onclick="BasketManage.doCreate('.$eDate['id'].');" class="btn btn-lg btn-secondary">'.s("Valider ma commande").' '.\Asset::icon('chevron-right').'</a> ';
				$h .= '</div>';

				$h .= '<div class="basket-buttons hide" data-ref="basket-update">';
					$h .= '<a onclick="BasketManage.doUpdate('.$eDate['id'].');" class="btn btn-lg btn-secondary">'.s("Valider la modification de commande").' '.\Asset::icon('chevron-right').'</a> ';
					$h .= '<a href="'.\shop\ShopUi::dateUrl($eShop, $eDate, 'confirmation').'" class="btn btn-lg btn-outline-secondary">'.s("Conserver ma commande initiale").'</a> ';
				$h .= '</div>';

			$h .= '</div>';

			$h .= '<br/><br/><br/>';

		$h .= '</div>';


		return $h;

	}

	public function getTermsBasket(Shop $eShop): string {

		$eShop->expects(['terms', 'termsField']);

		if($eShop['terms'] === NULL) {
			return '';
		}

		if($eShop['termsField']) {

			$h = '<label class="mb-1">';
				$h .= (new \util\FormUi())->inputCheckbox('terms').'  ';
				$h .= s("J'ai lu et j'accepte les <link>conditions générales de ventes</link>.", ['link' => '<a href="'.ShopUi::url($eShop).':conditions">']);
			$h .= '</label>';

		} else {

			$h = '<div class="mb-1">';
				$h .= s("En validant ma commande, je confirme que j'ai lu et que j'accepte les <link>conditions générales de ventes</link>.", ['link' => '<a href="'.ShopUi::url($eShop).':conditions">']);
			$h .= '</div>';

		}

		return $h;

	}

	public function getTerms(Shop $eShop): \Panel {

		if($eShop['terms'] !== NULL) {
			$h = (new \editor\EditorUi())->value($eShop['terms']);
		} else {
			$h = '<div class="util-info">'.s("Il n'y a pas encore de conditions générales de vente sur la boutique.").'</div>';
		}

		return new \Panel(
			title: s("Conditions générales de vente"),
			body: $h
		);

	}

	public function getDeliveryForm(Shop $eShop, Date $eDate, \Collection $ccPoint, \user\User $eUser, Point $ePointSelected): string {

		$h = '<div id="shop-basket-point">';
			$h .= (new PointUi())->getField($eShop, $ccPoint, $ePointSelected);
		$h .= '</div>';

		// Livraison à domicile activée
		if($ccPoint->offsetExists(Point::HOME)) {

			$isSelected = ($ePointSelected->notEmpty() and $ePointSelected['type'] === Point::HOME);

			$h .= '<div id="shop-basket-address-wrapper">';

				if($eUser->hasAddress()) {
					$h .= $this->getAddress($eUser, $isSelected ? '' : 'hide');
				} else {
					$h .= $this->getAddressForm($eShop, $eDate, $eUser, $isSelected ? '' : 'hide');
				}

			$h .= '</div>';

		}

		return $h;

	}

	public function getPhoneForm(Shop $eShop, Date $eDate, \user\User $eUser): string {

		$h = '<h2>'.s("Mon numéro de téléphone").'</h2>';
		$h .= '<p class="util-info">';
			$h .= s("Un numéro de téléphone est nécessaire pour que le producteur puisse vous contacter en cas de besoin à propos de votre commande !");
		$h .= '</p>';

		$form = new \util\FormUi();

		$h .= $form->openAjax(\shop\ShopUi::dateUrl($eShop, $eDate, ':doUpdatePhone'));

			$h .= $form->dynamicField($eUser, 'phone').'<br/>';
			$h .= $form->submit(s("Enregistrer le numéro").' '.\Asset::icon('chevron-right'), ['class' => 'btn btn-lg btn-secondary']);

		$h .= $form->close();

		return $h;

	}

	public function getAddressForm(Shop $eShop, Date $eDate, \user\User $eUser, string $class = 'hide'): string {

		$h = '<div id="shop-basket-address-form" class="'.$class.'">';

			$h .= '<h2>'.s("Mon adresse de livraison").'</h2>';
			$h .= '<p class="util-info">';
				$h .= s("Veuillez saisir une adresse qui correspond au lieu de livraison que vous avez choisi !");
			$h .= '</p>';

			$form = new \util\FormUi();

			$h .= $form->openAjax(\shop\ShopUi::dateUrl($eShop, $eDate, ':doUpdateAddress'), ['style' => 'max-width: 40rem']);

				$h .= $form->address(NULL, $eUser).'<br/>';
				$h .= $form->submit(s("Enregistrer l'adresse").' '.\Asset::icon('chevron-right'), ['class' => 'btn btn-lg btn-secondary']);

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getAddress(\user\User $eUser, string $class = 'hide'): string {

		$h = '<div id="shop-basket-address-show" class="'.$class.'">';

			$h .= '<div class="util-action">';
				$h .= '<h2>'.s("Mon adresse de livraison").'</h2>';
				$h .= '<a href="/user/settings:updateUser" class="btn btn-outline-primary">'.s("Mettre à jour").'</a>';
			$h .= '</div>';

			$h .= '<address>';
				$h .= nl2br(encode($eUser->getAddress()));
			$h .= '</address>';

			$h .= '<br/><br/>';

		$h .= '</div>';

		return $h;

	}

	public function getPayment(Shop $eShop, Date $eDate, \selling\Customer $eCustomer, \selling\Sale $eSale, \payment\StripeFarm $eStripeFarm): string {

		\Asset::js('shop', 'basket.js');

		$ePoint = $eShop['ccPoint']->find(fn($ePoint) => $ePoint['id'] === $eSale['shopPoint']['id'], depth: 2, limit: 1);

		$payments = $eShop->getPayments($ePoint);
		foreach($payments as $key => $payment) {

			if(
				$eStripeFarm->empty() and
				$payment === 'onlineCard'
			) {
				unset($payments[$key]);
			}

		}

		$h = '<h2>'.s("Mon moyen de paiement").'</h2>';

		switch(count($payments)) {

			case 0 :
				$h .= '<p class="util-danger">';
					$h .= s("Votre producteur n'a pas configuré de moyen de paiement, veuillez vous rapprocher de lui pour finaliser la commande !");
				$h .= '</p>';
				break;

			case 1 :
				$h .= '<p class="util-info">';
					$h .= s("Votre commande n'est pas encore confirmée, veuillez confirmer votre moyen de paiement :");
				$h .= '</p>';
				break;

			default :
				$h .= '<p class="util-info">';
					if(get_exists('modify')) {
						$h .= s("Vous commande est toujours confirmée, mais vous pouvoir choisir un autre moyen de paiement :");
					} else {
						$h .= s("Votre commande n'est pas encore confirmée, veuillez choisir votre moyen de paiement :");
					}
				$h .= '</p>';
				break;

		}

		$h .= '<div class="shop-payments">';

			foreach($payments as $payment) {

				$h .= '<a data-ajax="'.ShopUi::dateUrl($eShop, $eDate, ':doCreatePayment').'" post-payment="'.$payment.'" class="util-block-flat shop-payment">';
					$h .= $this->getPaymentBlock($eShop, $eDate, $eCustomer, $payment);
				$h .= '</a>';

			}

		$h .= '</div>';

		if($eSale->canCustomerCancel()) {
			$h .= '<br/>';
			$h .= '<br/>';
			$h .= '<div class="util-block-gradient">';
				$h .= '<h4>'.s("Vous ne souhaitez plus commander ?").'</h4>';
				$h .= '<a '.attr('onclick', 'BasketManage.doCancel('.$eSale['id'].')').'" class="btn btn-outline-secondary" data-confirm="'.s("Êtes-vous sûr de vouloir annuler cette commande ?").'">'.s("Annuler la commande").'</a>';
			$h .= '</div>';
		}


		return $h;

	}

	public function getPaymentBlock(Shop $eShop, Date $eDate, \selling\Customer $eCustomer, string $payment): string {

		$eDate->expects(['orderEndAt']);

		$h = '<div>';
			$h .= '<h4>';
				$h .= match($payment) {
					'offline' => s("Paiement avec le producteur"),
					'onlineCard' => s("Carte Bancaire"),
					'transfer' => s("Virement bancaire"),
				};
			$h .= '</h4>';

			$h .= '<p class="shop-payment-description">';

				$h .= '<b>';
					$h .= match($payment) {
						'offline' => $eShop['paymentOfflineHow'] ? encode($eShop['paymentOfflineHow']) : s("Vous gérez le paiement de votre commande directement avec votre producteur."),
						'onlineCard' => s("Payez maintenant votre commande en ligne avec votre carte bancaire."),
						'transfer' => $eShop['paymentTransferHow'] ? encode($eShop['paymentTransferHow']) : s("Vous paierez plus tard votre commande par virement bancaire à réception de facture.")
					};
				$h .= '</b>';

			$h .= '</p>';

		$h .= '</div>';

		$editCancel = \Asset::icon('check-lg').' '.s("Commande annulable et modifiable jusqu'au {value}.", ['value' => \util\DateUi::textual($eDate['orderEndAt'], \util\DateUi::DATE_HOUR_MINUTE)]);
		$notEditCancel = s("Commande non annulable et non modifiable.");

		$h .= '<div class="shop-payment-cancel">';
			$h .= match($payment) {
				'offline' => $editCancel,
				'onlineCard' => $notEditCancel,
				'transfer' => $editCancel
			};
		$h .= '</div>';

		$h .= '<span class="btn btn-secondary">';

			$h .= match($payment) {
				'offline' => s("Choisir le paiement avec le producteur").' ',
				'onlineCard' => s("Payer maintenant par carte bancaire").' ',
				'transfer' => s("Choisir le paiement par virement bancaire").' '
			};

		$h .= '</span>';

		return $h;

	}

	public function getPaymentStatus(Shop $eShop, Date $eDate, \selling\Sale $eSale): string {

		$class = '';
		$content = '';

		switch($eSale['paymentMethod']) {

			case \selling\Sale::ONLINE_CARD :

				switch($eSale['paymentStatus']) {

					case \selling\Sale::UNDEFINED :
						$class = 'bg-danger';
						$content .= '<h2>'.\Asset::icon('exclamation-triangle-fill').' '.s("Votre commande n'est pas encore confirmée !").'</h2>';
						$content .= '<p>'.s("Vous avez choisi de payer cette commande par carte bancaire, mais vous n'avez pas encore effectué le règlement.").'</p>';
						$content .= '<a href="'.\shop\ShopUi::dateUrl($eShop, $eDate, 'paiement').'" class="btn btn-transparent">'.s("Retenter un paiement").'</a> ';
						break;

					case \selling\Sale::PAID :
						$content .= '<h2>'.\Asset::icon('check').' '.s("Merci, votre commande est confirmée et payée !").'</h2>';
						$content .= '<p>'.s("Vous allez bientôt recevoir un e-mail de confirmation.").'</p>';
						break;

					case \selling\Sale::FAILED :
						$content .= '<h2>'.\Asset::icon('exclamation-triangle-fill').' '.s("Le paiement de votre commande a échoué !").'</h2>';
						$content .= '<p>'.s("Votre compte n'a pas été débité et votre commande n'est pas encore confirmée. Pour confirmer votre commande, veuillez retenter un paiement.").'</p>';
						$content .= '<a href="'.\shop\ShopUi::dateUrl($eShop, $eDate, 'paiement').'" class="btn btn-transparent">'.s("Retenter un paiement").'</a> ';
						break;

				};
				break;

			case \selling\Sale::TRANSFER :

				$content .= '<h2>'.\Asset::icon('check').' '.s("Merci, votre commande est confirmée !").'</h2>';
				$content .= '<p>'.s("Vous allez bientôt recevoir un e-mail de confirmation.").'</p>';
				$content .= '<p>';
					$content .= s("Vous avez choisi de régler cette commande par virement bancaire.<br/>Vous recevrez ultérieurement une facture de votre producteur afin de procéder au règlement.");
				$content .= '</p>';
				break;

			case \selling\Sale::OFFLINE :

				$content .= '<h2>'.\Asset::icon('check').' '.s("Merci, votre commande est confirmée !").'</h2>';
				$content .= '<p>'.s("Vous allez bientôt recevoir un e-mail de confirmation.").'</p>';
				$content .= '<p>';
					$content .= s("Vous avez choisi de régler cette commande en direct avec votre producteur.");
				$content .= '</p>';
				break;

		}

		$h = '<div class="basket-flow '.$class.'">';
			$h .= $content;
		$h .= '</div>';

		return $h;

	}

	public function getConfirmation(Shop $eShop, Date $eDate, \selling\Sale $eSale): string {

		$h = '<div class="sale-confirmation-container">';

			$h .= '<h2>'.s("Résumé de votre commande du {value}", \util\DateUi::textual($eSale['createdAt'], \util\DateUi::DATE_HOUR_MINUTE)).'</h2>';

			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Montant").'</dt>';
				$h .= '<dd>'.\util\TextUi::money($eSale['priceIncludingVat']).'</dd>';
				$h .= '<dt>'.s("État de la commande").'</dt>';
				$h .= '<dd>'.\selling\SaleUi::getPreparationStatusForCustomer($eSale).'</dd>';
				$h .= '<dt>'.s("Paiement").'</dt>';
				$h .= '<dd>';
					$h .= \selling\SaleUi::p('paymentMethod')->values[$eSale['paymentMethod']];
				$h .= '</dd>';
				if($eSale->isPaymentOnline()) {
					$h .= '<dt>'.s("État du paiement").'</dt>';
					$h .= '<dd>'.\selling\SaleUi::getPaymentStatusForCustomer($eSale).'</dd>';
				}
			$h .= '</dl>';

			$h .= '<br/>';

			if($eSale->canCustomerCancel()) {
				$h .= '<div>';
					$h .= '<a '.attr('onclick', 'BasketManage.modify('.$eDate['id'].', '.$this->getJsonBasket($eSale).', \'home\')').' class="btn btn-outline-primary" title="'.s("Cette commande est modifiable jusqu'au {value}.", ['value' => \util\DateUi::textual($eDate['orderEndAt'], \util\DateUi::DATE_HOUR_MINUTE)]).'">'.s("Modifier ma commande").'</a>';
					$h .= '&nbsp;';
					$h .= '<a '.attr('onclick', 'BasketManage.doCancel('.$eSale['id'].')').'" class="btn btn-outline-danger" data-confirm="'.s("Êtes-vous sûr de vouloir annuler cette commande ?").'" title="'.s("Cette commande est annulable jusqu'au {value}.", ['value' => \util\DateUi::textual($eDate['orderEndAt'], \util\DateUi::DATE_HOUR_MINUTE)]).'">'.s("Annuler ma commande").'</a>';
				$h .= '</div>';
				$h .= '<br/>';
			}

			$h .= '<h3>'.s("Mode de livraison").'</h3>';
			$h .= (new \selling\OrderUi())->getPointBySale($eSale);

			$h .= (new \selling\OrderUi())->getItemsBySale($eSale, $eSale['cItem']);

		$h .= '</div>';

		return $h;
	}

	public function getJsonBasket(\selling\Sale $eSale): string {

		if($eSale->empty()) {

			return '{}';

		} else {

			$h = '{';
				$h .= 'createdAt: '.time().',';
				$h .= 'sale: '.$eSale['id'].',';
				$h .= 'products: {';
					foreach ($eSale['cItem'] as $eItem) {
						$h .= '"'.$eItem['product']['id'].'": {quantity: '.$eItem['number'].', quantityOrdered: '.$eItem['number'].', unitPrice: '.$eItem['unitPrice'].'},';
					}
					$h = substr($h, 0, -1);
				$h .= '}';
			$h .= '}';

			return $h;

		}

	}

}