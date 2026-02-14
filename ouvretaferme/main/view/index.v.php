<?php
new AdaptativeView('anonymous', function($data, MainTemplate $t) {

	$t->title = s("Organisez le travail à la ferme de la production à la vente");
	$t->metaDescription = s("Logiciel gratuit pour les producteurs pour vendre en ligne, éditer des factures, concevoir votre plan de culture et gérer votre planning. C'est adapté à toutes les productions.");
	$t->template = 'home-main';

	Asset::css('main', 'home.css');

	$t->header .= '<div>';
		$t->header .= '<h1>'.s("Toute votre ferme sur un seul logiciel").'</h1>';
		$t->header .= '<h4 class="home-feature-title">'.s("Intuitif et facile à utiliser au bureau comme sur le terrain").'</h4>';
	$t->header .= '</div>';

	echo '<div class="home-features home-overlay home-features-3">';

		echo '<div class="home-feature">';

			echo '<h2 class="color-secondary">';
				echo '<div class="home-feature-icon">'.Asset::icon('basket').'</div>';
				echo s("Vente en ligne");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("Créez des boutiques en ligne sans commission").'</li>';
				echo '<li>'.s("Mutualisez vos boutiques entre producteurs").'</li>';
				echo '<li>'.s("Produits et catalogues de vente illimités").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-for">';
				echo s("Adapté à toutes les productions");
			echo '</div>';

		echo '</div>';
		echo '<div class="home-feature">';

			echo '<h2 class="color-production">';
				echo '<div class="home-feature-icon">'.Asset::icon('leaf').'</div>';
				echo s("Production");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("Concevez vos plan de culture et plan d'assolement").'</li>';
				echo '<li>'.s("Suivez votre planning semaine par semaine").'</li>';
				echo '<li>'.s("Notez et analysez votre temps de travail").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-for">';
				echo s("Maraichage  ·  Arboriculture  ·  Floriculture");
			echo '</div>';
		echo '</div>';
		echo '<div class="home-feature">';

			echo '<h2 class="color-accounting">';
				echo '<div class="home-feature-icon">'.Asset::icon('piggy-bank').'</div>';
				echo s("Comptabilité");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("Rapprochement bancaire avec vos factures").'</li>';
				echo '<li>'.s("Logiciel de comptabilité inclus pour le micro-BA").'</li>';
				echo '<li>'.s("Journal de caisse").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-for">';
				echo s("Nouveauté 2026 !");
			echo '</div>';

		echo '</div>';


		echo '<div class="home-feature">';

			echo '<h2 class="color-secondary">';
				echo '<div class="home-feature-icon">'.Asset::icon('receipt').'</div>';
				echo s("Facturation");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("Utilisez un logiciel de caisse pour vos marchés").'</li>';
				echo '<li>'.s("Éditez des devis, bons de livraison et factures").'</li>';
				echo '<li><a href="/facturation-electronique-les-mains-dans-les-poches">'.s("Compatible facturation électronique").'</a> 👍</li>';
			echo '</ul>';
			echo '<div class="home-feature-for">';
				echo s("Logiciel conforme").' '.Asset::icon('check-circle-fill');
			echo '</div>';

		echo '</div>';
		echo '<div class="home-feature">';

			echo '<h2 class="color-production">';
				echo '<div class="home-feature-icon">'.Asset::icon('megaphone', ['class' => 'asset-icon-flip-h']).'</div>';
				echo s("Communication");
			echo '</h2>';
			echo '<ul>';
				echo '<li>'.s("Travaillez en équipe sur le logiciel").'</li>';
				echo '<li>'.s("Campagnes d'e-mails pour vos clients").'</li>';
				echo '<li>'.s("Créez le site internet de votre ferme").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-for">';
				echo s("Aucune connaissance technique requise");
			echo '</div>';
		echo '</div>';
		echo '<div class="home-feature home-feature-other">';
			echo '<div class="home-feature-subtitle">'.s("Ouvretaferme est un logiciel gratuit qui contribue à l'autonomie de plus de 2500 producteurs").'</div>';
			echo '<div>';
				echo '<a href="/user/signUp" class="btn btn-xl btn-outline-primary" style="font-weight: bold;">'.s("Créer un compte").'</a>';
			echo '</div>';

		echo '</div>';

	echo '</div>';
	echo '<div class="text-center" style="margin-bottom: 4rem">';
		echo '<a href="/presentation/producteur" class="btn btn-xl btn-primary">'.s("Liste des fonctionnalités").'</a>';
		echo '  <a href="'.OTF_DEMO_URL.'/ferme/'.\farm\Farm::DEMO.'/series?view=area" class="btn btn-xl btn-outline-primary" target="_blank">'.s("Explorer la démo").'</a>';
	echo '</div>';


	if(FEATURE_GAME) {
		echo new \game\HelpUi()->getHome($data->ePlayer);
	}

	echo new \main\HomeUi()->getMission();
	echo new \main\HomeUi()->getEvidences();
	echo new \main\HomeUi()->getTraining();

	echo '<h2 class="mb-2">'.s("En savoir plus sur Ouvretaferme").'</h2>';

	echo '<div class="home-story-wrapper">';
		echo '<div class="home-story">';
			echo '<p>'.s("Le logiciel {siteName} est un projet associatif lancé en 2021 pour combler l'absence d'un logiciel ouvert, gratuit et intuitif destiné aux producteurs en circuits courts. Conçu pour simplifier l'organisation du travail à la ferme, ce logiciel complet accompagne les producteurs agricoles, du plan de culture à la vente de leurs produits.").'</p>';
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

		if($data->eRole['fqn'] === 'farmer') {
			echo '<div class="util-block-info mb-2">';
				echo \Asset::icon('person-workspace', ['class' => 'util-block-icon']);
				echo '<h4>'.s("Bienvenue sur {siteName} !").'</h4>';
				echo '<p>'.s("Pour travailler avec le logiciel comme producteur, nous vous suggérons de créer un compte personnel avec vos nom et prénom.").'</p>';
				echo '<p>'.s("Vous pourrez créer votre ferme ou rejoindre une ferme existante et profiter de toutes les fonctionnalités juste après !<br/>Vous pourrez également inviter autant de collègues que nécessaire dans l'équipe de votre ferme.").'</p>';
			echo '</div>';
		}

		echo '<h2 class="text-center mb-2">'.s("Mes informations").'</h2>';

		echo new \user\UserUi()->signUp($data->eUser, $data->eRole, REQUEST('redirect'));
	}


});

