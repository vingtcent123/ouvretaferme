<?php
namespace shop;

class ProductUi {

	public function __construct() {

		\Asset::css('shop', 'product.css');

	}

	public function toggle(Product $eProduct) {

		return \util\TextUi::switch([
			'id' => 'product-switch-'.$eProduct['id'],
			'data-ajax' => $eProduct->canWrite() ? '/shop/product:doUpdateStatus' : NULL,
			'post-id' => $eProduct['id'],
			'post-status' => ($eProduct['status'] === Product::ACTIVE) ? Product::INACTIVE : Product::ACTIVE
		], $eProduct['status'] === Product::ACTIVE);

	}

	public function getList(Shop $eShop, Date $eDate, \selling\Sale $eSale, \Collection $cCategory, bool $isModifying): string {

		$eDate->expects(['cProduct']);

		$h = '';

		$ccProduct = $eDate['cProduct']->reindex(['product', 'category']);

		if($ccProduct->empty()) {
			$h .= '<div class="util-block-help">';
				$h .= '<h4>'.s("Il n'y a pas encore de produit disponible à la vente !").'</h4>';
				$h .= '<p>'.s("La vente du {value} est fermée pour le moment car votre producteur n'a pas encore indiqué les produits qu'il souhaite vous proposer.", \util\DateUi::textual($eDate['deliveryDate'])).'</p>';
			$h .= '</div>';
			return $h;
		}

		if($ccProduct->count() === 1) {
			$h .= $this->getProducts($eShop, $eDate, $eSale, $isModifying, $ccProduct->first());
		} else {

			if($ccProduct->offsetExists('')) {
				$h .= $this->getProducts($eShop, $eDate, $eSale, $isModifying, $ccProduct['']);
			}

			foreach($cCategory as $eCategory) {

				if($ccProduct->offsetExists($eCategory['id']) === FALSE) {
					continue;
				}

				$h .= '<h3>'.encode($eCategory['name']).'</h3>';
				$h .= $this->getProducts($eShop, $eDate, $eSale, $isModifying, $ccProduct[$eCategory['id']]);

			}

		}

		$h .= '<br/><br/><br/><br/>';

		if($eDate['isOrderable'] and ($eSale->canBasket($eShop) or $isModifying)) {
			$h .= $this->getOrderedProducts($eShop, $eDate, $eSale, $isModifying);
		}

		return $h;

	}

	protected function getOrderedProducts(Shop $eShop, Date $eDate, \selling\Sale $eSale, bool $isModifying): string {

		$confirmEmpty = [
			'data-confirm-normal' => s("Voulez-vous vider votre panier ?"),
			'data-confirm-modify' => s("Votre commande n'a pas été modifiée, et votre ancienne commande reste valide. Confirmer ?"),
		];
		$labelEmpty = $isModifying ? s("Annuler") : s("Vider mon panier");

		if($eSale->notEmpty() and $eSale['paymentMethod'] === NULL) {
			$defaultJson = (new BasketUi())->getJsonBasket($eSale);
		} else {
			$defaultJson = 'null';
		}

		$h = '<div class="shop-product-ordered hide" id="shop-basket" '.attr('onrender', 'BasketManage.init('.$eDate['id'].', '.$defaultJson.')').'>';
			$h .= '<div>';
				$h .= '<div class="shop-product-ordered-icon">'.\Asset::icon('basket').'</div>';
				$h .= '<span id="shop-basket-articles"></span>';
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<div class="shop-product-ordered-icon">'.\Asset::icon('currency-euro').'</div>';
				$h .= '<span id="shop-basket-price"></span>';
				$h .= ' '.$this->getTaxes($eDate);
			$h .= '</div>';
			$h .= '<div style="display: flex;">';
				$h .= '<a href="'.ShopUi::url($eShop).'/'.$eDate['id'].'/panier'.($isModifying ? '?modify=1' : '').'" class="btn btn-secondary" id="shop-basket-next">';
					$h .= '<span class="hide-sm-up">'.($isModifying ? s("Modifier") : s("Commander")).'</span>';
					$h .= '<span class="hide-xs-down">'.($isModifying ? s("Modifier la commande") : s("Poursuivre la commande")).'</span>';
				$h .= '</a>';
				$h .= '&nbsp;';
				$h .= '<a onclick="BasketManage.empty(this, '.$eDate['id'].', true)" class="shop-basket-empty btn btn-danger" '.attrs($confirmEmpty).'>';
					$h .= '<span class="hide-sm-up" title="'.$labelEmpty.'">'.\Asset::icon('trash').'</span>';
					$h .= '<span class="hide-xs-down">'.$labelEmpty.'</span>';
				$h .= '</a>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getProducts(Shop $eShop, Date $eDate, \selling\Sale $eSale, bool $isModifying, \Collection $cProduct): string {

		$h = '<div class="shop-product-wrapper">';
			$h .= $cProduct->makeString(fn($eProduct) => $this->getProduct($eShop, $eDate, $eProduct, $eSale, $isModifying));
		$h .= '</div>';

		return $h;

	}

