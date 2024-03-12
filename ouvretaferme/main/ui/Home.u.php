<?php
namespace main;

class HomeUi {

	public function __construct() {

		\Asset::css('main', 'home.css');

	}

	public function getFarms(\Collection $cFarm): string {

		$h = '';

		if($cFarm->empty()) {
			if((new \farm\Farm())->canCreate()) {
				$h .= (new \farm\FarmerUi())->getNoFarms();
			} else {
				$h .= '';
			}
		} else {

			$h .= '<h2>'.($cFarm->count() === 1 ? s("Ma ferme") : s("Mes fermes")).'</h2>';
			$h .= (new \farm\FarmerUi())->getMyFarms($cFarm);

		}

		return $h;

	}

	public function getTraining(bool $hide = FALSE): string {

		if(currentDate() > \Setting::get('main\limitTraining')) {
			return '';
		}

		if(\user\ConnectionLib::isLogged() and $hide) {

			$eUser = \user\ConnectionLib::getOnline();

			$key = 'training-202403-'.$eUser['id'];
			$expires = (strtotime(\Setting::get('main\limitTraining')) + 86400 * 7 - time());

			if(get_exists('training')) {
				\Cache::redis()->set($key, 5);
				return '';
			}

			if(\Cache::redis()->add($key, 0, $expires) === FALSE) {

				$newValue = \Cache::redis()->get($key) + 1;

				if($newValue > 3) {
					return '';
				}

				\Cache::redis()->set($key, $newValue);

			}

		}

		$h = '<div class="home-blog bg-training util-block-flat">';
			$h .= '<div>';
				$h .= \Asset::image('main', 'favicon.png').'';
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<h4 class="mb-0 color-secondary">'.s("21, 22 ou 26 mars 2024 en Auvergne").'</h4>';
				$h .= '<h2 class="font-oswald">';
					$h .= s("Formations sur {siteName} !");
				$h .= '</h2>';
				$h .= '<div>';
					$h .= '<p>'.s("Des formations à la journée finançables VIVEA sont organisées pour {siteName}. Une occasion idéale pour prendre en main ou se perfectionner sur {siteName}, discuter des évolutions possibles et échanger sur vos problématiques !").'</p>';
					$h .= '<a href="/presentation/formations" target="_blank" class="btn btn-secondary" style="margin-bottom: 0.25rem">'.\Asset::icon('chevron-right').' '.s("En savoir plus").'</a>';
					if(\user\ConnectionLib::isLogged() and $hide) {
						$h .= ' <a href="'.\util\HttpUi::setArgument(LIME_REQUEST, 'training', 1).'" class="btn btn-secondary" style="margin-bottom: 0.25rem">'.\Asset::icon('x-lg').' '.s("Ok, cacher ce message").'</a>';
					}
				$h .= '</div>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getBlog(\website\News $eNews, bool $displayFallback): string {

		\Asset::css('main', 'font-oswald.css');

		if($eNews->empty()) {

			if($displayFallback === FALSE) {
				return '';
			}

			$h = '<h2>'.s("Quoi de neuf sur {siteName} ?").'</h2>';

			$h .= '<div class="home-blog bg-info util-block-flat">';
				$h .= \Asset::image('main', 'favicon.png', ['style' => 'width: 6rem; height: 6rem']).'';
				$h .= '<div>';
					$h .= '<p class="font-oswald" style="font-size: 1.3rem; line-height: 1.3">'.s("Suivez le blog de {siteName} pour retrouver les annonces de nouvelles fonctionnalités, la feuille de route avec les priorités de développement pour les mois à venir  et des ressources pour faciliter la prise en main du site !").'</p>';
					$h .= '<a href="https://blog.ouvretaferme.org/" target="_blank" class="btn btn-secondary">'.\Asset::icon('chevron-right').' '.s("Découvrir le blog").'</a>';
				$h .= '</div>';
			$h .= '</div>';

		} else {

			$content = (new \editor\ReadorFormatterUi())->getFromXml($eNews['content']);

			$start = strpos($content, '<p>') + 3;
			$length = strpos($content, '</p>') - $start;

			$content = substr($content, $start, $length);

			$h = '<h2>'.s("Du nouveau sur {siteName} !").'</h2>';

			$h .= '<div class="home-blog bg-info util-block-flat">';
				$h .= '<div>';
					$h .= \Asset::image('main', 'favicon.png').'';
				$h .= '</div>';
				$h .= '<div>';
					$h .= '<h4 class="mb-0 color-secondary">'.\util\DateUi::textual($eNews['publishedAt'], \util\DateUi::DATE).'</h4>';
					$h .= '<h2 class="font-oswald">';
						$h .= encode($eNews['title']);
					$h .= '</h2>';
					$h .= '<div>';
						$h .= '<p>'.$content.'</p>';
						$h .= '<a href="https://blog.ouvretaferme.org/" target="_blank" class="btn btn-secondary">'.\Asset::icon('chevron-right').' '.s("Lire la suite").'</a>';
					$h .= '</div>';
				$h .= '</div>';
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
	
	public function getPoints(): string {

		\Asset::css('main', 'font-oswald.css');

		$h = '<h2>'.s("Principes de conception de {siteName}").'</h2>';
		
		$h .= '<div class="home-points">';
			$h .= '<div class="home-point">';
				$h .= \Asset::icon('lock');
				$h .= '<h4>'.s("Vos données ne sont<br/>ni vendues, ni partagées").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-point">';
				$h .= \Asset::icon('columns-gap');
				$h .= '<h4>'.s("Facilite la vie<br/>sans décider à votre place").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-point">';
				$h .= \Asset::icon('people');
				$h .= '<h4>'.s("Plateforme conçue<br/>par et pour des producteurs").'</h4>';
			$h .= '</div>';
			$h .= '<div class="home-point">';
				$h .= \Asset::icon('phone');
				$h .= '<h4>'.s("Accessible<br/>sur ordinateur et téléphone").'</h4>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

}
?>