new AdaptativeView('/presentation/invitation', function($data, MainTemplate $t) {

	$t->title = s("Cette invitation a expiré, veuillez vous rapprocher de votre interlocuteur habituelle pour en obtenir une nouvelle !");
	$t->template = 'home-legal';

	Asset::css('main', 'home.css');

});


new AdaptativeView('/presentation/producteur', function($data, MainTemplate $t) {

	$t->title = s("{siteName} - Pour les producteurs");
	$t->metaDescription = s("Présentation des fonctionnalités de {siteName} pour les producteurs. Découvrez tous les outils de planification, de vente en ligne, de communication et de gestion d'équipe !");
	$t->template = 'home-farmer';

	Asset::css('main', 'home.css');

	$t->header = '<h4 class="home-domain">'.Lime::getName().'</h4>';
	$t->header .= '<h1>'.s("Les fonctionnalités").'</h1>';


	echo '<div class="home-presentation">';

		echo '<div>';
			echo '<h2 class="color-secondary">'.Asset::icon('basket').'<br/>'.s("Un logiciel pour vendre").'</h2>';
			echo '<div class="home-presentation-item">'.s("<b>Gérez vos ventes pour les professionnels et les particuliers</b><br/><small>Créez des ventes à partir de vos produits, gérez votre clientèle, choisissez vos prix. Imprimez des étiquettes de colisage si vous livrez aux professionnels. Exporter les ventes du jour au format PDF pour préparer vos livraisons.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Utilisez le logiciel de caisse intégré</b><br/><small>Utilisez le logiciel de caisse avec une tablette ou un téléphone pour préparer vos marchés et saisir vos ventes directement pendant le marché. Pour chaque vente, visualisez ce que le client a acheté et le montant qu'il doit vous régler. Simple et efficace.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Créez des boutiques en ligne</b><br/><small>Permettez à vos clients de passer commande en ligne et de récupérer leur colis à la date et l'endroit convenus, ou bien livrez-les à domicile selon vos préférences. Activez si vous le souhaitez le paiement par carte bancaire sans commission sur les ventes.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Partagez vos boutiques en ligne avec d'autres producteurs</b><br/><small>Vendez à plusieurs sur la même boutique pour partager vos créneaux de vente et simplifier l'expérience pour vos clients.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Éditez des mercuriales pour vos clients professionnels</b><br/><small>Créez des boutiques en ligne exclusivement réservées à vos clients professionnels. Personnalisez les prix et les disponibilités par client.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Pilotez vos stocks</b><br/><small>Choisissez les produits pour lesquels vous souhaitez avoir un suivi des stocks. Les récoltes et les ventes que vous saisissez impactent automatiquement le stock et vous savez toujours ce qui vous reste à vendre.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Gérez vos devis, bons de livraison et factures</b><br/><small>Créez toutes les factures du mois en une seule fois. Envoyez-les en un clic par e-mail à vos clients. Obtenez-les au format PDF. Suivez et relancez vos impayés.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Compatible avec la facturation électronique</b><br/><small>Les factures intègrent le format Factur-X.</small>").'</div>';

		echo '</div>';

		echo '<div>';
			echo '<h2 class="color-production">'.Asset::icon('leaf').'<br/>'.s("Un logiciel pour produire").'</h2>';
			echo '<div class="home-presentation-item">'.s("<b>Planifiez votre saison de culture en concevant vos plan de culture et plan d'assolement</b><br/><small>Gérez les variétés, la longueur des planches, les surfaces, les densités, les objectifs de récolte et les associations de cultures. Enregistrez et retrouvez facilement toutes les informations liées à vos séries de cultures. De plus, un prévisionnel financier vous aide à estimer vos ventes en fonction de votre plan de culture et de vos prévisions !</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Maîtrisez votre temps de travail</b><br/><small>Que ce soit à la ferme avec votre téléphone ou le soir sur l'ordinateur, un planning hebdomadaire ou quotidien vous permet de faire le suivi des interventions planifiées et réalisées sur la semaine. Renseignez facilement votre temps de travail pour comprendre là où passe votre temps.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Suivez précisément vos rotations sur votre parcellaire</b><br/><small>Choisissez vos critères pour les rotations et vérifiez en un coup d'oeil les planches qui correspondent à ces critères. Pratique pour éviter de mettre vos cultures aux mêmes emplacements trop souvent !</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Collaborez avec votre équipe</b><br/><small>Invitez votre équipe sur l'espace de votre ferme et gérez les droits de chaque personne.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>C'est adapté à toutes les productions</b><br/><small>{siteName} vous accompagne en maraichage, floriculture, arboriculture ou même en production de semences.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Et aussi...</b><br/><small>Consultez les quantités de semences et plants à produire ou commander. Créez des itinéraires techniques réutilisables saison après saison. Ajoutez des photos pour vous souvenir de vos cultures. Enregistrez le matériel disponible à la ferme pour l'utiliser dans vos interventions...</small>").'</div>';
		echo '</div>';

	echo '</div>';

	echo '<div class="home-presentation">';

		echo '<div>';
			echo '<h2>'.Asset::icon('megaphone').'<br/>'.s("Un logiciel pour communiquer").'</h2>';
			echo '<div class="home-presentation-item">'.s("<b>Programmez des campagnes d'e-mailing</b><br/><small>Vous pouvez envoyer des e-mails à vos clients pour les prévenir de l'ouverture de vos ventes ou tout simplement leur envoyer une newsletter</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Créez le site internet de votre ferme</b><br/><small>Créez autant de pages que vous voulez sur votre nouveau site et personnalisez le thème graphique. Vous pouvez même avoir un nom de domaine si vous le souhaitez.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Aucune connaissance technique n'est nécessaire</b><br/><small>Toutes les étapes de création de votre site internet se font depuis votre téléphone ou votre ordinateur.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Pas de publicité</b>").'</div>';
		echo '</div>';

		echo '<div>';
			echo '<h2>'.Asset::icon('send').'<br/>'.s("Un logiciel pour améliorer vos pratiques").'</h2>';
			echo '<div class="home-presentation-item">'.s("<b>Accédez à de nombreux graphiques et statistiques</b><br/><small>Visualisez les résultats de votre plan de culture, votre temps de travail et vos ventes. Retournez dans le passé pour mesurer vos progrès. Comprenez ce qui vous prend du temps pour améliorer vos pratiques.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Connaissez votre prix de revient pour chaque culture</b><br/><small>Avec le temps de travail et les ventes que vous avez saisis, calculez vos prix de revient pour mieux définir vos prix de vente.</small>").'</div>';
			echo '<div class="home-presentation-item">'.s("<b>Exportez vos données au format CSV</b><br/><small>Manipulez vos chiffres de vente ou de temps de travail dans un tableur pour tirer partie de vos données !</small>").'</div>';
		echo '</div>';

	echo '</div>';

	echo '<h2 class="mt-3">'.s("Un logiciel pour faire votre comptabilité").'</h2>';
	echo new \main\HomeUi()->getAccounting();

	echo '<br/>';

	echo '<div class="text-center">';
		echo '<a href="'.OTF_DEMO_URL.'/ferme/'.\farm\Farm::DEMO.'/series?view=area" class="btn btn-secondary btn-xl">'.s("Explorez la ferme démo pour découvrir le service").'</a>';
	echo '</div>';
	echo '<br/>';
	echo '<br/>';

	echo new \main\HomeUi()->getPoints();

});

