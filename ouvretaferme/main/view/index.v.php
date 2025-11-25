<?php
new AdaptativeView('anonymous', function($data, MainTemplate $t) {

	$t->title = s("Organisez le travail à la ferme de la production à la vente");
	$t->metaDescription = s("Logiciel gratuit pour les producteurs pour vendre en ligne, éditer des factures, concevoir votre plan de culture et gérer votre planning. C'est adapté à toutes les productions.");
	$t->template = 'home-main';

	Asset::css('main', 'font-ptserif.css');
	Asset::css('main', 'home.css');

	$t->header .= '<div>';
		$t->header .= '<h1>'.s("<a>O</a>rganisez le <b>T</b>ravail à la <a>F</a>erme<br/>de la production à la vente", ['a' => '<span style="border-bottom: 3px solid var(--border); font-weight: bold" ">', 'b' => '<span style="border-top: 3px solid var(--border); font-weight: bold" ">']).'</h1>';
	$t->header .= '</div>';

	echo '<div class="home-features home-features-3">';

		echo '<h3 class="home-feature-title">'.s("Ouvretaferme est un logiciel gratuit qui contribue à l'autonomie des fermes en circuits courts").'</h3>';

		echo '<div class="home-feature">';

			echo '<h2 class="color-secondary">';
				echo '<div class="home-feature-icon">'.Asset::icon('basket').'</div>';
				echo s("Vendez en ligne");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("Créez des boutiques en ligne sans commission").'</li>';
				echo '<li>'.s("Mutualisez vos boutiques entre producteurs").'</li>';
				echo '<li>'.s("Produits et catalogues de vente illimités").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-buttons">';
				echo '<div class="home-feature-for">';
					echo s("Adapté à toutes les productions");
				echo '</div>';
				echo '<a href="/presentation/producteur" class="btn btn-secondary">'.s("En savoir plus").'</a>';
			echo '</div>';

		echo '</div>';
		echo '<div class="home-feature">';

			echo '<h2 class="color-production">';
				echo '<div class="home-feature-icon">'.Asset::icon('leaf').'</div>';
				echo s("Planifiez votre production");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("Concevez vos plan de culture et plan d'assolement").'</li>';
				echo '<li>'.s("Suivez votre planning semaine par semaine").'</li>';
				echo '<li>'.s("Notez votre temps de travail").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-buttons">';
				echo '<div class="home-feature-for">';
					echo s("Maraichage  ·  Arboriculture  ·  Floriculture");
				echo '</div>';
				echo '<a href="/presentation/producteur" class="btn btn-production">'.s("En savoir plus").'</a>';
				echo '  <a href="'.OTF_DEMO_URL.'/ferme/'.\farm\Farm::DEMO.'/series?view=area" class="btn btn-outline-production" target="_blank">'.s("Explorer la démo").'</a>';
			echo '</div>';
		echo '</div>';
		echo '<div class="home-feature home-feature-other">';

			echo '<h2 class="color-primary">';
				echo '<div class="home-feature-icon">'.Asset::icon('boxes').'</div>';
				echo s("Et aussi");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("Éditez des devis, bons de livraison et factures").'</li>';
				echo '<li><a href="/facturation-electronique-les-mains-dans-les-poches">'.s("Compatible facturation électronique").'</a> 👍</li>';
				echo '<li>'.s("Utilisez un logiciel de caisse pour vos marchés").'</li>';
				echo '<li>'.s("Créez le site internet de votre ferme").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-buttons">';
				echo '<div class="home-feature-for">';
					echo s("Envie d'essayer Ouvretaferme ?");
				echo '</div>';
				echo '<a href="/user/signUp" class="btn btn-primary">'.Asset::icon('person-fill').' '.s("Créer un compte").'</a>';
			echo '</div>';

		echo '</div>';

	echo '</div>';

	if(FEATURE_GAME) {
		echo new \game\HelpUi()->getHome($data->ePlayer);
	}

	echo '<h2>'.s("Qui utilise Ouvretaferme et pourquoi ?").'</h2>';

	echo '<div class="home-profiles">';

		echo '<div class="home-profile home-profile-extended">';
			echo '<div class="home-profile-header">';
				echo '<div>'.Asset::image('main', 'profile/tomatesetpotirons.jpg', ['class' => 'home-profile-image']).'</div>';
				echo '<div>';
					echo '<h4>'.s("Tomates & Potirons (86)").'</h4>';
					echo '<h3>'.s("Maraichage").'</h3>';
				echo '</div>';
			echo '</div>';
			echo '<p>'.s("Avant Ouvretaferme, les maraichers de Tomates & Potirons ont testé plusieurs outils de planification, en commençant par Excel : très flexible, mais vite complexe et difficilement transmissible à une équipe. D’autres logiciels étaient intéressants, mais souvent limités à la production, avec peu de souplesse. Ouvretaferme a été une révélation : enfin un outil qui combine toutes les informations nécessaires à notre ferme, de la production à la commercialisation.").'</p>';
			echo '<p class="hide-sm-down">&laquo; '.s("Aujourd’hui, grâce à la centralisation des données (plan de culture, ventes, temps de travail), nous avons une analyse économique précise de chaque série. C’est un outil stratégique pour toute ferme diversifiée.").' &raquo;</p>';
		echo '</div>';

		echo '<div class="home-profile">';
			echo '<div class="home-profile-header">';
				echo '<div>'.Asset::image('main', 'profile/pain.jpg', ['class' => 'home-profile-image']).'</div>';
				echo '<div>';
					echo '<h3>'.s("Boulangerie paysanne").'</h3>';
				echo '</div>';
			echo '</div>';
			echo '<p>'.s("Adeline est une paysanne-boulangère qui vend sa production sur les marchés avec le logiciel de caisse de Ouvretaferme accessible sur son téléphone ou sa tablette. Elle vend aussi son pain sur une boutique en ligne qu'elle partage avec un collègue maraicher.").'</p>';
		echo '</div>';
		echo '<div class="home-profile">';
			echo '<div class="home-profile-header">';
				echo '<div>'.Asset::image('main', 'profile/oeuf.jpg', ['class' => 'home-profile-image']).'</div>';
				echo '<div>';
					echo '<h3>'.s("Élevage").'</h3>';
				echo '</div>';
			echo '</div>';
			echo '<p>'.s("Axel est un éleveur qui vend sa production en ligne avec Ouvretaferme et a bidouillé les fonctionnalités de planification destinées aux fruits, légumes et aux fleurs pour les adapter à sa production de volailles de chair et de poules pondeuses. Il bénéficiera peut-être bientôt de fonctionnalités spécifiques sur Ouvretaferme !").'</p>';
		echo '</div>';
		echo '<div class="home-profile home-profile-dark bg-shop">';
			echo '<div class="home-profile-header home-profile-header-text">';
				echo '<h3>'.s("Vos clients").'</h3>';
			echo '</div>';
			echo '<p>'.s("Ils commandent en vente directe à leur producteurs préférés les produits qu'ils proposent cette semaine et récupèrent leur commande au lieu et à la date convenus. Ils paient en ligne ou sur place selon le choix du producteur !").'</p>';
		echo '</div>';

		echo '<div class="home-profile">';
			echo '<div class="home-profile-header">';
				echo '<div>'.Asset::image('main', 'profile/jardindesmurmures.jpg', ['class' => 'home-profile-image']).'</div>';
				echo '<div>';
					echo '<h4>'.s("Le Jardin des Murmures (74)").'</h4>';
					echo '<h3>'.s("Maraichage").'</h3>';
				echo '</div>';
			echo '</div>';
			echo '<p>'.s("Lionel utilise Ouvretaferme depuis 2023 et notamment la boutique en ligne pour ses ventes directes et le système de facturation qui lui ont fait gagner des heures. Le planning de production lui permet également de travailler en équipe et notamment de connaître les planches à préparer, la fertilisation et le paillage à utiliser !").'</p>';
		echo '</div>';

		echo '<div class="home-profile">';
			echo '<div class="home-profile-header">';
				echo '<div>'.Asset::image('main', 'profile/fleur.jpg', ['class' => 'home-profile-image']).'</div>';
				echo '<div>';
					echo '<h3>'.s("Floriculture").'</h3>';
				echo '</div>';
			echo '</div>';
			echo '<p>'.s("Marie et Luc sont des floriculteurs qui gèrent avec Ouvretaferme la diversité de leur production sur petite surface. Ils vendent aussi sur une boutique en ligne destinée aux fleuristes leur gamme de fleurs coupées. Ils envoient leurs bons de livraison par e-mail et génèrent chaque mois en un clic les factures de leurs ventes.").'</p>';
		echo '</div>';

		echo '<div class="home-profile">';
			echo '<div class="home-profile-header">';
				echo '<div>'.Asset::image('main', 'profile/cfppacourcelles.png', ['class' => 'home-profile-image']).'</div>';
				echo '<div>';
					echo '<h4><a href="https://campus-courcelles.fr/">'.s("CFPPA de Courcelles-Chaussy (57)").'</a></h4>';
					echo '<h3>'.s("Centre de formation").'</h3>';
				echo '</div>';
			echo '</div>';
			echo '<p>'.s("Le <link>CFPPA de Courcelles-Chaussy</link> utilise Ouvretaferme non seulement pour gérer son atelier pédagogique mais aussi pour permettre aux stagiaires de mieux appréhender le travail à réaliser sur une ferme, les itinéraires techniques et tout ce qui concerne le plan de culture.", ['link' => '<a href="https://campus-courcelles.fr/">']).'</p>';
		echo '</div>';

		echo '<div class="home-profile">';
			echo '<div class="home-profile-header">';
				echo '<div>'.Asset::image('main', 'profile/carotte.jpg', ['class' => 'home-profile-image']).'</div>';
				echo '<div>';
					echo '<h4>'.s("Les Jardins de Tallende (63)").'</h4>';
					echo '<h3>'.s("Maraichage").'</h3>';
				echo '</div>';
			echo '</div>';
			echo '<p>'.s("Vincent est un maraicher diversifié qui conçoit son plan de culture avec Ouvretaferme pour la saison en respectant ses rotations. En saison, il utilise le planning pour se libérer de sa charge mentale et enregistre son temps de travail pour comprendre là où il peut améliorer son système. La nuit, il est aussi le développeur principal de Ouvretaferme !").'</p>';
		echo '</div>';
	echo '</div>';

	echo new \main\HomeUi()->getTraining();

	echo '<h2 class="mb-2">'.s("En savoir plus sur Ouvretaferme").'</h2>';

	echo '<h3>'.s("Philosophie du projet").'</h3>';

	echo '<div class="home-story-wrapper">';
		echo '<div class="home-story">';
			echo '<p>'.s("Le logiciel {siteName} est un projet associatif lancé en 2021 pour combler l'absence d'un logiciel ouvert, gratuit et intuitif destiné aux producteurs en agriculture biologique. Conçu pour simplifier l'organisation du travail à la ferme, ce logiciel complet accompagne les producteurs agricoles, du plan de culture à la vente de leurs produits. Notre mission : fournir aux producteurs les outils nécessaires pour contribuer à réaliser les finalités des fermes.").'</p>';
			echo '<a href="'.\association\AssociationSetting::URL.'" target="_blank" class="btn btn-secondary">'.S("Découvrir l'association").'</a> ';
			echo '<a href="'.\association\AssociationSetting::URL.'/nous-soutenir" target="_blank" class="btn btn-outline-secondary">'.S("Nous soutenir").'</a>';
		echo '</div>';
		echo Asset::image('main', 'cube.png');
	echo '</div>';

	echo new \main\HomeUi()->getPoints();

	echo '<br/><br/><br/>';

});

