<?php
new AdaptativeView('403', function($data, MainTemplate $t) {

	$h = '<div class="text-center">';
		$h .= '<br/><br/>';
		$h .= '<h2>'.s("Vous n'êtes pas autorisé à afficher cette page !").'</h2>';
		$h .= '<h4>'.s("Essayez de revenir à la page précédente pour continuer votre navigation.").'</h4>';
		$h .= '<br/><br/>';
	$h .= '</div>';

	$t->header = $h;

});

new AdaptativeView('404', function($data, MainTemplate $t) {

	$h = '<div class="text-center">';
		$h .= '<br/><br/>';
		$h .= '<h2>'.s("La page que vous avez demandée n'existe pas !").'</h2>';
		$h .= '<h4>'.s("Essayez de revenir à la page précédente pour prendre un autre chemin.").'</h4>';
		$h .= '<br/><br/>';
	$h .= '</div>';

	$t->header = $h;

	echo $data->error ?? '';

});
?>
