<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \website\WebpageUi())->create($data->eWebsite);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \website\WebpageUi())->update($data->e);
});

new AdaptativeView('updateContent', function($data, MainTemplate $t) {

	$t->template .= 'fluid';
	$t->title = s("Modifier une page");

	echo (new \website\WebpageUi())->updateContent($data->e);

});

new JsonView('doUpdateContent', function($data, AjaxTemplate $t) {
	$t->js()->success('website', 'Webpage::contentUpdate');
});
?>
