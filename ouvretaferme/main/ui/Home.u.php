<?php
namespace main;

class HomeUi {

	public function __construct() {

		\Asset::css('main', 'home.css');

	}

	public function getFarms(\Collection $cFarm): string {

		$h = '';

		if($cFarm->empty()) {
			if(new \farm\Farm()->canCreate()) {
				$h .= new \farm\FarmerUi()->getNoFarms();
			} else {
				$h .= '';
			}
		} else {

			$h .= '<h2>'.($cFarm->count() === 1 ? s("Ma ferme") : s("Mes fermes")).'</h2>';
			$h .= new \farm\FarmerUi()->getMyFarms($cFarm);

		}

		return $h;

	}

	public function getEvidences(): string {
	
		$h = '<h2>'.s("Témoignages de fermes").'</h2>';
	
		$h .= '<div class="home-profiles">';
	
			$h .= '<div class="home-profile home-profile-extended">';
				$h .= '<div class="home-profile-header">';
					$h .= '<div>'.\Asset::image('main', 'profile/tomatesetpotirons.jpg', ['class' => 'home-profile-image']).'</div>';
					$h .= '<div>';
						$h .= '<h4>'.s("Tomates & Potirons (86)").'</h4>';
						$h .= '<h3>'.s("Maraichage").'</h3>';
					$h .= '</div>';
				$h .= '</div>';
				$h .= '<p>'.s("Avant Ouvretaferme, les maraichers de Tomates & Potirons ont testé plusieurs outils de planification, en commençant par Excel : très flexible, mais vite complexe et difficilement transmissible à une équipe. D’autres logiciels étaient intéressants, mais souvent limités à la production, avec peu de souplesse. Ouvretaferme a été une révélation : enfin un outil qui combine toutes les informations nécessaires à notre ferme, de la production à la commercialisation.").'</p>';
				$h .= '<p class="hide-sm-down">&laquo; '.s("Aujourd’hui, grâce à la centralisation des données (plan de culture, ventes, temps de travail), nous avons une analyse économique précise de chaque série. C’est un outil stratégique pour toute ferme diversifiée.").' &raquo;</p>';
			$h .= '</div>';
	
			$h .= '<div class="home-profile">';
				$h .= '<div class="home-profile-header">';
					$h .= '<div>'.\Asset::image('main', 'profile/pain.jpg', ['class' => 'home-profile-image']).'</div>';
					$h .= '<div>';
						$h .= '<h3>'.s("Boulangerie paysanne").'</h3>';
					$h .= '</div>';
				$h .= '</div>';
				$h .= '<p>'.s("Adeline est une paysanne-boulangère qui vend sa production sur les marchés avec le logiciel de caisse de Ouvretaferme accessible sur son téléphone ou sa tablette. Elle vend aussi son pain sur une boutique en ligne qu'elle partage avec un collègue maraicher.").'</p>';
			$h .= '</div>';
			$h .= '<div class="home-profile">';
				$h .= '<div class="home-profile-header">';
					$h .= '<div>'.\Asset::image('main', 'profile/oeuf.jpg', ['class' => 'home-profile-image']).'</div>';
					$h .= '<div>';
						$h .= '<h3>'.s("Élevage").'</h3>';
					$h .= '</div>';
				$h .= '</div>';
				$h .= '<p>'.s("Axel est un éleveur qui vend sa production en ligne avec Ouvretaferme et a bidouillé les fonctionnalités de planification destinées aux fruits, légumes et aux fleurs pour les adapter à sa production de volailles de chair et de poules pondeuses. Il bénéficiera peut-être bientôt de fonctionnalités spécifiques sur Ouvretaferme !").'</p>';
			$h .= '</div>';
			$h .= '<div class="home-profile home-profile-dark bg-secondary">';
				$h .= '<div class="home-profile-header home-profile-header-text">';
					$h .= '<h3>'.s("Vos clients").'</h3>';
				$h .= '</div>';
				$h .= '<p>'.s("Ils commandent en vente directe à leur producteurs préférés les produits qu'ils proposent cette semaine et récupèrent leur commande au lieu et à la date convenus. Ils paient en ligne ou sur place selon le choix du producteur !").'</p>';
			$h .= '</div>';
	
			$h .= '<div class="home-profile">';
				$h .= '<div class="home-profile-header">';
					$h .= '<div>'.\Asset::image('main', 'profile/jardindesmurmures.jpg', ['class' => 'home-profile-image']).'</div>';
					$h .= '<div>';
						$h .= '<h4>'.s("Le Jardin des Murmures (74)").'</h4>';
						$h .= '<h3>'.s("Maraichage").'</h3>';
					$h .= '</div>';
				$h .= '</div>';
				$h .= '<p>'.s("Lionel utilise Ouvretaferme depuis 2023 et notamment la boutique en ligne pour ses ventes directes et le système de facturation qui lui ont fait gagner des heures. Le planning de production lui permet également de travailler en équipe et notamment de connaître les planches à préparer, la fertilisation et le paillage à utiliser !").'</p>';
			$h .= '</div>';
	
			$h .= '<div class="home-profile">';
				$h .= '<div class="home-profile-header">';
					$h .= '<div>'.\Asset::image('main', 'profile/fleur.jpg', ['class' => 'home-profile-image']).'</div>';
					$h .= '<div>';
						$h .= '<h3>'.s("Floriculture").'</h3>';
					$h .= '</div>';
				$h .= '</div>';
				$h .= '<p>'.s("Marie et Luc sont des floriculteurs qui gèrent avec Ouvretaferme la diversité de leur production sur petite surface. Ils vendent aussi sur une boutique en ligne destinée aux fleuristes leur gamme de fleurs coupées. Ils envoient leurs bons de livraison par e-mail et génèrent chaque mois en un clic les factures de leurs ventes.").'</p>';
			$h .= '</div>';
	
			$h .= '<div class="home-profile">';
				$h .= '<div class="home-profile-header">';
					$h .= '<div>'.\Asset::image('main', 'profile/cfppacourcelles.png', ['class' => 'home-profile-image']).'</div>';
					$h .= '<div>';
						$h .= '<h4><a href="https://campus-courcelles.fr/">'.s("CFPPA de Courcelles-Chaussy (57)").'</a></h4>';
						$h .= '<h3>'.s("Centre de formation").'</h3>';
					$h .= '</div>';
				$h .= '</div>';
				$h .= '<p>'.s("Le <link>CFPPA de Courcelles-Chaussy</link> utilise Ouvretaferme non seulement pour gérer son atelier pédagogique mais aussi pour permettre aux stagiaires de mieux appréhender le travail à réaliser sur une ferme, les itinéraires techniques et tout ce qui concerne le plan de culture.", ['link' => '<a href="https://campus-courcelles.fr/">']).'</p>';
			$h .= '</div>';
	
			$h .= '<div class="home-profile">';
				$h .= '<div class="home-profile-header">';
					$h .= '<div>'.\Asset::image('main', 'profile/carotte.jpg', ['class' => 'home-profile-image']).'</div>';
					$h .= '<div>';
						$h .= '<h4>'.s("Les Jardins de Tallende (63)").'</h4>';
						$h .= '<h3>'.s("Maraichage").'</h3>';
					$h .= '</div>';
				$h .= '</div>';
				$h .= '<p>'.s("Vincent est un maraicher diversifié qui conçoit son plan de culture avec Ouvretaferme pour la saison en respectant ses rotations. En saison, il utilise le planning pour se libérer de sa charge mentale et enregistre son temps de travail pour comprendre là où il peut améliorer son système. La nuit, il est aussi le développeur principal de Ouvretaferme !").'</p>';
			$h .= '</div>';
		$h .= '</div>';
		
		return $h;
		
	}

