<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	$t->title = s("Fermer mon compte");

	echo (new user\DropUi())->close($data->eUserOnline, $data->can);

});
?>
