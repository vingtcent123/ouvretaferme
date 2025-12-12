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
			$h .= s("Bienvenue dans le module de comptabilité d'{siteName} !");
		$h .= '</h1>';

	$h .= '</div>';

	$t->mainTitle = $h;

	Asset::css('company', 'company.css');

	echo '<div class="util-block">';
		echo '<p>'.s("{siteName} vous propose de tenir votre comptabilité facilement.").'</p>';
		echo '<p>'.s("Pour le moment, le module de comptabilité est fonctionnel pour les exploitations au <b>micro-BA</b>, en <b>comptabilité de trésorerie</b> ou <b>comptabilité de trésorie avec suivi d'encours pour les ventes</b> enregistrées sur Ouvretaferme.").'</p>';
		echo '<p>'.s("Vous avez deux options pour votre comptabilité :").'</p>';
		echo Asset::icon('arrow-up-right', ['style' => 'margin-bottom: -0.5rem; margin-left: 1rem; margin-right: 0.5rem;']).' '.s("Préparer les données de vos ventes puis exporter un FEC pour l'intégrer dans votre logiciel de comptabilité actuel.");
		echo '<br />';
		echo Asset::icon('arrow-down-right', ['style' => 'margin-top: -0.5rem; margin-left: 1rem; margin-right: 0.5rem;']).' '.s("Utiliser Ouvretaferme pour tenir toute votre comptabilité.");
	echo '</div>';

	echo '<div class="util-block">';
		echo '<p>'.s("{siteName} recherche actuellement des fermes qui souhaiteraient tester ce module en avant-première !").'</p>';
		echo '<h4>'.s("Qu'est-ce qu'une phase de test (dite bêta) ?").'</h4>';
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
			echo '<li>'.s("ou à la comptabilité de trésorerie + d'engagement pour la partie ventes (pour le suivi client)");
			echo '<li>'.s("accompagnées ou non par un cabinet, un organisme extérieurs");
			echo '<li>'.s("redevables et non redevables de la TVA");
		echo '</ul>';
	echo '</div>';

	echo '<div class="company-accounting-choose-container">';
		echo '<a class="company-accounting-choose-option" data-option="no" data-ajax="/company/public:doInitialize" post-farm="'.$data->eFarm['id'].'">';
			echo s("Je souhaite juste préparer mes données sans utiliser le module de comptabilité");
		echo '</a>';

		//href="/comptabilite/parametrer?farm='.$data->eFarm['id'].'"
		echo '<a class="company-accounting-choose-option" data-option="yes" onclick="CompanyConfiguration.toggleBetaForm();">';
			echo s("Oui, je veux utiliser la comptabilité avec {siteName} et commencer maintenant");
		echo '</a>';
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
			echo s("Pour faire la comptabilité de votre ferme avec {siteName}, vous devez préalablement renseigner quelques informations de base sur votre entité et les choix juridiques et fiscaux que vous avez fait.");
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
