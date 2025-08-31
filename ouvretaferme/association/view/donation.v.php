<?php
new HtmlView('/donner', function($data, MainTemplate $t) {

	echo new \association\AssociationUi()->donationForm($data->eUser);

});

new HtmlView('thankYou', function($data, MainTemplate $t) {

	echo new \association\AssociationUi()->donationThankYou($data->eHistory);

});

?>
