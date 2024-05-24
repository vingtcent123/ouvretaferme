<?php
namespace shop;

class ShopManageUi {

	public function __construct() {
		\Asset::css('shop', 'shop.css');
	}
	
	public function getIntroCreate(\farm\Farm $eFarm): string {
		
		$h = '<h1>'.s("Une boutique pour votre ferme").'</h1>';

		$h .= '<div class="util-block-help">';
			$h .= '<h4>'.s("Vendre ma production").'</h4>';
			$h .= '<p>'.s("Avec {siteName}, il est très facile de vendre votre production en ligne aux particuliers !").'</p>';
			$h .= '<ol>';
				$h .= '<li>'.s("Créez une boutique").'</li>';
				$h .= '<li>'.s("Ajoutez les produits que vous souhaitez vendre").'</li>';
				$h .= '<li>'.s("Définissez les points de livraison ou livrez vos clients à domicile").'</li>';
				$h .= '<li>'.s("Choisissez une première date de livraison").'</li>';
				$h .= '<li>'.s("Communiquez l'adresse de la boutique en ligne à vos clients").'</li>';
				$h .= '<li>'.s("Récupérez la liste des commandes et préparez les colis").'</li>';
				$h .= '<li>'.s("Et enfin servez vos clients !").'</li>';
			$h .= '</ol>';
		$h .= '</div>';

		$h .= '<br/>';

		$h .= '<h3>'.s("Créer une boutique").'</h3>';

		$h .= (new ShopUi())->create($eFarm)->body;

		return $h;

	}

	public function getHeader(\farm\Farm $eFarm, Shop $eShopCurrent, \Collection $cShop): string {

		$eShopCurrent->expects(['cDate']);

		$h = '<div class="util-action">';
			$h .= '<div class="shop-title">';
				$h .= (new \media\ShopLogoUi())->getCamera($eShopCurrent, size: '5rem');
				$h .= '<div>';
					$h .= '<h1>';
						$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">';
							$h .= encode($eShopCurrent['name']).' '.\farm\FarmUi::getNavigation();
						$h .= '</a>';
						$h .= '<div class="dropdown-list bg-secondary">';
							$h .= '<div class="dropdown-title">'.s("Mes boutiques").'</div>';
							foreach($cShop as $eShop) {
								$h .= '<a href="'.ShopUi::adminUrl($eFarm, $eShop).'" class="dropdown-item '.($eShop['id'] === $eShopCurrent['id'] ? 'selected' : '').'">';
									$h .= encode($eShop['name']);
								$h .= '</a> ';
							}
							if((new Shop(['farm' => $eFarm]))->canCreate()) {
								$h .= '<div class="dropdown-divider"></div>';
								$h .= '<a href="/shop/:create?farm='.$eFarm['id'].'" class="dropdown-item">';
									$h .= s("Créer une nouvelle boutique");
								$h .= '</a> ';
							}
						$h .= '</div>';
					$h .= '</h1>';
					$h .= '<div class="util-action-subtitle">';
						$h .= '<a href="'.ShopUi::url($eShopCurrent).'">'.ShopUi::url($eShopCurrent, showProtocol: FALSE).'</a>';;
					$h .= '</div>';
				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div>';

				if($eShopCurrent->canWrite()) {

					$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('gear-fill').'</a>';
					$h .= '<div class="dropdown-list bg-primary">';
						$h .= '<div class="dropdown-title">'.encode($eShopCurrent['name']).'</div>';
						$h .= '<a href="/shop/configuration:update?id='.$eShopCurrent['id'].'" class="dropdown-item">'.s("Paramétrer la boutique").'</a>';
						$h .= '<a href="/shop/:website?id='.$eShopCurrent['id'].'&farm='.$eFarm['id'].'" class="dropdown-item">'.s("Intégrer sur votre site internet").'</a>';
						$h .= '<a href="/shop/:emails?id='.$eShopCurrent['id'].'&farm='.$eFarm['id'].'" class="dropdown-item">'.s("Obtenir les adresses e-mail des clients").'</a>';
						if($eShopCurrent['cDate']->empty()) {
							$h .= '<div class="dropdown-divider"></div>';
							$h .= '<a data-ajax="/shop/:doDelete" post-id="'.$eShopCurrent['id'].'" class="dropdown-item" data-confirm="'.s("Voulez-vous réellement supprimer définitivement cette boutique ?").'">'.s("Supprimer la boutique").'</a>';
						}
					$h .= '</div>';

				}

			$h .= '</div>';
		$h .= '</div>';

		return $h;
		
	}

