<?php
new AdaptativeView('update', function($data, FarmTemplate $t) {

	$t->title = s("Paramétrer la boutique");
	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);

	echo '<a href="'.\shop\ShopUi::adminUrl($data->eFarm, $data->e).'" class="btn btn-outline-primary btn-sm mb-1">'.Asset::icon('arrow-left').' '.s("Revenir sur la boutique").'</a>';
	echo '<h1>'.$t->title.'</h1>';
	echo '<br/>';
	echo (new \shop\ShopUi())->update($data->e, $data->cCustomize, $data->eSaleExample);

});
?>