new AdaptativeView('logged', function($data, MainTemplate $t) {

	$t->title = s("Bienvenue sur {siteName}");
	$t->canonical = '/';

	$t->header = '<h1>'.s("Bienvenue, {userName}&nbsp;!", ['userName' => encode($data->eUserOnline['firstName'] ?? $data->eUserOnline['lastName'])]).'</h1>';

	if($data->eUserOnline['role']['fqn'] === 'customer') {

		$t->header .= '<div class="util-info">'.s("Vous êtes connecté sur l'espace client qui vous relie à tous les producteurs auxquels vous avez l'habitude de commander sur {value}.", '<a href="'.Lime::getUrl().'">'.s("{siteName}").'</a>').'</div>';

	}

	if(\user\ConnectionLib::getOnline()->isFarmer()) {

		echo new \main\HomeUi()->getFarms($data->cFarmUser);

		if(FEATURE_GAME) {
			echo new \game\HelpUi()->getHome($data->ePlayer);
		}

		echo new \main\HomeUi()->getTraining();

		if($data->cFarmUser->notEmpty()) {
			echo new \main\HomeUi()->getBlog($data->cNews);
		}

	} else {

		if(FEATURE_GAME) {
			echo new \game\HelpUi()->getHome($data->ePlayer);
		}

	}

	echo new \selling\CustomerUi()->getHome($data->cCustomerPro, $data->cShop, $data->cSale, $data->cInvoice);

});