	public function getList(\farm\Farm $eFarm, \Collection $cShop): string {

		$h = '<div class="util-action">';
			$h .= '<h1>'.s("Boutiques en ligne").'</h1>';
			$h .= '<a href="/shop/:create?farm='.$eFarm['id'].'" class="btn btn-secondary">'.\Asset::icon('plus-circle').' '.s("Nouvelle boutique").'</a>';
		$h .= '</div>';

		$cShop->setColumn('hasDate', fn($eShop) => (
			$eShop['status'] !== Shop::CLOSED and
			$eShop['eDate']->notEmpty()
		));

		$hasDate = $cShop->match(fn($eShop) => $eShop['hasDate']);

		$h .= '<div class="shop-list">';

			foreach($cShop as $eShop) {

				$h .= '<a href="'.ShopUi::adminUrl($eFarm, $eShop).'" class="shop-list-item util-block">';

					$h .= '<div class="shop-list-item-header">';
						$h .= ShopUi::getLogo($eShop, '3rem');
						$h .= '<h2>';
							$h .= encode($eShop['name']);
						$h .= '</h2>';
					$h .= '</div>';

					if($hasDate) {

						$h .= '<div class="shop-list-item-content">';

						if($eShop['hasDate']) {

								$eDate = $eShop['eDate'];

								$h .= '<h4>'.(new DateUi())->getStatus($eShop, $eDate, withColor: FALSE).'</h4>';

								$h .= '<dl class="util-presentation util-presentation-2">';

									$h .= '<dt>'.s("Date").'</dt>';
									$h .= '<dd>';
										$h .= \util\DateUi::textual($eDate['deliveryDate']);
									$h .= '</dd>';

									$h .= '<dt>'.s("Ventes").'</dt>';
									$h .= '<dd>'.$eDate['sales']['countValid'].'</dd>';

									if($eDate['sales']['countValid'] > 0) {
										$h .= '<dt>'.s("Montant").'</dt>';
										$h .= '<dd>'.($eDate['sales']['amountValidIncludingVat'] ? \util\TextUi::money($eDate['sales']['amountValidIncludingVat']) : '-').'</dd>';
									}

								$h .= '</dl>';

						} else {
							if($eShop['status'] === Shop::CLOSED) {
								$h .= '<h4 class="color-muted">'.s("Boutique fermée").'</h4>';
							} else {
								$h .= '<h4 class="color-muted">'.s("Aucune vente").'</h4>';
							}
						}

						$h .= '</div>';

					}

				$h .= '</a>';

			}
		$h .= '</div>';

		return $h;

	}

