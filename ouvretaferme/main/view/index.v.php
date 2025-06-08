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

	echo '<div class="home-features">';

		echo '<h3 class="home-feature-title">'.s("Ouvretaferme est un logiciel libre et gratuit qui contribue à l'autonomie des fermes").'</h3>';

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
			echo '<div class="home-feature-for">';
				echo s("Adapté à toutes les productions");
			echo '</div>';
			echo '<div class="home-feature-buttons">';
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
			echo '<div class="home-feature-for">';
				echo s("Maraichage  ·  Arboriculture  ·  Floriculture");
			echo '</div>';
			echo '<div class="home-feature-buttons">';
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
				echo '<li>'.s("Utilisez un logiciel de caisse pour vos marchés").'</li>';
				echo '<li>'.s("Créez le site internet de votre ferme").'</li>';
			echo '</ul>';
			echo '<div class="home-feature-for">';
				echo s("Envie d'essayer Ouvretaferme ?");
			echo '</div>';
			echo '<div class="home-feature-buttons">';
				echo '<a href="/user/signUp" class="btn btn-primary">'.Asset::icon('person-fill').' '.s("Créer un compte").'</a>';
			echo '</div>';

		echo '</div>';

	echo '</div>';

	echo '<h2>'.s("Qui utilise Ouvretaferme et pourquoi ?").'</h2>';

	echo '<div class="home-profiles">';
		echo '<div class="home-profile">';
			echo '<div class="home-profile-header">';
				echo '<div>'.Asset::image('main', 'profile/carotte.jpg', ['class' => 'home-profile-image']).'</div>';
				echo '<div>';
				//	echo '<h4>'.s("Les Jardins de Tallende (63)").'</h4>';
					echo '<h3>'.s("Maraichage").'</h3>';
				echo '</div>';
			echo '</div>';
			echo '<p>'.s("Vincent est un maraicher diversifié qui conçoit son plan de culture avec Ouvretaferme pour la saison en respectant ses rotations. En saison, il utilise le planning pour se libérer de sa charge mentale et enregistre son temps de travail pour comprendre là où il peut améliorer son système. La nuit, il est aussi le développeur principal de Ouvretaferme.").'</p>';
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
			echo '<p>'.s("Ils commandent à leur producteurs préférés les produits qu'ils proposent cette semaine et récupèrent leur commande au lieu et à la date convenus. Ils paient en ligne ou sur place selon le choix du producteur !").'</p>';
		echo '</div>';
	echo '</div>';

	echo new \main\HomeUi()->getTraining();

	echo '<h2>'.s("En savoir plus sur Ouvretaferme").'</h2>';

	echo '<h3>'.s("Philosophie du projet 👩‍🌾").'</h3>';

	echo '<div class="home-story">';
		echo s("Le logiciel {siteName} a été lancé en 2021 pour combler l'absence d'un logiciel libre, gratuit et intuitif destiné aux producteurs en agriculture biologique. Conçu pour simplifier l'organisation du travail à la ferme, ce logiciel complet accompagne les producteurs agricoles, du plan de culture à la vente de leurs produits. Notre mission : fournir aux producteurs les outils nécessaires pour contribuer à réaliser les finalités des fermes.");
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

	if(Privilege::can('farm\access')) {

		echo new \main\HomeUi()->getFarms($data->cFarmUser);

		echo new \main\HomeUi()->getTraining();

		if($data->cFarmUser->notEmpty()) {
			echo new \main\HomeUi()->getBlog($data->eNews, TRUE);
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

new AdaptativeView('/presentation/producteur', function($data, MainTemplate $t) {

	$t->title = s("{siteName} - Pour les producteurs");
	$t->metaDescription = s("Présentation des fonctionnalités de {siteName} pour les producteurs. Découvrez tous les outils de planification, de vente en ligne, de communication et de gestion d'équipe !");
	$t->template = 'home-farmer';

	Asset::css('main', 'font-ptserif.css');

	Asset::css('main', 'home.css');

	$t->header = '<h4 class="home-domain">'.Lime::getDomain().'</h4>';
	$t->header .= '<h1>'.s("De la production à la vente").'</h1>';
	$t->header .= '<h4 class="home-domain">'.s("Découvrez les principales fonctionnalités du logiciel !").'</h4>';


	echo '<div class="home-presentation">';

		echo '<div>';
			echo '<h2 class="color-secondary">'.Asset::icon('basket').'<br/>'.s("Un logiciel pour vendre").'</h2>';
			echo '<div class="home-presentation-description">';
				echo '<ul>';
					echo '<li>'.s("<b>Gérez vos ventes pour les professionnels et les particuliers.</b><br/><small>Créez des ventes à partir de vos produits, gérez votre clientèle, choisissez vos prix. Imprimez des étiquettes de colisage si vous livrez aux professionnels. Exporter les ventes du jour au format PDF pour préparer vos livraisons.</small>").'</li>';
					echo '<li>'.s("<b>Utilisez le logiciel de caisse intégré.</b><br/><small>Utilisez le logiciel de caisse avec une tablette ou un téléphone pour préparer vos marchés et saisir vos ventes directement pendant le marché. Pour chaque vente, visualisez ce que le client a acheté et le montant qu'il doit vous régler. Simple et efficace.</small>").'</li>';
					echo '<li>'.s("<b>Créez des boutiques en ligne.</b><br/><small>Permettez à vos clients de passer commande en ligne et de récupérer leur colis à la date et l'endroit convenus, ou bien livrez-les à domicile selon vos préférences. Activez si vous le souhaitez le paiement par carte bancaire sans commission sur les ventes.</small>").'</li>';
					echo '<li>'.s("<b>Partagez vos boutiques en ligne avec d'autres producteurs.</b><br/><small>Vendez à plusieurs sur la même boutique pour partager vos créneaux de vente et simplifier l'expérience pour vos clients.</small>").'</li>';
					echo '<li>'.s("<b>Éditez des mercuriales pour vos clients professionnels.</b><br/><small>Créez des boutiques en ligne exclusivement réservées à vos clients professionnels. Personnalisez les prix et les disponibilités par client.</small>").'</li>';
					echo '<li>'.s("<b>Pilotez vos stocks.</b><br/><small>Choisissez les produits pour lesquels vous souhaitez avoir un suivi des stocks. Les récoltes et les ventes que vous saisissez impactent automatiquement le stock et vous savez toujours ce qui vous reste à vendre.</small>").'</li>';
					echo '<li>'.s("<b>Éditez vos documents de vente au format PDF.</b><br/><small>Créez facilement les devis, bons de livraisons et factures de vos ventes. Créez toutes les factures du mois en une seule fois. Envoyez-les en un clic par e-mail à vos clients.</small>").'</li>';
				echo '</ul>';
			echo '</div>';
		echo '</div>';

		echo '<div>';
			echo '<h2 class="color-production">'.Asset::icon('leaf').'<br/>'.s("Un logiciel pour produire").'</h2>';
			echo '<div class="home-presentation-description">';
				echo '<ul>';
					echo '<li>'.s("<b>Planifiez votre saison de culture en concevant vos plan de culture et plan d'assolement.</b><br/><small>Gérez les variétés, la longueur des planches, les surfaces, les densités, les objectifs de récolte et les associations de cultures. Enregistrez et retrouvez facilement toutes les informations liées à vos séries de cultures. De plus, un prévisionnel financier vous aide à estimer vos ventes en fonction de votre plan de culture et de vos prévisions !</small>").'</li>';
					echo '<li>'.s("<b>Maîtrisez votre temps de travail.</b><br/><small>Que ce soit à la ferme avec votre téléphone ou le soir sur l'ordinateur, un planning hebdomadaire ou quotidien vous permet de faire le suivi des interventions planifiées et réalisées sur la semaine. Renseignez facilement votre temps de travail pour comprendre là où passe votre temps.</small>").'</li>';
					echo '<li>'.s("<b>Suivez précisément vos rotations sur votre parcellaire.</b><br/><small>Choisissez vos critères pour les rotations et vérifiez en un coup d'oeil les planches qui correspondent à ces critères. Pratique pour éviter de mettre vos cultures aux mêmes emplacements trop souvent !</small>").'</li>';
					echo '<li>'.s("<b>Collaborez avec votre équipe.</b><br/><small>Invitez votre équipe sur l'espace de votre ferme et gérez les droits de chaque personne.</small>").'</li>';
					echo '<li>'.s("<b>C'est adapté à toutes les productions.</b><br/><small>{siteName} vous accompagne en maraichage, floriculture, arboriculture ou même en production de semences.</small>").'</li>';
					echo '<li>'.s("<b>Et aussi...</b><br/><small>Consultez les quantités de semences et plants à produire ou commander. Créez des itinéraires techniques réutilisables saison après saison. Ajoutez des photos pour vous souvenir de vos cultures. Enregistrez le matériel disponible à la ferme pour l'utiliser dans vos interventions...</small>").'</li>';
				echo '</ul>';
			echo '</div>';
		echo '</div>';

	echo '</div>';

	echo '<div class="home-presentation">';

		echo '<div>';
			echo '<h2>'.Asset::icon('megaphone').'<br/>'.s("Un logiciel pour communiquer").'</h2>';
			echo '<div class="home-presentation-description">';
				echo '<ul>';
					echo '<li>'.s("<b>Créez le site internet de votre ferme.</b><br/><small>Créez autant de pages que vous voulez sur votre nouveau site et personnalisez le thème graphique. Vous pouvez même avoir un nom de domaine si vous le souhaitez.</small>").'</li>';
					echo '<li>'.s("<b>Aucune connaissance technique n'est nécessaire.</b><br/><small>Toutes les étapes de création de votre site internet se font depuis votre téléphone ou votre ordinateur.</small>").'</li>';
					echo '<li>'.s("<b>Pas de publicité.</b>").'</li>';
				echo '</ul>';
			echo '</div>';
		echo '</div>';

		echo '<div>';
			echo '<h2>'.Asset::icon('send').'<br/>'.s("Un logiciel pour améliorer vos pratiques").'</h2>';
			echo '<div class="home-presentation-description">';
				echo '<ul>';
					echo '<li>'.s("<b>Accédez à de nombreux graphiques et statistiques.</b><br/><small>Visualisez les résultats de votre plan de culture, votre temps de travail et vos ventes. Retournez dans le passé pour mesurer vos progrès. Comprenez ce qui vous prend du temps pour améliorer vos pratiques.</small>").'</li>';
					echo '<li>'.s("<b>Connaissez votre prix de revient pour chaque culture.</b><br/><small>Avec le temps de travail et les ventes que vous avez saisis, calculez vos prix de revient pour mieux définir vos prix de vente.</small>").'</li>';
					echo '<li>'.s("<b>Exportez vos données au format CSV.</b><br/><small>Manipulez vos chiffres de vente ou de temps de travail dans un tableur pour tirer partie de vos données !</small>").'</li>';
				echo '</ul>';
			echo '</div>';
		echo '</div>';

	echo '</div>';

	echo '<br/>';

	echo '<div class="text-center">';
		echo '<a href="'.OTF_DEMO_URL.'/ferme/'.\farm\Farm::DEMO.'/series?view=area" class="btn btn-secondary btn-lg">'.s("Explorez la ferme démo pour découvrir le service").'</a>';
	echo '</div>';
	echo '<br/>';
	echo '<br/>';

	echo new \main\HomeUi()->getPoints();

	echo '<h3 class="mt-2">'.s("Principe de gratuité").'</h3>';

	echo '<div class="home-story">';
		echo s("L'accès à toutes les fonctionnalités de {siteName} est libre et gratuit pour les producteurs sous signe de qualité <i>Agriculture biologique</i> ou <i>Nature & Progrès</i>. Pour les autres, reportez-vous aux <link>conditions d'utilisation du service</link>.", ['link' => '<a href="/presentation/service">']);
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

	if(currentDate() <= Setting::get('main\limitTraining')) {

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

new AdaptativeView('/presentation/legal', function($data, MainTemplate $t) {

	$t->title = s("Mentions légales");
	$t->metaNoindex = TRUE;
	$t->template = 'home-legal';

	Asset::css('main', 'font-ptserif.css');

	Asset::css('main', 'home.css');

	$t->header = '<h1>'.s("Mentions légales").'</h1>';

	echo '<h2>'.s("Directeur de la publication").'</h2>';
	echo '<p>'.s("Un maraîcher (ancien informaticien) du Puy-de-Dôme.").'</p>';

	echo '<br/>';

	echo '<h2>'.s("Hébergeur").'</h2>';
	echo '<ul>';
		echo '<li>'.s("Siège social : 2 rue Kellermann, 59100 Roubaix").'</li>';
		echo '<li>'.s("Numéro de téléphone : 09 72 10 10 07").'</li>';
	echo '</ul>';

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
