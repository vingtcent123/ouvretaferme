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

						$h .= '<span style="white-space: nowrap">'.\util\TextUi::money($eProduct['price']).' '.$this->getTaxes($eProduct).' / '.\main\UnitUi::getSingular($eProductSelling['unit'], by: TRUE).'</span>';

						$h .= '<div class="shop-product-buy-infos">';

							if($eProduct['packaging'] !== NULL) {
								$h.= '<div class="shop-product-buy-info">';
									$h .= s("Colis de {value}", \main\UnitUi::getValue($eProduct['packaging'], $eProductSelling['unit'], TRUE));
								$h .= '</div>';
							}

							if($eProduct['reallyAvailable'] !== NULL) {
								$h.= '<div class="shop-product-buy-info">';
								if($eProduct['reallyAvailable'] > 0) {
									$h .= s("Stock : {value}", $eProduct['reallyAvailable']);
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
									$h .= \main\UnitUi::getValue($step, $eProduct['unit']);
									break;

								case Date::PRO :
									if($eProduct['proPackaging'] !== NULL) {
										$h .= s("Colis de {value}", \main\UnitUi::getValue($eProduct['proPackaging'], $eProduct['unit'], TRUE));
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
									$h .= '<span title="'.\selling\StockUi::getDate($eProduct['stockUpdatedAt']).'">'.\main\UnitUi::getValue($eProduct['stock'], $eProduct['unit'], short: TRUE).'</span>';
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
			return $eProduct->getTaxes();
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
					$h .= \main\UnitUi::getSingular($eProductSelling['unit'], short: TRUE);
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

		return match($eProduct['unit']) {

			\selling\Product::GRAM => 100,
			\selling\Product::KG => 0.5,
			default => 1,

		};

	}

	public static function getDefaultProStep(\selling\Product $eProduct): float {

		return 1;

	}

	// Modifier (quick) les disponibilités
	public function getUpdateList(Date $eDate, \Collection $cProduct, \Collection $cCategory): string {

		if($cProduct->empty()) {
			return '<div class="util-info">'.s("Vous ne vendez encore aucun produit à cette date !").'</div>';
		}

		$ccProduct = $cProduct->reindex(['product', 'category']);

		if($ccProduct->count() === 1) {
			return $this->getUpdateProducts($eDate, $ccProduct->first());
		} else {

			$h = '';

			if($ccProduct->offsetExists('')) {
				$h .= $this->getUpdateProducts($eDate, $ccProduct['']);
			}

			foreach($cCategory as $eCategory) {

				if($ccProduct->offsetExists($eCategory['id']) === FALSE) {
					continue;
				}

				$h .= '<h3>'.encode($eCategory['name']).'</h3>';
				$h .= $this->getUpdateProducts($eDate, $ccProduct[$eCategory['id']]);

			}

			return $h;

		}

	}

	public function getUpdateProducts(Date $eDate, \Collection $cProduct): string {

		$taxes = $eDate['farm']->getSelling('hasVat') ? '<span class="util-annotation">'.$eDate->getTaxes().'</span>' : '';

		$h = '<div class="'.($eDate['type'] === Date::PRIVATE ? 'util-overflow-xs' : 'util-overflow-sm').' stick-xs">';
			$h .= '<table class="tr-even">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th colspan="2">'.s("Produit").'</th>';
						if($eDate['type'] === Date::PRO) {
							$h .= '<td></td>';
						}
						$h .= '<th class="text-end highlight">'.s("Prix").' '.$taxes.'</th>';
						$h .= '<th class="text-end">'.s("Disponible").'</th>';
						$h .= '<th class="text-end highlight">'.s("Vendu").'</th>';
						$h .= '<th class="text-end">';
							$h .= '<span class="hide-md-down">'.s("Vente en cours").'</span>';
							$h .= '<span class="hide-lg-up">'.s("Vente").'</span>';
						$h .= '</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</theaf>';
				$h .= '<tbody>';

					foreach($cProduct as $eProduct) {
						$h .= $this->getUpdateProduct($eDate, $eProduct);
					}

				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	public function getUpdateProduct(Date $eDate, Product $eProduct): string {

		$eProductSelling = $eProduct['product'];
		$uiProductSelling = new \selling\ProductUi();

		$h = '<tr>';

			$h .= '<td class="td-min-content">';
				if($eProductSelling['vignette'] !== NULL) {
					$h .= \selling\ProductUi::getVignette($eProductSelling, '3rem');
				} else if($eProductSelling['plant']->notEmpty()) {
					$h .= \plant\PlantUi::getVignette($eProductSelling['plant'], '3rem');
				}
			$h .= '</td>';

			$h .= '<td>';
				$h .= $uiProductSelling->getInfos($eProductSelling, includeStock: TRUE);
			$h .= '</td>';

			if($eDate['type'] === Date::PRO) {
				$h .= '<td class="td-min-content">';
					if($eProduct['packaging'] !== NULL) {
						$h .= s("Colis de {value}", \main\UnitUi::getValue($eProduct['packaging'], $eProductSelling['unit'], TRUE));
					}
				$h .= '</td>';
			}

			$h .= '<td class="text-end highlight" style="white-space: nowrap">';
				$h .= $eProduct->quick('price', \util\TextUi::money($eProduct['price']).' / '.\main\UnitUi::getSingular($eProductSelling['unit'], short: TRUE, by: TRUE));
			$h .= '</td>';
			$h .= '<td class="text-end">';
				if($eProduct['available'] === NULL) {
					$available = s("illimité");
				} else {
					$available = $eProduct['available'];
				}
				$h .= $eProduct->quick('available', $available);
			$h .= '</td>';
			$h .= '<td class="text-end highlight">';
				$h .= $eProduct['sold'] ?? 0;
			$h .= '</td>';
			$h .= '<td class="text-end">';
				$h .= $this->toggle($eProduct);
			$h .= '</td>';
			$h .= '<td class="td-min-content">';

				if($eProduct['sold'] === 0.0) {
					$h .= '<a data-ajax="/shop/product:doDelete" class="btn btn-danger" data-confirm="'.s("Voulez-vous vraiment supprimer ce produit de cette vente ?").'" post-id="'.$eProduct['id'].'">'.\Asset::icon('trash-fill').'</a>';
				} else {
					$h .= '<a class="btn btn-secondary btn-disabled" title="'.s("Vous ne pouvez pas supprimer ce produit car des ventes ont déjà été réalisées.").'">'.\Asset::icon('trash-fill').'</a>';

				}

			$h .= '</div>';
			$h .= '</td>';

		$h .= '</tr>';

		return $h;
	}

	public function create(\farm\Farm $eFarm, Date|Catalog $e): \Panel {

		$e->expects(['cProduct', 'cCategory']);

		$form = new \util\FormUi([
			'columnBreak' => 'sm'
		]);

		$h = $form->openAjax('/shop/product:doCreate');

			if($e instanceof Date) {
				$h .= $form->hidden('date', $e['id']);
			} else {
				$h .= $form->hidden('date', $e['id']);
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

	public static function p(string $property): \PropertyDescriber {

		$d = Product::model()->describer($property, [
			'product' => s("Produit"),
			'available' => s("Disponible"),
			'price' => s("Prix unitaire"),
			'date' => s("Vente"),
		]);

		switch($property) {

			case 'available' :
				$d->field = function(\util\FormUi $form, Product $e) use($d) {

					$e->expects([
						'packaging',
					]);

					$step = (
						$e['type'] === Product::PRO or
						in_array($e['product']['unit'], [\selling\Product::UNIT, \selling\Product::BUNCH])
					) ? 1 : 0.1;

					$h = '<div class="input-group" data-product="'.$e['product']['id'].'">';
						$h .= $form->number($d->name, $e['available'] ?? NULL, [
							'data-product' => $e['product']['id'],
							'onfocusin' => 'DateManage.checkAvailableFocusIn(this)',
							'onfocusout' => 'DateManage.checkAvailableFocusOut(this)',
							'placeholder' => s("Illimité"),
							'data-placeholder' => s("Illimité"),
							'min' => $step,
							'step' => $step,
						]);

						if(
							$e['type'] === Product::PRIVATE or
							$e['packaging'] === NULL
						) {
							$unit = \main\UnitUi::getNeutral($e['product']['unit'], TRUE);
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

					return $form->addon(s('€ {taxes} / {unit}', [
						'taxes' => $e['farm']->getSelling('hasVat') ? $e->getTaxes() : '',
						'unit' => \main\UnitUi::getSingular($e['product']['unit'], short: TRUE)
					]));

				};
				break;


		}

		return $d;

	}

}
