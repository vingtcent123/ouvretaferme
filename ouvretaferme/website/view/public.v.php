<?php
new AdaptativeView('nothing', function($data, MainTemplate $t) {

	$t->nav = FALSE;
	$t->header = '<h1>'.s("Site inaccessible").'</h1>';

	echo '<h3>'.s("Pourquoi un site peut-il être inaccessible ?").'</h3>';

	echo '<ol>';
		echo '<li>'.s("Il n'existe pas").'</li>';
		echo '<li>'.s("Il n'a pas été activé par son propriétaire").'</li>';
		echo '<li>'.s("Vous n'êtes pas autorisé à le consulter").'</li>';
		echo '<li>'.s("Vous avez saisi une mauvaise adresse dans votre navigateur").'</li>';
		echo '<li>'.s("L'adresse qu'on vous a donnée est erronée").'</li>';
	echo '</ol>';

});

new AdaptativeView('public', function($data, WebsiteTemplate $t) {

	$t->title = $data->eWebpage['title'] ?? $data->eWebsite['title'];
	$t->metaDescription = $data->eWebpage['description'] ?? $data->eWebsite['description'];

	if($data->eWebpage['content']) {

		echo (new \website\WidgetUi())->replace(
			$data->eWebpage,
			(new \editor\EditorUi())->value($data->eWebpage['content'], [
				'domain' => analyze_url(\website\WebsiteUi::url($data->eWebsite))['domain']
			])
		);

	}

	if($data->eWebpage['template']['fqn'] === 'news') {
		echo (new \website\NewsUi())->getAll($data->cNews);
	}

});

new AdaptativeView('404', function($data, WebsiteTemplate $t) {

	$t->title = s("Page non trouvée");
	$t->metaDescription = s("La page que vous avez demandée n'existe pas, merci de naviguer sur le site avec le menu !");

	echo '<h2>'.$t->metaDescription.'</h2>';

});
?>
