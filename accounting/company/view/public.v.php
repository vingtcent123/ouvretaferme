<?php
new AdaptativeView('/comptabilite/decouvrir', function($data, FarmTemplate $t) {

	$t->title = s("Découvrir la comptabilité sur {siteName}");
	$t->nav = 'settings-accounting';

	$h = '<h1>';
		$h .= s("La comptabilité avec {image}", ['image' => Asset::image('main', 'favicon.png', ['style' => 'height: 4rem'])]);
	$h .= '</h1>';
	$h .= '<h3 style="text-transform: uppercase">';
		$h .= s("Pour les adhérents de l'association Ouvretaferme");
	$h .= '</h3>';

	$t->mainTitle = $h;

	if(LIME_ENV === 'prod') {
		die('Bientôt disponible');
	}

	Asset::css('company', 'company.css');

	Asset::css('main', 'font-ptserif.css');
	Asset::css('main', 'home.css');

	$join = '<div class="mb-2">';
		$join .= '<a href="'.\association\AssociationUi::url($this->data->eFarm).'" class="btn btn-secondary btn-xl">'.s("Adhérer à l'association pour seulement 100 €").'</a>';
	$join .= '</div>';

	echo $join;

	if(false) {

		echo '<div class="util-block">';
			echo '<h4>'.s("Démarer avec la comptabilité sur Ouvretaferme").'</h4>';
			echo '<p>Youpi vous êtes adhérent</p>';
			echo '<div>';
				echo '<a class="company-accounting-choose-option" data-option="no" data-ajax="/company/public:doInitialize" post-farm="'.$data->eFarm['id'].'">';
					echo s("Je souhaite juste préparer mes données sans utiliser le module de comptabilité");
				echo '</a>';

			echo '</div>';
		echo '</div>';


	}

	echo '<div class="home-points">';
		echo '<div class="home-point" style="grid-column: span 2">';
			echo Asset::icon('piggy-bank');
			echo '<h2>'.s("Banque").'</h2>';
			echo '<h4>'.s("Importez vos relevés bancaires au format OFX et faites un rapprochement automatique avec vos factures pour vérifier en trois clics qui a payé.").'</h4>';
			echo '<h5 class="mt-1 util-badge bg-accounting">'.s("Déjà disponible !").'</h5>';
		echo '</div>';
		echo '<div class="home-point" style="grid-column: span 2">';
			echo Asset::icon('file-spreadsheet');
			echo '<h2>'.s("Précomptabilité").'</h2>';
			echo '<h4>'.s("Exportez les données de vos ventes et exportez vos factures au format FEC pour les importer sur votre logiciel de comptabilité.").'</h4>';
			echo '<h5 class="mt-1">'.s("Disponible le 1<sup>er</sup> janvier 2026").'</h5>';
		echo '</div>';
		echo '<div class="home-point" style="grid-column: span 2">';
			echo Asset::icon('receipt');
			echo '<h2>'.s("Facturation électronique").'</h2>';
			echo '<h4>'.s("Ouvretaferme sera prêt pour le lancement de la réforme de la facturation électronique le 1<up>er</up> septembre 2026 avec le <i>e-invoicing</i> et le <i>e-reporting</i>. L'accès à la plateforme agréée sera incluse dans le montant de l'adhésion à Ouvretaferme.").'</h4>';
			echo '<h5 class="mt-1">'.s("Disponible au printemps 2026").'</h5>';
		echo '</div>';
		echo '<div class="home-point" style="grid-column: span 2">';
			echo Asset::icon('database');
			echo '<h2>'.s("Cahier de caisse").'</h2>';
			echo '<h4>'.s("Ouvretaferme vous permettra de tenir votre cahier de caisse en ligne pour gérer les espèces liées votre activité et faciliter vos futures obligations de <i>e-reporting</i>.").'</h4>';
			echo '<h5 class="mt-1">'.s("Disponible au printemps 2026").'</h5>';
		echo '</div>';
		echo '<div class="home-point home-point-fill">';
			echo Asset::icon('journal-bookmark');
			echo '<h2>'.s("Logiciel de comptabilité pour le micro-BA").'</h2>';
			echo '<h4>'.s("Vous savez tenir la comptabilité de votre ferme et connaissez vos écritures comptable et classes de compte ?<br/>Utilisez Ouvretaferme comme logiciel de comptabilité, c'est toujours inclus dans le montant de l'adhésion à l'association.").'</h4>';
			echo '<h5 class="mt-1 util-badge bg-accounting">'.s("Déjà disponible en version beta").'</h5>';
		echo '</div>';
	echo '</div>';

	echo $join;

	echo '<div class="util-block">';
		echo '<h2>'.s("Rejoindre la version beta du logiciel de comptabilité pour le micro-BA").'</h2>';

		if(true) {
			echo "reviens quand tu as adhéré :p";
		}

		echo '<p>'.s("Vous pouvez dès cette année.").'</p>';
		echo '<p>'.s("Un logiciel passe en phase de bêta-test à partir du moment où ses fonctionnalités peuvent être testées par ses futur·e·s utilisateur·ices. Cette phase de test présente plusieurs avantages pour chaque partie :").'</p>';
		echo '<ul>';
			echo '<li>'.s("Trouver et corriger les éventuels bugs restants").'</li>';
			echo '<li>'.s("Vérifier que le logiciel correspond réellement au besoin").'</li>';
			echo '<li>'.s("Ajuster certaines fonctionnalités").'</li>';
		echo '</ul>';
		echo '<h4>'.s("Qui peut tester ?").'</h4>';
		echo '<p>'.s("Cette étape est très importante pour que le logiciel finalisé soit bien utilisable (ergonomique, fonctionnel etc.).").'</p>';
		echo '<p>'.s("Nous recherchons des personnes qui :").'</p>';
		echo '<ul>';
			echo '<li>'.s("ont du temps pour : ");
				echo '<ul>';
					echo '<li>'.s("utiliser en conditions réelles le logiciel (tout en tenant la comptabilité \"officielle\" sur l'outil habituel)").'</li>';
					echo '<li>'.s("remonter tous les bugs rencontrés, les problèmes d'usage ou de conception et faire un suivi de ces remontées (échanger pour clarifier le problème par exemple)").'</li>';
					echo '<li>'.s("retester la même fonctionnalité plusieurs fois selon les ajustements réalisés").'</li>';
				echo '</ul>';
			echo '<li>'.s("souhaitent améliorer {siteName} et participer activement sur Discord").'</li>';
			echo '<li>'.s("évidemment, qui croient au projet et ont déjà manifesté leur soutien via une adhésion !").'</li>';
		echo '</ul>';
		echo '<h4>'.s("Quels sont les profils de fermes recherchés ?").'</h4>';
		echo '<p>'.s("Pour que le test soit optimal, nous recherchons des fermes avec ces caractéristiques :").'</p>';
		echo '<ul>';
			echo '<li>'.s("au micro-BA");
			echo '<li>'.s("à la comptabilité de trésorerie");
			echo '<li>'.s("accompagnées ou non par un cabinet, un organisme extérieur");
			echo '<li>'.s("redevables et non redevables de la TVA");
		echo '</ul>';
		echo '<h3 class="mt-2" style="text-transform: uppercase">'.s("Rejoindre la beta").'</h3>';
		echo new \company\BetaApplicationUi()->create($data->eFarm);
	echo '</div>';

	if($data->eBetaApplication->notEmpty()) {

		echo '<div class="util-box-success">'.s("Nous avons bien pris en compte votre demande et reviendrons vers vous dès que possible ! Merci pour votre soutien").'</div>';

	} else {

		echo '<div id="beta-form-container" class="hide">';

			echo new \company\BetaApplicationUi()->create($data->eFarm);

		echo '</div>';
	}

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
			echo s("Pour tenir la comptabilité de votre ferme avec {siteName}, vous devez préalablement renseigner quelques informations de base sur votre entité et les choix juridiques et fiscaux que vous avez faits.");
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

?>
