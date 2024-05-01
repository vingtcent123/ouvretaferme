<?php
new AdaptativeView('shop', function($data, ShopTemplate $t) {

	$t->metaDescription = 'description';
	$t->title = encode($data->eShop['name']);
	$t->header = (new \shop\ShopUi())->getHeader($data->eShop, $data->cDate, $data->eDateSelected);

	Asset::js('shop', 'basket.js');

	if($data->eShop['status'] === \shop\Shop::CLOSED) {

		if($data->eShop->canWrite()) {

			echo '<div class="util-warning">';
				echo s("Cette boutique est actuellement fermée. Vos clients ne pourront pas consulter cette page tant que vous ne l'aurez pas ouverte !");
			echo '</div>';

		} else {

			echo '<div class="util-info">';
				echo s("La boutique est actuellement fermée.");
			echo '</div>';

			return;

		}

	}

	if($data->eDateSelected->notEmpty()) {

		echo '<h2 class="shop-date">';
			echo \shop\DateUi::name($data->eDateSelected);
		echo '</h2>';

		if($data->eDateSelected['isOrderable']) {

			echo '<div class="util-block">';
				echo (new \shop\DateUi())->getOrderPeriod($data->eDateSelected);
				echo ' ';
				echo (new \shop\DateUi())->getOrderLimits($data->eShop);
			echo '</div>';

			if(
				$data->eSaleExisting->canBasket() === FALSE and
				$data->isModifying === FALSE
			) {

				echo '<div class="util-block bg-success color-white">';
					echo '<p>';
						echo s("Merci, vous commande pour le {value} est enregistrée !", \util\DateUi::textual($data->eDateSelected['deliveryDate'], \util\DateUi::DATE_HOUR_MINUTE));
						if($data->eSaleExisting->canCustomerCancel()) {
							echo '<br/>'.s("Cette commande est modifiable et annulable jusqu'au {value}.", \util\DateUi::textual($data->eDateSelected['orderEndAt'], \util\DateUi::DATE_HOUR_MINUTE));
						}
					echo '</p>';
					echo '<a href="'.\shop\ShopUi::dateUrl($data->eShop, $data->eDateSelected, 'confirmation').'" class="btn btn-transparent">'.s("Consulter ma commande").'</a>';
				echo '</div>';

			}

		} else if($data->eDateSelected['isDeliverable']) {

			echo '<div class="util-block">';
				echo Asset::icon('lock-fill').' ';
				echo s("La vente est maintenant fermée, n'oubliez pas de venir chercher votre commande le {value} !", \util\DateUi::textual($data->eDateSelected['deliveryDate']));
			echo '</div>';

		} else if($data->eDateSelected['isSoonOpen']) {

			echo '<div class="util-block">';
				echo s("Les prises de commandes démarrent bientôt, revenez le {date} pour passer commande !",
					['date' => \util\DateUi::textual($data->eDateSelected['orderStartAt'], \util\DateUi::DATE_HOUR_MINUTE)]);
			echo '</div>';

		} else {

			echo '<div class="util-block">';
				echo s("Cette vente est désormais terminée !");
			echo '</div>';

		}

		if($data->discount > 0) {
			echo '<div class="util-block">';
				echo s("Les prix affichés tiennent compte de la remise commerciale de {value} % dont vous bénéficiez !", $data->discount);
			echo '</div>';
		}

		echo (new \shop\ProductUi())->getList($data->eShop, $data->eDateSelected, $data->eSaleExisting, $data->isModifying);

	}

});

new AdaptativeView('/shop/public/{id}:conditions', function($data, PanelTemplate $t) {

	return (new \shop\BasketUi())->getTerms($data->eShop);

});

new AdaptativeView('/shop/public/{fqn}/{date}/panier', function($data, ShopTemplate $t) {

	$t->metaDescription = 'description';
	$t->title = encode($data->eShop['name']);
	$t->header = (new \shop\BasketUi())->getHeader($data->eShop, $data->eDate, currentStep: \shop\BasketUi::STEP_SUMMARY);

	$uiBasket = new \shop\BasketUi();

	echo $uiBasket->getAccount($data->eUserOnline);

	echo '<div id="shop-basket-summary" onrender="BasketManage.loadSummary('.$data->eDate['id'].', '.($data->eSaleExisting->empty() ? 'null' : $data->eSaleExisting['id']).', '.($data->isModifying ? 'true' : 'false').');"></div>';

	if($data->eUserOnline['phone'] === NULL) {
		echo '<div id="shop-basket-phone">';
			echo (new \shop\BasketUi())->getPhoneForm($data->eShop, $data->eDate, $data->eUserOnline);
		echo '</div>';
	}

	echo '<div id="shop-basket-delivery" class="'.($data->eUserOnline['phone'] === NULL ? 'hide' : '').' mb-2">';
		echo $uiBasket->getDeliveryForm($data->eShop, $data->eDate, $data->eDate['ccPoint'], $data->eUserOnline, $data->ePointSelected);
		echo $uiBasket->getSubmitBasket($data->eShop, $data->eDate, $data->eUserOnline, $data->ePointSelected);
	echo '</div>';


});

