<?php
new AdaptativeView('check', function($data, MainTemplate $t) {

	echo '<br/><br/><br/>';
	echo '<h1 class="text-center">'.\Asset::icon('check').' '.s("Votre adresse e-mail est validée !").'</h1>';

});

new JsonView('doSend', function($data, AjaxTemplate $t) {

	$t->js()->success('mail', $data->message);

});
?>
