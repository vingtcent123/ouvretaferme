<?php
new AdaptativeView('denied', function($data, ShopTemplate $t) {

	$t->title = s("Boutique en accès restreint");
	$t->header = '<h1>'.Asset::icon('lock-fill').' '.$t->title.'</h1>';

	if(\shop\Shop::isEmbed()) {

		echo '<div class="util-danger">';
			echo s("Les boutiques en ligne restreintes à certains clients ne peuvent pas être embarquées sur un site internet.");
		echo '</div>';

	} else {

		if(\user\ConnectionLib::getOnline()->empty()) {

			echo '<div class="util-block">';
				echo '<h4>'.s("Vous n'êtes pas connecté !").'</h4>';
				echo s("Veuillez vous connecter pour accéder à cette boutique en ligne.");
			echo '</div>';

			echo new \user\UserUi()->logInBasic();

		} else {

			echo '<div class="util-block">';
				echo '<h4>'.s("Accès impossible").'</h4>';
				echo s("Vous n'avez pas accès à cette boutique en ligne, rapprochez-vous de la ferme pour corriger ce problème.");
			echo '</div>';

		}

	}

});
new AdaptativeView('shop', function($data, ShopTemplate $t) {

	$t->title = encode($data->eShop['name']);
	$t->header = new \shop\ShopUi()->getHeader($data->eShop, $data->cDate);

	if($data->eShop['logo'] !== NULL) {
		$t->og['image'] = new \media\ShopLogoUi()->getUrlByElement($data->eShop);
	}

	Asset::js('shop', 'basket.js');

	if($data->eShop['status'] === \shop\Shop::CLOSED) {

		if($data->eShop->canWrite()) {

			echo '<div class="util-danger">';
				echo s("Cette boutique est actuellement fermée. Vos clients ne pourront pas consulter cette page tant que vous ne l'aurez pas ouverte !");
			echo '</div>';

		} else {

			echo '<div class="util-info">';
				echo s("La boutique est actuellement fermée.");
			echo '</div>';

			return;

		}

	}

	if(
		$data->isModifying === FALSE and
		$data->cDate->count() > 1
	) {

		echo '<div class="shop-header-flow">';
			echo new \shop\DateUi()->getDeliveryPeriods($data->eShop, $data->cDate, $data->eDateSelected);
		echo '</div>';

	}

	if($data->eDateSelected->notEmpty()) {

		echo '<div class="util-title">';
			echo '<h3>';
				echo \shop\DateUi::name($data->eDateSelected);
			echo '</h3>';
			echo '<div>';
				if($data->eShop['shared']) {
					echo new \shop\BasketUi()->getSearch($data->eShop['cShare']);
				}
			echo '</div>';
		echo '</div>';

		if($data->eDateSelected['description'] !== NULL) {
			echo '<div class="util-block">';
				echo new \editor\EditorUi()->value($data->eDateSelected['description']);
			echo '</div>';
		}

		$details = [];

		if($data->eDateSelected['isOrderable']) {

			if(
				$data->canBasket === FALSE and
				$data->isModifying === FALSE
			) {

				echo '<div class="util-block bg-success color-white">';
					echo '<p>';
						echo s("Merci, votre commande pour le {value} est enregistrée !", \util\DateUi::textual($data->eDateSelected['deliveryDate'], \util\DateUi::DATE_HOUR_MINUTE));
						if(
							$data->cSaleExisting->notEmpty() and
							$data->cSaleExisting->first()->acceptStatusCanceledByCustomer()
						) {
							echo '<br/>'.s("Cette commande est modifiable et annulable jusqu'au {value}.", \util\DateUi::textual($data->eDateSelected['orderEndAt'], \util\DateUi::DATE_HOUR_MINUTE));
						}
					echo '</p>';
					echo '<a href="'.\shop\ShopUi::confirmationUrl($data->eShop, $data->eDateSelected).'" '.(\shop\Shop::isEmbed() ? 'target="_blank"' : '').' class="btn btn-transparent">'.s("Consulter ma commande").'</a>';
				echo '</div>';

			}

			$orderPeriod = new \shop\DateUi()->getOrderPeriod($data->eDateSelected);
			$orderLimits = new \shop\DateUi()->getOrderLimits($data->eShop, $data->eDateSelected['ccPoint']);

			if($orderPeriod) {
				$details[] = Asset::icon('clock').'  '.$orderPeriod;
			}

			if($orderLimits) {
				$details[] = Asset::icon('cart').'  '.$orderLimits;
			}

		} else if(
			$data->eDateSelected['isDeliverable'] and
			$data->cSaleExisting->notEmpty()
		) {

			$details[] = Asset::icon('lock-fill').'  '.s("La vente est maintenant fermée, n'oubliez pas de venir chercher votre commande le {value} !", \util\DateUi::textual($data->eDateSelected['deliveryDate']));

		} else if($data->eDateSelected['isSoonOpen']) {

			$details[] = Asset::icon('clock').'  '.s("Les prises de commandes démarrent bientôt, revenez le {date} pour passer commande !", ['date' => \util\DateUi::textual($data->eDateSelected['orderStartAt'], \util\DateUi::DATE_HOUR_MINUTE)]);

		} else {

			$details[] = Asset::icon('lock-fill').'  '.s("Cette vente est désormais terminée !");

		}

		if(array_sum($data->discounts) > 0) {

			if($data->eShop['shared']) {
				$details[] = Asset::icon('check-lg').'  '.s("Les prix affichés incluent la remise commerciale dont vous bénéficiez chez certains producteurs !");
			} else {
				$discount = $data->discounts[$data->eShop['farm']['id']];
				$details[] = Asset::icon('check-lg').'  '.s("Les prix affichés incluent la remise commerciale de {value} % dont vous bénéficiez !", $discount);
			}
		}

		if($details) {
			echo '<p>';
				echo implode('<br/>', $details);
			echo '</p>';
		}

		echo new \shop\ProductUi()->getList($data->eShop, $data->eDateSelected, $data->cItemExisting, $data->cCategory, $data->basketProducts, $data->canBasket, $data->isModifying);

	}

});

