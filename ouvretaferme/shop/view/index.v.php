<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \shop\ShopUi())->create($data->eFarm);
});

new AdaptativeView('website', function($data, PanelTemplate $t) {
	return (new \shop\ShopUi())->displayWebsite($data->e, $data->eDate, $data->eFarm, $data->eWebsite);
});

new AdaptativeView('emails', function($data, PanelTemplate $t) {
	return (new \shop\ShopUi())->displayEmails($data->e, $data->emails);
});
?>
