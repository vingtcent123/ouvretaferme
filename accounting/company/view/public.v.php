<?php
new AdaptativeView('inactive', function($data, FarmTemplate $t) {

	$t->title = s("Comptabilité");
	$t->nav = 'settings-accounting';

	$h = '<div class="util-action">';

		$h .= '<h1>';
			$h .= s("Accès au module de comptabilité");
		$h .= '</h1>';

	$h .= '</div>';

	$t->mainTitle = $h;

	echo '<div class="util-block-help">';
		echo '<h4>'.s("Module inactif !").'</h4>';
		echo '<p>';
			echo s("Le module de comptabilité n'est pas actif sur votre ferme.");
		echo '</p>';
	echo '</div>';

});
new AdaptativeView('create', function($data, FarmTemplate $t) {

	$t->title = s("Comptabilité pour {value}", $data->eFarm['name']);
	$t->nav = 'settings-accounting';


	$h = '<div class="util-action">';

		$h .= '<h1>';
			$h .= s("Comptabilité");
		$h .= '</h1>';

	$h .= '</div>';

	$t->mainTitle = $h;

	echo '<div class="util-block-help">';
		echo '<h4>'.s("Bienvenue sur le module de comptabilité de {siteName}").'</h4>';
		echo '<p>';
			echo s("Pour faire la comptabilité de votre ferme avec {siteName}, vous devez préalablement renseigner quelques informations de base sur votre structure et les choix juridiques et fiscaux que vous avez fait.");
		echo '</p>';
	echo '</div>';

	echo '<br/><br/>';

	if($data->eFarm->isLegalComplete() === FALSE) {
		echo '<h3>'.s("Informations requises sur votre ferme").'</h3>';
		echo new \farm\FarmUi()->updateLegal($data->eFarm);
	} else {
		echo new \company\CompanyUi()->create($data->eFarm);
	}

});

?>
