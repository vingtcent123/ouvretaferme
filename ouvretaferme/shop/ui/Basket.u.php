<?php
namespace shop;

class BasketUi {

	/**
	 * Basket steps
	 */
	const STEP_SUMMARY = 1;
	const STEP_PAYMENT = 2;
	const STEP_CONFIRMATION = 3;

	public function __construct() {

		\Asset::css('shop', 'basket.css');
		\Asset::js('shop', 'basket.js');

	}

	public function getSearch(\Collection $cShare): string {

		$label = s("Producteur");

		$h = '<div id="basket-search" data-label="'.$label.'">';
			$h .= '<a data-dropdown="bottom-end" class="btn btn-outline-secondary dropdown-toggle">';
				$h .= \Asset::icon('search').'  ';
				$h .= '<span id="basket-search-label">'.$label.'</span>';
			$h .= '</a>';
			$h .= '<div class="dropdown-list">';
				foreach($cShare as $eShare) {
					$h .= '<a onclick="BasketManage.searchFarm(this, '.$eShare['farm']['id'].')" class="dropdown-item">'.encode($eShare['farm']['name']).'</a>';
				}
			$h .= '</div>';
			$h .= '<a id="basket-search-close" onclick="BasketManage.closeSearchFarm()" class="btn btn-secondary ml-1 hide">'.\Asset::icon('x-lg').'</a>';
		$h .= '</div>';

		return $h;

	}

