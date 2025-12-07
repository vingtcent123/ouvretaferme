<?php
new AdaptativeView('/comptabilite/inactive', function($data, FarmTemplate $t) {

	$t->title = s("Comptabilité");
	$t->nav = 'settings-accounting';

	$h = '<div class="util-action">';

		$h .= '<h1>';
			$h .= s("Cheh !");
		$h .= '</h1>';

	$h .= '</div>';

	$t->mainTitle = $h;

	echo '<div class="util-block-help">';
		echo '<h4>'.s("Y'a pas d'chemin !").'</h4>';
		echo '<p>';
			echo s("Faut passer à la caisse.");
		echo '</p>';
	echo '</div>';

});
new AdaptativeView('/comptabilite/decouvrir', function($data, FarmTemplate $t) {

	$t->title = s("Découvrir la comptabilité sur {siteName}");
	$t->nav = 'settings-accounting';

	$h = '<div class="util-action">';

		$h .= '<h1>';
			$h .= s("Bienvenue sur le module de comptabilité !");
		$h .= '</h1>';

	$h .= '</div>';

	$t->mainTitle = $h;

	Asset::css('company', 'company.css');

	echo '<div class="company-accounting-choose-container">';
		echo '<a class="company-accounting-choose-option" data-ajax="/company/public:doInitialize" post-farm="'.$data->eFarm['id'].'">';
			echo s("Non, je ne souhaite pas utiliser la comptabilité avec {siteName}");
		echo '</a>';

		echo '<a class="company-accounting-choose-option" href="/comptabilite/parametrer?farm='.$data->eFarm['id'].'">';
			echo s("Oui, je veux utiliser la comptabilité avec {siteName} et commencer maintenant");
		echo '</a>';

	echo '</div>';

});

new AdaptativeView('/comptabilite/parametrer', function($data, FarmTemplate $t) {

	$t->title = s("Paramétrer la comptabilité sur {siteName}");
	$t->nav = 'settings-accounting';

	$h = '<div class="util-action">';

		$h .= '<h1>';
			$h .= s("Paramétrer ma comptabilité");
		$h .= '</h1>';

	$h .= '</div>';

	$t->mainTitle = $h;

	echo '<div class="util-block-help">';
		echo '<h4>'.s("Bienvenue sur le module de comptabilité de {siteName}").'</h4>';
		echo '<p>';
			echo s("Pour faire la comptabilité de votre ferme avec {siteName}, vous devez préalablement renseigner quelques informations de base sur votre entité et les choix juridiques et fiscaux que vous avez fait.");
		echo '</p>';
	echo '</div>';

	echo '<br/><br/>';

	// Première étape : les infos légales
	if($data->eFarm->isLegal() === FALSE) {

		echo '<h3>'.s("Informations requises sur votre ferme").'</h3>';
		echo new \farm\FarmUi()->updateLegal($data->eFarm);

	} else { // 2è étape : l'exercice

		echo new \company\CompanyUi()->create($data->eFarm);

	}

});

?>
