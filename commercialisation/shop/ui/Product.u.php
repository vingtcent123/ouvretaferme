<?php
namespace shop;

class ProductUi {

	public function __construct() {

		\Asset::css('shop', 'product.css');
		\Asset::js('shop', 'product.js');

	}

	public function toggle(Product $eProduct) {

		return \util\TextUi::switch([
			'id' => 'product-switch-'.$eProduct['id'],
			'disabled' => $eProduct->canWrite() === FALSE,
			'data-ajax' => $eProduct->canWrite() ? '/shop/product:doUpdateStatus' : NULL,
			'post-id' => $eProduct['id'],
			'post-status' => ($eProduct['status'] === Product::ACTIVE) ? Product::INACTIVE : Product::ACTIVE
		], $eProduct['status'] === Product::ACTIVE);

	}

	public function getList(Shop $eShop, Date $eDate, \Collection $cItem, \Collection $cCategory, ?array $basketProducts, bool $canBasket, bool $isModifying): string {

		$eDate->expects(['productsIndex', 'productsEmpty']);

		$h = '';

		if($eDate['productsEmpty']) {

			$h .= '<div class="util-block-help">';
				$h .= '<h4>'.s("Il n'y a pas encore de produit disponible à la vente !").'</h4>';
				$h .= '<p>'.s("La vente est fermée pour le moment car votre producteur n'a pas encore indiqué les produits qu'il souhaite vous proposer.").'</p>';
			$h .= '</div>';

			return $h;

		}

		$callback = fn(\Collection $cProduct) => $this->getProducts($eShop, $eDate, $canBasket, $isModifying, $cProduct);

		$h .= '<div class="shop-product-wrapper shop-product-'.$eShop['type'].'">';

			switch($eDate['productsIndex']) {

				case 'product'  :
					$h .= $callback($eDate['cProduct']);
					break;

				case 'farm' :
					$h .= $this->getListByFarm($eShop['cShare'], $eDate['ccProduct'], $callback);
					break;

				case 'department' :

					$h .= '<div id="product-department-list">';

						foreach($eShop['cDepartment'] as $eDepartment) {

							if($eDate['ccProduct']->offsetExists($eDepartment['id'])) {

								$h .= '<a onclick="BasketManage.clickDepartment(this);" class="shop-department-item" data-department="'.$eDepartment['id'].'">';
									$h .= DepartmentUi::getVignette($eDepartment, '2.5rem');
									$h .= encode($eDepartment['name']);
								$h .= '</a>';

							}

						}

					$h .= '</div>';

					$h .= $this->getListByDepartment($eShop['cDepartment'], $eDate['ccProduct'], $callback);

					break;

				case 'category' :
					$h .= $this->getListByCategory($cCategory, $eDate['ccProduct'], $callback);
					break;
			};

		$h .= '</div>';

		if($eDate->acceptOrder() and ($canBasket or $isModifying)) {
			$h .= $this->getOrderedProducts($eShop, $eDate, $cItem, $basketProducts, $isModifying);
		}

		return $h;

	}

	protected function getListByFarm(\Collection $cShare, \Collection $ccProduct, \Closure $callback): string {

		$h = '';

		foreach($cShare as $eShare) {

			$eFarm = $eShare['farm'];

			if($ccProduct->offsetExists($eFarm['id']) === FALSE) {
				continue;
			}

			$h .= '<div data-filter-farm="'.$eFarm['id'].'">';
				$h .= '<h3 class="shop-title-group">';
					$h .= encode($eFarm['name']);
					if($eShare['label'] !== NULL) {
						$h .= '<span class="shop-title-group-label">  /  '.encode($eShare['label']).'</span>';
					}
				$h .= '</h3>';
				$h .= $callback($ccProduct[$eFarm['id']]);
			$h .= '</div>';

		}

		if($ccProduct->offsetExists('')) {
			$h .= '<div data-filter-farm="">';
				$h .= '<h3 class="shop-title-group">'.s("Autres producteurs").'</h3>';
				$h .= $callback($ccProduct['']);
			$h .= '</div>';
		}

		return $h;

	}

	protected function getListByDepartment(\Collection $cDepartment, \Collection $ccProduct, \Closure $callback, string $header = ''): string {

		$h = '<div id="product-department-wrapper">';

			foreach($cDepartment as $eDepartment) {

				if($ccProduct->offsetExists($eDepartment['id']) === FALSE) {
					continue;
				}

				$farms = array_unique($ccProduct[$eDepartment['id']]->makeArray(fn($eProduct) => $eProduct['product']['farm']['id']));

				$h .= '<div class="product-department-element" data-department="'.$eDepartment['id'].'" data-filter-farm="'.implode(' ', $farms).'">';
					$h .= '<h3 class="shop-title-group">';
						$h .= encode($eDepartment['name']);
					$h .= '</h3>';
					$h .= $callback($ccProduct[$eDepartment['id']]);
				$h .= '</div>';

			}

			if($ccProduct->offsetExists('')) {
				$h .= '<div>';
					$h .= '<h3 class="shop-title-group">'.s("Autres").'</h3>';
					$h .= $callback($ccProduct['']);
				$h .= '</div>';
			}
		$h .= '</div>';

		return $h;

	}

	protected function getListByCategory(\Collection $cCategory, \Collection $ccProduct, \Closure $callback): string {

		$h = '';

		if($ccProduct->count() === 1) {
			$h .= $callback($ccProduct->first());
		} else {

			if($ccProduct->offsetExists('')) {
				$h .= $callback($ccProduct['']);
			}

			foreach($cCategory as $eCategory) {

				if($ccProduct->offsetExists($eCategory['id']) === FALSE) {
					continue;
				}

				$h .= '<h3>'.encode($eCategory['name']).'</h3>';
				$h .= $callback($ccProduct[$eCategory['id']]);

			}

		}

		return $h;

	}