	public function getHeader(Shop $eShop): string {


		$h = '<div class="util-vignette mb-0">';

			if($eShop['logo']) {

				$h .= '<div class="hide-xs-down">';
					$h .= ShopUi::getLogo($eShop, '4rem');
				$h .= '</div>';

			}

			$h .= '<div class="shop-header-content">';
				$h .= '<div class="util-action">';
					$h .= '<h1>';
						$h .= encode($eShop['name']);
					$h .= '</h1>';
				$h .= '</div>';


			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getDeliveryTitle(Date $eDate): string {

		$h = '<h2>';
			$h .= s("Livraison du {value}", \util\DateUi::getDayName(date('N', strtotime($eDate['deliveryDate']))).' '.\util\DateUi::textual($eDate['deliveryDate']));
		$h .= '</h2>';

		return $h;

	}

	public function getSteps(Shop $eShop, Date $eDate, ?int $currentStep): string {

		if($currentStep === NULL) {
			return '';
		}

		$h = $this->getDeliveryTitle($eDate);

		$h .= '<div class="basket-flow">';
			$h .= '<h4>'.s("Les étapes de votre commande").'</h4>';

			$steps = $this->getStepsList($eShop, $eDate);

			$h .= '<nav id="shop-order-nav">';
				$h .= '<ol>';

				$getCurrent = FALSE;

				foreach($steps as [$step, $text]) {

					if($getCurrent === FALSE and $currentStep === $step) {
						$getCurrent = TRUE;
					}

					$h .= '<li '.($currentStep === $step ? 'current' : '').'">';

						if($getCurrent === FALSE ) {

							$h .= '<span class="btn '.($getCurrent === FALSE ? 'btn-secondary' : 'btn-transparent').' btn-readonly">';
								$h .= $text;
							$h .= '</span>';

						} else if($currentStep === $step) {

							$h .= '<span class="btn btn-transparent btn-readonly">';
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
		$h .= '</div>';

		if(
			in_array($currentStep, [BasketUi::STEP_PAYMENT, BasketUi::STEP_SUMMARY]) and
			$eDate->isOrderSoonExpired()
		) {
			$h .= '<br/><span class="color-danger">'.\Asset::icon('exclamation-circle').' '.s("Attention, il ne vous reste plus que quelques minutes pour finaliser votre commande. Après {value}, votre panier sera supprimé et vous ne pourrez pas terminer votre achat.", \util\DateUi::numeric($eDate['orderEndAt'], \util\DateUi::TIME_HOUR_MINUTE)).'</span>';
		}

		return $h;

	}

	private function getStepsList(Shop $eShop, Date $eDate): array {

		$steps = [];

		$steps[] = [
			self::STEP_SUMMARY,
			s("Panier")
		];

		if($eShop['hasPayment']) {

			$steps[] = [
				self::STEP_PAYMENT,
				s("Paiement")
			];

		}

		$steps[] = [
			self::STEP_CONFIRMATION,
			s("Confirmation")

		];

		return $steps;

	}

	public function getSummary(Shop $eShop, Date $eDate, \selling\Sale $eSaleExisting, array $basket): string {

		\Asset::css('shop', 'product.css');

		$eDate->expects(['cProduct']);

		$h = '<div class="util-title">';
			$h .= '<h2>';
				$h .= s("Mon panier").' (<span id="shop-basket-articles">'.count($basket).'</span>)';
			$h .= '</h2>';
			$h .= '<a href="'.ShopUi::dateUrl($eShop, $eDate).'?modify=1" class="btn btn-outline-primary">'.\Asset::icon('chevron-left').' <span class="hide-xs-down">'.s("Modifier ma sélection").'</span><span class="hide-sm-up">'.s("Modifier").'</span></a>';
		$h .= '</div>';

		$total = 0.0;

		$h .= '<div id="shop-basket-summary-list" class="mb-2">';

			$h .= '<table class="stick-xs tr-bordered">';

				$columns = 6;

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th colspan="2">'.s("Produit").'</th>';
						if($eDate['type'] === Date::PRO) {
							$columns++;
							$h .= '<th class="hide-sm-down"></th>';
						}
						$h .= '<th>'.s("Quantité").'</th>';
						$h .= '<th class="text-end hide-xs-down">'.s("Prix unitaire").'</th>';
						$h .= '<th class="text-end">'.s("Total").'</th>';
						$h .= '<th class="hide-xs-down"></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					if($eShop['shared']) {

						$basketByFarm = [];

						foreach($basket as $product) {

							$eFarm = $product['product']['product']['farm'];

							$basketByFarm[$eFarm['id']] ??= [];
							$basketByFarm[$eFarm['id']][] = $product;

						}

						foreach($basketByFarm as $basket) {

							$eFarm = first($basket)['product']['product']['farm'];

							$h .= '<tr class="shop-basket-summary-farm">';
								$h .= '<td colspan="'.$columns.'">'.encode($eFarm['name']).'</td>';
							$h .= '</tr>';

							$h .= $this->getProducts($eShop, $eDate, $eSaleExisting, $basket, $total);

						}

					} else {
						$h .= $this->getProducts($eShop, $eDate, $eSaleExisting, $basket, $total);
					}

				$h .= '</tbody>';
				$h .= '<tfoot>';
					$h .= '<tr>';
						$h .= '<td class="hide-xs-down"></td>';
						if($eDate['type'] === Date::PRO) {
							$h .= '<td class="hide-sm-down"></td>';
						}
						$h .= '<td class="text-end" colspan="3"><b>'.s("Total").'</b></td>';
						$h .= '<td class="text-end"><b>'.\util\TextUi::money($total).' '.ProductUi::getTaxes($eDate).'</b></td>';
						$h .= '<td class="hide-xs-down"></td>';
					$h .= '</tr>';
				$h .= '</tfoot>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getProducts(Shop $eShop, Date $eDate, \selling\Sale $eSaleExisting, array $basket, float &$total): string {

		$h = '';

		foreach($basket as $product) {

			$eProduct = $product['product'];
			$eProductSelling = $eProduct['product'];

			$available = ProductLib::getReallyAvailable($eProduct, $eProductSelling, $eSaleExisting);

			$deleteAttributes = [
				'data-confirm' => s("Souhaitez-vous réellement supprimer ce produit ?"),
				'onclick' => 'BasketManage.deleteProduct('.$eDate['id'].', '.$eProductSelling['id'].')',
				'title' => s("Supprimer cet article"),
			];

			$unitPrice = \util\TextUi::money($eProduct['price']).' '.ProductUi::getTaxes($eProduct).\selling\UnitUi::getBy($eProductSelling['unit'], short: TRUE);
			$price = round($eProduct['price'] * $product['number'] * ($eProduct['packaging'] ?? 1), 2);

			$h .= '<tr>';
				$h .= '<td class="td-min-content">';
					if($eProductSelling['vignette'] !== NULL) {
						$h .= \selling\ProductUi::getVignette($eProductSelling, '3rem', public: TRUE);
					}
				$h .= '</td>';
				$h .= '<td class="basket-summary-product">';
					$h .= encode($eProductSelling->getName());
					$h .= '<div class="hide-sm-up"><small style="white-space: nowrap">'.$unitPrice.'</small></div>';
					if($product['warning'] !== NULL) {

						$h .= '<div class="color-danger">';
							$h .= match($product['warning']) {
								'number' => s("Ce produit n'étant plus disponible en quantité suffisante, la quantité de votre commande a été modifiée."),
								'min' => s("La quantité de ce produit a été modifiée car vous avez commandé en dessous du minimum de commande."),
							};
						$h .= '</div>';

					}
				$h .= '</td>';
				if($eDate['type'] === Date::PRO) {
					$h .= '<td class="hide-sm-down">';
						if($eProduct['packaging'] !== NULL) {
							$h .= s("Colis de {value}", \selling\UnitUi::getValue($eProduct['packaging'], $eProductSelling['unit'], TRUE));
						}
					$h .= '</td>';
				}
				$h .= '<td>';
					$h .= ProductUi::numberOrder($eShop, $eDate, $eProductSelling, $eProduct, $product['number'], $available);
				$h .= '</td>';
				$h .= '<td class="text-end hide-xs-down">';
					$h .= $unitPrice;
					if($eProduct['packaging'] !== NULL) {
						$h .= '<div class="hide-md-up">'.s("Colis de {value}", \selling\UnitUi::getValue($eProduct['packaging'], $eProductSelling['unit'], TRUE)).'</div>';
					}
				$h .= '</td>';
				$h .= '<td class="text-end">'.\util\TextUi::money($price).' '.ProductUi::getTaxes($eProduct).'</td>';
				$h .= '<td class="hide-xs-down text-center">';
					$h .= '<a '.attrs($deleteAttributes).'>'.\Asset::icon('trash').'</a>';
				$h .= '</td>';
			$h .= '</tr>';

			$total += $price;

		}

		return $h;

	}

	public function getAuthenticateText(string $step): string {

		$h = '';

		switch($step) {

			case BasketUi::STEP_SUMMARY :
				$h .= '<div class="util-block">';
				$h .= '<h4>'.s("Votre panier est enregistré !").'</h4>';
				$h .= '<p>'.s("Pour confirmer votre commande, veuillez vous connecter si vous avez déjà un compte. Si vous êtes un nouveau client, saisissez quelques informations qui permettront à votre producteur de vous reconnaître !").'</p>';
				$h .= '</div>';
				break;

			case BasketUi::STEP_CONFIRMATION :
				$h .= '<div class="util-block">';
				$h .= '<h4>'.s("Votre confirmation de commande").'</h4>';
				$h .= '<p>'.s("Pour consulter votre confirmation de commande, veuillez vous connecter à votre compte client.").'</p>';
				$h .= '</div>';
				break;

		}

		return $h;

	}

	public function getAuthenticateForm(\user\User $eUser, \user\Role $eRole): string {

		$h = '<div class="shop-identification">';
			$h .= '<div>';
				$h .= '<h2>'.s("Déjà inscrit ?").'</h2>';
				$h .= new \user\UserUi()->logInBasic();
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<h2>'.s("Nouveau client ?").'</h2>';
				$h .= new \user\UserUi()->signUp($eUser, $eRole, LIME_REQUEST);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getOrder(Date $eDate, \selling\Sale $eSale): string {

		$h = '<div class="util-title">';
			$h .= '<h2>'.s("Ma commande").'</h2>';
			$h .= '<a href="'.ShopUi::dateUrl($eDate['shop'], $eDate).'?modify=1" target="_parent" class="btn btn-outline-primary">'.\Asset::icon('chevron-left').' '.s("Modifier la commande").'</a>';
		$h .= '</div>';
		$h .= '<div class="util-block mb-2">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Nom").'</dt>';
				$h .= '<dd>'.encode($eSale['customer']->getName()).'</dd>';
				$h .= '<dt>'.s("Montant").'</dt>';
				$h .= '<dd>';
					if($eSale['hasVat'] and $eSale['type'] === \selling\Sale::PRO) {
						$h .= \util\TextUi::money($eSale['priceExcludingVat']).' '.$eSale->getTaxes();
					} else {
						$h .= \util\TextUi::money($eSale['priceIncludingVat']);
					}
				$h .= '</dd>';
			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	public function getAccount(\user\User $eUser): string {

		$h = '<div class="util-title">';
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

	public function getComment(Shop $eShop, \selling\Sale $eSaleExisting): string {

		if($eShop['comment'] === FALSE) {
			return '';
		}

		$h = '<h2>'.s("Laisser un commentaire").'</h2>';

		if($eShop['commentCaption'] !== NULL) {
			$h .= '<h4>'.encode($eShop['commentCaption']).'</h4>';
		}

		$h .= '<div class="mb-3">';
			$h .= (new \util\FormUi())->dynamicField($eSaleExisting, 'shopComment', function(\PropertyDescriber $d) {
				$d->attributes['id'] = 'basket-comment';
			});
		$h .= '</div>';


		return $h;

	}

	public function getSubmitBasket(Shop $eShop, Date $eDate, \user\User $eUserOnline, bool $hasPoint, Point $ePointSelected): string {

		$class = $hasPoint === FALSE or (
			$ePointSelected->notEmpty() and (
				($ePointSelected['type'] === Point::HOME and $eUserOnline->hasAddress()) or
				($ePointSelected['type'] === Point::PLACE)
			)
		) ? '' : 'hide';

		$h = '<div id="shop-basket-submit" class="'.$class.'" onrender="BasketManage.checkBasketButtons('.$eDate['id'].');">';

			$h .= '<h2>';
				$h .= match($eShop['type']) {
					Shop::PRO => s("Valider ma commande"),
					Shop::PRIVATE => s("Valider ma commande de <span>{value}</span> {taxes}", ['span' => '<span id="shop-basket-price">', 'taxes' => ProductUi::getTaxes($eDate)])
				};
			$h .= '</h2>';

			$h .= '<div class="shop-basket-submit-order-error color-danger hide">'.s("Vous n'avez pas atteint le minimum de commande pour ce mode de livraison, nous vous remercions de bien vouloir compléter vos achats !").'</div>';
			$h .= '<div class="shop-basket-submit-order-valid">';

				$h .= $this->getTermsBasket($eShop);

				$h .= '<div class="basket-buttons hide" data-ref="basket-create">';
					$h .= '<a onclick="BasketManage.doCreate('.$eDate['id'].');" class="btn btn-lg btn-secondary">'.s("Valider ma commande").' '.\Asset::icon('chevron-right').'</a> ';
				$h .= '</div>';

				$h .= '<div class="basket-buttons hide" data-ref="basket-update">';
					$h .= '<a onclick="BasketManage.doUpdate('.$eDate['id'].');" class="btn btn-lg btn-secondary">'.s("Valider la modification de commande").' '.\Asset::icon('chevron-right').'</a> ';
					$h .= '<a href="'.\shop\ShopUi::confirmationUrl($eShop, $eDate).'" class="btn btn-lg btn-outline-secondary">'.s("Conserver ma commande initiale").'</a> ';
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
				$h .= new \util\FormUi()->inputCheckbox('terms').'  ';
				$h .= s("J'ai lu et j'accepte les <link>conditions générales de vente</link>.", ['link' => '<a href="'.ShopUi::url($eShop).':conditions">']);
			$h .= '</label>';

		} else {

			$h = '<div class="mb-1">';
				$h .= s("En validant ma commande, je confirme que j'ai lu et que j'accepte les <link>conditions générales de vente</link>.", ['link' => '<a href="'.ShopUi::url($eShop).':conditions">']);
			$h .= '</div>';

		}

		return $h;

	}

	public function getTerms(Shop $eShop): \Panel {

		if($eShop['terms'] !== NULL) {
			$h = new \editor\EditorUi()->value($eShop['terms']);
		} else {
			$h = '<div class="util-empty">'.s("Il n'y a pas encore de conditions générales de vente sur la boutique.").'</div>';
		}

		return new \Panel(
			id: 'panel-basket-conditions',
			title: s("Conditions générales de vente"),
			body: $h
		);

	}

	public function getDeliveryForm(Shop $eShop, Date $eDate, \Collection $ccPoint, \user\User $eUser, Point $ePointSelected): string {

		if($ccPoint->empty()) {
			return '';
		}

		$h = '<div id="shop-basket-point">';
			$h .= new PointUi()->getField($eShop, $ccPoint, $ePointSelected);
		$h .= '</div>';

		$h .= '<div id="shop-basket-address-wrapper" data-type="'.($ePointSelected->empty() ? '' : $ePointSelected['type']).'">';

			$h .= $this->getAddress($eUser);

			if($eUser->hasAddress() === FALSE) {
				$h .= $this->getAddressForm($eShop, $eDate, $eUser);
			}

		$h .= '</div>';

		return $h;

	}

	public function getPhoneForm(Shop $eShop, Date $eDate, \user\User $eUser): string {

		$h = '<h2>'.s("Mon numéro de téléphone").'</h2>';
		$h .= '<p class="util-info">';
			$h .= s("Un numéro de téléphone est nécessaire pour que le producteur puisse vous contacter en cas de besoin à propos de votre commande !");
		$h .= '</p>';

		$form = new \util\FormUi();

		$h .= $form->openAjax(\shop\ShopUi::userUrl($eShop, $eDate, ':doUpdatePhone'));

			$h .= $form->dynamicField($eUser, 'phone').'<br/>';
			$h .= $form->submit(s("Enregistrer le numéro").' '.\Asset::icon('chevron-right'), ['class' => 'btn btn-lg btn-secondary']);

		$h .= $form->close();

		return $h;

	}

	public function getAddressForm(Shop $eShop, Date $eDate, \user\User $eUser): string {

		$h = '<div id="shop-basket-address-form">';

			$h .= '<h2>'.s("Mon adresse de livraison").'</h2>';
			$h .= '<p class="util-info">';
				$h .= s("Veuillez saisir une adresse qui correspond au lieu de livraison que vous avez choisi !");
			$h .= '</p>';

			$form = new \util\FormUi();

			$h .= $form->openAjax(\shop\ShopUi::userUrl($eShop, $eDate, ':doUpdateAddress'), ['style' => 'max-width: 40rem']);

				$h .= $form->address(NULL, $eUser).'<br/>';
				$h .= $form->submit(s("Enregistrer l'adresse").' '.\Asset::icon('chevron-right'), ['class' => 'btn btn-lg btn-secondary']);

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getAddress(\user\User $eUser): string {

		$h = '<div id="shop-basket-address-show">';

			$h .= '<div class="util-title">';
				$h .= '<h2>'.s("Mes coordonnées").'</h2>';
				$h .= '<a href="/user/settings:updateUser" class="btn btn-outline-primary">'.s("Mettre à jour").'</a>';
			$h .= '</div>';

			$h .= '<dl class="util-presentation util-presentation-1">';
				$h .= '<dt>'.s("Nom").'</dt>';
				$h .= '<dd>'.$eUser->getName().'</dd>';
				if($eUser->hasAddress()) {
					$h .= '<dt class="shop-basket-address-lines">'.s("Adresse").'</dt>';
					$h .= '<dd class="shop-basket-address-lines" style="line-height: 1.2">'.nl2br(encode($eUser->getAddress())).'</dd>';
				}
				$h .= '<dt>'.s("Téléphone").'</dt>';
				$h .= '<dd id="shop-basket-address-phone">'.encode($eUser['phone']).'</dd>';
			$h .= '</dl>';

			$h .= '<br/><br/>';

		$h .= '</div>';

		return $h;

	}

	public function getPayment(Shop $eShop, Date $eDate, \selling\Customer $eCustomer, \selling\Sale $eSale, \payment\StripeFarm $eStripeFarm): string {

		\Asset::js('shop', 'basket.js');

		$ePoint = $eShop['ccPoint']->find(fn($ePoint) => $eSale['shopPoint']->notEmpty() and $ePoint['id'] === $eSale['shopPoint']['id'], depth: 2, limit: 1, default: new Point());

		$payments = $eShop->getPayments($ePoint);

		foreach($payments as $key => $payment) {

			if(
				$eStripeFarm->empty() and
				$payment === \selling\Sale::ONLINE_CARD
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
				$h .= '<h4>';
					if(get_exists('modify')) {
						$h .= s("Vous commande est toujours confirmée, mais vous pouvoir choisir un autre moyen de paiement :");
					} else {
						$h .= s("Votre commande n'est pas encore confirmée, veuillez choisir votre moyen de paiement :");
					}
				$h .= '</h4>';
				break;

		}

		$h .= '<div class="shop-payments">';

			foreach($payments as $payment) {

				$h .= '<a data-ajax="'.ShopUi::userUrl($eShop, $eDate, ':doCreatePayment').'" post-payment="'.$payment.'" class="util-block shop-payment">';
					$h .= $this->getPaymentBlock($eShop, $eDate, $eCustomer, $payment);
				$h .= '</a>';

			}

		$h .= '</div>';

		if($eSale->acceptCustomerCancel()) {
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
					\selling\Sale::OFFLINE => s("Paiement avec le producteur"),
					\selling\Sale::ONLINE_CARD => s("Carte Bancaire"),
					\selling\Sale::TRANSFER => s("Virement bancaire"),
				};
			$h .= '</h4>';

			$h .= '<p class="shop-payment-description">';

				$h .= '<b>';
					$h .= match($payment) {
						\selling\Sale::OFFLINE => $eShop['paymentOfflineHow'] ? encode($eShop['paymentOfflineHow']) : s("Vous gérez le paiement de votre commande directement avec votre producteur."),
						\selling\Sale::ONLINE_CARD => s("Payez maintenant votre commande en ligne avec votre carte bancaire."),
						\selling\Sale::TRANSFER => $eShop['paymentTransferHow'] ? encode($eShop['paymentTransferHow']) : s("Vous paierez plus tard votre commande par virement bancaire à réception de facture.")
					};
				$h .= '</b>';

			$h .= '</p>';

		$h .= '</div>';

		$editCancel = \Asset::icon('check-lg').' '.s("Commande annulable et modifiable jusqu'au {value}.", ['value' => \util\DateUi::textual($eDate['orderEndAt'], \util\DateUi::DATE_HOUR_MINUTE)]);
		$notEditCancel = s("Commande non annulable et non modifiable.");

		$h .= '<div class="shop-payment-cancel">';
			$h .= match($payment) {
				\selling\Sale::OFFLINE => $editCancel,
				\selling\Sale::ONLINE_CARD => $notEditCancel,
				\selling\Sale::TRANSFER => $editCancel
			};
		$h .= '</div>';

		$h .= '<span class="btn btn-secondary">';

			$h .= match($payment) {
				\selling\Sale::OFFLINE => s("Choisir le paiement avec le producteur").' ',
				\selling\Sale::ONLINE_CARD => s("Payer maintenant par carte bancaire").' ',
				\selling\Sale::TRANSFER => s("Choisir le paiement par virement bancaire").' '
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

					case \selling\Sale::PAID :
						$content .= '<h2>'.\Asset::icon('check').' '.s("Merci, votre commande est confirmée et payée !").'</h2>';
						$content .= '<p>'.s("Vous avez reçu un e-mail de confirmation.").'</p>';
						break;

					case \selling\Sale::FAILED :
						$content .= '<h2>'.\Asset::icon('exclamation-triangle-fill').' '.s("Le paiement de votre commande a échoué !").'</h2>';
						$content .= '<p>'.s("Votre compte n'a pas été débité et votre commande n'est pas encore confirmée. Pour confirmer votre commande, veuillez retenter un paiement.").'</p>';
						$content .= '<a href="'.\shop\ShopUi::paymentUrl($eShop, $eDate).'" class="btn btn-transparent">'.s("Retenter un paiement").'</a> ';
						break;

				};
				break;

			case \selling\Sale::TRANSFER :

				$content .= '<h2>'.\Asset::icon('check').' '.s("Merci, votre commande est confirmée !").'</h2>';
				$content .= '<p>'.s("Vous avez reçu un e-mail de confirmation.").'</p>';
				$content .= '<p>';
					$content .= s("Vous avez choisi de régler cette commande par virement bancaire.<br/>Vous recevrez ultérieurement une facture de votre producteur afin de procéder au règlement.");
				$content .= '</p>';
				break;

			case \selling\Sale::OFFLINE :
			case NULL :

				$content .= '<h2>'.\Asset::icon('check').' '.s("Merci, votre commande est confirmée !").'</h2>';
				$content .= '<p>'.s("Vous avez reçu un e-mail de confirmation.").'</p>';
				if($eSale['paymentMethod'] === \selling\Sale::OFFLINE) {
					$content .= '<p>';
						$content .= s("Vous avez choisi de régler cette commande en direct avec votre producteur.");
					$content .= '</p>';
				}
				break;

		}

		$h = $this->getDeliveryTitle($eDate);
		$h .= '<div class="basket-flow '.$class.'">';
			$h .= $content;
		$h .= '</div>';

		if($eShop['embedOnly']) {
			$h .= '<div class="mb-1">';
				$h .= '<a href="'.encode($eShop['embedUrl']).'" class="btn btn-outline-secondary">'.s("Retourner sur le site du producteur").'</a>';
			$h .= '</div>';
		}

		$h .= '<br/>';

		return $h;

	}

	public function getConfirmation(Shop $eShop, Date $eDate, \selling\Sale $eSale): string {

		$h = '<div class="sale-confirmation-container">';

			$h .= '<h2>'.s("Résumé de votre commande du {value}", \util\DateUi::textual($eSale['createdAt'], \util\DateUi::DATE_HOUR_MINUTE)).'</h2>';

			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Montant").'</dt>';
				$h .= '<dd>';
					if($eSale['hasVat'] and $eSale['type'] === \selling\Sale::PRO) {
						$h .= \util\TextUi::money($eSale['priceExcludingVat']).' '.$eSale->getTaxes();
					} else {
						$h .= \util\TextUi::money($eSale['priceIncludingVat']);
					}
				$h .= '</dd>';
				$h .= '<dt>'.s("État de la commande").'</dt>';
				$h .= '<dd>'.\selling\SaleUi::getPreparationStatusForCustomer($eSale).'</dd>';

				$h .= '<dt>'.s("Paiement").'</dt>';
				$h .= '<dd>';
					if($eSale['paymentMethod'] !== NULL) {
						$h .= \selling\SaleUi::p('paymentMethod')->values[$eSale['paymentMethod']];
					} else {
						$h .= \selling\SaleUi::p('paymentMethod')->values[\selling\Sale::OFFLINE];
					}
				$h .= '</dd>';

				if($eSale->isPaymentOnline()) {
					$h .= '<dt>'.s("État du paiement").'</dt>';
					$h .= '<dd>'.\selling\SaleUi::getPaymentStatusForCustomer($eSale).'</dd>';
				}
			$h .= '</dl>';

			$h .= '<br/>';

			if($eSale->acceptCustomerCancel()) {
				$h .= '<div>';
					$h .= '<a href="'.ShopUi::dateUrl($eDate['shop'], $eDate).'?modify=1" target="_parent" class="btn btn-secondary" title="'.s("Cette commande est modifiable jusqu'au {value}.", ['value' => \util\DateUi::textual($eDate['orderEndAt'], \util\DateUi::DATE_HOUR_MINUTE)]).'">'.s("Modifier ma commande").'</a>';
					$h .= '&nbsp;';
					$h .= '<a '.attr('onclick', 'BasketManage.doCancel('.$eSale['id'].')').'" class="btn btn-outline-secondary" data-confirm="'.s("Êtes-vous sûr de vouloir annuler cette commande ?").'" title="'.s("Cette commande est annulable jusqu'au {value}.", ['value' => \util\DateUi::textual($eDate['orderEndAt'], \util\DateUi::DATE_HOUR_MINUTE)]).'">'.s("Annuler ma commande").'</a>';
				$h .= '</div>';
				$h .= '<br/>';
			}

			if($eSale['shopPoint']->notEmpty()) {
				$h .= '<h3>'.s("Mode de livraison").'</h3>';
				$h .= new \selling\OrderUi()->getPointBySale($eSale);
			}

			if($eSale['shopComment'] !== NULL) {
				$h .= '<h3>'.s("Commentaire").'</h3>';
				$h .= '<div class="util-block">'.encode($eSale['shopComment']).'</div>';
			}


			$h .= new \selling\OrderUi()->getItemsBySale($eSale, $eSale['cItem']);

		$h .= '</div>';

		return $h;
	}

	public function getJsonBasket(\selling\Sale $eSale, ?array $products = NULL): string {

		if($eSale->empty() and $products === NULL) {

			return '{}';

		} else {

			$h = '{';
				$h .= 'createdAt: '.time().',';
				$h .= 'sale: '.($eSale->notEmpty() ? $eSale['id'] : 'null').',';
				$h .= 'products: ';

					if($products !== NULL) {
						$h .= json_encode($products);
					} else if($eSale->notEmpty()) {

						$h .= '{';

							foreach ($eSale['cItem'] as $eItem) {
								$h .= '"'.$eItem['product']['id'].'": {number: '.$eItem['number'].'},';
							}

							$h = substr($h, 0, -1);

						$h .= '}';

					}
				$h .= '';
			$h .= '}';

			return $h;

		}

	}

}