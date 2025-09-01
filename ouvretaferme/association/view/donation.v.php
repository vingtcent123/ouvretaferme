<?php
new HtmlView('/donner', function($data, MainTemplate $t) {

	$t->title = s("Soutenir l'association Ouvretaferme avec un don");

	echo '<div class="util-block-secondary">'.s("Tout d'abord, un grand merci pour votre démarche !").'</div>';

	echo '<div class="util-block">';

		echo '<p>'.s("Ouvretaferme est un logiciel mis à disposition gratuitement pour les producteurs et les productrices en agriculture biologique et développé entièrement bénévolement. Vos dons sont précieux pour le maintenir et le faire vivre.").'</p>';

		echo '<a target="_blank" class="btn btn-outline-primary" href="https://asso.ouvretaferme.org/nous-soutenir">'.s("Lire plus d'informations sur l'association").' '.Asset::icon('box-arrow-up-right').'</a>';

	echo '</div>';

	echo '<h2>'.s("Votre don").'</h2>';
	echo '<div class="util-annotation mb-1">'.s("Pour éditer le reçu de votre don, nous vous demandons quelques informations personnelles. Le paiement s'effectuera sur {icon} Stripe à l'étape suivante.", ['icon' => Asset::icon('stripe')]).'</div>';
	echo new \association\AssociationUi()->donationForm($data->eUser);

});

new HtmlView('thankYou', function($data, MainTemplate $t) {

	$t->title = s("Merci pour votre don !");

	echo new \association\AssociationUi()->donationThankYou($data->eHistory);

});

?>
