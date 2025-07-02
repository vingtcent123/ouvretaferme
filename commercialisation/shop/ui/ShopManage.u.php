<?php
namespace shop;

class ShopManageUi {

	public function __construct() {

		\Asset::css('shop', 'shop.css');
		\Asset::css('shop', 'manage.css');

	}
	
	public function getIntroCreate(\farm\Farm $eFarm): string {

		$h = '<div class="util-block-help">';
			$h .= '<h4>'.s("Vendre sa production en ligne avec {siteName} en 6 étapes").'</h4>';
			$h .= '<ol>';
				$h .= '<li>'.s("Créez une boutique").'</li>';
				$h .= '<li>'.s("Ajoutez les produits que vous souhaitez vendre").'</li>';
				$h .= '<li>'.s("Définissez des points de livraison ou livrez vos clients à domicile").'</li>';
				$h .= '<li>'.s("Créez une première vente avec vos produits").'</li>';
				$h .= '<li>'.s("Communiquez l'adresse de la boutique en ligne à vos clients").'</li>';
				$h .= '<li>'.s("Et c'est parti !").'</li>';
			$h .= '</ol>';
		$h .= '</div>';

		$h .= '<br/>';


		if($eFarm->isLegal()) {

			$h .= '<h3>'.s("Créer une boutique").'</h3>';

			$eShop = new Shop([
				'farm' => $eFarm,
				'shared' => NULL
			]);

			$h .= new ShopUi()->create($eShop)->body;

		} else {

			$h .= '<h3>'.s("Informations requises pour créer une boutique en ligne").'</h3>';

			$h .= new \farm\FarmUi()->updateLegal($eFarm);

		}

		return $h;

	}

