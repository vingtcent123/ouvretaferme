<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \farm\FarmUi()->create($data->e);

});

new AdaptativeView('start', function($data, MainTemplate $t) {

	$t->title = s("Bienvenue sur {value}", $data->e['name']);
	$t->template = 'home-start';

	Asset::css('main', 'home.css');

	$t->header = '<h1>'.encode($data->e['name']).'</h1>';
	$t->header .= '<h2>'.Asset::icon('check-lg').' '.s("Votre ferme a bien été créée sur {siteName} !").'</h2>';

	echo '<div class="home-features home-features-3">';

		echo '<h3 class="home-feature-fill text-center">';
			echo s("{siteName} propose une large palette de fonctionnalités.<br/>Choisissez par quoi vous voulez démarrer !");
			echo '<div class="mt-1"><a href="'.\farm\FarmUi::urlSellingSales($data->e).'" class="btn btn-outline-primary">'.s("Passer cette étape").'</a></div>';
		echo '</h3>';

		echo '<div class="home-feature bg-background">';

			echo '<h2 class="color-commercialisation">';
				echo '<div class="home-feature-icon">'.Asset::icon('cart').'</div>';
				echo s("Vente en ligne");
			echo '</h2>';
			echo '<div>';
				echo '<ul>';
					echo '<li>'.s("Je crée une boutique en ligne").'</li>';
					echo '<li>'.s("J'enregistre ma gamme de produits").'</li>';
					echo '<li>'.s("Je configure mes points de livraison").'</li>';
					echo '<li>'.s("Je choisis mes moyens de paiement").'</li>';
				echo '</ul>';
			echo '</div>';
			echo '<div class="home-feature-buttons">';
				echo '<a href="'.\farm\FarmUi::urlSellingShop($data->e).'" class="btn btn-commercialisation"><p>'.Asset::icon('cart').'</p>'.s("Créer une boutique en ligne").'</a>';
			echo '</div>';

		echo '</div>';
		echo '<div class="home-feature bg-background">';

			echo '<h2 class="color-production">';
				echo '<div class="home-feature-icon">'.Asset::icon('leaf').'</div>';
				echo s("Planification");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("Je fais mon plan de culture ou d'assolement").'</li>';
				echo '<li>'.s("J'ai mon planning de travail hebdomadaire").'</li>';
				echo '<li>'.s("Je note éventuellement mon temps de travail").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-buttons">';
				echo '<a href="'.\farm\FarmUi::urlCultivationCartography($data->e).'" class="btn btn-production"><p>'.Asset::icon('geo-alt-fill').'</p>'.s("Démarrer la planification").'</a>';
			echo '</div>';
		echo '</div>';
		echo '<div class="home-feature bg-background">';

			echo '<h2 class="color-commercialisation">';
				echo '<div class="home-feature-icon">'.Asset::icon('receipt').'</div>';
				echo s("Facturation");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("J'enregistre les commandes de mes clients particuliers ou professionnels").'</li>';
				echo '<li>'.s("J'édite des devis, bons de livraison et factures").'</li>';
				echo '<li>'.s("J'envoie automatiquement ces documents par e-mail").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-buttons">';
				echo '<a href="'.\farm\FarmUi::urlSellingCustomers($data->e).'" class="btn btn-commercialisation"><p>'.Asset::icon('person-fill').'</p>'.s("Ajouter un client").'</a>';
			echo '</div>';

		echo '</div>';
		echo '<div class="home-feature bg-background">';

			echo '<h2 class="color-commercialisation">';
				echo '<div class="home-feature-icon">'.Asset::icon('cart4').'</div>';
				echo s("Logiciel de caisse");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("J'enregistre mes ventes lors de mes marchés").'</li>';
				echo '<li>'.s("Je peux éditer des tickets de caisse").'</li>';
				echo '<li>'.s("Je sais ce que j'ai vendu en fin de marché").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-buttons">';
				echo '<p class="home-feature-description">'.s("La première étape est de créer un point de vente pour utiliser le logiciel de caisse !").'</p>';
				echo '<a href="'.\farm\FarmUi::urlSellingCustomers($data->e).'" class="btn btn-commercialisation"><p>'.Asset::icon('person-fill').'</p>'.s("Ajouter un point de vente").'</a>';
			echo '</div>';

		echo '</div>';
		echo '<div class="home-feature bg-background">';

			echo '<h2 class="color-primary">';
				echo '<div class="home-feature-icon">'.Asset::icon('megaphone').'</div>';
				echo s("Site internet");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("Je crée le site internet de ma ferme").'</li>';
				echo '<li>'.s("J'ajoute autant de pages et de photos que je veux").'</li>';
				echo '<li>'.s("Je peux connecter un nom de domaine").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-buttons">';
				echo '<a href="'.\farm\FarmUi::urlCommunicationsWebsite($data->e).'" class="btn btn-primary"><p>'.Asset::icon('globe').'</p>'.s("Créer un site internet").'</a>';
			echo '</div>';

		echo '</div>';

	echo '</div>';

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \farm\FarmUi()->update($data->e);

});

new AdaptativeView('updateForElectronicInvoicing', function($data, PanelTemplate $t) {

	return new \farm\FarmUi()->updateForElectronicInvoicing($data->e);

});

new AdaptativeView('updatePlace', function($data, PanelTemplate $t) {

	return new \farm\FarmUi()->updatePlace($data->e);

});