new JsonView('/shop/public/{fqn}/{date}/:getBasket', function($data, AjaxTemplate $t) {

	$t->push('basketSummary', (new \shop\BasketUi())->getSummary($data->eShop, $data->eDate, $data->eSaleExisting, $data->basket, $data->isModifying));
	$t->push('basketPrice', $data->price);

});

new AdaptativeView('/shop/public/{fqn}/{date}/livraison', function($data, ShopTemplate $t) {

	$t->title = encode($data->eShop['name']);
	$t->header = (new \shop\BasketUi())->getHeader($data->eShop, $data->eDate, currentStep: \shop\BasketUi::STEP_DELIVERY);


});

new AdaptativeView('authenticate', function($data, ShopTemplate $t) {

	$t->metaDescription = 'description';
	$t->title = encode($data->eShop['name']);
	$t->header = (new \shop\BasketUi())->getHeader($data->eShop, $data->eDate, currentStep: \shop\BasketUi::STEP_SUMMARY);

	echo (new \shop\BasketUi())->getAuthenticateForm($data->eUserOnline, $data->eRole);

});

new AdaptativeView('/shop/public/{fqn}/{date}/paiement', function($data, ShopTemplate $t) {

	$t->title = encode($data->eShop['name']);
	$t->header = (new \shop\BasketUi())->getHeader($data->eShop, $data->eDate, currentStep: \shop\BasketUi::STEP_PAYMENT);

	echo (new \shop\BasketUi())->getOrder($data->eSaleExisting);
	echo (new \shop\BasketUi())->getPayment($data->eShop, $data->eDate, $data->eCustomer, $data->eSaleExisting, $data->eStripeFarm);

});

new AdaptativeView('/shop/public/{fqn}/{date}/confirmation', function($data, ShopTemplate $t) {

	$t->title = encode($data->eShop['name']);
	$t->header = (new \shop\BasketUi())->getHeader($data->eShop, $data->eDate, currentContent: (new \shop\BasketUi())->getPaymentStatus($data->eShop, $data->eDate, $data->eSaleExisting));

	echo (new \shop\BasketUi())->getConfirmation($data->eShop, $data->eDate, $data->eSaleExisting);

});

new AdaptativeView('/shop/public/{fqn}/{date}/:doCreateSale', function($data, AjaxTemplate $t) {

	if($data->created) {
		$t->js()->eval('BasketManage.deleteBasket('.$data->eSaleExisting['shopDate']['id'].')');
	}

	$t->redirect(\shop\ShopUi::dateUrl($data->eShop, $data->eDate, 'paiement'));

});

new AdaptativeView('/shop/public/{fqn}/{date}/:doCancelSale', function($data, AjaxTemplate $t) {

	$t->js()->eval('BasketManage.deleteBasket('.$data->eSaleExisting['shopDate']['id'].')');
	$t->redirect(\shop\ShopUi::dateUrl($data->eShop, $data->eDate, 'paiement'));

});

new AdaptativeView('/shop/public/{fqn}/{date}/:doUpdatePhone', function($data, AjaxTemplate $t) {

	if($data->e['phone'] !== NULL) {

		$t->js()->success('shop', 'Sale::phone');
		$t->qs('#shop-basket-phone')->remove();
		$t->qs('#shop-basket-delivery')->removeClass('hide');

	}

});

new AdaptativeView('/shop/public/{fqn}/{date}/:doUpdateAddress', function($data, AjaxTemplate $t) {

	// L'adresse a bien été renseignée
	if($data->e->hasAddress()) {

		$t->js()->success('shop', 'Sale::address');
		$t->qs('#shop-basket-address')->remove();
		$t->qs('#shop-basket-submit')->removeClass('hide');
		$t->qs('#shop-basket-address-wrapper')->innerHtml((new \shop\BasketUi())->getAddress($data->eUserOnline, class:  ''));

	}

});
?>
