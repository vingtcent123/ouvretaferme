<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->nav = 'settings-commercialisation';

	$t->title = s("Modes de livraison de {value}", $data->eFarm['name']);

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Modes de livraison");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \shop\PointUi()->getList($data->eFarm, $data->ccPoint, $data->pointsUsed);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \shop\PointUi()->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \shop\PointUi()->update($data->e);
});
?>
