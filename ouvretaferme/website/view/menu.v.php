<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \website\MenuUi())->create($data->eWebsite, $data->cWebpage, $data->for);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \website\WebpageUi())->update($data->e);
});

new AdaptativeView('updateContent', function($data, PanelTemplate $t) {
	return (new \website\WebpageUi())->updateContent($data->e);
});

new JsonView('doUpdateContent', function($data, AjaxTemplate $t) {
	$t->js()->success('website', 'Webpage::contentUpdate');
});
?>