	public function getHeader(\farm\Farm $eFarm, Shop $eShopCurrent, \Collection $ccShop): string {

		$eShopCurrent->expects(['cDate']);

		$h = '<div class="util-action">';
			$h .= '<div class="shop-title">';
				$h .= new \media\ShopLogoUi()->getCamera($eShopCurrent, size: '5rem');
				$h .= '<div>';
					$h .= '<h1 class="mb-0">';
						$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">';
							$h .= encode($eShopCurrent['name']).' '.\farm\FarmUi::getNavigation();
						$h .= '</a>';
						$h .= '<div class="dropdown-list bg-secondary">';
							$h .= '<div class="dropdown-title">'.s("Mes boutiques").'</div>';

							foreach($ccShop as $key => $cShop) {

								if($cShop->empty()) {
									continue;
								}

								if($key === 'admin') {
									$h .= '<div class="dropdown-subtitle">'.s("Administrateur").'</div>';
								} else if($key === 'selling' and $ccShop['admin']->notEmpty()) {
									$h .= '<div class="dropdown-subtitle">'.s("Producteur").'</div>';
								}

								foreach($cShop as $eShop) {
									$h .= '<a href="'.ShopUi::adminUrl($eFarm, $eShop).'" class="dropdown-item '.($eShop['id'] === $eShopCurrent['id'] ? 'selected' : '').'">';
										$h .= encode($eShop['name']);
										if($eShop['shared']) {
											$h .= ' <span class="util-badge bg-primary">'.\Asset::icon('people-fill').' '.s("Collective").'</span>';
										}
									$h .= '</a> ';
								}

							}

							if((new Shop(['farm' => $eFarm]))->canCreate()) {
								$h .= '<div class="dropdown-divider"></div>';
								$h .= '<a href="/shop/:create?farm='.$eFarm['id'].'" class="dropdown-item">';
									$h .= \Asset::icon('plus-circle').' '.s("Nouvelle boutique");
								$h .= '</a> ';
							}
						$h .= '</div>';
					$h .= '</h1>';
				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div>';

				if($eShopCurrent->canWrite()) {

					$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('gear-fill').'</a>';
					$h .= '<div class="dropdown-list bg-primary">';
						$h .= '<div class="dropdown-title">'.encode($eShopCurrent['name']).'</div>';
						$h .= '<a href="/shop/configuration:update?id='.$eShopCurrent['id'].'" class="dropdown-item">'.s("Paramétrer la boutique").'</a>';
						$h .= '<a href="/shop/:website?id='.$eShopCurrent['id'].'&farm='.$eFarm['id'].'" class="dropdown-item">'.s("Intégrer la boutique sur un site internet").'</a>';
						$h .= '<a href="/shop/:emails?id='.$eShopCurrent['id'].'&farm='.$eFarm['id'].'" class="dropdown-item">'.s("Obtenir les adresses e-mail des clients").'</a>';

						if(
							$eShopCurrent->canDelete() and
							$eShopCurrent['cDate']->empty()
						) {
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

		$cShop->setColumn('hasDate', fn($eShop) => (
			$eShop['status'] !== Shop::CLOSED and
			$eShop['date']->notEmpty()
		));


		$h = '<div class="shop-list mb-2">';

			foreach($cShop as $eShop) {

				$h .= '<a href="'.ShopUi::adminUrl($eFarm, $eShop).'" class="shop-list-item util-block">';

					$h .= '<div class="shop-list-item-header">';
						$h .= ShopUi::getLogo($eShop, '3rem');
						$h .= '<h2>';
							$h .= encode($eShop['name']);
							if($eShop['shared']) {
								$h .= ' <span class="shop-list-item-shared">'.\Asset::icon('people-fill').' '.s("Collective").'</span>';
							}
						$h .= '</h2>';
					$h .= '</div>';


						$h .= '<div class="shop-list-item-content">';

						if($eShop['hasDate']) {

								$eDate = $eShop['date'];

								$h .= '<h4>'.new DateUi()->getStatus($eShop, $eDate, withColor: FALSE).'</h4>';

								$h .= '<dl class="util-presentation util-presentation-max-content util-presentation-2">';

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
								$h .= '<h4>'.\Asset::icon('x-circle-fill').' '.s("Boutique fermée").'</h4>';
							} else {
								$h .= '<h4>'.s("Il n'y a pas encore de livraison planifiée sur cette boutique !").'</h4>';
							}
						}

						$h .= '</div>';


				$h .= '</a>';

			}
		$h .= '</div>';

		return $h;

	}

	public function getSharedContent(\farm\Farm $eFarm, Shop $eShop): string {

		$h = '';

		if($eShop->canShareRead($eFarm)) {

			$cRangeFarm = $eShop['ccRange'][$eFarm['id']] ?? new \Collection();

			if($cRangeFarm->empty()) {

				$h .= '<div class="util-block-help mb-2">';
					$h .= '<h3>'.s("Bienvenue sur cette boutique collective, {value} !", encode($eFarm['name'])).'</h3>';
					$h .= '<p>'.s("Pour commencer à vendre votre production ici, vous devez associer un ou plusieurs de vos catalogues à cette boutique. Ce sont les produits de ces catalogues qui seront proposés aux clients !").'</p>';

					if(GET('tab') !== 'farmers') {
						$h .= '<a href="/shop/range:create?farm='.$eFarm['id'].'&shop='.$eShop['id'].'?tab=farmers" class="btn btn-secondary">'.s("Associer un catalogue à cette boutique").'</a>';
					}

				$h .= '</div>';

			}

		}

		$h .= '<div class="tabs-h" id="shop-tabs" onrender="'.encode('Lime.Tab.restore(this, "farmers"'.(get_exists('tab') ? ', "'.GET('tab', ['dates', 'farmers', 'departments'], 'farmers').'"' : '').')').'">';

			$h .= '<div class="tabs-item">';

				$h .= '<a class="tab-item" data-tab="dates" onclick="Lime.Tab.select(this)">';
					$h .= s("Livraisons");
				$h .= '</a>';
				$h .= '<a class="tab-item" data-tab="farmers" onclick="Lime.Tab.select(this)">';
					$h .= s("Producteurs");
					if($eShop['cShare']->count() > 0) {
						$h .= '<span class="tab-item-count">'.$eShop['cShare']->count().'</span>';
					}
				$h .= '</a>';
				$h .= '<a class="tab-item" data-tab="departments" onclick="Lime.Tab.select(this)">';
					$h .= s("Rayons");
					if($eShop['cDepartment']->count() > 0) {
						$h .= '<span class="tab-item-count">'.$eShop['cDepartment']->count().'</span>';
					}
				$h .= '</a>';

			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="dates">';
				$h .= $this->getInlineContent($eFarm, $eShop);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="farmers">';
				$h .= new ShareUi()->getList($eFarm, $eShop, $eShop['cShare'], $eShop['cDepartment']);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="departments">';
				$h .= new DepartmentUi()->getManage($eShop, $eShop['cDepartment']);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getInlineContent(\farm\Farm $eFarm, Shop $eShop): string {

		$createPoint = (
			$eShop['hasPoint'] and
			$eShop['ccPoint']->empty() and
			$eShop['cDate']->empty() and
			$eShop->canWrite()
		);

		$h = '';

		if($createPoint) {
			$h .= new PointUi()->createFirst($eShop);
		}

		if($eShop['cDate']->empty()) {

			if($createPoint === FALSE or $eShop['shared']) {
				$h .= $this->createFirstDate($eFarm, $eShop);
			}

		} else {
			$h .= self::getDateList($eFarm, $eShop);
		}

		return $h;
	}
	
	public function createFirstDate(\farm\Farm $eFarm, Shop $eShop): string {

		if($eShop->canWrite() === FALSE) {
			return '<div class="util-empty">'.s("Aucune vente n'a encore été démarrée sur cette boutique.").'</div>';
		}

		$h = '';
		$h .= '<div class="util-block-help">';

		if($eShop['shared']) {

			if($eShop['cShare']->empty()) {

				$h .= '<h4>'.s("Il est trop tôt pour ajouter une première livraison !").'</h4>';

				$h .= '<p>'.s("Vous devriez plutôt inviter des premiers producteurs et les inciter à associer un ou plusieurs de ses catalogues à la boutique.").'</p>';
				$h .= '<a href="/shop/:invite?id='.$eShop['id'].'" class="btn btn-secondary">'.s("Envoyer des invitations").'</a>';

			} else {

				$h .= '<h4>'.s("Ajouter une première livraison").'</h4>';
				$h .= '<ul>';
					$h .= '<li>'.s("Vous avez invité des producteurs sur la boutique ?").'</li>';
					$h .= '<li>'.s("La configuration de la boutique est terminée ?").'</li>';
				$h .= '</ul>';
				$h .= '<p>'.s("Alors c'est le moment de configurer une première livraison à destination de vos clients !").'</p>';
				$h .= '<a href="/shop/date:create?shop='.$eShop['id'].'&farm='.$eFarm['id'].'" class="btn btn-secondary">'.s("Configurer une première livraison").'</a>';

			}

		} else {

			$h .= '<h4>'.s("Ajouter une première livraison").'</h4>';
			$h .= '<p>'.s("Vous êtes satisfait de la configuration de votre boutique ?<br/>Alors c'est le moment de configurer une première livraison en choisissant une date et la liste des produits que vous avez en stock et que vous souhaitez proposer à vos clients !").'</p>';
			$h .= '<a href="/shop/date:create?shop='.$eShop['id'].'&farm='.$eFarm['id'].'" class="btn btn-secondary">'.s("Ajouter une première livraison").'</a>';
			$h .= '<br/><br/>';
			$h .= '<p>'.s("Vous voulez continuer à personnaliser l'expérience de vos clients ?<br/>Activez par exemple le paiement en ligne ou personnalisez les e-mails envoyés automatiquement à vos clients lors de leurs commandes.").'</p>';
			$h .= '<a href="/shop/configuration:update?id='.$eShop['id'].'" class="btn btn-outline-secondary">'.s("Continuer à personnaliser la boutique").'</a>';

		}

		$h .= '</div>';

		return $h;
		
	}
	
	public function getDateList(\farm\Farm $eFarm, Shop $eShop): string {

		$cDate = $eShop['cDate'];

		$h = '<div class="util-title">';

			if($eShop['shared'] === FALSE) {
				$h .= '<h2>'.s("Livraisons").'</h2>';
			} else {
				$h .= '<div></div>';
			}

			$h .= '<div>';
				if($eShop->canWrite()) {
					if($cDate->empty()) {
						$h .= '<a href="/shop/date:create?shop='.$eShop['id'].'&farm='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouvelle livraison").'</a>';
					} else {
						$h .= '<a data-dropdown="bottom-end" class="btn btn-primary dropdown-toggle">'.\Asset::icon('plus-circle').' '.s("Nouvelle livraison").'</a>';
						$h .= '<div class="dropdown-list">';
							$h .= '<a href="/shop/date:create?shop='.$eShop['id'].'&farm='.$eFarm['id'].'" class="dropdown-item">'.s("Créer une livraison de zéro").'</a>';
							$h .= '<div class="dropdown-divider"></div>';
							$h .= '<div class="dropdown-subtitle">'.s("Créer à partir une autre livraison").'</div>';
							$count = 0;
							foreach($cDate as $eDate) {
								if($count++ >= 3) {
									break;
								}
								$h .= '<a href="/shop/date:create?shop='.$eShop['id'].'&farm='.$eFarm['id'].'&date='.$eDate['id'].'" class="dropdown-item">'.\Asset::icon('chevron-right').' '.s("Livraison du {value}", \util\DateUi::textual($eDate['deliveryDate'])).'</a>';
							}
						$h .= '</div>';
					}
				}
			$h .= '</div>';
		$h .= '</div>';

		$h .= new DateUi()->getList($eFarm, $eShop, $cDate);

		return $h;
	}
	
}
?>