	public function getTraining(bool $hide = FALSE): string {

		if(currentDate() > MainSetting::LIMIT_TRAINING) {
			return '';
		}

		if(\user\ConnectionLib::isLogged() and $hide) {

			$eUser = \user\ConnectionLib::getOnline();

			$key = 'pub-2025001-'.$eUser['id'];
			$expires = strtotime(MainSetting::LIMIT_TRAINING.' + 7 DAYS');

			if(get_exists('training')) {
				\Cache::redis()->set($key, 5);
				return '';
			}

			if(\Cache::redis()->add($key, 0, $expires) === FALSE) {

				$newValue = \Cache::redis()->get($key) + 1;

				if($newValue > 3) {
					return '';
				}

				\Cache::redis()->set($key, $newValue, $expires);

			}

		}

		$h = '<div class="home-blog bg-training util-block stick-xs">';
			$h .= '<div>';
				$h .= \Asset::image('main', 'favicon.png').'';
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<h4 class="mb-0 color-secondary">'.s("29 janvier 2025 dans le Puy-de-Dôme (63)").'</h4>';
				$h .= '<h2>';
					$h .= s("Formation sur {siteName} !");
				$h .= '</h2>';
				$h .= '<div>';
					$h .= '<p>'.s("Une formation à la journée finançable VIVEA est organisée pour {siteName}. Une occasion idéale pour prendre en main ou se perfectionner sur {siteName}, discuter des évolutions possibles sur le logiciel et échanger sur vos problématiques !").'</p>';
					$h .= '<a href="/presentation/formations" target="_blank" class="btn btn-secondary" style="margin-bottom: 0.25rem">'.\Asset::icon('chevron-right').' '.s("En savoir plus").'</a>';
					if(\user\ConnectionLib::isLogged() and $hide) {
						$h .= ' <a href="'.\util\HttpUi::setArgument(LIME_REQUEST, 'training', 1).'" class="btn btn-secondary" style="margin-bottom: 0.25rem">'.\Asset::icon('x-lg').' '.s("Ok, cacher ce message").'</a>';
					}
				$h .= '</div>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getBlog(\Collection $cNews): string {

		if($cNews->empty()) {

			$h = '<h2>'.s("Quoi de neuf sur {siteName} ?").'</h2>';

			$h .= '<div class="mb-2 bg-info util-block">';
				$h .= '<h3 style="font-weight: normal">'.s("Suivez le blog de {siteName} pour retrouver les annonces de nouvelles fonctionnalités, la feuille de route avec les priorités de développement pour les mois à venir  et des ressources pour faciliter la prise en main du site !").'</h3>';
				$h .= '<a href="https://blog.ouvretaferme.org/" target="_blank" class="btn btn-secondary">'.\Asset::icon('chevron-right').' '.s("Découvrir le blog").'</a>';
			$h .= '</div>';

		} else {

			$h = '<h2>'.s("Du nouveau sur {siteName}").'</h2>';

				$h .= '<table class="tr-bordered">';
					$h .= '<tbody>';

						foreach($cNews as $position => $eNews) {
							$h .= '<tr '.($position === 0 ? 'style="font-weight: bold; font-size: 1.1rem"' : '').'>';
								$h .= '<td class="'.($position === 0 ? '' : 'color-muted').' text-end td-min-content">'.\util\DateUi::textual($eNews['publishedAt'], \util\DateUi::DATE).'</td> ';
								$h .= '<td>';
									$h .=  '<a href="https://blog.ouvretaferme.org/#news-'.$eNews['id'].'">'.encode($eNews['title']).'</a>';
								$h .= '</td> ';
							$h .= '</tr>';
						}
					$h .= '</tbody>';
				$h .= '</table>';
				$h .= '<div class="mb-2">';
					$h .= '<a href="https://blog.ouvretaferme.org/" target="_blank" class="btn btn-secondary">'.\Asset::icon('chevron-right').' '.s("Toutes les actualités").'</a>';
				$h .= '</div>';

		}

		return $h;

	}

