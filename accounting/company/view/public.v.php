<?php
new AdaptativeView('/comptabilite/decouvrir', function($data, FarmTemplate $t) {

	$t->title = s("Découvrir la comptabilité sur {siteName}");
	$t->nav = 'settings-accounting';

	$h = '<h1>';
		$h .= s("La comptabilité avec {image}", ['image' => Asset::image('main', 'favicon.png', ['style' => 'height: 4rem'])]);
	$h .= '</h1>';

	$t->mainTitle = $h;

	Asset::css('company', 'company.css');

	Asset::css('main', 'font-ptserif.css');
	Asset::css('main', 'home.css');

	if($data->eFarm->isMembership()) {

		echo '<div class="util-association">';
			echo '<h4>'.s("Vous êtes adhérent à l'association et donc éligible à l'utilisation du module de comptabilité.").'</h4>';
			echo '<div>';
				echo '<a class="btn btn-primary btn-xl" data-option="no" data-waiter="'.s("Activation en cours").'" data-ajax="/company/public:doInitialize" post-farm="'.$data->eFarm['id'].'">';
					echo s("Activer le module de comptabilité");
				echo '</a>';

			echo '</div>';
		echo '</div>';

	} else {

		echo '<div class="util-association">';
			echo '<h4>'.s("Le module de comptabilité est accessible pour les fermes qui ont adhéré à notre association.").'</h4>';
			echo '<p>'.s("Les fonctionnalités de ce module sont pleinement intégrées avec le reste du logiciel pour que la comptabilité devienne presque un plaisir 🏖️").'</p>';
			echo '<a href="'.\association\AssociationUi::url($this->data->eFarm).'" class="btn btn-primary btn-xl">';
				echo \association\AssociationSetting::isDiscount($this->data->eFarm) ?
					s("Adhérer à l'association pour seulement {value} €", \association\AssociationSetting::getFee($this->data->eFarm)) :
					s("Adhérer à l'association");
			echo '</a> ';
			echo '<a href="'.\association\AssociationSetting::URL.'" class="btn btn-outline-primary btn-xl">'.s("Découvrir l'association").'</a>';
		echo '</div>';
	}

	echo new \main\HomeUi()->getAccounting();

});

new AdaptativeView('beta', function($data, FarmTemplate $t) {

	$t->title = s("Découvrir la comptabilité sur {siteName}");
	$t->nav = 'settings-accounting';

	$h = '<h1>';
		$h .= s("Rejoindre la version <span>BETA</span> du logiciel comptable pour le micro-BA", ['span' => '<span class="util-badge bg-primary">']);
	$h .= '</h1>';

	$t->mainTitle = $h;

	Asset::css('company', 'company.css');

	Asset::css('main', 'home.css');

	echo '<h2>'.s("Quel est l'objectif de cette version de test ?").'</h2>';
	echo '<div class="util-block">';
		echo '<h3>'.s("Pour Ouvretaferme").'</h3>';
		echo '<ul>';
			echo '<li>'.s("Trouver et corriger les éventuels bugs restants").'</li>';
			echo '<li>'.s("Vérifier que le logiciel correspond réellement au besoin des fermes").'</li>';
			echo '<li>'.s("Améliorer l'ergonomie du logiciel").'</li>';
			echo '<li>'.s("Identifier les fonctionnalités qui pourraient manquer").'</li>';
		echo '</ul>';
		echo '<h3>'.s("Pour vous").'</h3>';
		echo '<ul>';
			echo '<li>'.s("Avoir une comptabilité fiable de votre exercice 2025 et les suivants !").'</li>';
			echo '<li>'.s("Avoir contribué à améliorer {siteName}").'</li>';
		echo '</ul>';
	echo '</div>';
	echo '<h2>'.s("Qui peut tester ?").'</h2>';
	echo '<div class="util-info">';
		echo '<p>'.s("Nous recherchons des personnes qui :").'</p>';
		echo '<ul class="mb-0">';
			echo '<li>'.s("ont le temps et la patience pour : ");
				echo '<ul>';
					echo '<li>'.s("utiliser en conditions réelles le logiciel (idéalement en continuant à tenir la comptabilité sur l'outil habituel pour comparer les données)").'</li>';
					echo '<li>'.s("remonter tous les bugs rencontrés, les problèmes d'usage ou de conception et faire un suivi de ces remontées (échanger pour clarifier le problème par exemple)").'</li>';
					echo '<li>'.s("retester la même fonctionnalité plusieurs fois selon les ajustements réalisés").'</li>';
				echo '</ul>';
			echo '<li>'.s("participer activement sur Discord").'</li>';
			echo '<li>'.s("évidemment, qui croient au projet et ont déjà manifesté leur soutien via une adhésion !").'</li>';
		echo '</ul>';
	echo '</div>';
	echo '<h2>'.s("Quels sont les profils de fermes recherchés ?").'</h2>';
	echo '<div class="util-info">';
		echo '<p>'.s("Nous recherchons des fermes :").'</p>';
		echo '<ul class="mb-0">';
			echo '<li>'.s("au micro-BA,").'</li>';
			echo '<li>'.s("à la comptabilité de trésorerie").'</li>';
		echo '</ul>';
	echo '</div>';

	echo '<h2 class="mt-2">'.s("Rejoindre la beta").'</h2>';

	if($data->eBetaApplication->notEmpty()) {

		echo '<div class="util-block-success">'.s("Nous avons bien pris en compte votre demande et reviendrons vers vous dès que possible ! Merci pour votre soutien.").'</div>';

	} else {

		echo '<div class="util-block">';
			echo new \company\BetaApplicationUi()->create($data->eFarm);
		echo '</div>';
	}

});

