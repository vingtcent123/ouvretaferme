<?php
namespace farm;

class TipUi {

	public function __construct() {
		\Asset::css('farm', 'tip.css');
	}

	public function get(Farm $eFarm, string $tip, string $navigation): string {

		$content = $this->getContent($eFarm, $tip);

		$h = '<div id="tip-wrapper" class="tip-wrapper-'.$navigation.'">';
			$h .= '<div class="tip-block">';
				$h .= '<div class="tip-icons">';
					$h .= str_repeat($content['icon'], 9);
				$h .= '</div>';
				$h .= '<div class="tip-header">';
					$h .= '<div class="tip-intro">'.\Asset::icon('info-circle').'</div>';
					$h .= match($navigation) {
						'close' => '<a href="/farm/tip?farm='.$eFarm['id'].'" class="btn btn-transparent btn-sm">'.\Asset::icon('caret-right-fill').' '.s("Toutes les astuces").'</a>',
						'next' => '<a href="/farm/tip?farm='.$eFarm['id'].'" class="btn btn-transparent btn-sm">'.\Asset::icon('caret-right-fill').' '.s("Astuce suivante").'</a>',
						'inline' => ''
					};
				$h .= '</div>';
				$h .= '<h2 class="tip-title">'.$content['title'].'</h2>';
				$h .= '<div class="tip-content">';
					$h .= $content['content'];
					if($content['image']) {
						$h .= '<div class="tip-image"">';
							if($content['button'] !== NULL) {
								$h .= '<a href="/farm/tip:click?id='.$tip.'&redirect='.urlencode($content['button'][0]).'" data-ajax-navigation="never">'.\Asset::image('farm', $tip.'.png').'</a>';
							} else {
								$h .= \Asset::image('farm', $tip.'.png');
							}
						$h .= '</div>';
					}
				$h .= '</div>';
				$h .= '<div class="tip-button">';
					if($content['button'] !== NULL) {
						$h .= '<a href="/farm/tip:click?id='.$tip.'&redirect='.urlencode($content['button'][0]).'" data-ajax-navigation="never" class="btn btn-outline-tip">'.$content['button'][1].'</a>';
					}
					if($navigation === 'close' or $navigation === 'inline') {
						$h .= '<a data-ajax="/farm/tip:close?id='.$tip.'" data-ajax-method="get" class="btn btn-tip">'.\Asset::icon('x-lg').'  '.s("Cacher ce message").'</a>';
					}
				$h .= '</div>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	private function getContent(Farm $eFarm, string $fqn): array {

		switch($fqn) {

			case 'action-customize' :

				$link = '/farm/action:manage?farm='.$eFarm['id'];

				$h = '<p>'.s("Votre ferme a été créée automatiquement avec des interventions de base, comme le binage, le désherbage, la plantation, la récolte... Si ces interventions ne correspondent pas à votre contexte de production, vous pouvez tout à fait en ajouter ou en supprimer !").'</p>';
				$h .= '<div class="tip-list">';
					$h .= '<span>'.s("Binage").'</span>';
					$h .= '<span>'.s("Plantation").'</span>';
					$h .= '<span>'.s("Récolte").'</span>';
					$h .= '<span>'.s("...").'</span>';
					$h .= '<a href="'.$link.'">'.\Asset::icon('plus-circle').' '.s("Ajouter").'</a>';
				$h .= '</div>';

				return [
					'icon' => \Asset::icon('list-task'),
					'title' => s("Personnalisez les interventions !"),
					'content' => $h,
					'image' => FALSE,
					'button' => [$link, s("Personnaliser les interventions")],
				];

			case 'planning-checkboxes' :

				$link = FarmUi::urlPlanningWeekly($eFarm);

				$h = '<p>'.s("Sélectionnez plusieurs interventions sur votre planning hebdomadaire, et renseignez en une fois le temps de travail. Il sera réparti sur toutes les interventions sélectionnées en fonction de la clé de répartition que vous aurez choisie !").'</p>';

				return [
					'icon' => \Asset::icon('clock'),
					'title' => s("Saisie multiple du temps de travail !"),
					'content' => $h,
					'image' => TRUE,
					'button' => [$link, s("Revenir sur le planning")],
				];

			case 'plant-customize' :

				$link = \plant\PlantUi::urlManage($eFarm);

				$h = '<p>'.s("Si les espèces cultivées créées par défaut avec votre ferme ne sont pas suffisantes pour votre plan de culture, vous pouvez ajouter vos propres espèces !").'</p>';
				$h .= '<div class="tip-list">';
					$h .= '<span>'.s("Ail").'</span>';
					$h .= '<span>'.s("Betterave").'</span>';
					$h .= '<span>'.s("Carotte").'</span>';
					$h .= '<span>'.s("...").'</span>';
					$h .= '<a href="'.$link.'">'.\Asset::icon('plus-circle').' '.s("Ajouter").'</a>';
				$h .= '</div>';

				return [
					'icon' => \Asset::icon('flower2'),
					'title' => s("Ajoutez de nouvelles espèces cultivées !"),
					'content' => $h,
					'image' => FALSE,
					'button' => [$link, s("Personnaliser les espèces")],
				];

			case 'feature-rotation' :

				$link = FarmUi::urlHistory($eFarm);
				$rotationSeasons = $eFarm->getRotationSeasons(date('Y'));

				$year = last($rotationSeasons);
				$eFamily = \plant\FamilyLib::getByFqn('asteraceae');

				$h = '<p>'.s("Sur la page des rotations de cultures, vous pouvez retrouver facilement les planches qui n'ont pas été cultivées avec une même famille depuis deux, trois ou quatre ans. Par exemple, retrouvez toutes les planches qui n'ont pas reçu d'astéracées depuis {year} en <link>cliquant ici</link> !", ['year' => $year, 'link' => '<a href="'.$link.'?family='.$eFamily['id'].'&seen=0">']).'</p>';

				return [
					'icon' => \Asset::icon('arrow-repeat'),
					'title' => s("Suivez vos rotations de cultures !"),
					'content' => $h,
					'image' => FALSE,
					'button' => [$link, s("Voir les rotations")],
				];

			case 'feature-seeds' :

				$link = FarmUi::urlCultivationSeries($eFarm, Farmer::SEEDLING);

				$h = '<p>'.s("Lorsque vous avez terminé de saisir votre plan de culture, {siteName} peut vous sortir les quantités de semences et plants à commander pour la saison. Vous pouvez même indiquer vos fournisseurs pour chaque variété pour avoir une liste de courses par fournisseur !").'</p>';

				return [
					'icon' => \Asset::icon('flower2'),
					'title' => s("Consulter votre liste de semences et plants à commander !"),
					'content' => $h,
					'image' => TRUE,
					'button' => [$link, s("Voir mes semences et plants")],
				];

			case 'feature-team' :

				$link = FarmerUi::urlManage($eFarm);

				$h = '<p>'.s("Ajoutez autant de personnes que vous voulez à l'équipe de votre ferme, en donnant à chaque membre de l'équipe soit accès complet, soit un accès limité à certaines fonctionnalités.").'</p>';

				return [
					'icon' => \Asset::icon('flower2'),
					'title' => s("Vous travaillez en équipe ?"),
					'content' => $h,
					'image' => FALSE,
					'button' => [$link, s("Gérer mon équipe")],
				];

			case 'feature-time-disable' :

				$link = '/farm/farm:updateProduction?id='.$eFarm['id'];

				$h = '<p>'.s("Simplifiez-vous l'interface de {siteName} en désactivant cette fonctionnalité. Vous ne serez plus dérangé par les interfaces de saisie de temps de travail. Vous pourrez réactiver cette fonctionnalité ultérieurement !").'</p>';

				return [
					'icon' => \Asset::icon('clock'),
					'title' => s("Vous ne souhaitez pas renseigner votre temps de travail ?"),
					'content' => $h,
					'image' => FALSE,
					'button' => [$link, s("Désactiver cette fonctionnalité")],
				];

			case 'feature-tools' :

				$link = '/farm/tool:manage?farm='.$eFarm['id'];

				$h = '<p>'.s("Entrez la liste des outils que vous utilisez le plus pour chaque intervention, et retrouvez ensuite ces outils dans votre planning hebdomadaire !").'</p>';
				$h .= '<p>'.s("Vous avez plusieurs modèles de filets ? Entrez chaque modèle et vous retrouverez pour chaque série quel modèle vous devez utiliser. Vous cultivez sur bâche tissée ? Entrez vos différentes tailles pour retrouver lesquelles utiliser sur chaque série. Et ainsi de suite avec n'importe quel outil ou matériel !").'</p>';

				return [
					'icon' => \Asset::icon('wrench'),
					'title' => s("Votre matériel pour chaque intervention !"),
					'content' => $h,
					'image' => FALSE,
					'button' => [$link, s("Voir mon matériel")],
				];

			case 'feature-website' :

				$link = '/website/manage?id='.$eFarm['id'];

				$h = '<p>'.s("En quelques clics et sans connaissances techniques, créez le site internet de votre ferme avec {siteName}. Créez autant de pages que vous voulez. Personnalisez le thème et les couleurs !").'</p>';

				return [
					'icon' => \Asset::icon('globe'),
					'title' => s("Créez le site internet de votre ferme !"),
					'content' => $h,
					'image' => FALSE,
					'button' => [$link, s("Créer le site internet")],
				];

			case 'selling-market' :

				$link = FarmUi::urlSellingSalesAll($eFarm);

				$h = '<p>'.s("Au marché avec votre téléphone ou une tablette, saisissez vos ventes avec le logiciel de caisse intégré. Plus besoin d'une balance sophistiquée. À la fin du marché, vous savez exactement ce que vous avez vendu. Vous pouvez même saisir les commandes de vos clients à honorer ultérieurement directement en ligne.").'</p>';

				return [
					'icon' => \Asset::icon('cart2'),
					'title' => s("Un logiciel de caisse"),
					'content' => $h,
					'image' => TRUE,
					'button' => [$link, s("Aller aux ventes")],
				];

			case 'selling-pdf' :

				$link = FarmUi::urlSellingSales($eFarm);

				$h = '<p>'.s("Créez vos ventes avec {siteName} et éditez vos devis, bons de livraisons et factures en PDF à destination de vos clients avec envoi automatique par e-mail. Fonctionne aussi bien pour les ventes à destination des particuliers que des professionnels.").'</p>';

				return [
					'icon' => \Asset::icon('file-pdf'),
					'title' => s("Devis, bons de livraisons et factures !"),
					'content' => $h,
					'image' => TRUE,
					'button' => [$link, s("Aller aux ventes")],
				];

			case 'selling-shop' :

				$link = FarmUi::urlShopList($eFarm);

				$h = '<p>'.s("Créez une boutique en permettant à vos clients de faire leurs commandes en ligne et de venir retirer leur panier à la date et au lieu de votre choix. Pas de commission sur les ventes et facile à installer !").'</p>';

				return [
					'icon' => \Asset::icon('cart2'),
					'title' => s("Créez une boutique en ligne"),
					'content' => $h,
					'image' => TRUE,
					'button' => [$link, s("Créer une boutique en ligne")],
				];

			case 'sequence-weeks' :

				$h = '<p>'.s("Lorsque vous créerez une série à partir de cet itinéraire technique, vous pourrez choisir une semaine de démarrage différente de celle de l'itinéraire et toutes les interventions de la série seront décalées en conséquence.").'</p>';
				$h .= '<div style="display: flex; column-gap: 1rem">';
					$h .= \plant\PlantUi::getVignette(new \plant\Plant(['fqn' => 'radis-botte']), '3rem');
					$h .= '<p>'.s("Créez par exemple un seul itinéraire technique pour le radis, et vous pourrez ensuite ajouter plusieurs séries qui seront semées à des semaines différentes sur la base de cet itinéraire.").'</p>';
				$h .= '</div>';

				return [
					'icon' => \Asset::icon('list-task'),
					'title' => s("Un seul itinéraire technique par espèce"),
					'content' => $h,
					'image' => TRUE,
					'button' => NULL,
				];

			case 'mailing-contact-help' :

				$h = s("Vos contacts correspondent aux adresses e-mail de vos clients, aux personnes qui se sont inscrites à la lettre d'information de votre ferme sur votre site internet ainsi que celles que vous aurez ajouté manuellement. Pour chaque contact, vous retrouvez quelques chiffres qui vous permettent notamment de savoir si vos e-mails parviennent bien à leurs destinataires.");

				return [
					'icon' => \Asset::icon('envelope'),
					'title' => s("Que sont les contacts ?"),
					'content' => $h,
					'image' => FALSE,
					'button' => NULL,
				];

			case 'series-forecast-help' :

				$h = '<p>'.s("Le prévisionnel financier est un outil qui vous permet d'avoir une vue d'ensemble de la production et des ventes de la saison. Les ventes sont calculées automatiquement selon des prix et une répartition des ventes entre clients particuliers et professionnels que vous choisissez.").'</p>';
				$h .= '<h5>'.s("Vous pouvez utiliser le prévisionnel de deux manières différentes :").'</h5>';
				$h .= '<ol>';
					$h .= '<li>'.s("La page du prévisionnel reprend automatiquement les séries que vous avez déjà créé pour la saison en cours. Vous n'avez plus qu'à saisir vos prix de ventes pour avoir vos prévisions financières !").'</li>';
					$h .= '<li>'.s("Vous pouvez aussi utiliser le prévisionnel en amont de votre planification en ajoutant les espèces cultivées que vous souhaitez cultiver au prévisionnel. Une fois que vous êtes satisfait de votre prévisionnel, vous pouvez ainsi commencer votre planification !").'</li>';
				$h .= '</ol>';

				return [
					'icon' => \Asset::icon('wallet'),
					'title' => s("Qu'est-ce que le prévisionnel financier ?"),
					'content' => $h,
					'image' => FALSE,
					'button' => NULL,
				];

			case 'series-duplicate' :

				$link = FarmUi::urlCultivationSeries($eFarm, Farmer::AREA);

				$h = '<p>'.s("Vous êtes satisfait d'une de vos séries ? Dupliquez-là à partir de sa page pour passer moins de temps à faire votre plan de culture l'année prochaine !").'</p>';

				return [
					'icon' => \Asset::icon('flower2'),
					'title' => s("Dupliquez des séries"),
					'content' => $h,
					'image' => TRUE,
					'button' => [$link, s("Voir mes séries")],
				];

			case 'series-harvest' :

				$link = FarmUi::urlPlanningWeekly($eFarm);

				$h = '<p>'.s("Quand vous récoltez plusieurs fois par semaine sur une même série, ne créez pas plusieurs interventions de récolte. Vous gagnerez du temps en créant une seule intervention pour la semaine et en ajoutant dessus à chaque fois un complément de récolte.").'</p>';

				return [
					'icon' => \Asset::icon('basket2-fill'),
					'title' => s("Récoltes multiples"),
					'content' => $h,
					'image' => TRUE,
					'button' => [$link, s("Revenir sur le planning")],
				];

			case 'series-forecast' :

				$link = FarmUi::urlCultivationForecast($eFarm);

				$h = '<p>'.s("À partir de votre plan de culture ou même en amont, vous pouvez obtenir le prévisionnel financier de votre année. Il ne vous reste qu'à saisir vos prix de ventes pour les particuliers et les professionnels, ainsi que la répartition des ventes entre les deux !").'</p>';

				return [
					'icon' => \Asset::icon('currency-euro'),
					'title' => s("Votre prévisionnel financier est prêt !"),
					'content' => $h,
					'image' => TRUE,
					'button' => [$link, s("Voir mon prévisionnel")],
				];

			case 'blog' :

				$link = 'https://blog.ouvretaferme.org/';

				$h = '<p>'.s("Vous y trouverez :").'</p>';
				$h .= '<ul>';
					$h .= '<li>'.s("Les annonces de nouvelles fonctionnalités au fur et à mesure de leur développement,").'</li>';
					$h .= '<li>'.s("la feuille de route avec les priorités pour les mois à venir,").'</li>';
					$h .= '<li>'.s("et des ressources pour faciliter la prise en main du site !").'</li>';
				$h .= '</ul>';

				return [
					'icon' => \Asset::icon('book'),
					'title' => s("Suivez le blog de {siteName} !"),
					'content' => $h,
					'image' => TRUE,
					'button' => [$link, s("Lire le blog")],
				];

			case 'accounting-invoice-cashflow' :

				$link = \company\CompanyUi::urlAccount($eFarm).'/thirdParty';

				$h = '<p>'.s("Il vous est possible de marquer vos factures <b>payées</b> avec son moyen de paiement, en même temps que vous créez une écriture comptable à partir d'une opération bancaire.").'</p>';
				$h .= '<p>'.s("Pour que cela fonctionne, il faut :").'</p>';
				$h .= '<ul>';
					$h .= '<li>'.s("Avoir créé le tiers,").'</li>';
					$h .= '<li>'.s("Avoir indiqué le lien entre le tiers et le client de votre ferme,").'</li>';
					$h .= '<li>'.s("Que le montant de l'opération bancaire soit identique à la facture, à 1€ près,").'</li>';
					$h .= '<li>'.s("Que la facture soit encore au statut \"non réglée\".").'</li>';
				$h .= '</ul>';
				$h .= '<p>'.s("Cette petite icône {icon} apparaîtra sur la ligne de l'opération bancaire pour vous signaler qu'une facture correspondante a été trouvée.", ['icon' => \Asset::icon('magic')]).'</p>';

				return [
					'icon' => \Asset::icon('list-check'),
					'title' => s("Rapprochez factures et opérations bancaires"),
					'content' => $h,
					'image' => FALSE,
					'button' => [$link, s("Configurer mes tiers")],
				];

			default:
				throw new \Exception('Invalid tip \''.$fqn.'\'');

		}

	}

}
?>
