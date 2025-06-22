<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->title = s("Toutes les astuces");

	$t->nav = 'communications';
	$t->subNav = NULL;

	$t->footer = '';

});

new JsonView('close', function($data, AjaxTemplate $t) {
	$t->qs('#tip-wrapper')->remove(['mode' => 'fadeOut']);
});

?>