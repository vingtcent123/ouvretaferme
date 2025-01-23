<?php
new AdaptativeView('create', function($data, FarmTemplate $t) {

	$t->tab = 'settings';
	$t->title = s("Ajouter une actualité");
	$t->mainTitle = (new \website\NewsUi())->createTitle($data->e);

	echo (new \website\NewsUi())->create($data->e);
});

new AdaptativeView('update', function($data, FarmTemplate $t) {

	$t->tab = 'settings';
	$t->title = s("Modifier une actualité");
	$t->mainTitle = (new \website\NewsUi())->updateTitle($data->e);

	echo (new \website\NewsUi())->update($data->e);

});
?>