new AdaptativeView('/facturation-electronique-les-mains-dans-les-poches', function($data, MainTemplate $t) {

	$t->title = s("{siteName} - Facturation électronique");
	$t->metaDescription = s("{siteName} sera pleinement compatible avec la facturation électronique.");
	$t->template = 'home-invoicing';

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

});

new AdaptativeView('/presentation/formations', function($data, MainTemplate $t) {

	$t->title = s("{siteName} - Formations");
	$t->metaDescription = s("Formez-vous à l'utilisation de {siteName} !");
	$t->template = 'home-farmer';

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

	$t->title = s("Conditions d'utilisation de Ouvretaferme");
	$t->metaNoindex = TRUE;
	$t->template = 'home-legal';

	Asset::css('main', 'home.css');

	$t->header = '<h1>'.s("Conditions d'utilisation de Ouvretaferme").'</h1>';

	echo new \main\LegalUi()->tos();

});

new AdaptativeView('/presentation/adhesion', function($data, MainTemplate $t) {

	$t->title = s("Adhésion à l'association Ouvretaferme");
	$t->metaNoindex = TRUE;
	$t->template = 'home-legal';

	Asset::css('main', 'home.css');

	$t->header = '<div>';
		$t->header .= '<h4 class="home-domain">'.Lime::getName().'</h4>';
		$t->header .= '<h1>'.s("L'association").'</h1>';
		$t->header .= '<h4>'.s("Nous éditons un logiciel conçu pour organiser le travail à la ferme de la production à la vente.").'</h4>';
		$t->header .= '<div class="text-center mt-2">';
			$t->header .= '<a href="'.\association\AssociationSetting::URL.'" class="btn btn-transparent btn-xl">'.s("Découvrir l'association").'</a>  ';
			$t->header .= '<a href="/presentation/producteur" class="btn btn-transparent btn-xl">'.s("Liste des fonctionnalités").'</a>';
		$t->header .= '</div>';
	$t->header .= '</div>';

	echo new \main\LegalUi()->membership();

});

new AdaptativeView('/presentation/afocg', function($data, MainTemplate $t) {

	$t->title = s("Vous êtes une AFOCG ?");
	$t->metaNoindex = TRUE;
	$t->template = 'home-legal';

	Asset::css('main', 'home.css');

	$t->header = '<div>';
		$t->header .= '<h1>'.s("Vous êtes une AFOCG ?").'</h1>';
		$t->header .= '<h4>'.s("Voyons si vous pourriez utiliser Ouvretaferme comme solution logicielle").'</h4>';
	$t->header .= '</div>';

	echo new \main\LegalUi()->afocg();

});

new AdaptativeView('/presentation/faq', function($data, MainTemplate $t) {

	$t->title = s("Foire aux questions");
	$t->metaNoindex = TRUE;
	$t->template = 'home-legal';

	Asset::css('main', 'home.css');

	$t->header = '<h1>'.s("Foire aux questions").'</h1>';

	echo new \main\LegalUi()->faq();

});
?>
