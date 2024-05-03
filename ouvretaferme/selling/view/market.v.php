<?php
new AdaptativeView('/vente/{id}/marche', function($data, MarketTemplate $t) {

	echo '<h2 class="mt-2 text-center">';

		echo match($data->e['preparationStatus']) {
			\selling\Sale::DELIVERED => s("Ce marché est clôturé !"),
			default => s("Bienvenue sur la console de votre marché, à vous de jouer !")
		};

	echo '</h2>';

	if($data->nItems === 0) {

		echo '<div class="util-block-help mt-2">';
			echo '<p>'.s("Vous n'avez pas encore ajouté d'article à vendre à votre marché, vous risquez de décevoir vos clients !").'</p>';
			echo '<a href="'.\selling\SaleUi::urlMarket($data->e).'/articles" class="btn btn-secondary">'.s("Ajouter des articles").'</a>';
		echo '</div>';

	}

});

new AdaptativeView('/vente/{id}/marche/vente/{subId}', function($data, MarketTemplate $t) {

	$t->selected = 'sales';

	$t->eSaleSelected = $data->eSale;

	echo (new \selling\MarketUi())->displaySale($data->eSale, $data->cItemSale, $data->e, $data->cItemMarket);

});

new AdaptativeView('doUpdateSale', function($data, AjaxTemplate $t) {

	$t->qs('.market-main')->innerHtml((new \selling\MarketUi())->displaySale($data->eSale, $data->cItemSale, $data->e, $data->cItemMarket));
	$t->qs('#market-sale-'.$data->eSale['id'].'-price')->innerHtml(\util\TextUi::money($data->eSale['priceIncludingVat'] ?? 0));

});

new AdaptativeView('/vente/{id}/marche/articles', function($data, MarketTemplate $t) {

	$t->selected = 'items';

	echo (new \selling\MarketUi())->displayItems($data->e, $data->cItemMarket);

});

new AdaptativeView('/vente/{id}/marche/ventes', function($data, MarketTemplate $t) {

	$t->selected = 'sales';

	if($data->ccSale->empty()) {

		echo '<div class="util-info">';
			echo s("Vous n'avez encore saisi aucune vente pour ce marché !");
		echo '</div>';

	} else {

		foreach($data->ccSale as $cSale) {

			if($cSale->empty()) {
				continue;
			}

			echo '<h2>';
				echo match($cSale->first()['preparationStatus']) {
					\selling\Sale::DELIVERED => s("Ventes terminées"),
					\selling\Sale::DRAFT => s("Ventes en cours"),
					\selling\Sale::CANCELED => s("Ventes annulés")
				};
			echo '</h2>';
			echo (new \selling\SaleUi())->getList($data->e['farm'], $cSale, hide: ['deliveredAt', 'actions', 'documents'], show: ['createdAt'], link: fn($eSale) => \selling\SaleUi::urlMarket($data->e).'/vente/'.$eSale['id']);

		}

	}

});
?>
