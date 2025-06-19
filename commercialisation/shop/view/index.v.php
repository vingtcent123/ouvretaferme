<?php
new AdaptativeView('/ferme/{id}/boutique/{shop}', function($data, FarmTemplate $t) {

	$t->tab = 'shop';
	$t->subNav = new \farm\FarmUi()->getShopSubNav($data->e);

	$t->title = $data->eShop['name'];
	$t->canonical = \farm\FarmUi::urlShopList($data->e);

	$t->package('main')->updateNavShop($t->canonical);

	$uiShopManage = new \shop\ShopManageUi();

	$t->mainTitle = $uiShopManage->getHeader($data->e, $data->eShop, $data->ccShop);

	echo new \shop\ShopUi()->getDetails($data->eShop);

	if($data->eShop['shared']) {
		echo $uiShopManage->getSharedContent($data->e, $data->eShop);
	} else {

		if(
			$data->eShop['ccPoint']->notEmpty() and
			$data->eShop['cDate']->notEmpty()
		) {
			echo $uiShopManage->getDateList($data->e, $data->eShop);
		} else {
			echo $uiShopManage->getInlineContent($data->e, $data->eShop);
		}

	}

});

new AdaptativeView('join', function($data, PanelTemplate $t) {
	return new \shop\ShopUi()->join($data->eFarm);
});

new AdaptativeView('doJoin', function($data, AjaxTemplate $t) {
	$t->qs('#shop-join')->outerHtml(new \shop\ShopUi()->checkJoin($data->eFarm, $data->eShop, $data->hasJoin));
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \shop\ShopUi()->create($data->e);
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

	echo  new \shop\ShopUi()->displayWebsiteLimited($data->e, $data->eDate, $data->eFarm, $data->eWebsite);
	echo  new \shop\ShopUi()->displayWebsiteFull($data->e, $data->eDate, $data->eFarm, $data->eWebsite);

});

new AdaptativeView('updateEmbed', function($data, PanelTemplate $t) {

	return new \shop\ShopUi()->updateEmbed($data->e);

});

new AdaptativeView('emails', function($data, PanelTemplate $t) {
	return new \shop\ShopUi()->displayEmails($data->eFarm, $data->emails);
});

new AdaptativeView('invite', function($data, PanelTemplate $t) {
	return new \shop\ShopUi()->displayInvite($data->e);
});
?>