new AdaptativeView('signUp', function($data, MainTemplate $t) {

	$t->title = s("Inscription sur {siteName}");
	$t->metaDescription = s("Inscrivez-vous comme producteur sur {siteName} pour profiter de fonctionnalités de vente en ligne et de production du logiciel !");
	$t->template = 'home-legal';

	Asset::css('main', 'font-ptserif.css');

	Asset::css('main', 'home.css');


	$t->header = '<div class="home-user-already">';
		$t->header .= s("Vous êtes déjà inscrit sur {siteName} ?").' &nbsp;&nbsp;';
		$t->header .= '<a href="" class="btn btn-primary">'.s("Connectez-vous !").'</a>';
	$t->header .= '</div>';

	$t->header .= '<h1>'.s("Je m'inscris sur {siteName} !").'</h1>';
	$t->header .= '<div class="home-user-types">';
		if($data->chooseRole) {
			$t->header .= new \main\HomeUi()->getCustomer($data->eRole);
			$t->header .= new \main\HomeUi()->getFarmer($data->eRole);
		} else {
			$t->header .= match($data->eRole['fqn']) {
				'customer' => new \main\HomeUi()->getCustomer($data->eRole),
				'farmer' => new \main\HomeUi()->getFarmer($data->eRole)
			};
		}
	$t->header .= '</div>';

	if($data->eRole->notEmpty()) {

		echo '<h2>'.s("Mes informations").'</h2>';

		if($data->eRole['fqn'] === 'farmer') {
			echo '<div class="util-info">'.s("Renseignez quelques informations qui vous permettront ensuite de vous connecter sur {siteName}. Vous pourrez créer votre ferme ou rejoindre une ferme existante juste après cette étape !").'</div>';
		}

		echo new \user\UserUi()->signUp($data->eUserOnline, $data->eRole, REQUEST('redirect'));
	}


});

