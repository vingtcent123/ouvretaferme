<?php
new AdaptativeView('update', function($data, FarmTemplate $t) {

	$t->title = s("Paramétrer la boutique");
	$t->nav = 'shop';
	$t->subNav = 'shop';

	$h = '<h1>';
		$h .= '<a href="'.\shop\ShopUi::adminUrl($data->eFarm, $data->e).'" class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= $t->title;
	$h .= '</h1>';
	
	$t->mainTitle = $h;
	
	echo new \shop\ShopUi()->update($data->e, $data->eFarm, $data->cCustomize, $data->eSaleExample);

});
?>