	public function getCustomer(\user\Role $eRole): string {

		$class = $eRole->empty() ? '' : ($eRole['fqn'] === 'customer' ? 'selected' : 'other');

		$h = '<a href="/user/signUp?role=customer" class="home-user-type home-user-type-'.$class.'">';
			$h .= '<h2>👨‍🍳</h2>';
			$h .= '<h4>'.s("Je suis client / cliente").'</h4>';
		$h .= '</a>';

		return $h;

	}

	public function getFarmer(\user\Role $eRole): string {

		$class = $eRole->empty() ? '' : ($eRole['fqn'] === 'farmer' ? 'selected' : 'other');

		$h = '<a href="/user/signUp?role=farmer" class="home-user-type home-user-type-'.$class.'">';
			$h .= '<h2>👩‍🌾</h2>';
			$h .= '<h4>'.s("Je suis producteur / productrice").'</h4>';
		$h .= '</a>';

		return $h;

	}

	public function getAccounting(): string {
		
		$h = '<div class="home-features home-features-2">';
			$h .= '<div class="home-feature">';
				$h .= '<h2>';
					$h .= '<div class="home-feature-icon">'.\Asset::icon('piggy-bank').'</div>';
					$h .= s("Banque");
				$h .= '</h2>';
				$h .= '<h4>'.s("Importez vos relevés bancaires au format OFX et faites un rapprochement automatique avec vos factures pour vérifier en trois clics qui a payé.").'</h4>';
				$h .= '<h5 style="padding-right: 5rem">'.s("Terminée la vérification des relevés bancaire ligne par ligne !").'</h5>';
			$h .= '</div>';
			$h .= '<div class="home-feature">';
				$h .= '<h2>';
					$h .= '<div class="home-feature-icon">'.\Asset::icon('file-spreadsheet').'</div>';
					$h .= s("Précomptabilité");
				$h .= '</h2>';
				$h .= '<h4>'.s("Exportez les données de vos ventes et exportez vos factures au format FEC pour les importer sur votre logiciel de comptabilité.").'</h4>';
				$h .= '<h5 style="padding-right: 5rem">'.s("Faire la comptabilité de ses ventes devient un jeu d'enfant !").'</h5>';
			$h .= '</div>';
			$h .= '<div class="home-feature">';
				$h .= '<h2>';
					$h .= '<div class="home-feature-icon">'.\Asset::icon('receipt').'</div>';
					$h .= s("Facturation électronique");
				$h .= '</h2>';
				$h .= '<h4>'.s("Ouvretaferme sera prêt pour le lancement de la réforme de la facturation électronique le 1<up>er</up> septembre 2026 avec le <i>e-invoicing</i> et le <i>e-reporting</i>. L'accès à la plateforme agréée sera inclus dans le montant de l'adhésion à Ouvretaferme.").'</h4>';
				$h .= '<h5 class="mt-1">'.s("Disponible au printemps 2026").'</h5>';
			$h .= '</div>';
			$h .= '<div class="home-feature">';
				$h .= '<h2>';
					$h .= '<div class="home-feature-icon">'.\Asset::icon('journal-text').'</div>';
					$h .= s("Journal de caisse");
				$h .= '</h2>';
				$h .= '<h4>'.s("Ouvretaferme vous permet de tenir votre journal de caisse en ligne pour gérer les espèces liées à votre activité et être en règle vis-à-vis de l'administration fiscale.").'</h4>';
				$h .= '<h5 style="padding-right: 5rem">'.s("Le journal de caisse peut être importé en un clic dans votre comptabilité !").'</h5>';
			$h .= '</div>';
			$h .= '<div class="home-feature">';
				$h .= '<h2>';
					$h .= '<div class="home-feature-icon">'.\Asset::icon('journal-bookmark').'</div>';
					$h .= s("Logiciel comptable pour le micro-BA");
				$h .= '</h2>';
				$h .= '<h4>'.s("Vous êtes en comptabilité de trésorerie, savez tenir la comptabilité de votre ferme et connaissez vos écritures comptables et classes de compte ?<br/>Utilisez Ouvretaferme comme logiciel comptable, c'est toujours inclus dans le montant de l'adhésion à l'association.").'</h4>';
				$h .= '<h5>'.s("Disponible en version beta uniquement").'</h5>';
			$h .= '</div>';
			$h .= '<div class="home-feature">';
				$h .= '<h2>';
					$h .= '<div class="home-feature-icon">'.\Asset::icon('journal-plus').'</div>';
					$h .= s("Livre des recettes");
				$h .= '</h2>';
				$h .= '<h4 style="padding-right: 5rem">'.s("Vous pouvez utiliser Ouvretaferme comme pour tenir le livre des recettes de votre ferme. Le livre des recettes est une obligation légale pour votre activité si vous êtes au micro-BA.").'</h4>';
				$h .= '<h5>'.s("Disponible au printemps 2026").'</h5>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;
		
	}

