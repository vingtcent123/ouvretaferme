<?php
namespace main;

class LegalUi {

	public function tos() : string {

		$h = '<h2>'.s("Principe de gratuité").'</h2>';
		$h .= '<p>'.s("L'accès à toutes les fonctionnalités de {siteName} est libre et gratuit pour les producteurs dont la ferme est convertie <i>intégralement à l'Agriculture biologique</i> ou sous mention <i>Nature & Progrès</i>. <b>L'utilisation du site n'est pas autorisée pour les producteurs qui ne respectent aucun de ces deux critères.</b>").'</p>';

		$h .= '<br/>';
		$h .= '<h2>'.s("Données personnelles").'</h2>';
		$h .= '<p>'.s("Les données que vous saisissez sur {siteName} vous appartiennent et vous en avez seul la responsabilité. Elles ne sont ni analysées, ni réutilisées, ni revendues à des tiers. Il n'y a pas d'outil de mesure de trafic sur {siteName}. Les seuls cookies qui sont déposés dans votre navigateur sont ceux qui permettent de s'assurer que vous êtes bien connecté au site.").'</p>';

		$h .= '<br/>';
		$h .= '<h2>'.s("Garanties").'</h2>';
		$h .= '<p>'.s("Le site {siteName} est un projet développé bénévolement au service des producteurs en agriculture biologique. La conséquence immédiate est qu'il n'y a aucune garantie sur le bon fonctionnement du service ou sur la pérennité de vos données et vous utilisez le service à vos risques et périls. Si vous perdez des données, quelqu'en soit la cause, y compris à cause d'une erreur de notre part ou d'un arrêt du service, vous ne pourrez prétendre à aucun dédommagement, compensation ou droit particulier.").'</p>';

		$h .= '<br/>';
		$h .= '<h2>'.s("Fonctionnalités").'</h2>';
		$h .= '<p>'.s("Le site {siteName} est en perpétuelle amélioration. De nouvelles fonctionnalités sont développées très régulièrement, et peuvent parfois modifier les habitudes des utilisateurs. Tenez-en compte dans votre utilisation du site. L'intégrité de vos données est préservée lors des mises à jour du site, dans la limite des garanties exprimées plus haut.").'</p>';

		$h .= '<br/>';
		$h .= '<h2>'.s("Licence d'utilisation du code source").'</h2>';
		$h .= '<p>'.s("Un programme informatique qui utilise tout ou partie du code source de Ouvretaferme doit, qu'il soit installé sur un serveur privé ou public :").'</p>';

		$h .= '<ul>';
			$h .= '<li>'.s("être en lien avec la production agricole,").'</li>';
			$h .= '<li>'.s("être utilisé exclusivement par des exploitations agricoles converties intégralement à l'agriculture biologique selon le règlement européen 2018/848,").'</li>';
			$h .= '<li>'.s("être proposé gratuitement,").'</li>';
			$h .= '<li>'.s("ne pas inclure d'outil de mesure d'audience,").'</li>';
			$h .= '<li>'.s("ne pas inclure de publicité,").'</li>';
			$h .= '<li>'.s("ne pas faire commerce des données récoltées auprès des utilisateurs,").'</li>';
			$h .= '<li>'.s("être distribué sous la présente licence.").'</li>';
		$h .= '</ul>';
		$h .= '<p>'.s("Toute modification effectuée sur le code source de Ouvretaferme, même si elle est réalisée dans un cadre privé, doit être partagée publiquement par son auteur sur le dépôt officiel du code source. L'intégralité du code source de tout programme informatique qui utilise tout ou partie du code source de Ouvretaferme doit être publié publiquement sous la présente licence, y compris les parties du code source qui ne proviennent pas de Ouvretaferme.").'</p>';
		$h .= '<p><a href="https://github.com/vingtcent123/ouvretaferme" class="btn btn-outline-primary">'.s("Voir le dépôt du code source").'</a></p>';

		return $h;

	}

