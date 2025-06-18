<?php
new AdaptativeView('anonymous', function($data, MainTemplate $t) {

	$t->title = s("{name} - Conduire sa ferme à sa manière", ['name' => Lime::getName()]);
	$t->metaDescription = s("{name} - Pour les exploitations agricoles qui font le choix de l'autonomie", ['name' => Lime::getName()]);
	$t->template = 'home-main';

	Asset::css('main', 'font-itim.css');
	Asset::css('main', 'home.css');

	$t->header .= '<h1>'.s("Planifier, produire, vendre, comptabiliser. Simplement.").'</h1>';
	$t->header .= '<h4 class="home-domain">'.s("Le logiciel pour conduire votre ferme à votre manière.").'</h4>';

	$airtableForm = 'shrQ1Js8YZY1vcGxz';

	if((LIME_ENV === 'prod' or get_exists('form')) and GET('noairtable', 'bool', FALSE) !== TRUE) {

		echo '<script src="https://static.airtable.com/js/embed/embed_snippet_v1.js"></script>';
		echo '<iframe class="airtable-embed airtable-dynamic-height" src="https://airtable.com/embed/appZX7JttC6YAjC4v/'.$airtableForm.'" onmousewheel="" width="100%" height="1329" style="background: transparent; border: 1px solid #ccc;"></iframe>';

	} else {

		$sentences = [
			[
				'block' => 'planification',
				'word' => s("Planifiez"),
				'points' => [
					s("Préparez vos saisons"),
					s("Gérez vos plans de culture et votre assolement"),
					s("Améliorez vos pratiques années après années"),
				],
			],
			[
				'block' => 'production',
				'word' => s("Produisez"),
				'points' => [
					s("Organisez et suivez finement vos interventions"),
					s("Maîtrisez votre temps de travail"),
				],
			],
			[
				'block' => 'sale',
				'word' => s("Vendez"),
				'points' => [
					s("Gérez vos ventes aux professionnels et particuliers"),
					s("Créez vos boutiques en ligne"),
					s("Utilisez le logiciel de caisse intégré"),
				],
			],
			[
				'block' => 'accounting',
				'word' => s("Comptabilisez"),
				'points' => [
					s("Saisissez facilement votre comptabilité depuis <br />vos relevés bancaires et vos données de vente"),
					s("Pilotez la santé financière de votre exploitation"),
				],
			],
		];

		echo '<div class="home-description-container">';

			foreach($sentences as $sentence) {

				//echo '<div class="home-description-sentence">';

					echo '<div class="home-description-word">';
						echo $sentence['word'];
					echo '</div>';


					echo '<div class="home-description-lines">';

						echo '<ul>';
							foreach($sentence['points'] as $point) {
								echo '<li>'.$point.'</li>';
							}
						echo '</ul>';

					echo '</div>';

					echo '<a class="home-description-more" onclick="Home.open(\''.$sentence['block'].'\')">';
						echo s("Voir plus");
					echo '</a>';

					echo '<div class="home-description-more-detail" data-block="'.$sentence['block'].'"> ';
						echo new \main\HomeUi()->getDescriptionMore($sentence['block']);
					echo '</div>';

				//echo '</div>';
			}

		echo '</div>';

	}

});

new AdaptativeView('logged', function($data, MainTemplate $t) {

	$t->title = s("Bienvenue sur {siteName}");
	$t->canonical = '/';

	$t->header = '<h1>'.s("Bienvenue, {userName}&nbsp;!", ['userName' => encode($data->eUserOnline['firstName'] ?? $data->eUserOnline['lastName'])]).'</h1>';

	if(Privilege::can('company\access')) {

		echo new \main\HomeUi()->getCompanies($data->cCompanyUser);

	}

});

new AdaptativeView('signUp', function($data, MainTemplate $t) {

	$t->title = s("Inscription sur {siteName}");
	$t->metaDescription = s("Inscrivez-vous sur {siteName} pour profiter de fonctionnalités de la plateforme !");
	$t->template = 'home-legal';

	Asset::css('main', 'font-itim.css');

	Asset::css('main', 'home.css');


	$t->header = '<div class="home-user-already">';
		$t->header .= s("Vous avez déjà un compte sur {siteName} ?").' &nbsp;&nbsp;';
		$t->header .= '<a href="/user/log:form" class="btn btn-primary">'.s("Connectez-vous !").'</a>';
	$t->header .= '</div>';

	$t->header .= '<h1>'.s("Je crée mon compte sur {siteName} !").'</h1>';

		echo '<h2>'.s("Mes informations").'</h2>';

		echo '<div class="util-info">'.s("Renseignez quelques informations qui vous permettront ensuite de vous connecter sur {siteName}. Vous pourrez créer votre exploitation ou rejoindre une exploitation existante juste après cette étape !").'</div>';

		echo (new \user\UserUi())->signUp($data->eUserOnline, $data->cRole['employee'], REQUEST('redirect'));


});