new AdaptativeView('updateLegal', function($data, PanelTemplate $t) {

	return new \farm\FarmUi()->updateLegal($data->e);

});

new AdaptativeView('updateProduction', function($data, FarmTemplate $t) {

	$t->title = s("Les réglages de base de {value}", $data->e['name']);
	$t->nav = 'settings-production';

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsProduction($data->e).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Réglages de base");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \farm\FarmUi()->updateProduction($data->e);

});

new AdaptativeView('updateEmail', function($data, FarmTemplate $t) {

	$t->title = s("Les e-mails envoyés par {value}", $data->e['name']);
	$t->nav = 'settings-commercialisation';

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->e).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("E-mails");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \farm\FarmUi()->updateEmail($data->e);

});

new AdaptativeView('calendarMonth', function($data, AjaxTemplate $t) {

	$t->qs('#farm-update-calendar-month')->innerHtml(new \series\CultivationUi()->getListSeason($data->e, date('Y')));

});

new AdaptativeView('export', function($data, PanelTemplate $t) {
	
	return new \farm\FarmUi()->export($data->e, $data->year);

});

new AdaptativeView('surveyAnalyze', function($data, MainTemplate $t) {

	$t->title = s("Enquête auprès des adhérents");


	foreach($data->cSurvey as $eSurvey) {

		echo '<h3>'.\farm\FarmUi::getVignette($eSurvey['farm'], '2rem').' '.$eSurvey['farm']['id'].' / '.encode($eSurvey['farm']['name']).'</h3>';

		echo '<div style="max-width: 50rem">';

		foreach(['achatRevente', 'depotVente', 'autofacturation', 'cagnotte'] as $property) {

			echo '<h5>'.\farm\SurveyUi::p($property)->label.'</h5>';
			echo new \editor\EditorUi()->value($eSurvey[$property]);

		}

		echo '</div>';

	}


});

new AdaptativeView('surveyMain', function($data, MainTemplate $t) {

	$t->title = s("Enquête auprès des adhérents");

	if($data->cFarm->empty()) {
		echo '<p>'.s("Vous n'êtes exploitant d'aucune ferme sur {siteName}.").'</p>';
	} else {

		echo '<table>';
			echo '<tbody>';
				foreach($data->cFarm as $eFarm) {
					echo '<tr>';
						echo '<td class="td-min-content">'.\farm\FarmUi::getVignette($eFarm, '3rem').'</td>';
						echo '<td>'.encode($eFarm['name']).'</td>';
						echo '<td>';
							if($eFarm->isMembership()) {
								echo '<a href="/farm/farm:survey?farm='.$eFarm['id'].'" class="btn btn-outline-primary">'.s("Répondre à l'enquête").'</a>';
							} else {
								echo Asset::icon('x-lg').' '.s("Adhérez à l'association pour répondre à cette enquête");
							}
						echo '</td>';
					echo '</tr>';
				}
			echo '</tbody>';
		echo '</table>';

	}

});

new AdaptativeView('surveyFarm', function($data, FarmTemplate $t) {

	$t->title = s("Enquête auprès des fermes adhérentes");

	$t->nav = 'selling';
	$t->subNav = '';

	$t->mainTitle = '<h1>'.$t->title.'</h1>';

	if($data->hasSurvey) {
		echo '<div class="util-block">';
			echo '<h3>'.s("Merci pour votre participation !").'</h3>';
			echo '<p>'.s("Nous analyserons les réponses dans les semaines à venir avant de travailler sur de nouvelles fonctionnalités.").'</p>';
		echo '</div>';
	} else {
		echo '<div class="util-block-gradient mb-2">';
			echo '<p>'.s("En vue d'améliorer le logiciel dans les semaines à venir, nous menons une enquête jusqu'au 15 mars auprès de toutes les fermes qui ont adhéré à l'association Ouvretaferme pour comprendre vos besoins sur les sujets suivants :").'</p>';
			echo '<ul>';
				echo '<li>'.s("Achat-revente").'</li>';
				echo '<li>'.s("Dépôt-vente").'</li>';
				echo '<li>'.s("Autofacturation").'</li>';
				echo '<li>'.s("Encours clients").'</li>';
			echo '</ul>';
		echo '<p>'.s("Si vous souhaitez répondre à cette enquête, vous pouvez remplir le formulaire ci-dessous. Vous pouvez laissez vide les questions auxquelles vous ne souhaitez pas répondre.").'</p>';
		echo '</div>';
		echo '<div class="util-block">';
			echo '<h3>'.s("Notice d'utilisation du formulaire").'</h3>';
			echo '<ul>';
				echo '<li>'.s("N'hésitez pas à donner un maximum d'informations sur vos pratiques").'</li>';
				echo '<li>'.s("Vous pouvez joindre des images à vos commentaires pour nous permettre de bien comprendre vos besoins").'</li>';
				echo '<li>'.s("Les formulaires dont tout ou partie des réponses sortent du cadre défini ci-dessus ne seront pas pris en compte").'</li>';
				echo '<li>'.s("Ne faites pas de propositions d'interface sur le logiciel et restez centrés sur votre besoin, c'est ce dont nous avons besoin pour bien comprendre les usages.").'</li>';
			echo '</ul>';
		echo '</div>';
		echo new \farm\SurveyUi()->create($data->e);
	}


});
?>
