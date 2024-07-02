<?php
new AdaptativeView('update', function($data, FarmTemplate $t) {

	$t->title = s("Paramétrer la boutique");
	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);

	echo '<h1>';
		echo '<a href="'.\shop\ShopUi::adminUrl($data->eFarm, $data->e).'" class="h-back">'.\Asset::icon('arrow-left').'</a>';
		echo $t->title;
	echo '</h1>';
	echo '<br/>';
	echo (new \shop\ShopUi())->update($data->e, $data->eFarm, $data->cCustomize, $data->eSaleExample);

});
?>
