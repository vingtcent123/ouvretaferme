<?php
new HtmlView('index', function($data, HtmlTemplate $t) {

	echo '<html>';

		echo '<title>'.s("Maintenance").'</title>';

		echo '<body>';

			echo '<h1>'.s("{siteName} est en maintenance !").'</h1>';
			echo '<h2>'.s("Une maintenance est en cours pour améliorer le site, nous vous remercions de votre patience !").'</h2>';

		echo '</body>';

	echo '</html>';

});

new AdaptativeView('demo', function($data, MainTemplate $t) {

	$t->title = s("Maintenance");

	echo '<h2>'.s("La ferme de démonstration est en maintenance toutes les nuits pendant une heure afin de réinitialiser les données. Nous vous remercions de réessayer d'ici quelques minutes !").'</h2>';

});
?>
