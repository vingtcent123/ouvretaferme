<?php
new HtmlView('/donner', function($data, MainTemplate $t) {

	$t->title = s("Soutenir l'association Ouvretaferme avec un don");

	echo new \association\AssociationUi()->donationIntroduction();

	echo new \association\AssociationUi()->donationForm($data->eUser);

});

new HtmlView('thankYou', function($data, MainTemplate $t) {

	$t->title = s("Merci pour votre don !");

	echo new \association\AssociationUi()->donationThankYou($data->eHistory);

});

?>
