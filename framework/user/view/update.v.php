<?php
new AdaptativeView('email', function($data, MainTemplate $t) {

	$t->title = s("Changer mon adresse e-mail");

	echo (new user\UserUi())->updateEmail($data->eUserOnline);

});

new JsonView('email.api', function($data, JsonTemplate $t) {

	$t->push('email', $data->eUserOnline['email']);
	$t->push('verified', $data->eUserOnline['verified']);

});

new AdaptativeView('emailVerified', function($data, MainTemplate $t) {

	\Asset::css('user', 'user.css');

	echo '<div class="user-light">';
		echo '<h1>'.s("Votre adresse e-mail est validée !").'</h1>';
		echo '<h4>'.encode($data->eUserOnline['email']).'</h4>';
	echo '</div>';

});

new AdaptativeView('password', function($data, MainTemplate $t) {

	if($data->eUserOnline['canUpdate']['hasPassword']) {
		$t->title = s("Changer mon mot de passe");
	} else {
		$t->title = s("Créer mon mot de passe");
	}

	echo (new user\UserUi())->updatePassword($data->eUserOnline);

});
?>