	public function faq() : string {


		$h = '<ul>';
			$h .= '<li><a href="/presentation/faq#why">'.s("Pourquoi {siteName} ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#how">'.s("Comment accéder au service ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#much">'.s("Combien ça coûte ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#help">'.s("Comment obtenir de l'aide pour utiliser le site ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#sales">'.s("Comment vendre ma production avec {siteName} ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#online">'.s("Comment utiliser le paiement par carte bancaire sur la boutique en ligne ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#online">'.s("Existe t-il des formations à l'utilisation de {siteName} ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#data">'.s("Comment sont gérées vos données ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#organic">'.s("Puis-je utiliser {siteName} si ma ferme n'est pas en AB ou sous mention N&P ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#feature">'.s("Puis-je proposer une nouvelle fonctionnalité ?").'</a></li>';
		$h .= '</ul>';

		$h .= '<div id="why"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Pourquoi {siteName} ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("La plateforme ouvretaferme.org est née du constat qu'il n'existait pas de solution libre et simple d'utilisation pour permettre aux producteurs d'organiser le travail dans leur ferme, de la planification des cultures jusqu'à la vente. Le développement du site a commencé en 2019 sur cette base, avec comme point de départ le besoin exprimé par une petite ferme maraîchère située dans le Puy-de-Dôme.").'</p>';

			$h .= '<p>'.s("Depuis 2022, la plateforme est diffusée plus largement et a été adaptée pour répondre aux besoins du plus grand nombre de fermes possible.").'</p>';

			$h .= '<h4><u>'.s("Ce que nous voulons").'</u></h4>';
			$h .= '<ul>';
				$h .= '<li>'.s("Donner des outils pour contribuer à réaliser les finalités des fermes").'</li>';
				$h .= '<li>'.s("Réduire la charge mentale des maraîchers").'</li>';
				$h .= '<li>'.s("Développer l'agriculture biologique").'</li>';
			$h .= '</ul>';

			$h .= '<h4><u>'.s("Ce que nous ne voulons pas").'</u></h4>';
			$h .= '<ul>';
				$h .= '<li>'.s("Un outil qui décide à la place des maraîchers").'</li>';
				$h .= '<li>'.s("Développer l'agriculture conventionnelle").'</li>';
			$h .= '</ul>';

		$h .= '</div>';

		$h .= '<div id="how"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment accéder au service ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Le logiciel est accessible par un simple navigateur internet sur {url}. Dès lors que vous disposez d'une connexion à internet, vous pouvez y accéder indifféremment depuis votre ordinateur ou votre téléphone..", ['url' => \Lime::getDomain()]).'</p>';
			$h .= '<p>'.s("L'interface du site s'adapte automatiquement à la taille de l'écran. Les interfaces ont été pensées pour que vous puissez utiliser directement {siteName} au champ avec votre téléphone sans avoir besoin d'imprimer des feuilles volantes avec votre plan de culture ou vos listes de récoltes à faire.").'</p>';

		$h .= '</div>';

		$h .= '<div id="much"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Combien ça coûte ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<h4>'.s("Pour le développement, {siteName} c'est :").'</h4>';
			$h .= '<ul>';
				$h .= '<li>'.s("300 € de charges par an pour le serveur, le nom de domaine et l'envoi des e-mails").'</li>';
				$h .= '<li>'.s("1500 heures de travail par an pour le code informatique, soit l'équivalent de 60 000 € par an s'il fallait recruter un informaticien pour cela").'</li>';
			$h .= '</ul>';

			$h .= '<h4>'.s("Pour les producteurs, {siteName} c'est :").'</h4>';
			$h .= '<ul>';
				$h .= '<li>'.s("0 € pour utiliser le service").'</li>';
				$h .= '<li>'.s("0 % de commission sur vos ventes").'</li>';
			$h .= '</ul>';

			$h .= '<p>'.s("Les coûts d'opérations sont supportables sans qu'il soit nécessaire de faire payer le service. Le temps de développement restera lui toujours bénévole. À l'avenir, si les coûts d'opérations deviennent trop importants, il pourra être envisageable de faire appel à des contributions sous la forme du volontariat, mais ce n'est pas à l'ordre du jour pour le moment.").'</p>';

		$h .= '</div>';

		$h .= '<div id="help"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment obtenir de l'aide pour utiliser le site ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Le temps bénévole consacré à développer {siteName} est majoritairement utilisé pour implémenter de nouvelles fonctionnalités. Hormis des formations proposées de temps en temps en Auvergne, il n'est pas possible d'assurer de support individuel.").'</p>';
			$h .= '<p>'.s("Néanmoins, vous avez accès à différentes ressources :").'</p>';
			$h .= '<ul>';
				$h .= '<li>'.s("Un blog pour suivre l'actualité du site").'<br/><a href="https://blog.ouvretaferme.org/" class="btn btn-secondary mb-1">'.s("Voir le blog").'</a></li>';
				$h .= '<li>'.s("Un site de démo pour voir comment est utilisée la plateforme par la ferme à l'origine de {siteName} et dont les données ont été anonymisées").'<br/><a href="https://demo.ouvretaferme.org/" class="btn btn-secondary mb-1">'.s("Voir la démo").'</a></li>';
				$h .= '<li>'.s("Un salon de discussion ouvert à tous").'<br/><a href="https://app.element.io/#/room/#ouvretaferme:matrix.org" class="btn btn-secondary mb-1">'.s("Voir le salon de discussion").'</a></li>';
				$h .= '<li>'.s("Probablement des collègues qui utilisent l'outil et pourraient vous aider !").'</li>';
			$h .= '</ul>';

			$h .= '<p>'.s("Si malgré cela, vous avez des problèmes avec le site ou n'êtes pas satisfait des fonctionnalités ou de l'ergonomie, <b>n'utilisez pas {siteName}</b>. Il y a des alternatives payantes (elzeard.co, Brinjel), gratuite et Open Source (Qrop), le tableur (LibreOffice), le couple crayon / papier ou encore votre mémoire.").'</p>';

		$h .= '</div>';

		$h .= '<div id="sales"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment vendre ma production avec {siteName} ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Il existe plusieurs façon de vendre votre production avec {siteName} :").'</p>';
			$h .= '<ul>';
				$h .= '<li>'.s("Vous pouvez créer des ventes pour des clients particuliers et professionnels et éditer des devis, des bons de livraison et des factures").'</li>';
				$h .= '<li>'.s("Un mode <i>Marché</i> permet d'enregistrer directement vos ventes (avec une tablette de préférence) lorsque vous vendez sur un marché ou à la ferme").'</li>';
				$h .= '<li>'.s("Une boutique en ligne permet de vendre vos produits à vos clients avec une livraison en point de retrait, à la ferme ou à domicile").'</li>';
			$h .= '</ul>';

			$h .= '<p>'.s("Nous vous invitons à passer du temps à explorer et bien tester les fonctionnalités liées à la commercialisation avant de les utiliser.").'</p>';

		$h .= '</div>';

		$h .= '<div id="online"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment utiliser le paiement par carte bancaire sur la boutique en ligne ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("{siteName} ne prend aucune commission sur le paiement par carte bancaire, seules vous seront facturées des commissions prélevées par le prestataire de paiement <i>Stripe</i> et inférieures à 2 %. Vous gérez le paiement directement avec ce prestataire et {siteName} n'intervient à aucun moment dans la transaction et n'a pas connaissance des données bancaires de vos clients.").'</p>';
			$h .= '<p>'.s("La configuration du paiement en ligne peut être déroutante, nous vous recommandons de bien lire les instructions et de vous faire aider par une personne qui maîtrise bien les outils informatiques si vous n'êtes pas à l'aise.").'</p>';
			$h .= '<p>'.s("La contrepartie naturelle de l'absence de commission prélevée par {siteName} sur vos ventes est une absence de support technique individuel sur cette fonctionnalité.").'</p>';

		$h .= '</div>';

		$h .= '<div id="online"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Existe t-il des formations à l'utilisation de {siteName} ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Des formations à l'utilisation de {siteName} sont parfois organisées à Clermont-Ferrand par la FRAB AuRA. Vous pouvez vous rapprocher de cet organisme si vous êtes intéressé.").'</p>';
			$h .= '<p>'.s("Si vous comptez animer une formation à l'utilisation du site, n'hésitez pas à vous rapprocher de nous sur le salon de discussion pour que nous puissions référencer votre formation, si celle-ci est finançable Vivea.").'</p>';

		$h .= '</div>';

		$h .= '<div id="data"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment sont gérées vos données ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Les données relatives à votre ferme vous appartiennent, vous en êtes seul responsable. Cela concerne votre plan de culture, votre assolement, vos ventes, vos clients... et tout autre contenu relatif à votre ferme. Personne d'autre que vous n'a accès à ces données, à moins que vous ne donniez un consentement explicite, par exemple en invitant sur {siteName} des membres de votre équipe.").'</p>';
			$h .= '<p>'.s("Nos serveurs sont situés en France, et vos données restent donc en France. Des sauvegardes sont effectuées de manière régulière de façon à limiter le risque de perte de données.").'</p>';

		$h .= '</div>';

		$h .= '<div id="organic"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Puis-je utiliser {siteName} si ma ferme n'est pas intégralement en AB ou sous mention N&P ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Non, et cela signifie que vous ne pouvez pas utiliser {siteName} si vous pratiquez la bio rotationnelle. Vous pouvez par contre faire un choix d'avenir en convertissant votre ferme à l'agriculture biologique.").'</p>';

		$h .= '</div>';

		$h .= '<div id="feature"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Puis-je proposer une nouvelle fonctionnalité ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Votre demande de nouvelle fonctionnalité ne sera pas prise en compte. Ce ne sont pas les idées qui manquent sur {siteName} mais plutôt le temps disponible pour ajouter de nouvelles fonctionnalités. La feuille de route est déjà bien chargée, et il est d'ailleurs probable que votre besoin s'y trouve déjà.").'</p>';
			$h .= '<p><a href="https://blog.ouvretaferme.org/feuille-de-route" class="btn btn-secondary mb-1">'.s("Voir la feuille de route").'</a></p>';

			$h .= '<p>'.s("Les priorités de développement sont choisies en fonction des besoins d'un groupe de maraîchers situé en Auvergne, des affinités du développeur et du temps disponible. Si vous pensez qu'il manque des fonctionnalités structurantes pour votre ferme, <b>n'utilisez pas {siteName}</b> et privilégiez des solutions qui vous correspondront mieux.").'</p>';

		$h .= '</div>';

		return $h;
	}

}
?>