new AdaptativeView('/shop/public/{fqn}:conditions', function($data, PanelTemplate $t) {

	return new \shop\BasketUi()->getTerms($data->eShop);

});

new AdaptativeView('/shop/public/{fqn}/{date}/panier', function($data, ShopTemplate $t) {

	$uiBasket = new \shop\BasketUi();

	$t->title = encode($data->eShop['name']);
	$t->header = $uiBasket->getHeader($data->eShop);

	echo $uiBasket->getSteps($data->eShop, $data->eDate, $data->step);

	echo $uiBasket->getAccount($data->eUserOnline);

	echo '<div id="shop-basket-summary" '.attr('onrender', 'BasketManage.loadSummary('.$data->eDate['id'].', '.($data->cItemExisting->empty() ? 'null' : $data->eUserOnline['id']).', '.($data->basketProducts === NULL ? 'null' : json_encode($data->basketProducts)).', '.($data->isModifying ? 'true' : 'false').');').'></div>';

	if($data->eUserOnline['phone'] === NULL) {
		echo '<div id="shop-basket-phone">';
			echo new \shop\BasketUi()->getPhoneForm($data->eShop, $data->eDate, $data->eUserOnline);
		echo '</div>';
	}

	echo '<div id="shop-basket-delivery" class="'.($data->eUserOnline['phone'] === NULL ? 'hide' : '').' mb-2">';
		if($data->hasPoint) {
			echo $uiBasket->getDeliveryForm($data->eShop, $data->eDate, $data->eDate['ccPoint'], $data->eUserOnline, $data->ePointSelected);
		}
		echo $uiBasket->getComment($data->eShop, $data->eSaleReference);
		echo $uiBasket->getSubmitBasket($data->eShop, $data->eDate, $data->eUserOnline, $data->hasPoint, $data->ePointSelected);
	echo '</div>';


});

new JsonView('/shop/public/{fqn}/{date}/:getBasket', function($data, AjaxTemplate $t) {

	$t->push('basketSummary', new \shop\BasketUi()->getSummary($data->eShop, $data->eDate, $data->cItemExisting, $data->basket));
	$t->push('basketPrice', $data->price);

});

new AdaptativeView('basketExisting', function($data, ShopTemplate $t) {

	$uiBasket = new \shop\BasketUi();

	$t->title = encode($data->eShop['name']);
	$t->header = $uiBasket->getHeader($data->eShop);

	echo '<div class="util-block">';
		echo '<h4>'.s("Vous avez déjà commandé !").'</h4>';
		echo '<p>'.s("Nous avons déjà une commande enregistrée à votre nom pour la livraison du {value} et vous ne pouvez pas en passer une autre avec le même compte client.", \util\DateUi::textual($data->eDate['deliveryDate'])).'</p>';
		echo '<a href="'.\shop\ShopUi::confirmationUrl($data->eShop, $data->eDate).'" class="btn btn-primary">'.s("Consulter ma commande").'</a>';
	echo '</div>';

});

