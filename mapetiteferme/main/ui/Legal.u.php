<?php
namespace main;

class LegalUi {

	public function tos() : string {

		$h = '<h2>'.s("Principe").'</h2>';
		$h .= '<p>'.s("L'accès à toutes les fonctionnalités de {siteName} est libre pour tous les agriculteurs. Il est possible de tester les fonctionnalités du site dans des conditions limitées avant d'opter pour un abonnement annuel.").'</p>';

		$h .= '<br/>';
		$h .= '<h2>'.s("Données personnelles").'</h2>';
		$h .= '<p>'.s("Les données que vous saisissez sur {siteName} vous appartiennent et vous en avez seul·e la responsabilité. Elles ne sont ni analysées, ni réutilisées, ni revendues à des tiers. Il n'y a pas d'outil de mesure de trafic sur {siteName}. Les seuls cookies qui sont déposés dans votre navigateur sont ceux qui permettent de s'assurer que vous êtes bien connecté·e au site.").'</p>';

		$h .= '<br/>';
		$h .= '<h2>'.s("Fonctionnalités").'</h2>';
		$h .= '<p>'.s("Le site {siteName} est en perpétuelle amélioration. De nouvelles fonctionnalités sont développées très régulièrement, notamment pour répondre au mieux aux besoins des agriculteurs. Ces nouveautés peuvent parfois modifier vos habitudes. Elles sont toujours expliquées sur le journal des modifications.").'</p>';

		$h .= '<br/>';
		$h .= '<h2>'.s("Abonnement").'</h2>';
		$h .= '<p>'.s("Pour utiliser toutes les fonctionnalités de {siteName}, un abonnement annuel vous est proposé. Cet abonnement est renouvelable chaque année.").'</p>';

		return $h;

	}

	public function engagements() : string {

		$h = '';

		$h .= '<p>'.s("{siteName} est développé au maximum dans le respect des bonnes pratiques d'éco-conception de site internet.").'</p>';

		$h .= '<h2>'.s("Hébergement").'</h2>';
		$h .= '<p>'.s("{siteName} est hébergé sur un serveur chez le prestataire OVH, qui s'engage sur son impact sur l'environnement et communique avec transparence sur ses actions. Vous pouvez trouver ci-après la documentation à ce sujet :").'</p>';
		$h .= '<ul>';
			$h .= '<li><a href="https://corporate.ovhcloud.com/fr/sustainability/" target="_blank">'.s("OVH : Engagement en faveur du développement durable").'&nbsp;'.\Asset::icon('box-arrow-up-right').'</a></li>';
			$h .= '<li><a href="https://corporate.ovhcloud.com/fr/sustainability/environment/" target="_blank">'.s("OVH : Indicateurs environnementaux").'&nbsp;'.\Asset::icon('box-arrow-up-right').'</a></li>';
			$h .= '<li><a href="https://corporate.ovhcloud.com/sites/default/files/2024-12/carbon_balance_2024_ovhcloud_fr.pdf" target="_blank">'.s("OVH : Bilan carbone 2024 (PDF, 650 Ko)").'&nbsp;'.\Asset::icon('box-arrow-up-right').'</a></li>';
			$h .= '<li><a href="https://www.thegreenwebfoundation.org/green-web-check/?url=www.ouvretaferme.org" target="_blank">'.s("{siteName} est alimenté par une énergie renouvelable (source : Green Web Foundation)").'&nbsp;'.\Asset::icon('box-arrow-up-right').'</a></li>';
		$h .= '</ul>';

		$h .= '<h2>'.s("Impact environnemental").'</h2>';

		$h .= '<ul>';
			$h .= '<li>'.s("{siteName} est hébergé en France pour limiter l'usage des infrastructures mondiales.").'</li>';
			$h .= '<li><a href="https://www.websitecarbon.com/website/mapetiteferme-app-noairtable1/" target="_blank">'.s("L'empreinte carbone de {siteName} est évaluée à B, soit plus propre que 76% de toutes les pages web connues (source: Website Carbon Calculator)").'&nbsp;'.\Asset::icon('box-arrow-up-right').'</a></li>';
			$h .= '<li>'.s("Seulement 0,23g de CO2 est produit à chaque visite (source : Website Carbon Calculator)").'</li>';
			$h .= '<li><a href="https://www.ecoindex.fr/resultat/?id=e671de04-7209-4570-bd8f-ad5bb2e026d9" target="_blank">'.s("Performance environnementale de {siteName} par EcoIndex, proche de la perfection").'&nbsp;'.\Asset::icon('box-arrow-up-right').'</a></li>';
		$h .= '</ul>';

		$h .= '<h2>'.s("Pratiques de développement").'</h2>';

		$h .= '<p>'.s("{siteName} est structuré autour d'une <b>technologie économe</b>. Chaque ligne de code est mûrement réfléchie et un ensemble de principes est respecté tout au long des développements :").'</p>';

		$h .= '<ul>';
			$h .= '<li>'.s("Respect autant que possible des <link>115 bonnes pratiques pour une écoconception web {icon}</link>, préconisées par le collectif greenIT.", ['link' => '<a href="https://collectif.greenit.fr/ecoconception-web/">', 'icon' => \Asset::icon('box-arrow-up-right')]).'</li>';
			$h .= '<li>'.s("Design épuré : {siteName} s'adapte à l'écran (design <i>responsive</i>, utilisation d'icônes plutôt que d'images, si des images sont nécessaires, elles sont compressées avant usage, etc.)").'</li>';
			$h .= '<li>'.s("Fonctionnalités utiles : pour un service maintenable et utilisable de tous, seules les fonctionnalités utiles sont développées et conservées.").'</li>';
			$h .= '<li>'.s("Requêtes limitées : une attention particulière est portée au nombre d'interactions entre l'ordinateur de l'utilisateur et le serveur pour éviter de gaspiller de l'énergie.").'</li>';
		$h .= '</ul>';

		return $h;

	}

