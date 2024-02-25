<?php
new AdaptativeView('set', function($data, MainTemplate $t) {

	$t->title = s("Réinitialiser mon mot de passe");

	echo (new user\UserUi())->updatePassword(new \user\User(), $data->hash, $data->email);

});

new AdaptativeView('setFailed', function($data, MainTemplate $t) {

	$t->title = s("Réinitialiser mon mot de passe");

	echo '<p>'.s("Ce lien pour réinitialiser votre mot de passe a expiré ou alors vous avez déjà modifié votre mot de passe.").'</p>';

	echo '<a href="/" class="btn btn-primary">'.s("Revenir sur la page d'accueil").'</a>';
	echo ' <a href="/user/log:forgottenPassword" class="btn btn-outline-primary">'.s("Renvoyer un lien").'</a>';

});
?>
