<?php
new AdaptativeView('create', function($data, FarmTemplate $t) {

	$t->tab = 'settings';
	$t->mainTitle = (new \website\NewsUi())->createTitle($data->e);

	echo (new \website\NewsUi())->create($data->e);
});

new AdaptativeView('update', function($data, FarmTemplate $t) {

	$t->tab = 'settings';
	$t->mainTitle = (new \website\NewsUi())->updateTitle($data->e);

	echo (new \website\NewsUi())->update($data->e);

});
?>
