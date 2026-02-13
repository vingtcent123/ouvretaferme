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
					$h .= '<div class="tip-intro">'.\Asset::icon('lightbulb').'</div>';
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
								$h .= '<a href="/farm/tip:click?id='.$tip.'&redirect='.urlencode($content['button'][0]).'" data-ajax-navigation="never" target="_blank">'.\Asset::image('farm', $tip.'.png').'</a>';
							} else {
								$h .= \Asset::image('farm', $tip.'.png');
							}
						$h .= '</div>';
					}
				$h .= '</div>';
				$h .= '<div class="tip-button">';
					if($content['button'] !== NULL) {
						$h .= '<a href="/farm/tip:click?id='.$tip.'&redirect='.urlencode($content['button'][0]).'" data-ajax-navigation="never" target="_blank" class="btn btn-outline-tip">'.$content['button'][1].'</a>';
					}
					if($navigation === 'close' or $navigation === 'inline') {
						$h .= '<a data-ajax="/farm/tip:close?id='.$tip.'" data-ajax-method="get" class="btn btn-tip">'.\Asset::icon('x-lg').'  '.s("Cacher cette astuce").'</a>';
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
					'button' => NULL,
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

				$h = '<p>'.s("Simplifiez-vous l'interface de {siteName} en désactivant cette fonctionnalité. Vous ne serez plus dérangé par les interfaces de saisie de temps de travail dans votre planning. Vous pourrez réactiver cette fonctionnalité ultérieurement !").'</p>';

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

				$link = FarmUi::urlSellingSalesMarket($eFarm);

				$h = '<p>'.s("Au marché avec votre téléphone ou une tablette, saisissez vos ventes avec le logiciel de caisse intégré sur {siteName}. Plus besoin d'une balance sophistiquée. À la fin du marché, vous savez exactement ce que vous avez vendu.").'</p>';

				return [
					'icon' => \Asset::icon('cart2'),
					'title' => s("Le logiciel de caisse"),
					'content' => $h,
					'image' => TRUE,
					'button' => [$link, s("Aller aux ventes")],
				];

			case 'selling-photo' :

				$link = \main\MainSetting::URL_PHOTOS;

				$h = '<p>'.s("Nous mettons à votre disposition des photos libres de droit pour les principaux légumes cultivés dans les systèmes maraîchers.<br/>Vous pouvez notamment les utiliser pour vos produits sur Ouvretaferme !").'</p>';

				return [
					'icon' => \Asset::icon('image'),
					'title' => s("Des photos libres de droit pour vos produits"),
					'content' => $h,
					'image' => TRUE,
					'button' => [$link, s("Voir les photos")],
				];

			case 'selling-market-start' :

				$h = '<p>'.s("Le logiciel de caisse proposé par Ouvretaferme vous permet d'enregistrer les ventes que vous réalisez pendant vos marchés avec une tablette ou un téléphone. C'est une solution simple et efficace qui permet de gérer un grand nombre de clients par heure.").'</p>';

				$h .= '<ul>';
					$h .= '<li>'.s("Il est conforme aux contraintes réglementaires d’inaltérabilité, de sécurisation, de conservation et d’archivage des données").'</li>';
					$h .= '<li>'.s("Il est peut être utilisé par plusieurs vendeurs simultanément").'</li>';
					$h .= '<li>'.s("Il est simple d'utilisation").'</li>';
					$h .= '<li>'.s("Il est directement connecté à votre comptabilité si vous la tenez sur {siteName}").'</li>';
				$h .= '</ul>';

				$h .= '<p>'.s("Nous vous conseillons de <link>lire la documentation</link> pour tirer pleinement partie de cette fonctionnalité !", ['link' => '<a href="/doc/selling:market">']).'</p>';

				return [
					'icon' => \Asset::icon('cart2'),
					'title' => s("Le logiciel de caisse"),
					'content' => $h,
					'image' => TRUE,
					'button' => ['/doc/selling:market', s("Lire la documentation")],
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

				$h = s("Vos contacts correspondent aux adresses e-mail de vos clients, aux personnes qui se sont inscrites à la lettre d'information de votre ferme sur votre site internet ainsi que celles que vous aurez ajoutées manuellement. Pour chaque contact, vous retrouvez quelques chiffres qui vous permettent notamment de savoir si vos e-mails parviennent bien à leurs destinataires.");

				return [
					'icon' => \Asset::icon('envelope'),
					'title' => s("Que sont les contacts ?"),
					'content' => $h,
					'image' => FALSE,
					'button' => NULL,
				];

			case 'mailing-campaign-help' :

				$h = '<p>'.s("Un campagne est un envoi groupé d'e-mails à certains de vos contacts. Vous pouvez par exemple envoyer un e-mail à tous les clients qui ont déjà été livrés sur une boutique en ligne afin de les prévenir de l'ouverture de la prochaine vente.").'</p>';
				$h .= '<p>'.s("Cette fonctionnalité est une source de coût pour l'association {siteName}, vous êtes donc limité à {value} envoi d'e-mails par semaine.", $eFarm->getCampaignLimit()).'</p>';
				$h .= '<p>'.s("Enfin, il n'est pas possible d'envoyer des e-mails aux contacts pour qui vous avez désactivé l'envoi des e-mails ainsi que ceux qui ont refusé vos communications. Rappelez-vous qu'en envoyant des e-mails non sollicités ou en refusant de désabonner les clients qui le souhaitent, vous engagez votre propre responsabilité et vous exposez à un bannissement à vie de {siteName}.").'</p>';

				return [
					'icon' => \Asset::icon('envelope'),
					'title' => s("Les campagnes d'e-mailing"),
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

				$link = \farm\FarmUi::urlConnected($eFarm).'/banque/imports:import';

				$h = '<p>'.s("Il vous est possible de marquer vos factures <b>payées</b> avec leur moyen et date de paiement, en les rapprochant avec une opération bancaire.").'</p>';
				$h .= '<p>'.s("Pour que cela fonctionne, il faut :").'</p>';
				$h .= '<ul>';
					$h .= '<li>'.s("Avoir réalisé un import bancaire,").'</li>';
					$h .= '<li>'.s("Avoir une facture du montant correspondant,").'</li>';
				$h .= '</ul>';
				$h .= '<p>'.s("{siteName} calculera un <b>indice de confiance</b> en se basant sur les critères suivants :").'</p>';
				$h .= '<ul>';
					$h .= '<li>'.s("La correspondance entre le nom du client sur la facture avec le nom du tiers sur l'opération bancaire,").'</li>';
					$h .= '<li>'.s("Le numéro de facture est indiqué dans l'intitulé de l'opération bancaire").'</li>';
					$h .= '<li>'.s("Le moyen de paiement indiqué sur la facture correspond à ce qui est indiqué sur l'opération bancaire.").'</li>';
					$h .= '<li>'.s("Le montant de la facture correspond à celui de l'opération bancaire.").'</li>';
					$h .= '<li>'.s("La date du paiement de la facture arrive dans le mois qui suit la date de la facture.").'</li>';
				$h .= '</ul>';
				$h .= '<p>'.s("Une petite icone {icon} sera ensuite affichée sur la page des factures ou la page des opérations bancaires et vous indiquera que des rapprochement peuvent être faits. Cliquez dessus pour les vérifier et les valider.", ['icon' => \Asset::icon('fire')]).'</p>';
				$h .= '<p>'.s("Une fois les rapprochements validés, vos factures seront marquées payées et le moyen de paiement et la date de paiement renseignés. Tout simplement !").'</p>';

				return [
					'icon' => \Asset::icon('list-check'),
					'title' => s("Rapprochez factures et opérations bancaires"),
					'content' => $h,
					'image' => FALSE,
					'button' => NULL,
				];

			case 'accounting-pre-accounting' :

				$h = '<p>'.s("La précomptabilité est l'opération préparatoire de vos ventes avant l'intégration dans votre comptabilité.").'</p>';
				$h .= '<p>'.s("Cette opération se fait en plusieurs étapes, si elles ne sont pas déjà réalisées au fur et à mesure :").'</p>';
				$h .= '<ul style="list-style-type: none;">';
					$h .= '<li>'.\Asset::icon('1-circle-fill').' '.s("Associer un numéro de compte à <link>chaque produit</link> et aux articles sans référence produit", [
						'link' => '<a href="'.\farm\FarmUi::urlSellingProducts($eFarm).'">',
						'linkType' => '<a href="/farm/configuration:update?id='.$eFarm['id'].'">',
					]).'</li>';
					$h .= '<li>'.\Asset::icon('2-circle-fill').' '.s("Renseigner le moyen de paiement des factures").'</li>';
					$h .= '<li>'.\Asset::icon('3-circle-fill').' '.s("Intégrer en comptabilité :").'</li>';
				$h .= '</ul>';
				$h .= \Asset::icon('arrow-up-right', ['style' => 'margin-bottom: -0.5rem; margin-left: 4rem; margin-right: 0.5rem;']).' '.s("en exportant un <span>FEC</span> (<i>fichier des écritures comptables</i>) de vos ventes et factures pour l'intégrer dans votre outil comptable", ['span' => '<span class="util-badge bg-primary">']);
				$h .= '<br />';
				$h .= \Asset::icon('arrow-down-right', ['style' => 'margin-top: -0.5rem; margin-left: 4rem; margin-right: 0.5rem;']).' '.s("en important vos factures dans le logiciel comptable de Ouvretaferme");

				return [
					'icon' => \Asset::icon('file-spreadsheet'),
					'title' => s("Qu'est-ce que la précomptabilité ?"),
					'content' => $h,
					'image' => FALSE,
					'button' => ['/doc/accounting', \Asset::icon('person-raised-hand').' '.s("En savoir plus avec l'Aide")],
				];

			case 'accounting-cash' :

				$h = '<p>'.s("Un journal de caisse est un document comptable qui sert à enregistrer de manière chronologique et détaillée toutes les transactions en espèces, comprenant à la fois les recettes et les dépenses. Le journal de caisse est obligatoire dès lors que vous manipulez des espèces.").'</p>';
				$h .= '<p>'.s("{siteName} vous permet de créer un nombre illimité de journaux de caisses conformes aux exigences de la réglementation. Vous avez également la possibilité de créer des journaux pour d'autres moyens de paiement que les espèces, par exemple si vous voulez faire un suivi de vos chèques.").'</p>';
				$h .= '<h3>'.s("Utiliser le journal de caisse").'</h3>';
				$h .= '<ul style="list-style-type: none;">';
					$h .= '<li>'.\Asset::icon('1-circle-fill').' '.s("Configurez le moyen de paiement du journal de caisse").'</li>';
					$h .= '<li>'.\Asset::icon('2-circle-fill').' '.s("Commencez à saisir vos transactions").'</li>';
				$h .= '</ul>';

				return [
					'icon' => \Asset::icon('journal-text'),
					'title' => s("Qu'est-ce qu'un journal de caisse ?"),
					'content' => $h,
					'image' => FALSE,
					'button' => NULL,
				];

			case 'accounting-custom-account' :

				$h = '<p>'.s("Les numéros de comptes peuvent être personnalisés pour refléter au mieux vos habitudes.").'</p>';
				$h .= '<p>'.s("Vous pouvez :").'</p>';
				$h .= '<ul>';
					$h .= '<li>'.s("<b>Créer des numéros</b> de compte personnalisés, par exemple pour créer un compte-courant par associé").'</li>';
					$h .= '<li>'.s("<b>Personnaliser le journal</b> par défaut à utiliser pour chaque numéro de compte").'</li>';
				$h .= '</ul>';
				$h .= '<p>'.s("Ces paramétrages vous permettront d'être plus efficaces lors de la saisie de vos écritures comptables, mais aussi, si vous êtes plusieurs à saisir la comptabilité de votre exploitation, de mettre en place des règles que tout le monde pourra respecter facilement !").'</p>';

				return [
					'icon' => \Asset::icon('list-columns-reverse'),
					'title' => s("La personnalisation des numéros de compte"),
					'content' => $h,
					'image' => FALSE,
					'button' => NULL,
				];

			case 'accounting-cashflow-attach' :

				$h = '<p>'.s("Vous pouvez rattacher à une opération bancaire des écritures comptables déjà saisies mais non équilibrées par une contrepartie en compte {bankAccount}. La contrepartie en compte de banque {bankAccount} du montant de l'opération bancaire sera la seule écriture créée.", ['bankAccount' => '<b>'.\account\AccountSetting::BANK_ACCOUNT_CLASS.'</b>']).'</p>';
				$h .= '<p>'.s("Les écritures proposées sont classées avec le tiers sélectionné à l'étape {icon} en premier, mais il est possible de sélectionner des écritures liées à d'autres tiers.<br /><i>exemple de cas d'usage: si votre opération bancaire correspond à un virement {iconStripe} Stripe et que vous y rattachez toutes les écritures de ventes de vos clients</i>.", ['iconStripe' => \Asset::icon('stripe'), 'icon' => \Asset::icon('1-circle')]).'</p>';

				return [
					'icon' => \Asset::icon('bank'),
					'title' => s("Rattacher une opération bancaire à une ou plusieurs écritures comptables"),
					'content' => $h,
					'image' => FALSE,
					'button' => ['/doc/accounting:bank#cashflow-manage', \Asset::icon('person-raised-hand').' '.s("Lire l'aide sur le rattachement des opérations bancaires")],
				];

			case 'accounting-operation-attach' :

				$h = '<p>'.s("Vous pouvez rattacher des écritures comptables à une opération bancaire. La contrepartie en compte de banque {bankAccount} du montant de l'opération bancaire sera la seule écriture créée.", ['bankAccount' => '<b>'.\account\AccountSetting::BANK_ACCOUNT_CLASS.'</b>']).'</p>';
				$h .= '<p>'.s("Les opérations bancaires proposées sont filtrées selon le montant ± 1€, et elles ne doivent pas déjà être rattachées à une écriture comptable.", ).'</p>';

				return [
					'icon' => \Asset::icon('bank'),
					'title' => s("Rattacher une ou plusieurs écritures comptables à une opération bancaire"),
					'content' => $h,
					'image' => FALSE,
					'button' => ['/doc/accounting:bank#cashflow-manage', \Asset::icon('person-raised-hand').' '.s("Lire l'aide sur le rattachement des opérations bancaires")],
				];

			case 'accounting-financial-year-created' :

				$importButton = '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/fec:import" class="btn btn-sm btn-primary btn-md">';
					$importButton .= s("Importer un fichier FEC");
				$importButton .= '</a>';

				$h = '<p><b>'.s("Et maintenant ?").'</b></p>';

				$h .= '<ul>';

					if($eFarm['cFinancialYear']->empty()) {
						$h .= '<li>'.s("Si c'est le <b>premier exercice de votre exploitation</b>, vous pouvez dès à présent réaliser le bilan d'ouverture : aucune écriture ne sera créée.").'</li>';
					}

					if($eFarm['eFinancialYear']->acceptImportFec()) {
						$h .= '<li>';
							$h .= s("Si vous avez déjà créé des <b>écritures dans un autre logiciel comptable</b> pour cet exercice, vous pouvez les importer sur {siteName} en cliquant sur {button}.", ['button' => $importButton]);
						$h .= '</li>';
					}

					if($eFarm['eFinancialYear']->acceptOpen() and $eFarm['cFinancialYear']->empty() === FALSE) {
							$openButton = '<a href="'.\company\CompanyUi::urlAccount($eFarm).'/financialYear/:open?id='.$eFarm['eFinancialYear']['id'].'">';
								$openButton .= s("bilan d'ouverture");
							$openButton .= '</a>';
						$h .= '<li>'.s("Réalisez le {button} pour reprendre le bilan de l'exercice précédent", ['button' => $openButton]).'</li>';
					}

					$h .= '</ul>';

				$h .= '<p><b>'.s("Et après ?").'</b></p>';

				$h .= '<ul>';

					$h .= '<li><a href="'.\farm\FarmUi::urlConnected($eFarm).'/precomptabilite:importer">'.s("Importez vos factures rapprochées").'</a></li>';
					$h .= '<li><a href="'.\company\CompanyUi::urlJournal($eFarm).'/livre-journal">'.s("Enregistrez des écritures comptables").'</a></li>';

				$h .= '</ul>';

				return [
					'icon' => \Asset::icon('journal-plus'),
					'title' => s("Votre exercice comptable est maintenant créé !"),
					'content' => $h,
					'image' => FALSE,
					'button' => ['/doc/accounting:start', \Asset::icon('person-raised-hand').' '.s("Lire l'aide sur les exercices comptables")],
				];

			default:
				throw new \Exception('Invalid tip \''.$fqn.'\'');

		}

	}

}
?>