new AdaptativeView('authenticate', function($data, ShopTemplate $t) {

	$uiBasket = new \shop\BasketUi();

	$t->title = encode($data->eShop['name']);
	$t->header = $uiBasket->getHeader($data->eShop);

	if($data->step === \shop\BasketUi::STEP_SUMMARY) {
		echo $uiBasket->getSteps($data->eShop, $data->eDate, $data->step);
	}

	echo $uiBasket->getAuthenticateText($data->step);
	echo $uiBasket->getAuthenticateForm($data->eUserOnline, $data->eRole);

});

new AdaptativeView('/shop/public/{fqn}/{date}/paiement', function($data, ShopTemplate $t) {

	$uiBasket = new \shop\BasketUi();

	$t->title = encode($data->eShop['name']);
	$t->header = $uiBasket->getHeader($data->eShop);

	echo $uiBasket->getSteps($data->eShop, $data->eDate, $data->step);
	echo $uiBasket->getOrder($data->eDate, $data->eSaleReference);
	echo $uiBasket->getPayment($data->eShop, $data->eDate, $data->eCustomer, $data->eSaleReference, $data->eStripeFarm);

});

new AdaptativeView('/shop/public/{fqn}/{date}/confirmation', function($data, ShopTemplate $t) {

	$uiBasket = new \shop\BasketUi();

	$t->title = encode($data->eShop['name']);
	$t->header = $uiBasket->getHeader($data->eShop);

	echo $uiBasket->getPaymentStatus($data->eShop, $data->eDate, $data->eSaleReference);
	echo $uiBasket->getConfirmation($data->eShop, $data->eDate, $data->eSaleReference, $data->cSaleExisting, $data->cItemExisting);

});

new AdaptativeView('confirmationEmpty', function($data, ShopTemplate $t) {

	$uiBasket = new \shop\BasketUi();

	$t->title = encode($data->eShop['name']);
	$t->header = $uiBasket->getHeader($data->eShop);

	echo '<div class="util-block">';
		echo '<h4>'.s("Aucune commande enregistrée").'</h4>';
		echo '<p>'.s("Nous n'avons pas retrouvé de commande enregistrée à votre nom pour la livraison du {value}.", \util\DateUi::textual($data->eDate['deliveryDate'])).'</p>';
		echo '<a href="'.\shop\ShopUi::url($data->eShop).'" class="btn btn-primary">'.s("Revenir sur la boutique").'</a>';
	echo '</div>';

});

new AdaptativeView('/shop/public/{fqn}/{date}/:doCreateSale', function($data, AjaxTemplate $t) {

	if($data->created) {
		$t->js()->eval('BasketManage.deleteBasket('.$data->eSaleReference['shopDate']['id'].')');
	}

	$t->redirect(\shop\ShopUi::paymentUrl($data->eShop, $data->eDate));

});

new AdaptativeView('/shop/public/{fqn}/{date}/:doCancelCustomer', function($data, AjaxTemplate $t) {

	$t->js()->eval('BasketManage.deleteBasket('.$data->eSaleReference['shopDate']['id'].')');
	$t->redirect(\shop\ShopUi::paymentUrl($data->eShop, $data->eDate));

});

new AdaptativeView('/shop/public/{fqn}/{date}/:doUpdatePhone', function($data, AjaxTemplate $t) {

	if($data->e['phone'] !== NULL) {

		$t->js()->success('shop', 'Sale::phone');
		$t->qs('#shop-basket-phone')->remove();
		$t->qs('#shop-basket-delivery')->removeClass('hide');
		$t->qs('#shop-basket-address-phone')->innerHtml(encode($data->e['phone']));

	}

});

new AdaptativeView('/shop/public/{fqn}/{date}/:doUpdateAddress', function($data, AjaxTemplate $t) {

	// L'adresse a bien été renseignée
	if($data->e->hasAddress()) {

		$t->js()->success('shop', 'Sale::address');
		$t->qs('#shop-basket-address')->remove();
		$t->qs('#shop-basket-submit')->removeClass('hide');
		$t->qs('#shop-basket-address-wrapper')->innerHtml(new \shop\BasketUi()->getAddress($data->eUserOnline));

	}

});
?>