new AdaptativeView('/presentation/invitation', function($data, MainTemplate $t) {

	$t->title = s("Cette invitation a expiré, veuillez vous rapprocher de votre interlocuteur habituelle pour en obtenir une nouvelle !");
	$t->template = 'home-legal';

	Asset::css('main', 'font-itim.css');

	Asset::css('main', 'home.css');

});

new AdaptativeView('/presentation/entreprise', function($data, MainTemplate $t) {

	$t->title = s("{siteName} - Pour les petites exploitations agricoles");
	$t->metaDescription = s("Présentation des fonctionnalités de {siteName} pour les petites exploitations agricoles. Découvrez tous les outils de gestion comptable !");
	$t->template = 'home-employee';

	Asset::css('main', 'font-itim.css');

	Asset::css('main', 'home.css');

	$t->header = '<h4 class="home-domain">'.Lime::getDomain().'</h4>';
	$t->header .= '<h1>'.s("Du plan de culture à la vente").'</h1>';
	$t->header .= '<h4 class="home-domain">'.s("Découvrez les principales fonctionnalités du logiciel !").'</h4>';


	echo '<div class="home-presentation">';

		echo '<div>';
			echo '<h2>'.Asset::icon('arrow-right').''.s("Un logiciel pour produire").'</h2>';
			echo '<ul>';
				echo '<li>'.s("<b>Vous planifiez votre saison en concevant vos plan de culture et plan d'assolement en ligne.</b> <small>Variétés, longueurs de planche ou surfaces, densités, objectifs de récolte, associations de culture... Enregistrez et retrouvez facilement toutes les informations sur chacune de vos séries. Un prévisionnel financier permet d'estimer vos ventes en fonction de votre plan de culture et de vos prévisions !</small>").'</li>';
				echo '<li>'.s("<b>Vous maîtrisez votre temps de travail.</b> <small>Que ce soit à la ferme avec votre téléphone ou le soir sur l'ordinateur, un planning hebdomadaire ou quotidien vous permet de faire le suivi des interventions planifiées et réalisées sur la semaine. Renseignez facilement votre temps de travail pour comprendre là où passe votre temps.</small>").'</li>';
				echo '<li>'.s("<b>Vous suivez précisément vos rotations sur votre parcellaire.</b> <small>Choisissez vos critères pour les rotations et vérifiez en un coup d'oeil les planches qui correspondent à ces critères. Pratique pour éviter de mettre vos cultures aux mêmes emplacements trop souvent !</small>").'</li>';
				echo '<li>'.s("<b>Vous collaborez avec votre équipe.</b> <small>Invitez votre équipe sur l'espace de votre ferme et gérez les droits de chaque personne.</small>").'</li>';
				echo '<li>'.s("<b>C'est adapté à toutes les productions.</b> <small>{siteName} vous accompagne en maraichage, floriculture, arboriculture ou même en production de semences.</small>").'</li>';
				echo '<li>'.s("<b>Et aussi...</b> <small>Consultez les quantités de semences et plants à produire ou commander. Créez des itinéraires techniques réutilisables saison après saison. Ajoutez des photos pour vous souvenir de vos cultures. Enregistrez le matériel disponible à la ferme pour l'utiliser dans vos interventions...</small>").'</li>';
			echo '</ul>';
		echo '</div>';

		echo '<div>';
			echo '<h2>'.Asset::icon('arrow-right').''.s("Un logiciel pour vendre").'</h2>';
			echo '<ul>';
				echo '<li>'.s("<b>Vous gérez vos ventes pour les professionnels et les particuliers.</b> <small>Créez des ventes à partir de vos produits, gérez votre clientèle, choisissez vos prix. Imprimez des étiquettes de colisage si vous livrez aux professionnels. Exporter les ventes du jour au format PDF pour préparer vos livraisons.</small>").'</li>';
				echo '<li>'.s("<b>Vous avez un logiciel de caisse intégré.</b> <small>Utilisez le logiciel de caisse avec une tablette ou un téléphone pour préparer vos marchés et saisir vos ventes directement pendant le marché. Pour chaque vente, visualisez ce que le client a acheté et le montant qu'il doit vous régler. Simple et efficace.</small>").'</li>';
				echo '<li>'.s("<b>Vous pouvez créer des boutiques en ligne.</b> <small>Permettez à vos clients de passer commande en ligne et de récupérer leur colis à la date et l'endroit convenus, ou bien livrez-les à domicile selon vos préférences. Activez si vous le souhaitez le paiement par carte bancaire sans commission sur les ventes.</small>").'</li>';
				echo '<li>'.s("<b>Vous pilotez vos stocks.</b> <small>Choisissez les produits pour lesquels vous souhaitez avoir un suivi des stocks. Les récoltes et les ventes que vous saisissez impactent automatiquement le stock et vous savez toujours ce qui vous reste à vendre.</small>").'</li>';
				echo '<li>'.s("<b>Vous éditez vos documents de vente au format PDF.</b> <small>Créez facilement les devis, bons de livraisons et factures de vos ventes. Créez toutes les factures du mois en une seule fois. Envoyez-les en un clic par e-mail à vos clients.</small>").'</li>';
			echo '</ul>';
		echo '</div>';

	echo '</div>';

	echo '<div class="home-presentation">';

		echo '<div>';
			echo '<h2>'.Asset::icon('arrow-right').''.s("Un logiciel pour communiquer").'</h2>';
			echo '<ul>';
				echo '<li>'.s("<b>Vous pouvez créer le site internet de votre ferme.</b> <small>Créez autant de pages que vous voulez sur votre nouveau site et personnalisez le thème graphique. Vous pouvez même avoir un nom de domaine si vous le souhaitez.</small>").'</li>';
				echo '<li>'.s("<b>Aucune connaissance technique n'est nécessaire.</b> <small>Toutes les étapes de création de votre site internet se font depuis votre téléphone ou votre ordinateur.</small>").'</li>';
				echo '<li>'.s("<b>Pas de publicité.</b>").'</li>';
			echo '</ul>';
		echo '</div>';

		echo '<div>';
			echo '<h2>'.Asset::icon('arrow-right').''.s("Un logiciel pour améliorer vos pratiques").'</h2>';
			echo '<ul>';
				echo '<li>'.s("<b>Vous avez accès à de nombreux graphiques et statistiques.</b> <small>Visualisez les résultats de votre plan de culture, votre temps de travail et vos ventes. Retournez dans le passé pour mesurer vos progrès. Comprenez ce qui vous prend du temps pour améliorer vos pratiques.</small>").'</li>';
				echo '<li>'.s("<b>Vous connaissez votre prix de revient pour chaque culture.</b> <small>Avec le temps de travail et les ventes que vous avez saisis, calculez vos prix de revient pour mieux définir vos prix de vente.</small>").'</li>';
				echo '<li>'.s("<b>Vous pouvez exporter vos données au format CSV.</b> <small>Manipulez vos chiffres de vente ou de temps de travail dans un tableur pour tirer partie de vos données !</small>").'</li>';
			echo '</ul>';
		echo '</div>';

	echo '</div>';

	echo '<br/>';

	echo '<br/>';
	echo '<br/>';

	echo (new \main\HomeUi())->getPoints();

	echo '<h2>'.s("Principe de gratuité").'</h2>';

	echo '<ul class="home-story">';
		echo s("L'accès à toutes les fonctionnalités de {siteName} est libre et gratuit pour les producteurs sous signe de qualité <i>Agriculture biologique</i> ou <i>Nature & Progrès</i>. Pour les autres, reportez-vous aux <link>conditions d'utilisation du service</link>.", ['link' => '<a href="/presentation/service">']);
	echo '</ul>';

});