new AdaptativeView('/comptabilite/parametrer', function($data, FarmTemplate $t) {

	$t->title = s("Paramétrer la comptabilité sur {siteName}");
	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$h = '<div class="util-action">';

		$h .= '<h1>';
			$h .= s("Paramétrer ma comptabilité");
		$h .= '</h1>';

	$h .= '</div>';

	$t->mainTitle = $h;

	echo '<div class="util-block-info">';
		echo '<h3>'.s("Bienvenue sur le module de comptabilité de {siteName}").'</h3>';
		echo '<p>';
			echo s("Pour utiliser ce module, vous devez préalablement paramétrer un premier exercice comptable !");
		echo '</p>';
	echo '</div>';

	echo '<br/><br/>';

	// Première étape : les infos légales
	if($data->eFarm->isLegal() === FALSE) {

		echo '<h3>'.s("Informations requises sur votre ferme").'</h3>';
		echo new \farm\FarmUi()->getLegalForm($data->eFarm);

	} else { // 2è étape : l'exercice

		echo new \company\CompanyUi()->create($data->eFarm);

	}

});

new AdaptativeView('/comptabilite/demarrer', function($data, MainTemplate $t) {

	$t->title = s("Démarrer avec la comptabilité sur {siteName}");
	$t->template = 'home-start';

	Asset::css('main', 'home.css');

	$t->header = '<h1>'.encode($data->eFarm['name']).'</h1>';
	$t->header .= '<h2>'.Asset::icon('check-lg').' '.s("La comptabilité a bien été activée pour votre ferme !").'</h2>';

	echo '<div class="home-features home-features-3">';

		echo '<h3 class="home-feature-fill text-center">';
			echo s("La comptabilité sur Ouvretaferme regroupe une large palette de fonctionnalités.<br/>Que voulez-vous découvrir en premier ?");
		echo '</h3>';

		echo '<div class="home-feature bg-background">';

			echo '<h2 class="color-accounting">';
				echo '<div class="home-feature-icon">'.Asset::icon('bank').'</div>';
				echo s("Banque");
			echo '</h2>';
			echo '<div>';
				echo '<ul>';
					echo '<li>'.s("J'importe mes relevés bancaires au format OFX").'</li>';
					echo '<li>'.s("Je fais le rapprochement bancaire avec mes factures").'</li>';
					echo '<li>'.s("Je crée mes écritures comptables dans le logiciel comptable de Ouvretaferme").'</li>';
				echo '</ul>';
			echo '</div>';
			echo '<div class="home-feature-buttons">';
				echo '<a href="'.\farm\FarmUi::urlConnected($data->eFarm).'/banque/operations" class="btn btn-accounting"><p>'.Asset::icon('file-plus').'</p>'.s("Importer un relevé bancaire").'</a>';
			echo '</div>';

		echo '</div>';
		echo '<div class="home-feature bg-background">';

			echo '<h2 class="color-accounting">';
				echo '<div class="home-feature-icon">'.Asset::icon('file-spreadsheet').'</div>';
				echo s("Pré-comptabilité");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("J'attribue des numéros de compte à mes produits").'</li>';
				echo '<li>'.s("J'exporte mes factures au format FEC ou dans le logiciel comptable de Ouvretaferme").'</li>';
				echo '<li>'.s("Je visualise des données synthétiques de mes ventes non facturées pour une intégration comptable").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-buttons">';
				echo '<a href="'.\farm\FarmUi::urlConnected($data->eFarm).'/precomptabilite" class="btn btn-accounting"><p>'.Asset::icon('file-spreadsheet').'</p>'.s("Commencer la précomptabilité").'</a>';
			echo '</div>';
		echo '</div>';
		echo '<div class="home-feature bg-background">';

			echo '<h2 class="color-accounting">';
				echo '<div class="home-feature-icon">'.Asset::icon('journal-bookmark').'</div>';
				echo s("Logiciel comptable pour le micro-BA");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("Je suis en comptabilité de trésorerie").'</li>';
				echo '<li>'.s("Je veux tenir la comptabilité de ma ferme avec Ouvretaferme").'</li>';
				echo '<li>'.s("En version {value} pour le moment", '<span class="util-badge bg-primary">BETA</span>').'</li>';
			echo '</ul>';
			echo '<div class="home-feature-buttons">';
				echo '<a href="'.\farm\FarmUi::urlConnected($data->eFarm).'/journal/livre-journal" class="btn btn-accounting"><p>'.Asset::icon('journal-bookmark').'</p>'.s("Démarrer la comptabilité").'</a>';
			echo '</div>';

		echo '</div>';
		echo '<div class="home-feature bg-background">';

			echo '<h2 class="color-commercialisation">';
				echo '<div class="home-feature-icon">'.Asset::icon('receipt').'</div>';
				echo s("Facturation électronique");
			echo '</h2>';
			echo '<div class="home-feature-buttons">';
				echo s("Printemps 2026");
			echo '</div>';

		echo '</div>';
		echo '<div class="home-feature bg-background">';

			echo '<h2 class="color-primary">';
				echo '<div class="home-feature-icon">'.Asset::icon('journal-text').'</div>';
				echo s("Journal de caisse");
			echo '</h2>';
			echo '<div class="home-feature-buttons">';
				echo s("Printemps 2026");
			echo '</div>';

		echo '</div>';

	echo '</div>';

});

?>