	protected function getOrderedProducts(Shop $eShop, Date $eDate, \Collection $cItem, ?array $basketProducts, bool $isModifying): string {

		if($cItem->notEmpty() and $isModifying) {
			$defaultJson = new BasketUi()->getJsonBasket($cItem, $basketProducts);
		} else if($basketProducts !== NULL) {
			$defaultJson = new BasketUi()->getJsonBasket(new \Collection(), $basketProducts);
		} else {
			$defaultJson = 'null';
		}

		$h = '<div class="shop-product-ordered hide" id="shop-basket" '.attr('onrender', 'BasketManage.init('.$eDate['id'].', '.$defaultJson.')').'>';
			$h .= '<div class="shop-product-ordered-item">';
				$h .= '<div class="shop-product-ordered-icon">'.\Asset::icon('basket').'</div>';
				$h .= '<span id="shop-basket-articles"></span>';
			$h .= '</div>';
			$h .= '<div class="shop-product-ordered-item '.($eShop->isApproximate() ? 'shop-product-ordered-approximate' : '').'">';
				$h .= '<div class="shop-product-ordered-icon">'.\Asset::icon('currency-euro').'</div>';
				$h .= '<div>';
					if($eShop->isApproximate()) {
						$h .= '<div id="shop-product-ordered-around" class="shop-product-around hide">'.s("Environ").'</div>';
					}
					$h .= '<span id="shop-basket-price"></span>';
				$h .= '</div>';
				$h .= ' '.$this->getTaxes($eDate);
			$h .= '</div>';
			$h .= '<div class="shop-product-ordered-item">';

				if(Shop::isEmbed()) {

					$h .= '<a onclick="BasketManage.transfer(this, '.$eDate['id'].')" data-url="'.ShopUi::basketUrl($eShop, $eDate).'?products=" class="btn btn-secondary" id="shop-basket-next">';
						$h .= '<span class="hide-sm-up">'.($isModifying ? s("Modifier") : s("Commander")).'</span>';
						$h .= '<span class="hide-xs-down">'.($isModifying ? s("Modifier la commande") : s("Poursuivre la commande")).'</span>';
					$h .= '</a>';
					$h .= '&nbsp;';

				} else {

					$h .= '<a href="'.ShopUi::basketUrl($eShop, $eDate).($isModifying ? '?modify=1' : '').'" class="btn btn-secondary" id="shop-basket-next">';
						$h .= '<span class="hide-sm-up">'.($isModifying ? s("Modifier") : s("Commander")).'</span>';
						$h .= '<span class="hide-xs-down">'.($isModifying ? s("Modifier la commande") : s("Poursuivre la commande")).'</span>';
					$h .= '</a>';

				}

				$h .= '&nbsp;';

				if($isModifying) {

					$confirmEmpty = s("Votre commande n'a pas été modifiée, et votre ancienne commande reste valide. Confirmer ?");
					$labelEmpty = s("Annuler");
					$urlEmpty = ShopUi::confirmationUrl($eShop, $eDate);

				} else {

					$confirmEmpty = s("Voulez-vous vider votre panier ?");
					$labelEmpty = s("Vider mon panier");
					$urlEmpty = ShopUi::dateUrl($eShop, $eDate).(Shop::isEmbed() ? '?embed' : '');

				}

				$h .= '<a onclick="BasketManage.empty(this, '.$eDate['id'].')" data-url="'.$urlEmpty.'" class="shop-basket-empty btn btn-outline-secondary" data-confirm="'.$confirmEmpty.'">';
					$h .= '<span class="hide-sm-up" title="'.$labelEmpty.'">'.\Asset::icon('trash').'</span>';
					$h .= '<span class="hide-xs-down">'.$labelEmpty.'</span>';
				$h .= '</a>';

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getProducts(Shop $eShop, Date $eDate, bool $canBasket, bool $isModifying, \Collection $cProduct): string {

		$h = '<div class="shop-product-list">';
			$h .= $cProduct->makeString(fn($eProduct) => ($eProduct['parent'] and $eProduct['cProductChild']->count() > 1) ?
				$this->getParentProduct($eShop, $eDate, $eProduct, $canBasket, $isModifying) :
				$this->getProduct($eShop, $eDate, $eProduct['parent'] ? $eProduct['cProductChild']->first() : $eProduct, $canBasket, $isModifying));
		$h .= '</div>';

		return $h;

	}

	public function getParentProduct(Shop $eShop, Date $eDate, Product $eProduct, bool $canBasket, bool $isModifying): string {

		$eShop->expects(['shared']);
		$eDate->expects(['productsIndex']);

		$eProduct->expects(['reallyAvailable']);

		$showFarm = (
			$eShop['shared'] and
			$eDate['productsIndex'] !== 'farm'
		);

		$promotion = $this->getPromotion($eProduct);

		$eFarm = $eProduct['farm'];
		$eProductDefault = $eProduct['cProductChild']->first();

		$h = '<div id="shop-product-'.$eProduct['id'].'" class="shop-product shop-product-parent shop-product-parent-only" data-children="'.implode(',', $eProduct['cProductChild']->getIds()).'" '.($showFarm ? 'data-filter-farm="'.$eFarm['id'].'"' : '').'>';

			$url = $this->getVignetteUrl($eProductDefault);

			$h .= '<div ';
			if($url !== NULL) {
				$h .= 'class="shop-product-image" style="background-image: url('.$url.')"';
			} else {
				$h .= 'class="shop-product-image shop-product-image-empty"';
			}
			$h .= '>';
				if($url === NULL) {
					$h .= $this->getDefaultVignette($eProductDefault, $eShop);
				}
				if($eShop['type'] === Shop::PRIVATE) {
					$h .= $promotion;
				}
			$h .= '</div>';
			$h .= '<div class="shop-product-content">';

				if($eShop['type'] === Shop::PRO) {
					$h .= $promotion;
				}

				$h .= '<div class="shop-product-header">';
					$h .= '<div class="shop-product-name">';

						$h .= '<h4 class="shop-product-name-link">';
							$h .= encode($eProduct['parentName']);
							$h .= '<div class="shop-product-additional">'.p("{value} format", "{value} formats", $eProduct['cProductChild']->count()).'</div>';
						$h .= '</h4>';

						if($showFarm) {
							$h .= '<div class="shop-product-farm">';
								$h .= \Asset::icon('person-fill').' '.encode($eShop['cShare'][$eFarm['id']]['farm']['name']);
							$h .= '</div>';
						}

					$h .= '</div>';
					$h .= '<div class="shop-product-title">';

						$h .= '<div class="shop-product-buy-price">';

							$prices = [];

							foreach($eProduct['cProductChild'] as $eProductChild) {

								$eProductSelling = $eProductChild['product'];
								$unit = $eProductSelling['unit']->notEmpty() ? $eProductSelling['unit']['id'] : NULL;

								$prices[$unit] ??= [
									'eUnit' => $eProductSelling['unit'],
									'min' => $eProductChild['price'],
									'max' => $eProductChild['price']
								];

								$prices[$unit]['min'] = min($prices[$unit]['min'], $eProductChild['price']);
								$prices[$unit]['max'] = max($prices[$unit]['max'], $eProductChild['price']);

							}

							$list = [];

							foreach($prices as ['eUnit' => $eUnit, 'min' => $min, 'max' => $max]) {

								$unit = ' '.$this->getTaxes($eProduct).\selling\UnitUi::getBy($eUnit);

								if($min === $max) {
									$list[] = \util\TextUi::money($min).$unit;
								} else {
									$list[] = s("{from} à {to}", ['from' => \util\TextUi::money($min), 'to' => \util\TextUi::money($max).$unit]);
								}

							}

							$h .= implode(' '.s("ou").' ', $list);

						$h .= '</div>';

					$h .= '</div>';
				$h .= '</div>';

			$h .= '</div>';

			$h .= '<div class="shop-product-buy">';
				$h .= '<a onclick="BasketManage.showChildren(this);" class="btn btn-outline-primary">'.s("Choisir un format").'</a>';
			$h .= '</div>';

		$h .= '</div>';

		foreach($eProduct['cProductChild'] as $eProductChild) {
			$h .= $this->getProduct($eShop, $eDate, $eProductChild, $canBasket, $isModifying, TRUE);
		}

		return $h;

	}

	public function getProduct(Shop $eShop, Date $eDate, Product $eProduct, bool $canBasket, bool $isModifying, bool $isChild = FALSE): string {

		$eShop->expects(['shared']);
		$eDate->expects(['productsIndex']);

		$eProduct->expects(['reallyAvailable']);

		$showFarm = (
			$eShop['shared'] and
			$eDate['productsIndex'] !== 'farm'
		);

		$acceptOrder = ($canBasket or $isModifying);

		$eProductSelling = $eProduct['product'];

		if($eProduct['packaging'] === NULL) {
			$price = $eProduct['price'];
		} else {
			$price = $eProduct['price'] * $eProduct['packaging'];
		}

		if($eProductSelling['quality']) {
			$quality = '<div class="shop-header-image-quality">'.\farm\FarmUi::getQualityLogo($eProductSelling['quality'], match($eShop['type']) {
				Shop::PRO => '1.75rem',
				Shop::PRIVATE => '2.5rem'
			}).'</div>';
		} else {
			$quality = '';
		}

		$promotion = $this->getPromotion($eProduct);

		$eFarm = $eProduct['product']['farm'];

		$h = '<div id="shop-product-'.$eProduct['id'].'" class="shop-product '.($isChild ? 'shop-product-child' : '').' '.(($eProductSelling['compositionVisibility'] === \selling\Product::PUBLIC and $eProductSelling['cItemIngredient']->notEmpty()) ? 'shop-product-composition' : '').'" data-id="'.$eProductSelling['id'].'" data-price="'.$price.'" data-approximate="'.($eProductSelling['unit']->notEmpty() and $eProductSelling['unit']['approximate'] ? 1 : 0).'" data-has="0" '.($showFarm ? 'data-filter-farm="'.$eFarm['id'].'"' : '').'>';

			$url = $this->getVignetteUrl($eProduct);

			$h .= '<div ';
			if($url !== NULL) {
				$h .= 'class="shop-product-image" style="background-image: url('.$url.')"';
			} else {
				$h .= 'class="shop-product-image shop-product-image-empty"';
			}
			$h .= '>';
				if($url === NULL) {
					$h .= $this->getDefaultVignette($eProduct, $eShop);
				}
				if($eShop['type'] === Shop::PRIVATE) {
					$h .= $quality;
					$h .= $promotion;
				}
			$h .= '</div>';
			$h .= '<div class="shop-product-content">';

				if($eShop['type'] === Shop::PRO) {
					$h .= $promotion;
				}
				$h .= '<div class="shop-product-header">';
					$h .= '<div class="shop-product-name">';

						$name = $eProductSelling->getName('html');

						if($eShop['type'] === Shop::PRO) {
							$name .= $quality;
						}

						if($eProductSelling['additional'] !== NULL) {
							$name .= '<div class="shop-product-additional">';
								$name .= encode($eProductSelling['additional']);
							$name .= '</div>';
						}


						if($eProductSelling['processedPackaging'] !== NULL) {
							$name .= '<div class="shop-product-additional">';
								$name .= encode($eProductSelling['processedPackaging']);
							$name .= '</div>';
						}

						if(
							$eProductSelling['processedComposition'] or
							$eProductSelling['processedAllergen']
						) {

							$h .= '<h4 class="shop-product-name-link" data-dropdown="bottom-start" data-dropdown-hover="true" data-dropdown-enter-timeout="0">';
								$h .= $name;
								$h .= '<span class="shop-product-name-info">'.\Asset::icon('info-circle').'</span>';
							$h .= '</h4>';

							$h .= '<div class="dropdown-list dropdown-list-unstyled shop-product-name-dropdown">';
								if($eProductSelling['processedComposition']) {
									$h .= '<h5>'.s("Composition").'</h5>';
									$h .= '<div class="shop-product-name-dropdown-content">'.nl2br(encode($eProductSelling['processedComposition'])).'</div>';
								}
								if($eProductSelling['processedAllergen']) {
									$h .= '<h5>'.s("Allergènes").'</h5>';
									$h .= '<div class="shop-product-name-dropdown-content">'.nl2br(encode($eProductSelling['processedAllergen'])).'</div>';
								}
							$h .= '</div>';

						} else {

							$h .= '<h4 class="shop-product-name-link">';
								$h .= $name;
							$h .= '</h4>';

						}

						if($showFarm) {
							$h .= '<div class="shop-product-farm">';
								$h .= \Asset::icon('person-fill').' '.encode($eFarm['name']);
							$h .= '</div>';
						}

						if($eProductSelling['origin'] !== NULL) {
							$h .= '<div class="shop-product-origin">';
								$h .= s("Origine <u>{value}</u>", encode($eProductSelling['origin']));
							$h .= '</div>';
						}
					$h .= '</div>';
					$h .= '<div class="shop-product-title">';

						$h .= '<div class="shop-product-buy-price">';

							$unit = ' '.$this->getTaxes($eProduct).\selling\UnitUi::getBy($eProductSelling['unit']);
							if($eProduct['priceInitial'] !== NULL) {
								$h .= new \selling\PriceUi()->priceWithoutDiscount($eProduct['priceInitial'], unit: $unit);
							}
							$h .= \util\TextUi::money($eProduct['price']).$unit;

						$h .= '</div>';

						if($acceptOrder) {

							$h .= '<div class="shop-product-buy-infos">';

								if($eProduct['packaging'] !== NULL) {
									$h.= '<div class="shop-product-buy-packaging">';
										$h .= s("Colis : {value}", \selling\UnitUi::getValue($eProduct['packaging'], $eProductSelling['unit']));
									$h .= '</div>';
								}

								if($eProduct['limitMin'] !== NULL) {

									$h.= '<div class="shop-product-buy-info">';
										$h .= s("Minimum de commande : {value}", ($eProduct['packaging'] === NULL) ? \selling\UnitUi::getValue($eProduct['limitMin'], $eProductSelling['unit'], TRUE) : s("{value} colis", $eProduct['limitMin']));
									$h .= '</div>';

								}

								if($eProduct['reallyAvailable'] !== NULL) {

									$h.= '<div class="shop-product-buy-info">';
									if($eProduct['reallyAvailable'] > 0) {
										$value = ($eProduct['packaging'] === NULL) ? \selling\UnitUi::getValue($eProduct['reallyAvailable'], $eProductSelling['unit'], TRUE) : s("{value} colis", $eProduct['reallyAvailable']);
										$h .= '<span class="hide-sm-down">'.s("Disponible : {value}", $value).'</span>';
										$h .= '<span class="hide-md-up">'.s("Dispo : {value}", $value).'</span>';
									} else {
										$h .= s("Rupture de stock");
									}
									$h .= '</div>';

								}

							$h .= '</div>';

						}

					$h .= '</div>';
				$h .= '</div>';

				if($eProductSelling['description'] !== NULL) {
					$h .= '<div class="shop-product-description">';
						$h .= new \editor\EditorUi()->value($eProductSelling['description']);
					$h .= '</div>';
				}

			$h .= '</div>';

			$h .= '<div class="shop-product-buy">';

				if(
					$acceptOrder and
					($eProduct['reallyAvailable'] === NULL or $eProduct['reallyAvailable'] > 0.0)
				) {
					$h .= self::numberOrder($eShop, $eDate, $eProductSelling, $eProduct, 0, $eProduct['reallyAvailable']);
				}

			$h .= '</div>';

			if(
				$eProductSelling['compositionVisibility'] === \selling\Product::PUBLIC and
				$eProductSelling['cItemIngredient']->notEmpty()
			) {

				$ingredients = $eProductSelling['cItemIngredient']->count();

				$h .= '<div class="shop-product-ingredients">';

					$h .= '<h5>'.s("Composition").'</h5>';

					$vignetteSize = 2.25;
					$listSize = 1;

					if($ingredients >= 6) {
						$vignetteSize = 2;
						$listSize = 6;
					}

					if($ingredients >= 10) {
						$vignetteSize = 1.5;
						$listSize = 10;
					}

					$h .= '<div class="shop-product-ingredients-list shop-product-ingredients-list-'.$listSize.'">';

						foreach($eProductSelling['cItemIngredient'] as $eItemIngredient) {

							$h .= \selling\ProductUi::getVignette($eItemIngredient['product'], $vignetteSize.'rem');
							$h .= '<div>';
								$h .= encode($eItemIngredient['name']).' ';
								$h .= '<b>'.\selling\UnitUi::getValue($eItemIngredient['number'] * ($eItemIngredient['packaging'] ?? 1), $eItemIngredient['unit'], short: TRUE).'</b>';
							$h .= '</div>';


						}
					$h .= '</div>';

				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	public function getVignetteUrl(Product $eProduct): ?string {

		$eProductSelling = $eProduct['product'];

		if($eProductSelling['vignette'] !== NULL) {
			return new \media\ProductVignetteUi()->getUrlByElement($eProductSelling, 'l');
		} else if($eProductSelling['unprocessedPlant']->notEmpty()) {
			return new \media\PlantVignetteUi()->getUrlByElement($eProductSelling['unprocessedPlant'], 'l');
		} else {
			return NULL;
		}

	}

	public function getDefaultVignette(Product $eProduct, Shop $eShop): string {

		$eProductSelling = $eProduct['product'];

		if($eProductSelling['unprocessedPlant']->notEmpty()) {
			return \plant\PlantUi::getVignette($eProductSelling['unprocessedPlant'], match($eShop['type']) {
				Shop::PRO => '4rem',
				Shop::PRIVATE => '9rem'
			});
		} else {
			return \Asset::icon('camera', ['class' => 'shop-product-image-placeholder']);
		}

	}

	public function getPromotion(Product $eProduct): string {

		switch($eProduct['promotion']) {

			case Product::NONE :
			case Product::BASIC :
				return '';

			case Product::NEW :
				return '<div class="shop-header-image-promotion">'.\Asset::icon('star-fill').' '.s("Nouveauté").'</div>';

			case Product::WEEK :
				return '<div class="shop-header-image-promotion">'.\Asset::icon('star-fill').' '.s("Produit de la semaine").'</div>';

			case Product::MONTH :
				return '<div class="shop-header-image-promotion">'.\Asset::icon('star-fill').' '.s("Produit du mois").'</div>';

		}

	}

	public static function getCreateByCategory(\util\FormUi $form, \farm\Farm $eFarm, string $type, \Collection $cProduct): string {

		\Asset::css('shop', 'shop.css');
		\Asset::css('shop', 'manage.css');
		\Asset::js('shop', 'manage.js');

		$displayStock = $cProduct->match(fn($eProduct) => $eProduct['stock'] !== NULL);

		$h = '<div class="date-products-wrapper">';
			$h .= '<div class="date-products '.($displayStock ? 'date-products-with-stock' : '').' util-grid-header">';

				$h .= '<div class="shop-select '.($cProduct->count() < 2 ? 'shop-select-hide' : '').'">';
					$h .= '<input type="checkbox" '.attr('onclick', 'CheckboxField.all(this.firstParent(\'.date-products-wrapper\'), this.checked, \'[name^="products["]\', node => DateManage.selectProduct(node))').'"  title="'.s("Tout cocher / Tout décocher").'"/>';
				$h .= '</div>';
				$h .= '<div style="grid-column: span 2">';
					$h .= s("Produit");
				$h .= '</div>';
				$h .= '<div class="date-products-fields">';
					$h .= '<div class="date-products-multiple">';
						if($type === Date::PRIVATE) {
							$h .= s("Multiple<br/>de vente");
						}
					$h .= '</div>';
					$h .= '<div>';
						$h .= s("Prix unitaire");
						if($eFarm->getSelling('hasVat')) {
							$h .= ' <span class="util-annotation">'.\selling\CustomerUi::getTaxes($type).'</span>';
						}
					$h .= '</div>';
					$h .= '<div>'.s("Disponible à la vente").'</div>';
					if($displayStock) {
						$h .= '<div>';
							$h .= s("Stock");
						$h .= '</div>';
					}

				$h .= '</div>';
			$h .= '</div>';

			foreach($cProduct as $eProduct) {

				$checked = $eProduct['checked'] ?? FALSE;

				$attributes = [
					'id' => 'checkbox-'.$eProduct['id'],
					'onclick' => 'DateManage.selectProduct(this)'
				];

				if($eProduct['checked'] ?? FALSE) {
					$attributes['checked'] = $checked;
				}

				switch($type) {

					case Date::PRIVATE :
						$priceInitial = $eProduct['privatePriceInitial'];
						$price = $eProduct['privatePrice'] ?? $eProduct->calcPrivateMagicPrice($eFarm->getSelling('hasVat'));
						$packaging = NULL;
						break;

					case Date::PRO :
						$priceInitial = $eProduct['proPriceInitial'];
						$price = $eProduct['proPrice'] ?? $eProduct->calcProMagicPrice($eFarm->getSelling('hasVat'));
						$packaging = $eProduct['proPackaging'];
						break;

				}

				$eShopProduct = new Product([
					'farm' => $eFarm,
					'type' => $type,
					'product' => $eProduct,
					'price' => $price,
					'priceInitial' => $priceInitial,
					'packaging' => $packaging,
					'available' => NULL,
				]);

				$h .= '<div class="date-products '.($displayStock ? 'date-products-with-stock' : '').' '.($checked ? 'selected' : '').'">';

					$h .= '<label class="shop-select">';
						$h .= $form->inputCheckbox('products['.$eProduct['id'].']', $eProduct['id'], $attributes);
					$h .= '</label>';
					$h .= '<label for="'.$attributes['id'].'">';
						$h .= \selling\ProductUi::getVignette($eProduct, '2rem');
					$h .= '</label>';
					$h .= '<label for="'.$attributes['id'].'" class="date-products-info">';
						$h .= \selling\ProductUi::getInfos($eProduct, includeUnit: TRUE, link: FALSE);
					$h .= '</label>';
					$h .= '<div class="date-products-fields">';
						$h .= '<div class="date-products-multiple">';

							switch($type) {

								case Date::PRIVATE :
									$step = ProductUi::getStep($type, $eProduct);
									$h .= '<h4>'.s("Multiple de vente").'</h4>';
									$h .= $eProduct->quick('privateStep', \selling\UnitUi::getValue($step, $eProduct['unit']));
									break;

								case Date::PRO :
									if($eProduct['proPackaging'] !== NULL) {
										$h .= '<h4>'.s("Colisage").'</h4>';
										$h .= s("Colis de {value}", \selling\UnitUi::getValue($eProduct['proPackaging'], $eProduct['unit'], TRUE));
									}
									break;

							}

						$h .= '</div>';
						$h .= '<div data-wrapper="price['.$eProduct['id'].']">';
							$h .= '<h4>'.s("Prix unitaire").'</h4>';
							$h .= '<div>';
								$h .= $form->dynamicField($eShopProduct, 'price['.$eProduct['id'].']', function($d) use($eShopProduct) {
									$d->default = fn() => $eShopProduct['priceInitial'] ?? $eShopProduct['price'];
								});
								$h .= $form->dynamicField($eShopProduct, 'priceDiscount['.$eProduct['id'].']', function($d) use($eShopProduct) {
									$d->default = fn() => $eShopProduct['priceInitial'] ? $eShopProduct['price'] : NULL;
								});
							$h .= '</div>';
						$h .= '</div>';
						$h .= '<div data-wrapper="available['.$eProduct['id'].']">';
							$h .= '<h4>'.s("Disponible").'</h4>';
							$h .= $form->dynamicField($eShopProduct, 'available', function($d) use($eProduct) {
								$d->name = 'available['.$eProduct['id'].']';
							});
						$h .= '</div>';
						if($displayStock) {
							$h .= '<label for="'.$attributes['id'].'">';
								if($eProduct['stock'] !== NULL) {
									$h .= '<h4>'.s("Stock").'</h4>';
									$h .= '<div>';
										$h .= \selling\StockUi::getExpired($eProduct);
										$h .= '<span title="'.\selling\StockUi::getDate($eProduct['stockUpdatedAt']).'">'.\selling\UnitUi::getValue(round($eProduct['stock']), $eProduct['unit'], short: TRUE).'</span>';
									$h .= '</div>';
								}
							$h .= '</label>';
						}

					$h .= '</div>';
				$h .= '</div>';

			}

		$h .= '</div>';


		return $h;

	}

	public static function getTaxes(Product|Date $eProduct): string {

		if(
			$eProduct['type'] === Shop::PRO and
			$eProduct['farm']->getSelling('hasVat')
		) {
			return \selling\CustomerUi::getTaxes($eProduct['type']);
		} else {
			return '';
		}

	}

	public static function numberOrder(Shop $eShop, Date $eDate, \selling\Product $eProductSelling, Product $eProduct, float $number, ?float $available): string {

		if($eDate->acceptOrder() === FALSE) {
			return '';
		}

		$step = self::getStep($eDate['type'], $eProductSelling);
		$min = $eProduct['limitMin'] ?? 0;

		$attributesDecrease = 'BasketManage.update('.$eDate['id'].', '.$eProductSelling['id'].', -'.$step.', '.$step.', '.$min.', '.($available !== NULL ? $available : -1).')';
		$attributesIncrease = 'BasketManage.update('.$eDate['id'].', '.$eProductSelling['id'].', '.$step.', '.$step.', '.$min.', '.($available !== NULL ? $available : -1).')';

		if($available !== NULL) {
			$inconsistency = ($min > $available);
		} else {
			$inconsistency = FALSE;
		}

		if($eProduct['packaging'] === NULL) {
			$price = $eProduct['price'];
		} else {
			$price = $eProduct['price'] * $eProduct['packaging'];
		}

		if($eProduct['packaging'] === NULL) {
			$unit = \selling\UnitUi::getSingular($eProductSelling['unit'], short: TRUE);
		} else {
			$unit = s("colis");
		}

		$form = new \util\FormUi();

		$h = '<div class="shop-product-number" data-inconsistency="'.($inconsistency ? 1 : 0).'">';
			$h .= '<a class="btn btn-outline-primary shop-product-number-decrease" onclick="'.$attributesDecrease.'">-</a>';
			$h .= '<span class="shop-product-number-value" data-price="'.$price.'" data-step="'.$step.'" data-available="'.$available.'" data-number="'.$number.'" data-product="'.$eProductSelling['id'].'" data-field="number">';
				$h .= match($eShop['type']) {
					Shop::PRIVATE => '<span class="shop-product-number-display">'.$number.'</span> '.$unit,
					Shop::PRO => $form->inputGroup(
						$form->number('value', $number > 0 ? $number : '', ['min' => $min, 'class' => 'shop-product-number-display', 'onfocus' => 'this.select()', 'onchange' => 'BasketManage.update('.$eDate['id'].', '.$eProductSelling['id'].', this, '.$step.', '.$min.', '.($available !== NULL ? $available : -1).')']).
						$form->addon($unit)
					)
				};

			$h .= '</span>';
			$h .= '<a class="btn btn-outline-primary shop-product-number-increase" onclick="'.$attributesIncrease.'">+</a>';
		$h .= '</div>';

		return $h;

	}

	public static function getStep(string $type, \selling\Product $eProduct): float {

		return match($type) {
			Date::PRIVATE => $eProduct['privateStep'] ?? self::getDefaultPrivateStep($eProduct),
			Date::PRO => $eProduct['proStep'] ?? self::getDefaultProStep($eProduct),
		};

	}

	public static function getDefaultPrivateStep(\selling\Product $eProduct): float {

		if($eProduct['unit']->empty()) {
			return 1;
		}

		return match($eProduct['unit']['fqn']) {

			'gram' => 100,
			'kg' => 0.5,
			default => 1,

		};

	}

	public static function getDefaultProStep(\selling\Product $eProduct): float {
		return 1;
	}

	public function getUpdateDate(\farm\Farm $eFarm, Shop $eShop, Date $eDate, bool $isExpired = FALSE): string {

		$eDate->expects(['productsIndex']);

		$showFarm = ($eShop['shared'] and $eDate['productsIndex'] !== 'farm');

		$callback = fn(\Collection $cProduct) => $this->getListByDate($eFarm, $eDate, $cProduct, $isExpired, $showFarm);

		return match($eDate['productsIndex']) {
			'product' => $callback($eDate['cProduct']),
			'farm' => $this->getListByFarm($eShop['cShare'], $eDate['ccProduct'], $callback),
			'department' => $this->getListByDepartment($eShop['cDepartment'], $eDate['ccProduct'], $callback),
			'category' => $this->getListByCategory($eDate['cCategory'], $eDate['ccProduct'], $callback),
		};

	}

	public function getUpdateCatalog(\farm\Farm $eFarm, Catalog $eCatalog, \Collection $cProduct, \Collection $cCategory): string {

		$ccProduct = $cProduct->reindex(['product', 'category']);

		return $this->getListByCategory(
			$cCategory,
			$ccProduct,
			fn(\Collection $cProduct) => $this->getListByCatalog($eFarm, $eCatalog, $cProduct)
		);

	}

	public function getListByDate(\farm\Farm $eFarm, Date $eDate, \Collection $cProduct, bool $isExpired, bool $showFarm): string {

		$taxes = $eFarm->getSelling('hasVat') ? '<span class="util-annotation">'.\selling\CustomerUi::getTaxes($eDate['type']).'</span>' : '';
		$hasSold = $cProduct->contains(fn($eProduct) => $eProduct['sold'] !== NULL);
		$columns = 2;

		$hasCatalog = $cProduct->contains(fn($eProduct) => $eProduct['catalog']->notEmpty());
		$canAction = ($isExpired === FALSE and $cProduct->contains(fn($eProduct) => $eProduct->exists() and $eProduct['catalog']->empty()));
		$canGroup = ($canAction === FALSE and $cProduct->contains(fn($eProduct) => $eProduct['parent']));

		if($eDate['type'] === Date::PRIVATE) {
			$overflow = $eDate['shop']['shared'] ? 'util-overflow-sm' : 'util-overflow-xs';
		} else {
			$overflow = $eDate['shop']['shared'] ? 'util-overflow-lg' : 'util-overflow-sm';
		}

		$h = '<div class="'.$overflow.' stick-xs mb-3">';
			$h .= '<table class="tbody-even td-padding-sm" data-batch="#batch-catalog">';
				$h .= '<thead>';
					$h .= '<tr>';
						if($canAction) {
							$h .= '<th class="shop-product-group-first td-checkbox">';
								$h .= '<label title="'.s("Tout cocher / Tout décocher").'">';
									$h .= '<input type="checkbox" class="batch-all" onclick="ShopProduct.toggleSelection(this)"/>';
								$h .= '</label>';
							$h .= '</th>';
						} else if($canGroup) {
							$h .= '<th class="shop-product-group-first shop-product-group-readonly"></th>';
						}
						$h .= '<th colspan="2">'.s("Produit").'</th>';
						if($showFarm) {
							$columns++;
							$h .= '<td></td>';
						}
						if($eDate['type'] === Date::PRO) {
							$columns++;
							$h .= '<td></td>';
						}
						$h .= '<th class="text-end">'.s("Prix").' '.$taxes.'</th>';
						if($isExpired === FALSE) {
							$h .= '<th class="highlight">'.s("Disponible").'</th>';
						}
						if($hasSold) {
							$h .= '<th class="text-center">'.s("Vendu").'</th>';
						}
						if($canAction) {
							$h .= '<th></th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';

				foreach($cProduct as $eProduct) {

					if($eProduct['parent'] === FALSE) {

						$h .= '<tbody>';
							$h .= $this->getOneByDate($eDate, $eProduct, $columns, $showFarm, $canAction, $canGroup, $isExpired, $hasCatalog, $hasSold);
						$h .= '</tbody>';

					} else {

						$h .= '<tbody class="shop-product-group">';
							$h .= '<tr>';
								if($canAction) {
									$h .= '<td class="shop-product-group-first td-checkbox"></td>';
								} else if($canGroup) {
									$h .= '<td class="shop-product-group-first shop-product-group-readonly"></td>';
								}
								$h .= '<td></td>';
								$h .= '<th>';
									$h .= '<h4>'.encode($eProduct['parentName']).'</h4>';
								$h .= '</th>';
								if($showFarm) {
									$h .= '<td></td>';
								}
								if($eDate['type'] === Date::PRO) {
									$h .= '<td></td>';
								}
								$h .= '<th class="text-end"></th>';
								if($isExpired === FALSE) {
									$h .= '<th class="highlight"></th>';
								}
								if($hasSold) {
									$h .= '<th></th>';
								}
								if($canAction) {
									$h .= '<th class="td-min-content">';
										$h .= '<a href="/shop/product:update?id='.$eProduct['id'].'" class="btn btn-secondary">'.\Asset::icon('gear-fill').'</a> ';
										$h .= '<a data-ajax="/shop/product:doDelete" class="btn btn-secondary" data-confirm="'.s("Voulez-vous vraiment supprimer ce groupe ? Les produits actuellement dans le groupe resteront dans le catalogue.").'" post-id="'.$eProduct['id'].'">'.\Asset::icon('trash-fill').'</a>';
									$h .= '</th>';
								}
							$h .= '</tr>';

							foreach($eProduct['cProductChild'] as $eProductChild) {
								$h .= $this->getOneByDate($eDate, $eProductChild, $columns, $showFarm, $canAction, $canGroup, $isExpired, $hasCatalog, $hasSold);
							}

						$h .= '</tbody>';

					}

				}
;
			$h .= '</table>';
		$h .= '</div>';

		$h .= $this->getBatch($eFarm, eDate: $eDate);

		return $h;

	}

	protected function getOneByDate(Date $eDate, Product $eProduct, int $columns, bool $showFarm, bool $canAction, bool $canGroup, bool $isExpired, bool $hasCatalog, bool $hasSold): string {

		$eProductSelling = $eProduct['product'];
		$uiProductSelling = new \selling\ProductUi();

		$canUpdate = ($isExpired === FALSE and $eProduct->exists() and $eProduct['catalog']->empty());
		$outCatalog = ($hasCatalog and $canUpdate);

		$hasLimits = (
			$eProduct['promotion'] !== Product::NONE or
			$eProduct['limitCustomers'] or
			$eProduct['limitGroups'] or
			$eProduct['excludeCustomers'] or
			$eProduct['excludeGroups'] or
			$eProduct['limitMax'] or
			$outCatalog
		);

		if($showFarm) {
			$eProduct['farm'] = $eDate['cFarm'][$eProduct['farm']['id']] ?? new \farm\Farm();
		}

		$h = '<tr>';

			if($canAction) {
				$h .= '<td class="shop-product-group-first td-checkbox" rowspan="'.($hasLimits ? 2 : 1).'">';
					if($canUpdate) {
						$h .= '<label>';
							$h .= '<input type="checkbox" name="batch[]" value="'.$eProduct['id'].'" oninput="ShopProduct.changeSelection()" data-batch="'.$this->getProductBatch($eProduct).'"/>';
						$h .= '</label>';
					}
				$h .= '</td>';
			} else if($canGroup) {
				$h .= '<td class="shop-product-group-first shop-product-group-readonly"></td>';
			}

			$h .= '<td class="td-min-content" '.($hasLimits ? 'rowspan="2"' : '').'>';
				if(
					$eProductSelling['vignette'] !== NULL or
					$eProductSelling['profile'] === \selling\Product::COMPOSITION
				) {
					$h .= \selling\ProductUi::getVignette($eProductSelling, '3rem');
				} else if($eProductSelling['unprocessedPlant']->notEmpty()) {
					$h .= \plant\PlantUi::getVignette($eProductSelling['unprocessedPlant'], '3rem');
				}
			$h .= '</td>';

			$h .= '<td class="'.(($isExpired or $eProduct->exists()) ? '' : 'shop-product-not-exist').'">';
				$h .= $uiProductSelling->getInfos($eProductSelling, includeStock: $isExpired === FALSE);
			$h .= '</td>';

			if($showFarm) {
				$h .= '<td class="font-sm color-muted">';
					if($eProduct['farm']->empty()) {
						$h .= '<i>'.s("Ancien producteur").'</i>';
					} else {
						$h .= encode($eProduct['farm']['name']);
					}
				$h .= '</td>';
			}

			if($eDate['type'] === Date::PRO) {
				$h .= '<td class="td-min-content '.(($isExpired or $eProduct->exists()) ? '' : 'shop-product-not-exist').'">';
					if($eProduct['packaging'] !== NULL) {
						$h .= s("Colis de {value}", \selling\UnitUi::getValue($eProduct['packaging'], $eProductSelling['unit'], TRUE));
					}
				$h .= '</td>';
			}

			$h .= '<td class="text-end '.(($isExpired or $eProduct->exists()) ? '' : 'shop-product-not-exist').'" style="white-space: nowrap">';
				$price = '';
				$unit = \selling\UnitUi::getBy($eProductSelling['unit'], short: TRUE);
				if($eProduct['priceInitial'] === NULL) {
					$field = 'price';
				} else {
					$field = 'priceDiscount';
					$h .= new \selling\PriceUi()->priceWithoutDiscount($eProduct['priceInitial'], unit: ' '.$unit);
				}
				$price .= \util\TextUi::money($eProduct['price']).$unit;
				if($canUpdate) {
					$h .= $eProduct->quick($field, $price);
				} else {
					$h .= $price;
				}
				if($eProduct['price'] < 0) {
					$h .= '<div class="shop-product-alert">'.\Asset::icon('exclamation-circle').' '.s("Prix négatif").'</div>';
				}
			$h .= '</td>';

			if($isExpired === FALSE) {

				$h .= '<td class="shop-product-available highlight" '.($eProduct->exists() ? 'id="product-available-'.$eProduct['id'].'"' : '').' '.($hasLimits ? 'rowspan="2"' : '').'>';
					$h .= $this->getStatus($eProduct, $canUpdate);
				$h .= '</td>';

			}

			if($hasSold) {

				$sold = $eProduct['sold'] ?? 0;

				$h .= '<td class="text-center" '.($hasLimits ? 'rowspan="2"' : '').'>';

					if($sold > 0) {
						$h .= '<span class="shop-product-sold">'.$sold.'</span>';
					} else {
						$h .= '-';
					}

				$h .= '</td>';

			}

			if($canAction) {

				$h .= '<td class="td-min-content" '.($hasLimits ? 'rowspan="2"' : '').' >';

					if($canUpdate) {

						$h .= '<a href="/shop/product:update?id='.$eProduct['id'].'" class="btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a> ';
						$h .= '<a data-ajax="/shop/product:doDelete" class="btn btn-outline-secondary" data-confirm="'.s("Voulez-vous vraiment supprimer ce produit de la vente ?").'" post-id="'.$eProduct['id'].'">'.\Asset::icon('trash-fill').'</a>';

					}

				$h .= '</td>';

			}

		$h .= '</tr>';

		if($hasLimits) {
			$h .= $this->getLimits($columns, $eProduct, $eDate['cCustomer'], $eDate['cGroup'], $canGroup, excludeAt: TRUE, outCatalog: $outCatalog);
		}

		return $h;

	}

	public function getListByCatalog(\farm\Farm $eFarm, Catalog $eCatalog, \Collection $cProduct): string {

		$taxes = $eFarm->getSelling('hasVat') ? '<span class="util-annotation">'.\selling\CustomerUi::getTaxes($eCatalog['type']).'</span>' : '';
		$columns = 2;

		$h = '<div class="util-overflow-sm stick-xs">';
			$h .= '<table class="tbody-even td-padding-sm" data-batch="#batch-catalog">';
				$h .= '<thead>';
					$h .= '<tr>';
						if($eCatalog->canWrite()) {
							$h .= '<th class="shop-product-group-first td-checkbox">';
								$h .= '<label title="'.s("Tout cocher / Tout décocher").'">';
									$h .= '<input type="checkbox" class="batch-all" onclick="ShopProduct.toggleSelection(this)"/>';
								$h .= '</label>';
							$h .= '</th>';
						} else {
							$h .= '<th class="shop-product-group-first shop-product-group-readonly"></th>';
						}
						$h .= '<th colspan="2">'.s("Produit").'</th>';
						if($eCatalog['type'] === Date::PRO) {
							$columns++;
							$h .= '<th>'.s("Colisage").'</th>';
						}
						$h .= '<th class="text-end">'.s("Prix").' '.$taxes.'</th>';
						$h .= '<th class="highlight">'.s("Disponible à la vente").'</th>';
						$h .= '<th class="text-center">';
							$h .= s("En vente");
						$h .= '</th>';
						if($eCatalog->canWrite()) {
							$h .= '<th></th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';

				foreach($cProduct as $eProduct) {

					if($eProduct['parent'] === FALSE) {

						$h .= '<tbody>';
							$h .= $this->getOneByCatalog($eCatalog, $eProduct, $columns, FALSE);
						$h .= '</tbody>';

					} else {

						$h .= '<tbody class="shop-product-group">';
							$h .= '<tr>';

								if($eCatalog->canWrite()) {
									$h .= '<td class="shop-product-group-first td-checkbox"></td>';
								} else {
									$h .= '<td class="shop-product-group-first shop-product-group-readonly"></td>';
								}

								$h .= '<td class="td-min-content">';
								$h .= '</td>';

								$h .= '<td colspan="'.(2 + (($eCatalog['type'] === Date::PRO) ? 1 : 0)).'">';
									$h .= '<h4>'.encode($eProduct['parentName']).'</h4>';
								$h .= '</td>';

								$h .= '<td class="shop-product-available highlight"></td>';

								$h .= '<td class="text-center"></td>';

								if($eCatalog->canWrite()) {

									$h .= '<td class="td-min-content">';
										$h .= '<a href="/shop/product:update?id='.$eProduct['id'].'" class="btn btn-secondary">'.\Asset::icon('gear-fill').'</a> ';
										$h .= '<a data-ajax="/shop/product:doDelete" class="btn btn-secondary" data-confirm="'.s("Voulez-vous vraiment supprimer ce groupe ? Les produits actuellement dans le groupe resteront dans le catalogue.").'" post-id="'.$eProduct['id'].'">'.\Asset::icon('trash-fill').'</a>';
									$h .= '</td>';

								}

							$h .= '</tr>';

							foreach($eProduct['cProductChild'] as $eProductChild) {
								$h .= $this->getOneByCatalog($eCatalog, $eProductChild, $columns, TRUE);
							}

						$h .= '</tbody>';

					}

				}
;
			$h .= '</table>';
		$h .= '</div>';

		$h .= $this->getBatch($eFarm, eCatalog: $eCatalog);

		return $h;

	}

	protected function getOneByCatalog(Catalog $eCatalog, Product $eProduct, int $columns, bool $inGroup): string {

		$eProductSelling = $eProduct['product'];
		$uiProductSelling = new \selling\ProductUi();

		$hasLimits = (
			$inGroup === FALSE and
			$eProduct['promotion'] !== Product::NONE or
			$eProduct['limitCustomers'] or
			$eProduct['limitGroups'] or
			$eProduct['excludeCustomers'] or
			$eProduct['excludeGroups'] or
			$eProduct['limitMax'] or
			$eProduct['limitStartAt'] or
			$eProduct['limitEndAt']
		);

		$h = '<tr class="shop-product-one">';

			if($eCatalog->canWrite()) {
				$h .= '<td class="shop-product-group-first td-checkbox" rowspan="'.($hasLimits ? 2 : 1).'">';
					$h .= '<label>';
						$h .= '<input type="checkbox" name="batch[]" value="'.$eProduct['id'].'" oninput="ShopProduct.changeSelection()" data-batch="'.$this->getProductBatch($eProduct).'"/>';
					$h .= '</label>';
				$h .= '</td>';
			} else {
				$h .= '<td class="shop-product-group-first shop-product-group-readonly" rowspan="'.($hasLimits ? 2 : 1).'"></td>';
			}

			$h .= '<td class="td-min-content" '.($hasLimits ? 'rowspan="2"' : '').'>';
				if($eProductSelling['vignette'] !== NULL) {
					$h .= \selling\ProductUi::getVignette($eProductSelling, '3rem');
				} else if($eProductSelling['unprocessedPlant']->notEmpty()) {
					$h .= \plant\PlantUi::getVignette($eProductSelling['unprocessedPlant'], '3rem');
				}
			$h .= '</td>';

			$h .= '<td>';
				$h .= $uiProductSelling->getInfos($eProductSelling, includeStock: TRUE);
			$h .= '</td>';

			if($eCatalog['type'] === Date::PRO) {
				$h .= '<td class="td-min-content">';
					if($eProduct['packaging'] !== NULL) {
						$h .= $eProduct->quick('packaging', s("Colis de {value}", \selling\UnitUi::getValue($eProduct['packaging'], $eProductSelling['unit'], TRUE)));
					} else {
						$h .= $eProduct->quick('packaging', '-');
					}
				$h .= '</td>';
			}

			$h .= '<td class="text-end" style="white-space: nowrap">';
				$unit = \selling\UnitUi::getBy($eProductSelling['unit'], short: TRUE);
				if($eProduct['priceInitial'] !== NULL) {
					$h .= new \selling\PriceUi()->priceWithoutDiscount($eProduct['priceInitial'], unit: $unit);
					$field = 'priceDiscount';
				} else {
					$field = 'price';
				}
				$price = \util\TextUi::money($eProduct['price']).$unit;
				$h .= $eProduct->quick($field, $price);
				if($eProduct['price'] < 0) {
					$h .= '<div class="shop-product-alert">'.\Asset::icon('exclamation-circle').' '.s("Prix négatif").'</div>';
				}
			$h .= '</td>';

			$h .= '<td class="shop-product-available highlight" '.($hasLimits ? 'rowspan="2"' : '').' id="product-available-'.$eProduct['id'].'">';
				$h .= $this->getStatus($eProduct, TRUE);
			$h .= '</td>';

			$h .= '<td class="text-center" '.($hasLimits ? 'rowspan="2"' : '').'>';
				$h .= $this->toggle($eProduct);
			$h .= '</td>';

			if($eCatalog->canWrite()) {

				$h .= '<td class="td-min-content" '.($hasLimits ? 'rowspan="2"' : '').'>';
					$h .= '<a href="/shop/product:update?id='.$eProduct['id'].'" class="btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a> ';
					$h .= '<a data-ajax="/shop/product:doDelete" class="btn btn-outline-secondary" data-confirm="'.s("Voulez-vous vraiment supprimer ce produit de ce catalogue ?").'" post-id="'.$eProduct['id'].'">'.\Asset::icon('trash-fill').'</a>';
				$h .= '</td>';

			}

		$h .= '</tr>';

		if($hasLimits) {
			$h .= $this->getLimits($columns, $eProduct, $eCatalog['cCustomer'], $eCatalog['cGroup'], $inGroup);
		}

		return $h;

	}

	public function getProductBatch(Product $eProduct): string {

		$eProduct->expects(['child']);

		$batch = [];

		if(
			$eProduct->acceptRelation() === FALSE or
			$eProduct['child']
		) {
			$batch[] = 'not-relation';
		}

		return implode(' ', $batch);

	}

	public function getBatch(\farm\Farm $eFarm, Date $eDate = new Date(), Catalog $eCatalog = new Catalog()): string {

		$menu = '';

		if($eCatalog->notEmpty()) {

			$menu .= '<a data-ajax-submit="/shop/product:doUpdateStatusCollection" post-status="'.Product::ACTIVE.'" data-confirm="'.s("Activer ces produits ?").'" class="batch-menu-active batch-menu-item">';
				$menu .= \Asset::icon('toggle-on');
				$menu .= '<span>'.s("Activer").'</span>';
			$menu .= '</a>';

			$menu .= '<a data-ajax-submit="/shop/product:doUpdateStatusCollection" post-status="'.Product::INACTIVE.'" data-confirm="'.s("Désactiver ces produits ?").'" class="batch-menu-inactive batch-menu-item">';
				$menu .= \Asset::icon('toggle-off');
				$menu .= '<span>'.s("Désactiver").'</span>';
			$menu .= '</a>';

			$menu .= '<a data-url="/shop/relation:create?farm='.$eFarm['id'].($eDate->notEmpty() ? '&date='.$eDate['id'] : '').($eCatalog->notEmpty() ? '&catalog='.$eCatalog['id'] : '').'" class="batch-menu-relation batch-menu-item">';
				$menu .= \Asset::icon('plus-circle');
				$menu .= '<span>'.s("Créer un groupe").'</span>';
			$menu .= '</a>';

		}

		$danger = '<a data-ajax-submit="/shop/product:doDeleteCollection" data-confirm="'.s("Confirmer la suppression de ces produits du catalogue ?").'" class="batch-menu-delete batch-menu-item batch-menu-item-danger">';
			$danger .= \Asset::icon('trash');
			$danger .= '<span>'.s("Supprimer").'</span>';
		$danger .= '</a>';

		return \util\BatchUi::group('batch-catalog', $menu, $danger, title: s("Pour les produits sélectionnés"));

	}

	protected function getLimits(int $columns, Product $eProduct, \Collection $cCustomer, \Collection $cCustomerGroup, bool $canGroup, bool $excludeAt = FALSE, bool $outCatalog = FALSE): string {

		$h = '<tr>';

			if($canGroup) {
				$h .= '<td class="shop-product-group-first shop-product-group-readonly"></td>';
			}

			$h .= '<td colspan="'.$columns.'" style="padding-top: 0; padding-bottom: 0rem">';

				$h .= '<div class="shop-product-limits">';

					switch($eProduct['promotion']) {

						case Product::BASIC :
							$h .= '<span class="shop-product-promotion">'.\Asset::icon('star-fill').' '.s("Mis en avant").'</span>';
							break;

						case Product::NEW :
							$h .= '<span class="shop-product-promotion">'.\Asset::icon('star-fill').' '.s("Nouveauté").'</span>';
							break;

						case Product::WEEK :
							$h .= '<span class="shop-product-promotion">'.\Asset::icon('star-fill').' '.s("Produit de la semaine").'</span>';
							break;

						case Product::MONTH :
							$h .= '<span class="shop-product-promotion">'.\Asset::icon('star-fill').' '.s("Produit du mois").'</span>';
							break;

					}

					if($outCatalog) {
						$h .= '<span>'.s("Hors catalogue").'</span>';
					}

					if($eProduct['limitMin']) {

						if($eProduct['packaging'] === NULL) {
							$value = \selling\UnitUi::getValue($eProduct['limitMin'], $eProduct['product']['unit']);
						} else {
							$value = s("{value} colis", $eProduct['limitMin']);
						}

						$h .= '<span>'.s("Minimum demandé par commande {value}", '<u>'.$value.'</u>').'</span>';
					}

					if($eProduct['limitMax']) {

						if($eProduct['packaging'] === NULL) {
							$value = \selling\UnitUi::getValue($eProduct['limitMax'], $eProduct['product']['unit']);
						} else {
							$value = s("{value} colis", $eProduct['limitMax']);
						}

						$h .= '<span>'.s("Maximum autorisé par commande {value}", '<u>'.$value.'</u>').'</span>';
					}

					if($eProduct['limitCustomers'] or $eProduct['limitGroups']) {

						$for = [];

						foreach($eProduct['limitCustomers'] as $customer) {

							if($cCustomer->offsetExists($customer)) {
								$for[] = '<u>'.encode($cCustomer[$customer]->getName()).'</u>';
							}

						}

						foreach($eProduct['limitGroups'] as $group) {

							if($cCustomerGroup->offsetExists($group)) {
								$for[] = '<u>'.\selling\CustomerGroupUi::link($cCustomerGroup[$group]).'</u>';
							}

						}

						$h .= '<span>'.s("Uniquement pour {value}", implode(', ', $for)).'</span>';

					}

					if($eProduct['excludeCustomers'] or $eProduct['excludeGroups']) {

						$for = [];

						foreach($eProduct['excludeCustomers'] as $customer) {

							if($cCustomer->offsetExists($customer)) {
								$for[] = '<u>'.encode($cCustomer[$customer]->getName()).'</u>';
							}

						}

						foreach($eProduct['excludeGroups'] as $group) {

							if($cCustomerGroup->offsetExists($group)) {
								$for[] = '<u>'.\selling\CustomerGroupUi::link($cCustomerGroup[$group]).'</u>';
							}

						}

						$h .= '<span>'.s("Non vendu à {value}", implode(', ', $for)).'</span>';

					}

					if($excludeAt === FALSE) {

						if($eProduct['limitStartAt'] !== NULL and $eProduct['limitEndAt'] !== NULL) {
							$h .= '<span>'.s("Disponible pour les ventes livrées du {from} au {to}", [
								'from' => '<u>'.\util\DateUi::numeric($eProduct['limitStartAt']).'</u>',
								'to' => '<u>'.\util\DateUi::numeric($eProduct['limitEndAt']).'</u>',
							]).'</span>';
						} else if($eProduct['limitStartAt'] !== NULL) {
							$h .= '<span>'.s("Disponible à partir des ventes livrées le {value}", '<u>'.\util\DateUi::numeric($eProduct['limitStartAt']).'</u>').'</span>';
						} else if($eProduct['limitEndAt'] !== NULL) {
							$h .= '<span>'.s("Disponible jusqu'aux ventes livrées le {value}", '<u>'.\util\DateUi::numeric($eProduct['limitEndAt']).'</u>').'</span>';
						}

					}

				$h .= '</div>';

			$h .= '</td>';

		$h .= '</tr>';

		return $h;

	}

	public function getStatus(Product $eProduct, bool $canUpdate): string {

		$h = '';

		if($eProduct->exists()) {

			switch($eProduct['status']) {

				case Product::ACTIVE :

					if($eProduct['available'] === NULL) {

						$available = '<span class="color-success">'.\Asset::icon('check-circle-fill').' '.s("Illimité").'<span>';

					} else {

						if($eProduct['available'] === 0.0) {
							$available = '<span class="color-danger">'.\Asset::icon('x-circle-fill').' '.s("Rupture de stock").'</span>';
						} else {
							$available = '<span class="color-warning">'.\Asset::icon('check-circle-fill').' '.$eProduct['available'].'<span>';
						}

					}

					if($canUpdate) {
						$h .= $eProduct->quick('available', $available);
					} else {
						$h .= $available;
					}
					break;

				case Product::INACTIVE :
					$h .= '<span class="color-muted">'.\Asset::icon('x-circle-fill').' '.s("Pas en vente").'<span>';
					break;

			};

		} else {
			$h .= '<span class="color-danger">'.\Asset::icon('x-circle-fill').' '.s("Supprimé").'<span>';
		}

		return $h;

	}

	public function create(\farm\Farm $eFarm, Date|Catalog $e): \Panel {

		$e->expects(['cProduct', 'cCategory']);

		$form = new \util\FormUi([
			'columnBreak' => 'sm'
		]);

		$title = ($e instanceof Date) ?
			s("Ajouter des produits à la vente") :
			s("Ajouter des produits au catalogue");

		if($e['cProduct']->empty()) {

			$h = '<div class="util-block-help">';
				$h .= '<p>'.s("Vous devez d'abord renseigner les produits que vous souhaitez proposer à la vente dans votre ferme !").'</p>';
				$h .= '<a href="'.\farm\FarmUi::urlSellingProducts($eFarm).'" class="btn btn-secondary">'.s("Renseigner mes produits").'</a>';
			$h .= '</div>';

			return new \Panel(
				id: 'panel-product-create',
				title: $title,
				body: $h
			);

		} else {

			$h = '';

			if($e instanceof Date) {
				$h .= $form->hidden('date', $e['id']);
			} else {
				$h .= $form->hidden('catalog', $e['id']);
			}

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicField($e, 'productsList');

		}

		return new \Panel(
			id: 'panel-product-create',
			title: $title,
			dialogOpen: $form->openAjax('/shop/product:doCreateCollection', ['class' => 'panel-dialog']),
			dialogClose: $form->close(),
			body: $h,
			footer: $form->submit(s("Ajouter les produits"), ['data-submit-waiter' => s("Ajout en cours..."), 'class' => 'btn btn-primary btn-lg'])
		);
	}

	public function update(Product $e): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/shop/product:doUpdate');

			$h .= $form->hidden('id', $e['id']);

			if($e['parent']) {

				$h .= $form->dynamicGroups($e, ['parentName', 'children']);

			} else {

				if($e['catalog']->notEmpty()) {
					$info = s("Le prix que vous donnez ici ne s'applique qu'à ce catalogue et n'est pas prioritaire par rapport aux prix personnalisés de vos clients");
				} else {
					$info = s("Le prix que vous donnez ici ne s'applique qu'à cette boutique et n'est pas prioritaire par rapport aux prix personnalisés de vos clients");
				}

				$info .= ' '.s("(<link>en savoir plus</link>)", ['link' => '<a href="/doc/selling:pricing">']);

				$h .= $form->group(
					(self::p('price')->label)($e).\util\FormUi::info($info),
					$form->dynamicField($e, 'price').$form->dynamicField($e, 'priceDiscount')
				);

				$h .= $form->dynamicGroups($e, match($e['type']) {
					Product::PRO => ['packaging', 'available', 'promotion'],
					Product::PRIVATE => ['available', 'promotion']
				});

				$h .= '<br/>';
				$h .= '<h3>'.\Asset::icon('lock-fill').'  '.s("Limitations de commande").'</h3>';
				$h .= '<div class="util-block bg-background-light">';

					$h .= $form->group(
					);

					if($e['catalog']->notEmpty()) {
						$h .= $this->getLimitAtField($form, $e);
					}

					$h .= $form->dynamicGroups($e, ['limitMin', 'limitMax']);

				$h .= '</div>';

				$h .= '<br/>';
				$h .= '<h3>'.\Asset::icon('person-circle').'  '.s("Autorisations et interdictions à certains clients").'</h3>';

				$h .= '<p class="util-info">'.s("Vous pouvez laisser ces champs vides si vous souhaitez que ce produit soit accessible à tous vos clients. Vous ne pouvez pas à la fois autoriser et interdire les commandes d'un produit à certains clients.").'</pa>';

				$h .= '<div class="util-block bg-background-light">';

					$limit = '<div class="shop-product-update-restriction">';
						$limit .= '<div>';
							$limit .= '<fieldset>';
								$limit .= '<legend class="color-success">'.\Asset::icon('check-circle-fill').' '.s("Clients").'</legend>';
								$limit .= $form->dynamicField($e, 'limitCustomers');
							$limit .= '</fieldset>';
						$limit .= '</div>';
						$limit .= '<div>';
							$limit .= '<fieldset>';
								$limit .= '<legend class="color-success">'.\Asset::icon('check-circle-fill').' '.s("Groupes de clients").'</legend>';
								$limit .= $form->dynamicField($e, 'limitGroups');
							$limit .= '</fieldset>';
						$limit .= '</div>';
					$limit .= '</div>';

					$h .= $form->group(
						s("Autoriser uniquement les commandes de ce produit à :"),
						$limit
					);

					$limit = '<div class="shop-product-update-restriction">';
						$limit .= '<div>';
							$limit .= '<fieldset>';
								$limit .= '<legend class="color-danger">'.\Asset::icon('x-circle-fill').' '.s("Clients").'</legend>';
								$limit .= $form->dynamicField($e, 'excludeCustomers');
							$limit .= '</fieldset>';
						$limit .= '</div>';
						$limit .= '<div>';
							$limit .= '<fieldset>';
								$limit .= '<legend class="color-danger">'.\Asset::icon('x-circle-fill').' '.s("Groupes de clients").'</legend>';
								$limit .= $form->dynamicField($e, 'excludeGroups');
							$limit .= '</fieldset>';
						$limit .= '</div>';
					$limit .= '</div>';

					$h .= $form->group(
						s("Interdire les commandes de ce produit à :").\util\FormUi::info(s("Nous vous recommandons d'utiliser l'interdiction avec précaution car vos clients pourraient voir qu'ils ne peuvent pas acheter ce produit s'ils consultent votre boutique sans être connecté.")),
						$limit
					);

				$h .= '</div>';

			}

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-product-update',
			title: $e['parent'] ?
				s("Modifier un groupe") :
				s("Modifier un produit"),
			subTitle: $e['parent'] ?
				NULL :
				\selling\ProductUi::getPanelHeader($e['product']),
			body: $h
		);

	}

	protected function getLimitAtField(\util\FormUi $form, Product $e): string {

		$h = '<div class="mb-1">';
			$h .= $form->dynamicField($e, 'limitStartAt');
		$h .= '</div>';
		$h .= '<div>';
			$h .= $form->dynamicField($e, 'limitEndAt');
		$h .= '</div>';

		return $form->group(
			s("Disponible uniquement pour les ventes livrées"),
			$h,
			['wrapper' => 'limitStartAt limitStartEnd']
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Product::model()->describer($property, [
			'product' => s("Produit"),
			'parentName' => s("Nom du groupe"),
			'available' => s("Disponible"),
			'packaging' => s("Colisage"),
			'promotion' => s("Mise en avant"),
			'price' => fn($e) => s("Prix unitaire").($e['farm']->getSelling('hasVat') ? ' <span class="util-annotation">'.$e->getTaxes().'</span>' : ''),
			'priceDiscount' => s("Prix remisé"),
			'date' => s("Vente"),
			'limitStartAt' => s("Proposer pour les commandes livrées à partir de"),
			'limitEndAt' => s("Proposer pour les commandes livrées jusqu'au"),
			'limitMin' => s("Quantité minimale demandée en cas de commande"),
			'limitMax' => s("Quantité maximale autorisée par commande"),
			'limitCustomers' => s("N'autoriser les commandes de ce produit qu'à certains clients"),
			'excludeCustomers' => s("Interdire les commandes de ce produit à certains clients"),
			'children' => s("Produits")
		]);

		switch($property) {

			case 'promotion' :
				$d->attributes['mandatory'] = TRUE;
				$d->field = 'select';
				$d->prepend = \Asset::icon('star-fill');
				$d->labelAfter = \util\FormUi::info(s("Les produits mis en avant sont affichés en tête de liste."));
				$d->values = [
					Product::NONE => s("Pas de mise en avant"),
					Product::BASIC => s("Mise en avant sans mention"),
					Product::NEW => s("Mise en avant avec mention « Nouveauté »"),
					Product::WEEK => s("Mise en avant avec mention « Produit de la semaine »"),
					Product::MONTH => s("Mise en avant avec mention « Produit du mois »"),
				];
				break;

			case 'limitStartAt' :
				$d->prepend = s("À partir du");
				break;

			case 'limitEndAt' :
				$d->prepend = s("Jusqu'au");
				break;

			case 'limitMin' :
				$d->append = fn(\util\FormUi $form, Product $e) => $form->addon(($e['packaging'] === NULL) ?
					\selling\UnitUi::getSingular($e['product']['unit'], short: TRUE) :
					s("colis"));
				$d->placeholder = s("Aucune");
				break;

			case 'limitMax' :
				$d->append = fn(\util\FormUi $form, Product $e) => $form->addon(($e['packaging'] === NULL) ?
					\selling\UnitUi::getSingular($e['product']['unit'], short: TRUE) :
					s("colis"));
				$d->placeholder = s("Illimité");
				break;

			case 'limitCustomers' :
				$d->autocompleteDefault = fn(Product $e) => $e['cCustomerLimit'] ?? $e->expects(['cCustomerLimit']);
				$d->placeholder = s("Tapez un nom de client à autoriser");
				$d->autocompleteBody = function(\util\FormUi $form, Product $e) {
					return [
						'farm' => $e['farm']['id'],
						'type' => $e['type']
					];
				};
				new \selling\CustomerUi()->query($d, TRUE);
				$d->group = ['wrapper' => 'limitCustomers'];
				break;

			case 'excludeCustomers' :
				$d->autocompleteDefault = fn(Product $e) => $e['cCustomerExclude'] ?? $e->expects(['cCustomerExclude']);
				$d->placeholder = s("Tapez un nom de client à interdire");
				$d->autocompleteBody = function(\util\FormUi $form, Product $e) {
					return [
						'farm' => $e['farm']['id'],
						'type' => $e['type']
					];
				};
				new \selling\CustomerUi()->query($d, TRUE);
				$d->group = ['wrapper' => 'excludeCustomers'];
				break;

			case 'limitGroups' :
				$d->autocompleteDefault = fn(Product $e) => $e['cGroupLimit'] ?? $e->expects(['cGroupLimit']);
				$d->placeholder = s("Tapez un nom de groupe de clients à autoriser");
				$d->autocompleteBody = function(\util\FormUi $form, Product $e) {
					return [
						'farm' => $e['farm']['id'],
						'type' => $e['type']
					];
				};
				new \selling\CustomerGroupUi()->query($d, TRUE);
				$d->group = ['wrapper' => 'limitGroups'];
				break;

			case 'excludeGroups' :
				$d->autocompleteDefault = fn(Product $e) => $e['cGroupExclude'] ?? $e->expects(['cGroupExclude']);
				$d->placeholder = s("Tapez un nom de groupe de clients à interdire");
				$d->autocompleteBody = function(\util\FormUi $form, Product $e) {
					return [
						'farm' => $e['farm']['id'],
						'type' => $e['type']
					];
				};
				new \selling\CustomerGroupUi()->query($d, TRUE);
				$d->group = ['wrapper' => 'excludeGroups'];
				break;

			case 'available' :
				$d->field = function(\util\FormUi $form, Product $e) use($d) {

					$e->expects([
						'packaging',
					]);

					$step = (
						$e['type'] === Product::PRO or
						$e['product']['unit']->isInteger()
					) ? 1 : 0.1;

					$h = '<div class="input-group" data-product="'.$e['product']['id'].'">';
						$h .= $form->number($d->name, $e['available'] ?? NULL, [
							'data-product' => $e['product']['id'],
							'onfocusin' => 'DateManage.checkAvailableFocusIn(this)',
							'onfocusout' => 'DateManage.checkAvailableFocusOut(this)',
							'placeholder' => s("Illimité"),
							'data-placeholder' => s("Illimité"),
							'min' => 0,
							'max' => Product::model()->getPropertyRange('available')[1],
							'step' => $step,
						]);

						if(
							$e['type'] === Product::PRIVATE or
							$e['packaging'] === NULL
						) {
							$unit = \selling\UnitUi::getSingular($e['product']['unit'], TRUE);
						} else {
							$unit = s("colis");
						}

						$h .= $form->addon($unit);

					$h .= '</div>';

					return $h;

				};
				break;

			case 'packaging' :
				$d->append = fn(\util\FormUi $form, Product $e) => $form->addon(\selling\UnitUi::getSingular($e['product']['unit'], TRUE));
				break;

			case 'price' :
				$d->append = function(\util\FormUi $form, Product $e) {
					return $form->addon(s('€ {unit}', [
							'unit' => \selling\UnitUi::getBy($e['product']['unit'], short: TRUE)
						]));

				};
				$d->default = function(Product $e) {
					return $e['priceInitial'] ?? $e['price'];
				};
				$d->after = function(\util\FormUi $form, Product $e) {
					if($e->isQuick()) {
						return NULL;
					}
					return new \selling\PriceUi()->getDiscountLink($e['product']['id'], hasDiscountPrice: $e['priceInitial'] !== NULL);
				};
				$d->attributes = [
					'onfocus' => 'this.select()'
				];
				break;

			case 'priceDiscount':
				$d->groupLabel = FALSE;
				$d->inputGroup = function(Product $eProduct) {
					return ['data-price-discount' => $eProduct['product']['id'], 'class' => $eProduct['priceInitial'] !== NULL ? '' : 'hide'];
				};
				$d->field = function(\util\FormUi $form, Product $eProduct) {
					return $form->number(
						$this->name,
						($eProduct['priceInitial'] ?? NULL) !== NULL ? $eProduct['price'] : NULL,
						['step' => 0.01],
					);
				};
				$d->groupLabel = FALSE;
				$d->prepend = s("Prix remisé");
				$d->append = function(\util\FormUi $form, Product $eProduct) {

					$unit = s("€ {unit}", ['unit' => \selling\UnitUi::getBy($eProduct['product']['unit'], short: TRUE)]);
					$append = '<div class="input-group-addon">'.$unit.'</div>'.
						'<div class="input-group-addon">'.new \selling\PriceUi()->getDiscountTrashAddon($eProduct['product']['id']).'</div>';

					return $append;

				};
				break;

			case 'children' :
				$d->labelAfter = \util\FormUi::info(s("La vignette du premier produit de la liste sera utilisée comme vignette pour le groupe sur les boutiques pour les particuliers."));
				$d->autocompleteDefault = fn(Product $e) => $e['cRelation'] ?? new \Collection();
				$d->autocompleteBody = function(\util\FormUi $form, Product $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id'],
						'catalog' => $e['catalog']->empty() ? NULL : $e['catalog']['id'],
						'date' => $e['date']->empty() ? NULL : $e['date']['id'],
						'relations' => FALSE
					];
				};
				new \shop\RelationUi()->query($d, multiple: TRUE);
				$d->group += [
					'class' => 'relation-wrapper'
				];
				break;


		}

		return $d;

	}

}