	public function getProduct(Shop $eShop, Date $eDate, Product $eProduct, \selling\Sale $eSale, bool $isModifying): string {

		$eProduct->expects(['reallyAvailable']);

		$canOrder = ($eSale->canBasket($eShop) or $isModifying);

		$eProductSelling = $eProduct['product'];

		if($eProduct['packaging'] === NULL) {
			$price = $eProduct['price'];
		} else {
			$price = $eProduct['price'] * $eProduct['packaging'];
		}

		$h = '<div class="shop-product" data-id="'.$eProductSelling['id'].'" data-price="'.$price.'" data-has="0">';

			if($eProductSelling['vignette'] !== NULL) {
				$url = (new \media\ProductVignetteUi())->getUrlByElement($eProductSelling, 'l');
			} else if($eProductSelling['plant']->notEmpty()) {
				$url = (new \media\PlantVignetteUi())->getUrlByElement($eProductSelling['plant'], 'l');
			} else {
				$url = NULL;
			}

			$h .= '<div ';
			if($url !== NULL) {
				$h .= 'class="shop-product-image" style="background-image: url('.$url.')"';
			} else {
				$h .= 'class="shop-product-image shop-product-image-empty"';
			}
			$h .= '>';
				if($url === NULL) {
					if($eProductSelling['plant']->notEmpty()) {
						$h .= \plant\PlantUi::getVignette($eProductSelling['plant'], '8rem');
					} else {
						$h .= \Asset::icon('camera', ['class' => 'shop-product-image-placeholder']);
					}
				}
				if($eProductSelling['quality']) {
					$h .= '<div class="shop-header-image-quality">'.\farm\FarmUi::getQualityLogo($eProductSelling['quality'], '2.5rem').'</div>';
				}
			$h .= '</div>';

			$h .= '<div class="shop-product-text">';
				$h .= '<div class="shop-product-content">';

					$h .= '<h4>';
						$h .= $eProductSelling->getName('html');
					$h .= '</h4>';

					if($eDate['type'] === Date::PRO and $eProductSelling['size'] !== NULL) {
						$h .= '<div class="shop-product-size">';
							$h .= encode($eProductSelling['size']);
						$h .= '</div>';
					}

					if($eProductSelling['description'] !== NULL) {
						$h .= '<div class="shop-product-description">';
							$h .= (new \editor\EditorUi())->value($eProductSelling['description']);
						$h .= '</div>';
					}

				$h .= '</div>';

				$h .= '<div class="shop-product-buy">';

					$h .= '<div class="shop-product-buy-price">';

						$h .= '<span style="white-space: nowrap">'.\util\TextUi::money($eProduct['price']).' '.$this->getTaxes($eProduct).\selling\UnitUi::getBy($eProductSelling['unit']).'</span>';

						$h .= '<div class="shop-product-buy-infos">';

							if($eProduct['packaging'] !== NULL) {
								$h.= '<div class="shop-product-buy-info">';
									$h .= s("Colis de {value}", \selling\UnitUi::getValue($eProduct['packaging'], $eProductSelling['unit'], TRUE));
								$h .= '</div>';
							}

							if(
								$canOrder and
								$eProduct['reallyAvailable'] !== NULL
							) {

								$h.= '<div class="shop-product-buy-info">';
								if($eProduct['reallyAvailable'] > 0) {
									$h .= s("Disponible : {value}", $eProduct['reallyAvailable']);
								} else {
									$h .= s("Rupture de stock");
								}
								$h .= '</div>';

							}

						$h .= '</div>';
					$h .= '</div>';

					if(
						$canOrder and
						($eProduct['reallyAvailable'] === NULL or $eProduct['reallyAvailable'] > 0.0)
					) {
						$h .= self::numberOrder($eDate, $eProductSelling, $eProduct, 0, $eProduct['reallyAvailable']);
					}

				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getCreateList(\util\FormUi $form, \farm\Farm $eFarm, string $type, \Collection $cProduct, \Collection $cCategory, string $class = ''): string {

		\Asset::css('shop', 'shop.css');
		\Asset::css('shop', 'manage.css');
		\Asset::js('shop', 'manage.js');

		if($cProduct->empty()) {
			$h = '<div class="util-block-requirement">';
				$h .= '<p>'.s("Avant d'enregistrer une nouvelle date, vous devez renseigner les produits que vous souhaitez proposer à la vente dans votre ferme !").'</p>';
				$h .= '<a href="'.\farm\FarmUi::urlSellingProduct($eFarm).'" class="btn btn-secondary">'.s("Renseigner mes produits").'</a>';
			$h .= '</div>';
			return $h;
		}

		if($cCategory->empty()) {
			return self::getCreateByCategory($form, $eFarm, $type, $cProduct);
		}

		$ccProduct = $cProduct->reindex(['category']);

		$h = '<div class="tabs-h" id="date-products-tabs" onrender="'.encode('Lime.Tab.restore(this)').'">';

			$h .= '<div class="tabs-item">';

				foreach($cCategory as $eCategory) {

					if($ccProduct->offsetExists($eCategory['id']) === FALSE) {
						continue;
					}

					$products = $ccProduct[$eCategory['id']]->find(fn($eProduct) => $eProduct['checked'] ?? FALSE)->count();

					$h .= '<a class="tab-item " data-tab="'.$eCategory['id'].'" onclick="Lime.Tab.select(this)">';
						$h .= encode($eCategory['name']);
						$h .= '<span class="tab-item-count">';
							if($products > 0) {
								$h .= $products;
							}
						$h .= '</span>';
					$h .= '</a>';

				}

				if($ccProduct->offsetExists('')) {

					$products = $ccProduct['']->find(fn($eProduct) => $eProduct['checked'] ?? FALSE)->count();

					$h .= '<a class="tab-item " data-tab="empty" onclick="Lime.Tab.select(this)">';
						$h .= s("Non catégorisé");
						$h .= '<span class="tab-item-count">';
							if($products > 0) {
								$h .= $products;
							}
						$h .= '</span>';
					$h .= '</a>';
				}

			$h .= '</div>';

			$h .= '<div class="tabs-panel '.$class.' stick-sm">';

				foreach($ccProduct as $category => $cProduct) {

					$h .= '<div class="tab-panel" data-tab="'.($category ?: 'empty').'">';
						$h .= self::getCreateByCategory($form, $eFarm, $type, $cProduct);
					$h .= '</div>';

				}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public static function getCreateByCategory(\util\FormUi $form, \farm\Farm $eFarm, string $type, \Collection $cProduct): string {

		$displayStock = $cProduct->match(fn($eProduct) => $eProduct['stock'] !== NULL);

		$h = '<div class="date-products-list util-overflow-xs">';

			$h .= '<div class="date-products-item '.($displayStock ? 'date-products-item-with-stock' : '').' util-grid-header">';

				$h .= '<div class="shop-select '.($cProduct->count() < 2 ? 'shop-select-hide' : '').'">';
					$h .= '<input type="checkbox" '.attr('onclick', 'CheckboxField.all(this, \'[name^="products["]\', node => DateManage.selectProduct(node), \'.date-products-list\')').'"  title="'.s("Tout cocher / Tout décocher").'"/>';
				$h .= '</div>';
				$h .= '<div>';
					$h .= s("Produit");
				$h .= '</div>';
				$h .= '<div class="date-products-item-unit text-end">';
					if($type === Date::PRIVATE) {
						$h .= s("Multiple de vente");
					}
				$h .= '</div>';
				$h .= '<div class="date-products-item-price">'.s("Prix").'</div>';
				$h .= '<div>'.s("Limiter les ventes").'</div>';
				if($displayStock) {
					$h .= '<div class="text-end hide-xs-down">';
						$h .= s("Stock");
					$h .= '</div>';
				}

			$h .= '</div>';

			$h .= '<div class="date-products-body">';
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
							$price = $eProduct['privatePrice'] ?? $eProduct->calcPrivateMagicPrice($eFarm->getSelling('hasVat'));
							$packaging = NULL;
							break;

						case Date::PRO :
							$price = $eProduct['proPrice'] ?? $eProduct->calcProMagicPrice($eFarm->getSelling('hasVat'));
							$packaging = $eProduct['proPackaging'];
							break;

					}

					$eShopProduct = new Product([
						'farm' => $eFarm,
						'type' => $type,
						'product' => $eProduct,
						'price' => $price,
						'packaging' => $packaging,
						'available' => NULL,
					]);

					$h .= '<div class="date-products-item '.($displayStock ? 'date-products-item-with-stock' : '').' '.($checked ? 'selected' : '').'">';

						$h .= '<label class="shop-select">';
							$h .= $form->inputCheckbox('productsList['.$eProduct['id'].']', $eProduct['id'], $attributes);
						$h .= '</label>';
						$h .= '<label class="date-products-item-product" for="'.$attributes['id'].'">';
							$h .= \selling\ProductUi::getVignette($eProduct, '2rem');
							$h .= '&nbsp;&nbsp;';
							$h .= \selling\ProductUi::link($eProduct, TRUE);
							if($eProduct['size']) {
								$h .= ' <small class="color-muted"><u>'.encode($eProduct['size']).'</u></small>';
							}
						$h .= '</label>';
						$h .= '<label class="date-products-item-unit text-end" for="'.$attributes['id'].'">';

							switch($type) {

								case Date::PRIVATE :
									$step = ProductUi::getStep($type, $eProduct);
									$h .= \selling\UnitUi::getValue($step, $eProduct['unit']);
									break;

								case Date::PRO :
									if($eProduct['proPackaging'] !== NULL) {
										$h .= s("Colis de {value}", \selling\UnitUi::getValue($eProduct['proPackaging'], $eProduct['unit'], TRUE));
									}
									break;

							}

						$h .= '</label>';
						$h .= '<div data-wrapper="price['.$eProduct['id'].']" class="date-products-item-price '.($checked ? '' : 'hidden').'">';
							$h .= $form->dynamicField($eShopProduct, 'price['.$eProduct['id'].']');
						$h .= '</div>';
						$h .= '<div data-wrapper="available['.$eProduct['id'].']" class="date-products-item-available '.($checked ? '' : 'hidden').'">';
							$h .= $form->dynamicField($eShopProduct, 'available', function($d) use ($eProduct) {
								$d->name = 'available['.$eProduct['id'].']';
							});
						$h .= '</div>';
						if($displayStock) {
							$h .= '<label class="date-products-item-product-stock hide-xs-down '.($checked ? '' : 'hidden').'" for="'.$attributes['id'].'">';
								if($eProduct['stock'] !== NULL) {
									$h .= \selling\StockUi::getExpired($eProduct);
									$h .= '<span title="'.\selling\StockUi::getDate($eProduct['stockUpdatedAt']).'">'.\selling\UnitUi::getValue(round($eProduct['stock']), $eProduct['unit'], short: TRUE).'</span>';
								}
							$h .= '</label>';
						}

					$h .= '</div>';

				}
			$h .= '</div>';
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

	public static function numberOrder(Date $eDate, \selling\Product $eProductSelling, Product $eProduct, float $number, ?float $available): string {

		if($eDate['isOrderable'] === FALSE) {
			return '';
		}

		$attributesDecrease = 'BasketManage.update('.$eDate['id'].', '.$eProductSelling['id'].', -'.self::getStep($eDate['type'], $eProductSelling).', '.($available !== NULL ? $available : -1).')';
		$attributesIncrease = 'BasketManage.update('.$eDate['id'].', '.$eProductSelling['id'].', '.self::getStep($eDate['type'], $eProductSelling).', '.($available !== NULL ? $available : -1).')';

		if($eProduct['packaging'] === NULL) {
			$price = $eProduct['price'];
		} else {
			$price = $eProduct['price'] * $eProduct['packaging'];
		}

		$h = '<div class="shop-product-number">';
			$h .= '<a class="btn btn-outline-primary btn-sm shop-product-number-decrease" onclick="'.$attributesDecrease.'">-</a>';
			$h .= '<span class="shop-product-number-value" data-price="'.$price.'" data-available="'.$available.'" data-product="'.$eProductSelling['id'].'" data-field="number">';
				$h .= '<span>'.$number.'</span> ';

				if($eProduct['packaging'] === NULL) {
					$h .= \selling\UnitUi::getSingular($eProductSelling['unit'], short: TRUE);
				} else {
					$h .= s("colis");
				}

			$h .= '</span>';
			$h .= '<a class="btn btn-outline-primary btn-sm shop-product-number-increase" onclick="'.$attributesIncrease.'">+</a>';
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

	public function getUpdateList(\farm\Farm $eFarm, Date|Catalog $e, \Collection $cProduct, \Collection $cCategory, bool $isExpired = FALSE): string {

		$ccProduct = $cProduct->reindex(['product', 'category']);

		$update = fn($cProduct) => ($e instanceof Date) ?
			$this->getUpdateDate($eFarm, $e, $cProduct, $isExpired) :
			$this->getUpdateCatalog($eFarm, $e, $cProduct);

		if($ccProduct->count() === 1) {
			return $update($ccProduct->first());
		} else {

			$h = '';

			if($ccProduct->offsetExists('')) {
				$h .= $update($ccProduct['']);
			}

			foreach($cCategory as $eCategory) {

				if($ccProduct->offsetExists($eCategory['id']) === FALSE) {
					continue;
				}

				$h .= '<h3>'.encode($eCategory['name']).'</h3>';
				$h .= $update($ccProduct[$eCategory['id']]);

			}

			return $h;

		}

	}

	public function getUpdateDate(\farm\Farm $eFarm, Date $e, \Collection $cProduct, bool $isExpired = FALSE): string {

		$type = $e['type'];
		$taxes = $eFarm->getSelling('hasVat') ? '<span class="util-annotation">'.\selling\CustomerUi::getTaxes($type).'</span>' : '';
		$hasSold = $cProduct->contains(fn($eProduct) => $eProduct['sold'] !== NULL);
		$columns = 2;

		$hasCatalog = $cProduct->contains(fn($eProduct) => $eProduct['catalog']->notEmpty());
		$canAction = ($isExpired === FALSE and $cProduct->contains(fn($eProduct) => $eProduct->exists() and $eProduct['catalog']->empty()));

		$h = '<div class="'.($type === Date::PRIVATE ? 'util-overflow-xs' : 'util-overflow-sm').' stick-xs">';
			$h .= '<table class="tbody-even tbody-bordered">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th colspan="2">'.s("Produit").'</th>';
						if($type === Date::PRO) {
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

					$eProductSelling = $eProduct['product'];
					$uiProductSelling = new \selling\ProductUi();

					$canUpdate = ($isExpired === FALSE and $eProduct->exists() and $eProduct['catalog']->empty());
					$outCatalog = ($hasCatalog and $canUpdate);

					$hasLimits = (
						$eProduct['limitCustomers'] or
						$eProduct['limitNumber'] or
						$outCatalog
					);

					$h .= '<tbody>';

						$h .= '<tr>';

							$h .= '<td class="td-min-content" '.($hasLimits ? 'rowspan="2"' : '').'>';
								if($eProductSelling['vignette'] !== NULL) {
									$h .= \selling\ProductUi::getVignette($eProductSelling, '3rem');
								} else if($eProductSelling['plant']->notEmpty()) {
									$h .= \plant\PlantUi::getVignette($eProductSelling['plant'], '3rem');
								}
							$h .= '</td>';

							$h .= '<td class="'.(($isExpired or $eProduct->exists()) ? '' : 'shop-product-not-exist').'">';
								$h .= $uiProductSelling->getInfos($eProductSelling, includeStock: $isExpired === FALSE);
							$h .= '</td>';

							if($type === Date::PRO) {
								$h .= '<td class="td-min-content '.(($isExpired or $eProduct->exists()) ? '' : 'shop-product-not-exist').'">';
									if($eProduct['packaging'] !== NULL) {
										$h .= s("Colis de {value}", \selling\UnitUi::getValue($eProduct['packaging'], $eProductSelling['unit'], TRUE));
									}
								$h .= '</td>';
							}

							$h .= '<td class="text-end '.(($isExpired or $eProduct->exists()) ? '' : 'shop-product-not-exist').'" style="white-space: nowrap">';
								$price = \util\TextUi::money($eProduct['price']).\selling\UnitUi::getBy($eProductSelling['unit'], short: TRUE);
								if($canUpdate) {
									$h .= $eProduct->quick('price', $price);
								} else {
									$h .= $price;
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
							$h .= $this->getLimits($columns, $eProduct, $e['cCustomer'], excludeAt: TRUE, outCatalog: $outCatalog);
						}

					$h .= '</tbody>';

				}
;
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	public function getUpdateCatalog(\farm\Farm $eFarm, Catalog $e, \Collection $cProduct): string {

		$taxes = $eFarm->getSelling('hasVat') ? '<span class="util-annotation">'.\selling\CustomerUi::getTaxes($e['type']).'</span>' : '';
		$columns = 2;

		$h = '<div class="util-overflow-sm stick-xs">';
			$h .= '<table class="tbody-even tbody-bordered">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th colspan="2">'.s("Produit").'</th>';
						if($e['type'] === Date::PRO) {
							$columns++;
							$h .= '<td></td>';
						}
						$h .= '<th class="text-end">'.s("Prix").' '.$taxes.'</th>';
						$h .= '<th class="highlight">'.s("Disponible").'</th>';
						$h .= '<th class="text-center">';
							$h .= s("En vente");
						$h .= '</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				foreach($cProduct as $eProduct) {

					$eProductSelling = $eProduct['product'];
					$uiProductSelling = new \selling\ProductUi();

					$hasLimits = (
						$eProduct['limitCustomers'] or
						$eProduct['limitNumber'] or
						$eProduct['limitStartAt'] or
						$eProduct['limitEndAt']
					);

					$h .= '<tbody>';
						$h .= '<tr>';

							$h .= '<td class="td-min-content" '.($hasLimits ? 'rowspan="2"' : '').'>';
								if($eProductSelling['vignette'] !== NULL) {
									$h .= \selling\ProductUi::getVignette($eProductSelling, '3rem');
								} else if($eProductSelling['plant']->notEmpty()) {
									$h .= \plant\PlantUi::getVignette($eProductSelling['plant'], '3rem');
								}
							$h .= '</td>';

							$h .= '<td>';
								$h .= $uiProductSelling->getInfos($eProductSelling, includeStock: TRUE);
							$h .= '</td>';

							if($e['type'] === Date::PRO) {
								$h .= '<td class="td-min-content">';
									if($eProduct['packaging'] !== NULL) {
										$h .= s("Colis de {value}", \selling\UnitUi::getValue($eProduct['packaging'], $eProductSelling['unit'], TRUE));
									}
								$h .= '</td>';
							}

							$h .= '<td class="text-end" style="white-space: nowrap">';
								$price = \util\TextUi::money($eProduct['price']).\selling\UnitUi::getBy($eProductSelling['unit'], short: TRUE);
								$h .= $eProduct->quick('price', $price);
							$h .= '</td>';

							$h .= '<td class="shop-product-available highlight" '.($hasLimits ? 'rowspan="2"' : '').' id="product-available-'.$eProduct['id'].'">';
								$h .= $this->getStatus($eProduct, TRUE);
							$h .= '</td>';

							$h .= '<td class="text-center" '.($hasLimits ? 'rowspan="2"' : '').'>';
								$h .= $this->toggle($eProduct);
							$h .= '</td>';

							$h .= '<td class="td-min-content" '.($hasLimits ? 'rowspan="2"' : '').'>';
								$h .= '<a href="/shop/product:update?id='.$eProduct['id'].'" class="btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a> ';
								$h .= '<a data-ajax="/shop/product:doDelete" class="btn btn-outline-secondary" data-confirm="'.s("Voulez-vous vraiment supprimer ce produit de ce catalogue ?").'" post-id="'.$eProduct['id'].'">'.\Asset::icon('trash-fill').'</a>';
							$h .= '</td>';

						$h .= '</tr>';

						if($hasLimits) {
							$h .= $this->getLimits($columns, $eProduct, $e['cCustomer']);
						}

					$h .= '</tbody>';

				}
;
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	protected function getLimits(int $columns, Product $eProduct, \Collection $cCustomer, bool $excludeAt = FALSE, bool $outCatalog = FALSE): string {

		$h = '<tr>';

			$h .= '<td colspan="'.$columns.'" style="padding-top: 0; padding-bottom: 0rem">';

				$h .= '<div class="shop-product-limits">';

					if($outCatalog) {
						$h .= '<span>'.s("Hors catalogue").'</span>';
					}

					if($eProduct['limitNumber']) {

						if($eProduct['packaging'] === NULL) {
							$value = \selling\UnitUi::getValue($eProduct['limitNumber'], $eProduct['product']['unit']);
						} else {
							$value = s("{value} colis", $eProduct['limitNumber']);
						}

						$h .= '<span>'.s("Limite par commande {value}", '<u>'.$value.'</u>').'</span>';
					}

					if($eProduct['limitCustomers']) {

						$customers = [];

						foreach($eProduct['limitCustomers'] as $customer) {

							if($cCustomer->offsetExists($customer)) {
								$customers[] = '<u>'.encode($cCustomer[$customer]->getName()).'</u>';
							}

						}

						$h .= '<span>'.s("Uniquement pour {value}", implode(', ', $customers)).'</span>';
					}

					if($excludeAt === FALSE) {

						if($eProduct['limitStartAt'] !== NULL and $eProduct['limitEndAt'] !== NULL) {
							$h .= '<span>'.s("Pour les ventes livrées du {from} au {to}", [
								'from' => '<u>'.\util\DateUi::numeric($eProduct['limitStartAt']).'</u>',
								'to' => '<u>'.\util\DateUi::numeric($eProduct['limitEndAt']).'</u>',
							]).'</span>';
						} else if($eProduct['limitStartAt'] !== NULL) {
							$h .= '<span>'.s("Pour les ventes livrées à partir du {value}", '<u>'.\util\DateUi::numeric($eProduct['limitStartAt']).'</u>').'</span>';
						} else if($eProduct['limitEndAt'] !== NULL) {
							$h .= '<span>'.s("Pour les ventes livrées jusqu'au {value}", '<u>'.\util\DateUi::numeric($eProduct['limitEndAt']).'</u>').'</span>';
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

		$h = $form->openAjax('/shop/product:doCreateCollection');

			if($e instanceof Date) {
				$h .= $form->hidden('date', $e['id']);
			} else {
				$h .= $form->hidden('catalog', $e['id']);
			}

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicField($e, 'productsList');
			$h .= '<br/>';
			$h .= $form->submit(s("Ajouter"), ['class' => 'btn btn-primary']);

		$h .= $form->close();

		return new \Panel(
			title: ($e instanceof Date) ?
				s("Ajouter des produits à la vente") :
				s("Ajouter des produits au catalogue"),
			body: $h
		);
	}

	public function update(Product $e): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/shop/product:doUpdate', ['id' => 'product-update']);

			$h .= $form->hidden('id', $e['id']);

			$h .= $form->dynamicGroups($e, ['price', 'available']);

			$h .= '<br/>';
			$h .= '<div class="util-block bg-background-light">';

				$h .= $form->group(
					'<h4>'.\Asset::icon('lock-fill').' '.s("Restrictions de commande").'</h4>'
				);

				if($e['catalog']->notEmpty()) {
					$h .= $this->getLimitAtField($form, $e);
				}

				$h .= $form->dynamicGroups($e, ['limitNumber', 'limitCustomers']);

			$h .= '</div>';

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un produit"),
			subTitle: \selling\ProductUi::getPanelHeader($e['product']),
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
			s("Limiter les commandes pour les ventes livrées"),
			$h,
			['wrapper' => 'limitStartAt limitStartEnd']
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Product::model()->describer($property, [
			'product' => s("Produit"),
			'available' => s("Disponible"),
			'price' => s("Prix unitaire"),
			'date' => s("Vente"),
			'limitStartAt' => s("Proposer pour les commandes livrées à partir de"),
			'limitEndAt' => s("Proposer pour les commandes livrées jusqu'au"),
			'limitNumber' => s("Limiter les quantités disponibles par commande"),
			'limitCustomers' => s("Limiter les commandes de ce produit à certains clients"),
		]);

		switch($property) {

			case 'limitStartAt' :
				$d->prepend = s("À partir du");
				break;

			case 'limitEndAt' :
				$d->prepend = s("Jusqu'au");
				break;

			case 'limitNumber' :
				$d->append = fn(\util\FormUi $form, Product $e) => $form->addon(($e['packaging'] === NULL) ?
					\selling\UnitUi::getSingular($e['product']['unit'], short: TRUE) :
					s("colis"));
				$d->placeholder = s("Illimité");
				break;

			case 'limitCustomers' :
				$d->after = \util\FormUi::info(s("Seuls les clients que vous aurez choisis pour acheter ce produit dans vos boutiques."));
				$d->autocompleteDefault = fn(Product $e) => $e['cCustomer'] ?? $e->expects(['cCustomer']);
				$d->autocompleteBody = function(\util\FormUi $form, Product $e) {
					return [
						'farm' => $e['farm']['id']
					];
				};
				(new \selling\CustomerUi())->query($d, TRUE);
				$d->group = ['wrapper' => 'limitCustomers'];
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

			case 'price' :
				$d->append = function(\util\FormUi $form, Product $e) {

					return $form->addon(s('€ {taxes}{unit}', [
						'taxes' => $e['farm']->getSelling('hasVat') ? $e->getTaxes() : '',
						'unit' => \selling\UnitUi::getBy($e['product']['unit'], short: TRUE)
					]));

				};
				break;


		}

		return $d;

	}

}