new AdaptativeView('/presentation/invitation', function($data, MainTemplate $t) {

	$t->title = s("Cette invitation a expiré, veuillez vous rapprocher de votre interlocuteur habituelle pour en obtenir une nouvelle !");
	$t->template = 'home-legal';

	Asset::css('main', 'font-ptserif.css');

	Asset::css('main', 'home.css');

});

new AdaptativeView('/facturation-electronique-les-mains-dans-les-poches', function($data, MainTemplate $t) {

	$t->title = s("{siteName} - Facturation électronique");
	$t->metaDescription = s("{siteName} sera pleinement compatible avec la facturation électronique.");
	$t->template = 'home-invoicing';

	Asset::css('main', 'font-ptserif.css');
	Asset::css('main', 'home.css');

	$t->header .= '<h1>'.s("À propos de la facturation électronique").'</h1>';
	$t->header .= '<h4 class="home-domain">'.s("(et pourquoi ce ne sera pas un problème)").'</h4>';


	echo '<h3 class="mt-2">'.s("Principes généraux").'</h3>';

	echo '<div class="home-story">';
		echo '<p>'.s("La réforme de la facturation électronique concerne toutes les entreprises assujetties à la TVA.").'</p>';
		echo '<ul>';
			echo '<li>'.s("À partir du 1<sup>er</sup> septembre 2026, elles devront être en mesure de recevoir des factures électroniques de la part de leurs fournisseurs.").'</li>';
			echo '<li>';
				echo 	s("À partir du 1<sup>er</sup> septembre 2027, elles seront tenues :");
				echo '<ul>';
					echo '<li>'.s("d'émettre leurs factures au format électronique (<i>e-invoicing</i>)").'</li>';
					echo '<li>'.s("de transmettre le montant des opérations réalisées avec des clients particuliers ou certaines associations (<i>e-reporting</i>)").'</li>';
				echo '</ul>';
			echo '</li>';
		echo '</ul>';
		echo '<p>'.s("Vous pouvez trouver des informations fiables sur la <link>foire aux questions</link> éditée par les finances publiques.", ['link' => '<a href="https://www.impots.gouv.fr/sites/default/files/media/1_metier/2_professionnel/EV/2_gestion/290_facturation_electronique/faq---fe_je-decouvre-la-facturation-electronique.pdf">']).'</p>';
	echo '</div>';

	echo '<br/>';

	echo '<h3>'.s("Comment émettre et recevoir des factures électroniques ?").'</h3>';

	echo '<div class="home-story">';
		echo '<p>'.s("Vous devrez contractualiser avec une plateforme agréée (PA), qui vous permettra de réaliser l'ensemble des opérations. Il est important de comprendre qu'avec cette réforme, vous n'aurez plus le droit de transmettre vos factures directement à vos clients professionnels et qu'elles devront obligatoirement transiter par votre PA.").'</p>';
		echo '<p><i>'.s("Point important : vous pourrez tout à fait utiliser plusieurs PA en parallèle et en changer comme bon vous semble.").'</i></p>';
	echo '</div>';

	echo '<br/>';

	echo '<h3>'.s("Pourquoi il n'y a rien d'urgent ?").'</h3>';

	echo '<div class="home-story">';
		echo '<p>'.s("Un grand nombre d'opérateurs ayant identifié une opportunité commerciale se sont positionnés sur le marché de la facturation électronique. Il y a une situation de forte concurrence qui poussent certains de ces opérateurs à jouer sur la peur et l'urgence.").'</p>';
		echo '<p>'.s("Néanmoins, à l'heure actuelle, il faut bien comprendre que les infrastructures techniques ne sont pas encore prêtes du côté de la plupart des PA et que le travail de normalisation est encore en cours.").'</p>';
	echo '</div>';

	echo '<br/>';

	echo '<h3>'.s("Comment ça va se passer sur Ouvretaferme ?").'</h3>';

	echo '<div class="home-story">';
		echo '<p>'.s("Nous allons travailler avec une plateforme agréée qui vous permettra d'envoyer automatiquement vos factures depuis Ouvretaferme. Nous avons choisi <link>SUPER PDP</link>. Cette plateforme est l'une des plus avancées et nous sommes déjà en train de l'intégrer.", ['link' => '<a href="https://www.superpdp.tech/">']).'</p>';
		echo '<p>'.s("L'utilisation de <i>SUPER PDP</i> est <link>gratuite jusqu'à 1000 factures par mois</link>, ce qui correspond à l'immense majorité des producteurs. Vous pourrez même l'utiliser indépendamment de Ouvretaferme.", ['link' => '<a href="https://www.superpdp.tech/tarifs">']).'</p>';
		echo '<p>'.s("Nous allons chercher également à intégrer pleinement <i>SUPER PDP</i> avec Ouvretaferme. Cette intégration sera facturée à l'association par <i>SUPER PDP</i> et nous la rendrons donc disponible pour les fermes ayant adhéré à l'association. <b>Notre objectif est que vous puissiez gérer l'ensemble de vos factures de ventes directement depuis Ouvretaferme.</b>").'</p>';
		echo '<p><i>'.s("<p>Notre opinion : il ne faut pas être trop pressé et il est stratégiquement intéressant de laisser d'autres acteurs essuyer les plâtres et les bugs qui accompagneront le lancement de la réforme.").'</i></p>';
	echo '</div>';

	echo '<br/>';

	echo '<h3>'.s("Vous souhaitez une synthèse simple en 4 points ?").'</h3>';

	echo '<div class="home-points">';
		echo '<div class="home-point">';
			echo \Asset::icon('music-note');
			echo '<h4>'.s("Il n'y a aucune urgence car personne n'est encore prêt.").'</h4>';
		echo '</div>';
		echo '<div class="home-point">';
			echo \Asset::icon('wallet2');
			echo '<h4>'.s("Cela ne vous coûtera pas grand chose si vous choisissez un bon logiciel.").'</h4>';
		echo '</div>';
		echo '<div class="home-point">';
			echo \Asset::icon('heart');
			echo '<h4>'.s("Ouvretaferme est un bon logiciel et nous travaillons activement le sujet.").'</h4>';
		echo '</div>';
		echo '<div class="home-point">';
			echo \Asset::icon('basket3');
			echo '<h4>'.s("Vous pouvez donner la priorité à vos productions bio et locales.").'</h4>';
		echo '</div>';
	echo '</div>';

});

