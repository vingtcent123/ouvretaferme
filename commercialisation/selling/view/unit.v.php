<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Les unités de vente de {value}", $data->eFarm['name']);
	$t->nav = 'settings-commercialisation';

	$t->mainTitle = new \selling\UnitUi()->getManageTitle($data->eFarm);
	echo new \selling\UnitUi()->getManage($data->cUnit);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \selling\UnitUi()->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \selling\UnitUi()->update($data->e);
});
?>