	public function getMission(): string {

		$h = '<div class="home-highlight">';

			$h .= '<p>';
				$h .= '<span class="font-xl mr-1" style="font-weight: bold">'.s("Vous êtes une AFOCG ?").'</span><br/>';
				$h .= s("Lisez pourquoi Ouvretaferme est la solution qu'il vous faut pour accompagner vos producteurs et productrices sur tous les aspects de leur métier.");
			$h .= '</p>';

			$h .= '<a href="/presentation/afocg" class="btn btn-primary">'.s("En savoir plus").'</a>';

		$h .= '</div>';

		$h .= '<h2>'.s("Nos objectifs avec {siteName}").'</h2>';

		$h .= '<p class="color-muted">'.s("Depuis 2021, nous proposons et améliorons quotidiennement un logiciel gratuit et intuitif pour les producteurs en circuits courts avec pour mission de :").'</p>';

		$h .= '<div class="home-why">';
			$h .= '<div class="home-why-item">';
				$h .= \Asset::icon('people-fill');
				$h .= '<h4>'.s("Soutenir les producteurs et productrices pour réaliser les finalités économiques, sociales et environnementales de leur projet.").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-why-item">';
				$h .= \Asset::icon('activity');
				$h .= '<h4>'.s("Pérenniser les fermes, soutenir l'emploi et contribuer à améliorer la qualité de vie de celles et ceux qui les portent.").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-why-item">';
				$h .= \Asset::icon('arrow-repeat');
				$h .= '<h4>'.s("Favoriser la diffusion des savoirs et des techniques entre les fermes en pleine intégration avec les acteurs qui partagent nos objectifs.").'</h4>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getPoints(): string {

		$h = '<h2>'.s("Principes de conception").'</h2>';

		$h .= '<div class="home-points">';
			$h .= '<div class="home-point" style="grid-column: span 2">';
				$h .= \Asset::icon('inboxes');
				$h .= '<h4>'.s("Toutes les fonctionnalités sont indépendantes,<br/>vous utilisez seulement celles adaptées à votre ferme !").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-point" style="grid-column: span 2">';
				$h .= \Asset::icon('columns-gap');
				$h .= '<h4>'.s("Les interfaces sont simples et intuitives,<br/>elles s'adaptent à vos pratiques").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-point">';
				$h .= \Asset::icon('lock');
				$h .= '<h4>'.s("Vos données vous appartiennent<br/>et ne sont ni vendues, ni partagées").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-point">';
				$h .= \Asset::icon('cup-hot');
				$h .= '<h4>'.s("Conçu pour réduire la charge mentale sans décider à votre place").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-point">';
				$h .= \Asset::icon('phone');
				$h .= '<h4>'.s("Accessible facilement<br/>sur ordinateur et téléphone").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-point">';
				$h .= \Asset::icon('code-slash');
				$h .= '<h4>'.s("Logiciel ouvert dont<br/>le code source est public").'</h4>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

}
?>
