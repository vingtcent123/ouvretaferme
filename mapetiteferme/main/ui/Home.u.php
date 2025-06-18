<?php
namespace main;

class HomeUi {

	public function __construct() {

		\Asset::css('main', 'home.css');

	}

	public function getCompanies(\Collection $cCompany): string {

		$h = '';

		if($cCompany->empty()) {
			if(new \company\Company()->canCreate()) {
				$h .= new \company\EmployeeUi()->getNoCompany();
			} else {
				$h .= '';
			}
		} else {

			$h .= '<h2>'.($cCompany->count() === 1 ? s("Ma ferme") : s("Mes fermes")).'</h2>';
			$h .= new \company\EmployeeUi()->getMyCompanies($cCompany);

		}

		return $h;

	}

	public function getDescriptionMore(string $block): string {

		\Asset::js('main', 'home.js');

		switch($block) {

			case 'planification':
					$description = '<ul>'
						.'<li>'.\Asset::icon('calendar3').' '.s("Planifiez votre saison de culture en concevant vos plans de culture et plans d'assolement").'</li>'
						.'<li>'.\Asset::icon('cart4').' '.s("Consultez les quantités de semences et plants à produire ou commander").'</li>'
						.'<li>'.\Asset::icon('file-earmark-richtext').' '.s("Créez des itinéraires techniques, réutilisez et améliorez-les saison après saison").'</li>'
						.'<li>'.\Asset::icon('gear').' '.s("Enregistrez et suivez le matériel disponible à la ferme").'</li>'
					.'</ul>';
					$description .= '<sup>*</sup>'.\Asset::icon('stars').' '.s("Le module de production est inclus avec le module de planification !");
					$price = '<p>'.s("{price}<br />/ an HT", ['price' => '<span>'.\util\TextUi::money(\Setting::get('company\subscriptionPrices')[\company\SubscriptionElement::PRODUCTION], 'EUR', 0).'</span>']).'</p>';
					break;

			case 'production':
					$description = '<ul>'
						.'<li>'.\Asset::icon('arrow-repeat').' '.s("Suivez précisément vos rotations sur votre parcellaire").'</li>'
						.'<li>'.\Asset::icon('people').' '.s("Collaborez avec votre équipe : chacun est autonome sur son périmètre").'</li>'
						.'<li>'.\Asset::icon('calendar-week').' '.s("Renseignez les interventions sur le planning et analysez où votre temps est consacré").'</li>'
					.'</ul>';
					$description .= '<sup>*</sup>'.\Asset::icon('stars').' '.s("Le module de planification est inclus avec le module de production !");
					$price = '<p>'.s("{price}<br />/ an HT", ['price' => '<span>'.\util\TextUi::money(\Setting::get('company\subscriptionPrices')[\company\SubscriptionElement::PRODUCTION], 'EUR', 0).'</span>']).'</p>';
					break;

			case 'sale':
					$description = '<ul>'
						.'<li>'.\Asset::icon('currency-euro').' '.s("Créez des ventes à partir de vos produits, gérez vos clients et vos prix.").'</li>'
						.'<li>'.\Asset::icon('phone').' '.s("Utilisez le logiciel de caisse paramétré avec vos produits et vos prix, simplement avec un téléphone ou une tablette.").'</li>'
						.'<li>'.\Asset::icon('shop').' '.s("Ouvrez une boutique en ligne pour un retrait au lieu et à l'horaire convenu, ou livrez à domicile.").'</li>'
						.'<li>'.\Asset::icon('stripe').' '.s("Inclus : paiement par carte bancaire via Stripe sans commission de {name}.", ['name' => \Lime::getName()]).'</li>'
					.'</ul>';
					$price = '<p>'.s("{price}<br />/ an HT", ['price' => '<span>'.\util\TextUi::money(\Setting::get('company\subscriptionPrices')[\company\SubscriptionElement::SALES], 'EUR', 0).'</span>']).'</p>';
					break;

			case 'accounting':
					$description = '<ul>'
						.'<li>'.\Asset::icon('upload').' '.s("Importez vos relevés bancaires et créez vos opérations comptables facilement à partir de ces transactions.").'</li>'
						.'<li>'.\Asset::icon('wallet').' '.s("Utilisez vos données de vente du module de vente pour les rapprocher avec vos transactions.").'</li>'
						.'<li>'.\Asset::icon('journals').' '.s("Exportez vos documents comptables en un clic (grand livre, ...).").'</li>'
					.'</ul>';
					$price = '<p>'.s("{price}<br />/ an HT", ['price' => '<span>'.\util\TextUi::money(\Setting::get('company\subscriptionPrices')[\company\SubscriptionElement::ACCOUNTING], 'EUR', 0).'</span>']).'</p>';
					break;

			default:
				throw new \Exception('not expected');

		}

		return '<div class="home-description-detail-container">'
			.'<div class="home-description-detail-content">'
				.$description
			.'</div>'
			.'<div class="home-description-detail-price">'
				.$price
			.'</div>'
		.'</div>';

	}

	public function getPoints(): string {

		\Asset::css('main', 'font-oswald.css');

		$h = '<h2>'.s("Principes de conception de {siteName}").'</h2>';
		
		$h .= '<div class="home-points">';
			$h .= '<div class="home-point" style="grid-column: span 2">';
				$h .= \Asset::icon('inboxes');
				$h .= '<h4>'.s("Toutes les fonctionnalités sont indépendantes,<br/>vous utilisez seulement celles adaptées à votre activité !").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-point" style="grid-column: span 2">';
				$h .= \Asset::icon('columns-gap');
				$h .= '<h4>'.s("Les interfaces sont simples et intuitives,<br/>elles s'adaptent à vos pratiques").'</h4>';
			$h .= '</div>';
		$h .= '</div>';
		
		$h .= '<div class="home-points">';
			$h .= '<div class="home-point">';
				$h .= \Asset::icon('lock');
				$h .= '<h4>'.s("Vos données ne sont<br/>ni vendues, ni partagées").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-point">';
				$h .= \Asset::icon('cup-hot');
				$h .= '<h4>'.s("Conçu pour réduire la charge mentale<br/>sans décider à votre place").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-point">';
				$h .= \Asset::icon('flower2');
				$h .= '<h4>'.s("Développé dans le respect<br/>des bonnes pratiques écologiques").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-point">';
				$h .= \Asset::icon('phone');
				$h .= '<h4>'.s("Accessible facilement<br/>sur ordinateur et téléphone").'</h4>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

}
?>