	public function getTabsContent(\farm\Farm $eFarm, Shop $eShop): string {

		$h = '<div class="tabs-h" id="shop-tabs" onrender="'.encode('Lime.Tab.restore(this, "dates"'.(get_exists('tab') ? ', "'.GET('tab', ['dates', 'points'], 'dates').'"' : '').')').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="dates" onclick="Lime.Tab.select(this)">'.s("Dernières ventes").'</a>';
				$h .= '<a class="tab-item" data-tab="points" onclick="Lime.Tab.select(this)">'.s("Tous les modes de livraison").'</a>';
			$h .= '</div>';

			$h .= '<div class="tab-panel selected" data-tab="dates">';
				$h .= self::getDateList($eFarm, $eShop, TRUE);
			$h .= '</div>';

			$h .= '<div class="tab-panel selected" data-tab="points">';
				$h .= (new PointUi())->getList($eShop, $eShop['ccPoint'], TRUE);
			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

	public function getInlineContent(\farm\Farm $eFarm, Shop $eShop): string {

		$h = '';

		if(
			$eShop['ccPoint']->empty() and
			$eShop['cDate']->empty() and
			$eShop->canWrite()
		) {
			$h .= (new PointUi())->createFirst();
		}

		$h .= (new PointUi())->getList($eShop, $eShop['ccPoint'], FALSE);

		if($eShop['cDate']->empty()) {

			if($eShop['ccPoint']->notEmpty()) {
				$h .= $this->createFirstDate($eFarm, $eShop);
			}

		} else {
			$h .= self::getDateList($eFarm, $eShop, FALSE);
		}

		return $h;
	}
	
	public function createFirstDate(\farm\Farm $eFarm, Shop $eShop): string {

		if($eShop->canWrite() === FALSE) {
			return '';
		}

		$h = '<div class="util-block-help mb-2">';
			$h .= '<p>'.s("La configuration initiale de votre boutique est terminée. Si vous le souhaitez, vous pouvez continuer à personnaliser l'expérience de vos clients en activant par exemple le paiement en ligne ou en personnalisant les e-mails envoyés automatiquement à vos clients lors de leurs commandes.").'</p>';
			$h .= '<a href="/shop/:update?id='.$eShop['id'].'" class="btn btn-secondary">'.s("Continuer à personnaliser la boutique").'</a>';
		$h .= '</div>';

		$h .= '<h2>'.s("Vendre pour la première fois").'</h2>';

		$h .= '<div class="util-block-help">';
			$h .= '<p>'.s("Lorsque vous êtes satisfait de la configuration de votre boutique, créez une première vente en choisissant une date et la liste des produits que vous avez en stock et que vous souhaitez proposer à vos clients !").'</p>';
		$h .= '</div>';

		$h .= '<a href="/shop/date:create?shop='.$eShop['id'].'&farm='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter une première vente").'</a>';

		return $h;
		
	}
	
	public function getDateList(\farm\Farm $eFarm, Shop $eShop, bool $inTabs): string {

		$cDate = $eShop['cDate'];

		$h = '<div class="util-action">';

			if($inTabs) {
				$h .= '<div></div>';
			} else {
				$h .= '<h2>'.s("Prochaines ventes").'</h2>';
			}

			$h .= '<div>';
				if($eShop->canWrite()) {
					if($cDate->empty()) {
						$h .= '<a href="/shop/date:create?shop='.$eShop['id'].'&farm='.$eFarm['id'].'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Nouvelle vente").'</a>';
					} else {
						$h .= '<a data-dropdown="bottom-end" class="btn btn-outline-primary dropdown-toggle">'.\Asset::icon('plus-circle').' '.s("Nouvelle vente").'</a>';
						$h .= '<div class="dropdown-list">';
							$h .= '<a href="/shop/date:create?shop='.$eShop['id'].'&farm='.$eFarm['id'].'" class="dropdown-item">'.s("Créer une vente de zéro").'</a>';
							$h .= '<div class="dropdown-divider"></div>';
							$h .= '<div class="dropdown-item" style="font-style: italic">'.s("Créer à partir une autre vente :").'</div>';
							$count = 0;
							foreach($cDate as $eDate) {
								if($count++ >= 3) {
									break;
								}
								$h .= '<a href="/shop/date:create?shop='.$eShop['id'].'&farm='.$eFarm['id'].'&date='.$eDate['id'].'" class="dropdown-item">'.\Asset::icon('chevron-right').' '.s("Vente du {value}", \util\DateUi::textual($eDate['deliveryDate'])).'</a>';
							}
						$h .= '</div>';
					}
				}
			$h .= '</div>';
		$h .= '</div>';

		$h .= (new DateUi())->getList($eFarm, $eShop, $cDate);

		return $h;
	}
	
}
?>
