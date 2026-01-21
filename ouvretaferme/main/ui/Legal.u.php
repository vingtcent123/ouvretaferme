<?php
namespace main;

class LegalUi {

	public function tos() : string {

		$h = '<h2>'.s("Fonctionnalités").'</h2>';
		$h .= '<p>'.s("L'accès aux modules de <sale>VENTE</sale> et de <production>PRODUCTION</production> est libre et gratuit pour :", ['sale' => '<b class="color-commercialisation">', 'production' => '<b class="color-production">']).'</p>';
		$h .= '<ul>';
			$h .= '<li>'.s("les particuliers (jardiniers, étudiants, porteurs de projet...) qui ne sont pas installés comme agriculteurs et ne font pas commerce de leur production").'</li>';
			$h .= '<li>'.s("les agriculteurs dont la ferme est convertie intégralement à l'Agriculture biologique, en cours de conversion vers l'Agriculture biologique ou sous mention Nature & Progrès,").'</li>';
			$h .= '<li>'.s("les établissements scolaires.").'</li>';
		$h .= '</ul>';
		$h .= '<p>'.s("L'accès au module de <accounting>COMPTABILITÉ</accounting> ainsi que l'accès au logiciel pour les producteurs non concernés par le paragraphe précédent est possible après adhésion à l'association Ouvretaferme.", ['accounting' => '<b class="color-accounting">']).'</p>';
		$h .= '<p>';
			$h .= '<a href="/presentation/adhesion" class="btn btn-outline-primary">'.s("Voir plus d'informations sur l'adhésion").'</a>';
		$h .= '</p>';

		$h .= '<br/>';
		$h .= '<h2>'.s("Données personnelles").'</h2>';
		$h .= '<p>'.s("Les données que vous saisissez sur {siteName} vous appartiennent et vous en avez seul la responsabilité. Elles ne sont ni analysées, ni réutilisées, ni revendues à des tiers. Il n'y a pas d'outil de mesure de trafic sur {siteName}. Les seuls cookies qui sont déposés dans votre navigateur sont ceux qui permettent de s'assurer que vous êtes bien connecté au site.").'</p>';

		$h .= '<br/>';
		$h .= '<h2>'.s("Garanties").'</h2>';
		$h .= '<p>'.s("Le site {siteName} est un projet développé bénévolement au service des producteurs en circuits courts. La conséquence immédiate est qu'il n'y a aucune garantie sur le bon fonctionnement du service ou sur la pérennité de vos données et vous utilisez le service à vos risques et périls. Si vous perdez des données, quelqu'en soit la cause, y compris à cause d'une erreur de notre part ou d'un arrêt du service, vous ne pourrez prétendre à aucun dédommagement, compensation ou droit particulier.").'</p>';

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
			$h .= '<li><a href="/presentation/faq#who">'.s("Qui est derrière Ouvretaferme ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#help">'.s("Comment obtenir de l'aide pour utiliser le site ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#sales">'.s("Comment vendre ma production avec {siteName} ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#online">'.s("Comment utiliser le paiement par carte bancaire sur la boutique en ligne ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#online">'.s("Existe t-il des formations à l'utilisation de {siteName} ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#data">'.s("Comment sont gérées les données de ma ferme ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#cookies">'.s("Pourquoi n'y a-t-il pas de bandeau <i>Youpi c'est nous les cookies</i> quand je me connecte ?").'</a></li>';
			$h .= '<li><a href="/presentation/faq#feature">'.s("Puis-je proposer une nouvelle fonctionnalité ?").'</a></li>';
		$h .= '</ul>';

		$h .= '<div id="why"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Pourquoi {siteName} ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("La plateforme ouvretaferme.org est née du constat qu'il n'existait pas de logiciel ouvert, gratuit et simple d'utilisation pour permettre aux producteurs d'organiser le travail dans leur ferme, de la production jusqu'à la vente. Le développement du site a commencé en 2019 sur cette base, avec comme point de départ le besoin exprimé par une petite ferme en maraichage diversifié située dans le Puy-de-Dôme.").'</p>';

			$h .= '<p>'.s("Depuis 2022, la plateforme est diffusée plus largement et a été adaptée pour répondre aux besoins du plus grand nombre de fermes possible.").'</p>';

			$h .= '<h4><u>'.s("Ce que nous voulons").'</u></h4>';
			$h .= '<ul>';
				$h .= '<li>'.s("Donner des outils pour contribuer à réaliser les finalités des fermes").'</li>';
				$h .= '<li>'.s("Réduire la charge mentale des producteurs").'</li>';
				$h .= '<li>'.s("Développer les circuits courts").'</li>';
			$h .= '</ul>';

			$h .= '<h4>'.s("Nous ne voulons pas un outil qui décide à la place des producteurs.").'</h4>';

		$h .= '</div>';

		$h .= '<div id="how"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment accéder au service ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Le logiciel est accessible par un simple navigateur internet sur {url}. Dès lors que vous disposez d'une connexion à internet, vous pouvez y accéder indifféremment depuis votre ordinateur ou votre téléphone..", ['url' => \Lime::getDomain()]).'</p>';
			$h .= '<p>'.s("L'interface du site s'adapte automatiquement à la taille de l'écran. Les interfaces ont été pensées pour que vous puissez utiliser directement {siteName} au champ avec votre téléphone sans avoir besoin d'imprimer des feuilles volantes avec votre plan de culture ou vos listes de récoltes à faire.").'</p>';

		$h .= '</div>';

		$h .= '<div id="who"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Qui est derrière Ouvretaferme ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Initialement créé par un maraîcher bénévole, Ouvretaferme est géré par une association depuis 2025.<br/>Ouvretaferme est donc un projet collectif au service des producteurs et productrices en circuits courts.").'</p>';
			$h .= '<a href="'.\association\AssociationSetting::URL.'" class="btn btn-secondary">'.s("Découvrir l'association").'</a>';

		$h .= '</div>';

		$h .= '<div id="help"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment obtenir de l'aide pour utiliser le site ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Le temps bénévole consacré à développer {siteName} est majoritairement utilisé pour implémenter de nouvelles fonctionnalités. Il n'est pas possible d'assurer de support individuel.").'</p>';
			$h .= '<p>'.s("Néanmoins, vous avez accès à différentes ressources :").'</p>';
			$h .= '<ul>';
				$h .= '<li>'.s("Une documentation qui couvre quelques fonctionnalités").'<br/><a href="/doc/" class="btn btn-secondary mb-1">'.s("Voir la documentation").'</a></li>';
				$h .= '<li>'.s("Un blog pour suivre l'actualité du site").'<br/><a href="https://blog.ouvretaferme.org/" class="btn btn-secondary mb-1">'.s("Voir le blog").'</a></li>';
				$h .= '<li>'.s("Un site de démo pour voir comment est utilisée la plateforme par la ferme à l'origine de {siteName} et dont les données ont été anonymisées").'<br/><a href="https://demo.ouvretaferme.org/" class="btn btn-secondary mb-1">'.s("Voir la démo").'</a></li>';
				$h .= '<li>'.s("Un salon de discussion sur Discord ouvert à tous").'<br/><a href="https://discord.gg/bdSNc3PpwQ" class="btn btn-secondary mb-1">'.s("Voir le salon de discussion").'</a></li>';
				$h .= '<li>'.s("Probablement des collègues qui utilisent l'outil et pourraient vous aider !").'</li>';
			$h .= '</ul>';

			$h .= '<p>'.s("Si malgré cela, vous avez des problèmes avec le site ou n'êtes pas satisfait des fonctionnalités ou de l'ergonomie, <b>n'utilisez pas {siteName}</b>. Il y a des alternatives !").'</p>';

			$h .= '<h3>'.s("Alternatives pour la production").'</h3>';
			$h .= '<ul>';
				$h .= '<li>'.s("Gratuites et libres :").' <a href="https://greli.net/potaleger.html">Potaléger</a>, <a href="https://qrop.frama.io/">Qrop</a></li>';
				$h .= '<li>'.s("Payante et libre :").' <a href="https://brinjel.com/">Brinjel</a></li>';
				$h .= '<li>'.s("Commerciales :").' <a href="https://www.elzeard.co/">Elzeard</a>, <a href="https://heirloom.ag/">Heirloom</a></li>';
				$h .= '<li>'.s("Ou à défaut un tableur ou un crayon !").'</li>';
			$h .= '</ul>';

			$h .= '<h3>'.s("Alternatives pour la commercialisation").'</h3>';
			$h .= '<ul>';
				$h .= '<li>'.s("Gratuite et libre :").' <a href="https://latourneedesproducteurs.com/">La Tournée des Producteurs</a></li>';
				$h .= '<li>'.s("Un nombre incalculable d'initiatives payantes (Ciboulette, Socleo, Kuupanda, Coopcircuits, Cagette, Local.direct...)").'</li>';
			$h .= '</ul>';

		$h .= '</div>';

		$h .= '<div id="sales"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment vendre ma production avec {siteName} ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Il existe plusieurs façons de vendre votre production avec {siteName} :").'</p>';
			$h .= '<ul>';
				$h .= '<li>'.s("Vous pouvez créer des ventes pour des clients particuliers et professionnels et éditer des devis, des bons de livraison et des factures").'</li>';
				$h .= '<li>'.s("Un logiciel de caisse permet d'enregistrer directement vos ventes (avec une tablette de préférence) lorsque vous vendez sur un marché ou à la ferme").'</li>';
				$h .= '<li>'.s("Une boutique en ligne permet de vendre vos produits à vos clients particuliers ou professionnels avec une livraison en point de retrait, à la ferme ou à domicile").'</li>';
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

			$h .= '<p>'.s("Des formations à l'utilisation de {siteName} sont régulièrement organisées. L'association n'en assure pas directement  mais nous faisons la promotion de celles dont nous en avons connaissance.").'</p>';
			$h .= '<p>'.s("Si vous-même comptez animer une formation à l'utilisation du logiciel, n'hésitez pas à nous contacter pour que nous puissions la référencer, si celle-ci est finançable Vivea.").'</p>';

		$h .= '</div>';

		$h .= '<div id="data"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Comment sont gérées les données de ma ferme ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Les données relatives à votre ferme vous appartiennent, vous en êtes seul responsable. Cela concerne votre plan de culture, votre assolement, vos ventes, vos clients... et tout autre contenu relatif à votre ferme. Personne d'autre que vous n'a accès à ces données, à moins que vous ne donniez un consentement explicite, par exemple en invitant sur {siteName} des membres de votre équipe.").'</p>';
			$h .= '<p>'.s("Nos serveurs sont situés en France, et vos données restent donc en France. Des sauvegardes sont effectuées de manière régulière de façon à limiter le risque de perte de données.").'</p>';

		$h .= '</div>';

		$h .= '<div id="cookies"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Pourquoi n'y a-t-il pas de bandeau <i>Youpi c'est nous les cookies</i> quand je me connecte ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Nous ne collectons pas de données à travers ces fameux cookies, et il n'est par conséquent pas nécessaire de vous demander l'autorisation d'en manger.").'</p>';

		$h .= '</div>';

		$h .= '<div id="feature"></div>';
		$h .= '<br/>';
		$h .= '<h2>'.s("Puis-je proposer une nouvelle fonctionnalité ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<p>'.s("Votre demande de nouvelle fonctionnalité ne sera pas prise en compte. Ce ne sont pas les idées qui manquent sur {siteName} mais plutôt le temps disponible pour ajouter de nouvelles fonctionnalités. La feuille de route est déjà bien chargée, et il est d'ailleurs probable que votre besoin s'y trouve déjà.").'</p>';
			$h .= '<p><a href="https://blog.ouvretaferme.org/feuille-de-route" class="btn btn-secondary mb-1">'.s("Voir la feuille de route").'</a></p>';

			$h .= '<p>'.s("Les priorités de développement sont choisies en fonction des besoins des adhérents à l'association, des affinités des développeurs et du temps disponible. Si vous pensez qu'il manque des fonctionnalités structurantes pour votre ferme, <b>n'utilisez pas {siteName}</b> et privilégiez des solutions qui vous correspondront mieux.").'</p>';

		$h .= '</div>';

		return $h;
	}

	public function friends(bool $isDiscount) : string {

		$h = '<div class="util-block util-overflow-sm">';
		$h .= '<table style="font-size: 1.2rem" class="tr-bordered">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th></th>';
					$h .= '<th>'.s("Logiciel").'</th>';
					$h .= '<th>'.s("Tarif annuel").'</th>';
					$h .= '<th></th>';
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';
				$h .= '<tr>';
					$h .= '<td rowspan="3">';
						$h .= '<span class="util-circle util-circle-lg bg-production mr-1">'.\Asset::icon('leaf').'</span>';
						$h .= s("Production");
					$h .= '</td>';
					$h .= '<td>Elzeard</td>';
					$h .= '<td>330 - 990 €</td>';
					$h .= '<td rowspan="12" class="text-center bg-background" style="font-size: 1.5rem; font-weight: bold">Ouvretaferme</td>';
					if($isDiscount) {
						$h .= '<td rowspan="7" class="text-center" style="font-size: 1.5rem; font-weight: bold">0 €</td>';
					} else {
						$h .= '<td rowspan="11" class="text-center" style="font-size: 1.5rem; font-weight: bold">'.\association\AssociationSetting::MEMBERSHIP_FEE_FULL.' €</td>';
					}
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td>Brinjel</td>';
					$h .= '<td>50 - 300 €</td>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td>Permatechnics</td>';
					$h .= '<td>220 - 494 €</td>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td rowspan="4">';
						$h .= '<span class="util-circle util-circle-lg bg-commercialisation mr-1">'.\Asset::icon('basket3').'</span>';
						$h .= s("Commercialisation");
					$h .= '</td>';
					$h .= '<td>Socleo</td>';
					$h .= '<td>Minimum 720 €</td>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td>Kuupanda</td>';
					$h .= '<td>420 – 1500 €</td>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td>Ciboulette</td>';
					$h .= '<td>2 % des ventes (60 – 480 €)</td>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td>Cagette</td>';
					$h .= '<td>2 à 6 % des ventes (max 1400 €)</td>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td rowspan="3">';
						$h .= '<span class="util-circle util-circle-lg bg-production mr-1">'.\Asset::icon('bank').'</span>';
						$h .= s("Comptabilité");
					$h .= '</td>';
					$h .= '<td>Isagri</td>';
					$h .= '<td>420 - 1000 € et plus</td>';
					if($isDiscount) {
						$h .= '<td rowspan="4" class="text-center" style="font-size: 1.5rem; font-weight: bold">'.\association\AssociationSetting::MEMBERSHIP_FEE_DISCOUNT.' €</td>';
					}
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td>Macompta</td>';
					$h .= '<td>159 €</td>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td>Istea</td>';
					$h .= '<td>320 €</td>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td rowspan="2">';
						$h .= '<span class="util-circle util-circle-lg bg-private mr-1">'.\Asset::icon('receipt').'</span>';
						$h .= s("Facturation électronique");
					$h .= '</td>';
					$h .= '<td>Votre banque</td>';
					$h .= '<td>100 - 300 €</td>';
				$h .= '</tr>';
			$h .= '</thead>';
		$h .= '</table>';
		$h .= '</div>';
		
		return $h;
		
	}

	public function membership() : string {

		$arguments = ['sale' => '<b class="color-commercialisation">', 'production' => '<b class="color-production">', 'accounting' => '<b class="color-accounting">'];

		$h = '<h2>'.s("Utilisation du logiciel").'</h2>';

		$h .= '<div class="home-features home-features-2 mb-1">';
			$h .= '<div class="home-feature"><h4>'.s("Agriculture biologique").'</h4><div>'.s("Utilisation gratuite pour les modules <sale>VENTE</sale> et de <production>PRODUCTION</production> et soumise à l'adhésion à l'association pour {fee} € / an pour le module <accounting>COMPTABILITÉ</accounting>.", $arguments + ['fee' => \association\AssociationSetting::MEMBERSHIP_FEE_DISCOUNT]).'</div></div>';
			$h .= '<div class="home-feature"><h4>'.s("Agriculture conventionnelle").'</h4><div>'.s("Utilisation soumise à l'adhésion à l'association pour {fee} € / an, avec une période d'essai gratuite de 6 mois pour les modules <sale>VENTE</sale> et de <production>PRODUCTION</production>.", $arguments + ['fee' => \association\AssociationSetting::MEMBERSHIP_FEE_FULL]).'</div></div>';
		$h .= '</div>';
		$h .= '<div>'.s("L'utilisation du module de <production>PRODUCTION</production> uniquement est gratuite pour les particuliers ou les établissements scolaires.", $arguments).'</div>';

		$h .= '<br/>';
		$h .= '<br/>';

		$h .= '<h2>'.s("Pourquoi le logiciel est-il aussi accessible ?").'</h2>';

		$h .= '<div class="home-category">';

			$h .= '<ul>';
				$h .= '<li>'.s("Nous sommes une association et nous n'avons pas de pression commerciale").'</li>';
				$h .= '<li>'.s("Nous sommes bénévoles").'</a></li>';
			$h .= '</ul>';

		$h .= '</div>';

		$h .= '<br/>';
		$h .= '<br/>';

		$h .= '<h2>'.s("Vous n'êtes pas tout à fait convaincu ?").'</h2>';
		$h .= '<p>'.s("Alors jetez un oeil au tableau ci-dessous pour mesurer le coût réel des services équivalents si Ouvretaferme n'existait pas.").'</p>';

		$h .= '<div class="tabs-h mt-2">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="legal-organic"  onclick="Lime.Tab.select(this)">'.s("Agriculture biologique").'</a>';
				$h .= '<a class="tab-item" data-tab="legal-other"  onclick="Lime.Tab.select(this)">'.s("Agriculture conventionnelle").'</a>';
			$h .= '</div>';

			$h .= '<div class="tab-panel selected" data-tab="legal-organic">';
				$h .= $this->friends(TRUE);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="legal-other">';
				$h .= $this->friends(FALSE);
			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

}
?>