new AdaptativeView('/presentation/legal', function($data, MainTemplate $t) {

	$t->title = s("Mentions légales");
	$t->metaNoindex = TRUE;
	$t->template = 'home-legal';

	Asset::css('main', 'font-itim.css');

	Asset::css('main', 'home.css');

	$t->header = '<h1>'.s("Mentions légales").'</h1>';

	echo '<h2>'.s("Directrice de la publication").'</h2>';
	echo '<p>'.s("Une ingénieure du Puy-de-Dôme.").'</p>';

	echo '<br/>';

	echo '<h2>'.s("Hébergeur").'</h2>';
	echo '<ul>';
		echo '<li>'.s("Siège social : 2 rue Kellermann, 59100 Roubaix").'</li>';
		echo '<li>'.s("Numéro de téléphone : 09 72 10 10 07").'</li>';
	echo '</ul>';

});

new AdaptativeView('/presentation/pricing', function($data, MainTemplate $t) {

	$t->title = s("Tarifs");
	$t->metaNoindex = TRUE;
	$t->template = 'home-legal';

	Asset::css('main', 'font-itim.css');

	Asset::css('main', 'home.css');

	$t->header = '<h1>'.s("Grille tarifaire").'</h1>';

	echo new \company\SubscriptionUi()->getPlans(new \company\Company());

});

new AdaptativeView('/presentation/service', function($data, MainTemplate $t) {

	$t->title = s("Conditions d'utilisation du service");
	$t->metaNoindex = TRUE;
	$t->template = 'home-legal';

	Asset::css('main', 'font-itim.css');

	Asset::css('main', 'home.css');

	$t->header = '<h1>'.s("Conditions d'utilisation du service").'</h1>';

	echo (new \main\LegalUi())->tos();

});

new AdaptativeView('/presentation/faq', function($data, MainTemplate $t) {

	$t->title = s("Foire aux questions");
	$t->metaNoindex = TRUE;
	$t->template = 'home-legal';

	Asset::css('main', 'font-itim.css');

	Asset::css('main', 'home.css');

	$t->header = '<h1>'.s("Foire aux questions").'</h1>';

	echo (new \main\LegalUi())->faq();

});

new AdaptativeView('/presentation/engagements', function($data, MainTemplate $t) {

	$t->title = s("Les engagements de {siteName}");
	$t->metaNoindex = TRUE;
	$t->template = 'home-legal';

	Asset::css('main', 'font-itim.css');

	Asset::css('main', 'home.css');

	$t->header = '<h1>'.s("Les engagements de {siteName}").'</h1>';

	echo (new \main\LegalUi())->engagements();

});
?>