new AdaptativeView('/presentation/formations', function($data, MainTemplate $t) {

	$t->title = s("{siteName} - Formations");
	$t->metaDescription = s("Formez-vous à l'utilisation de {siteName} !");
	$t->template = 'home-farmer';

	Asset::css('main', 'font-ptserif.css');

	Asset::css('main', 'home.css');

	$t->header = '<h4 class="home-domain">'.Lime::getDomain().'</h4>';
	$t->header .= '<h1>'.s("Journée de formation le 29 janvier 2025 <br/>Puy-de-Dôme (63)").'</h1>';

	if(currentDate() <= \main\MainSetting::LIMIT_TRAINING) {

		echo '<div class="home-presentation">';

			echo '<div>';
				echo '<h2>'.Asset::icon('arrow-right').''.s("Présentation de la formation").'</h2>';
				echo '<p>';
					echo s("La formation à {siteName} se déroule sur une journée en présentiel. Elle est organisée par la FRAB AuRA et finançable VIVEA.");
				echo '</p>';
				echo '<h2>'.Asset::icon('arrow-right').''.s("Contenu de la formation").'</h2>';
				echo '<ul>';
					echo '<li>'.s("<b>Le matin.</b> Présentation des fonctionnalités et des finalités de l'outil, interactive en fonction des attentes des participants (plan de culture, temps de travail, assolement, commercialisation, analyse des données...).</small>").'</li>';
					echo '<li>'.s("<b>L'après-midi.</b> Pour les novices, accompagnement sur la prise en main de l'outil. Pour ceux qui utilisent déjà l'outil, approfondissement sur des fonctionnalités spécifiques et échanges sur des évolutions possibles pour {siteName}.").'</li>';
				echo '</ul>';
				echo '<b>'.s("Une occasion idéale pour prendre en main ou se perfectionner sur {siteName}, discuter des évolutions possibles et échanger sur vos problématiques !").'</b>';
			echo '</div>';

			echo '<div>';
				echo '<h2>'.Asset::icon('arrow-right').''.s("Une date").'</h2>';
				echo '<ul>';
					echo '<li class="mb-2">'.s("<b>Le 29 janvier 2025 autour d'Issoire (63)</b>").'<br/><a href="https://forms.office.com/e/xx2zWdrRVz" class="btn btn-secondary" style="margin-top: 0.5rem">'.s("Inscription pour le 29 janvier").'</a></li>';
				echo '</ul>';
			echo '</div>';

		echo '</div>';

	} else {
		echo s("Il n'y a pas de formation à venir.");
	}

	echo '<br/>';
	echo '<br/>';

});

new AdaptativeView('/presentation/service', function($data, MainTemplate $t) {

	$t->title = s("Conditions d'utilisation du service");
	$t->metaNoindex = TRUE;
	$t->template = 'home-legal';

	Asset::css('main', 'font-ptserif.css');

	Asset::css('main', 'home.css');

	$t->header = '<h1>'.s("Conditions d'utilisation du service").'</h1>';

	echo new \main\LegalUi()->tos();

});

new AdaptativeView('/presentation/faq', function($data, MainTemplate $t) {

	$t->title = s("Foire aux questions");
	$t->metaNoindex = TRUE;
	$t->template = 'home-legal';

	Asset::css('main', 'font-ptserif.css');

	Asset::css('main', 'home.css');

	$t->header = '<h1>'.s("Foire aux questions").'</h1>';

	echo new \main\LegalUi()->faq();

});
?>
