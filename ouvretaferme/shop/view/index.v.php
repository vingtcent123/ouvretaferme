<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \shop\ShopUi()->create($data->eFarm);
});

new AdaptativeView('website', function($data, FarmTemplate $t) {

	$t->title = s("Intégrer la boutique sur un site internet");
	$t->tab = 'shop';
	$t->subNav = new \farm\FarmUi()->getShopSubNav($data->eFarm);

	$h = '<h1>';
		$h .= '<a href="'.\shop\ShopUi::adminUrl($data->eFarm, $data->e).'" class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= $t->title;
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo  new \shop\ShopUi()->displayWebsiteInternal($data->e, $data->eDate, $data->eFarm, $data->eWebsite);
	echo  new \shop\ShopUi()->displayWebsiteExternal($data->e, $data->eDate, $data->eFarm);

});

new AdaptativeView('emails', function($data, PanelTemplate $t) {
	return new \shop\ShopUi()->displayEmails($data->eFarm, $data->emails);
});
?>