	public function faq() : string {

		$h = '<ul>';
			$h .= '<li><a href="/presentation/faq#why">'.s("Pourquoi {siteName} ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#how">'.s("Comment accéder au service ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#much">'.s("Combien ça coûte ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#subscribe">'.s("Comment m'abonner ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#help">'.s("Comment obtenir de l'aide pour utiliser le site ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#online">'.s("Existe t-il des formations à l'utilisation de {siteName} ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#data">'.s("Comment sont gérées vos données ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#feature">'.s("Puis-je proposer une nouvelle fonctionnalité ?").'</a></li>';
		$h .= '</ul>';

		$h .= '<div id="why"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Pourquoi {siteName} ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s(
				"La plateforme mapetiteferme.app est née du constat qu'il n'est pas simple de gérer sa comptabilité dans le monde agricole. De plus, avec la <link>généralisation de la facturation électronique {icon}</link>, de nouvelles contraintes réglementaires vont se poser dans les prochains mois. {siteName} a été conçu pour que ces changements soient les plus simples pour vous.",
					['link' => '<a href="https://entreprendre.service-public.fr/actualites/A15683" target="_blank">', 'icon' => \Asset::icon('box-arrow-up-right')]
				).'</p>';

		$h .= '</div>';

		$h .= '<div id="how"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment accéder au service ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Le logiciel est accessible par un simple navigateur internet sur {url}. Dès lors que vous disposez d'une connexion à internet, vous pouvez y accéder indifféremment depuis votre ordinateur ou votre téléphone.", ['url' => \Lime::getDomain()]).'</p>';
			$h .= '<p>'.s("L'interface du site s'adapte automatiquement à la taille de votre écran, mais nous vous recommandons un ordinateur de bureau (ou ordinateur portable) pour un usage plus confortable du site.").'</p>';

		$h .= '</div>';

		$h .= '<div id="much"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Combien ça coûte ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Le site est aujourd'hui maintenu par une ingénieure en informatique à temps plein, épaulée par des experts agricoles et des experts en comptabilité.").'</p>';
			$h .= '<ul>';
				$h .= '<li>'.s("Une version d'essai gratuite est disponible, avec des accès limités aux fonctionnalités du service.").'</li>';
				$h .= '<li>'.s("Pour un accès complet à toutes les fonctionnalités du site et au support utilisateur, l'abonnement coûte {subscriptionPrice} €HT.", ['subscriptionPrice' => 100]).'</li>';
			$h .= '</ul>';

		$h .= '</div>';

		$h .= '<div id="subscribe"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment m'abonner ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Vous pouvez vous abonner à {siteName} directement sur le site, dans vos options de compte.").'</p>';
			// TODO ajouter un laius sur le prestataire de paiement (français, confidentialité des données etc.)

		$h .= '</div>';

		$h .= '<div id="help"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment obtenir de l'aide pour utiliser le site ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Plusieurs ressources sont disponibles :").'</p>';
			$h .= '<ul>';
				$h .= '<li>'.s("Un journal des modifications pour suivre l'actualité du site").'<br/><a href="https://'.\Lime::getDomain().'/updates" class="btn btn-secondary mb-1">'.s("Voir les actualités").'</a></li>';
				$h .= '<li>'.s("Un salon de discussion ouvert à tous").'<br/><a href="https://app.element.io/#/room/#mapetiteferme:matrix.org" class="btn btn-secondary mb-1">'.s("Voir le salon de discussion").'</a></li>';
				$h .= '<li>'.s("Probablement des collègues qui utilisent l'outil et pourraient vous aider !").'</li>';
			$h .= '</ul>';

		$h .= '</div>';

		$h .= '<div id="online"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Existe t-il des formations à l'utilisation de {siteName} ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Des formations à l'utilisation de {siteName} seront prochainement organisées. Rapprochez-vous de votre référent comptable, ou contactez-nous pour en discuter.").'</p>';

		$h .= '</div>';

		$h .= '<div id="data"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment sont gérées vos données ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Les données relatives à votre ferme vous appartiennent, vous en êtes seul·e responsable. Personne d'autre que vous n'a accès à ces données, à moins que vous ne donniez un consentement explicite, par exemple en invitant sur {siteName} des membres de votre équipe.").'</p>';
			$h .= '<p>'.s("Nos serveurs sont situés en France, et vos données restent donc en France. Des sauvegardes sont effectuées de manière régulière de façon à limiter le risque de perte de données.").'</p>';

		$h .= '</div>';

		$h .= '<div id="feature"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Puis-je proposer une nouvelle fonctionnalité ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Une feuille de route sera bientôt mise à disposition pour que vous puissiez voir si vos idées seront disponibles dans les prochains mois sur le site.").'</p>';

		$h .= '</div>';

		return $h;
	}

}
?>
