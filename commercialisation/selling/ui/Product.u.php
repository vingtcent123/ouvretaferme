<?php
namespace selling;

class ProductUi {

	public function __construct() {

		\Asset::css('selling', 'product.css');
		\Asset::js('selling', 'product.js');

	}

	public static function link(Product $eProduct, bool $newTab = FALSE): string {
		return '<a href="'.self::url($eProduct).'" '.($newTab ? 'target="_blank"' : '').'>'.encode($eProduct->getName()).'</a>';
	}

	public static function url(Product $eProduct): string {

		$eProduct->expects(['id']);

		return '/produit/'.$eProduct['id'];

	}

	public function query(\PropertyDescriber $d, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('box');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Tapez un nom de produit");
		$d->multiple = $multiple;
		$d->group += ['wrapper' => 'product'];

		$d->autocompleteUrl = '/selling/product:query';
		$d->autocompleteResults = function(Product $e) {
			return self::getAutocomplete($e);
		};

	}

	public static function getAutocomplete(Product $eProduct): array {

		\Asset::css('media', 'media.css');

		$details = self::getDetails($eProduct);

		$item = '<div class="flex-align-center">';
			$item .= self::getVignette($eProduct, '2.5rem');
			$item .= '<div>';
				$item .= $eProduct->getName('html');
				$item .= \selling\UnitUi::getBy($eProduct['unit']);
				$item .= '<br/>';
				if($details) {
					$item .= '<small class="color-muted">'.implode(' | ', $details).'</small>';
				}
			$item .= '</div>';
		$item .= '</div>';

		return [
			'value' => $eProduct['id'],
			'itemHtml' => $item,
			'itemText' => $eProduct->getName()
		];

	}

	public static function getPanelHeader(Product $eProduct): string {

		return '<div class="panel-header-subtitle">'.self::getVignette($eProduct, '2rem').'  '.$eProduct->getName('html').'</div>';

	}

	public static function getProfileIcon(?string $profile, string $size = '2.5rem'): string {

		return '<div class="product-profile-icon" style="background-color: var(--'.$profile.'); width: '.$size.'; height: '.$size.'; font-size: calc('.$size.' / 2)">'.self::p('profile')->icons[$profile].'</div>';

	}

	public static function getProfileDropdown(Product $eProduct, ?\Closure $destination = NULL): string {

		$eProduct->expects(['farm']);

		$destination ??= fn($profile) => 'href="/selling/product:create?farm='.$eProduct['farm']['id'].'&profile='.$profile.'"';

		$h = '<div class="dropdown-list">';

			foreach(\selling\ProductUi::p('profile')->values as $profile => $value) {

				if($profile === Product::GROUP) {
					continue;
				}

				if($profile === Product::COMPOSITION and $eProduct->exists()) {
					continue;
				}

				$examples = \selling\ProductUi::p('profile')->examples[$profile];

				$h .= '<a '.$destination($profile).' class="dropdown-item dropdown-item-icon" data-profile="'.$profile.'">';
					$h .= self::getProfileIcon($profile);
					$h .= '<div>';
						$h .= '<span class="product-profile-name">'.$value.'</span>';
						if($examples) {
							$h .= '<br/><small style="color: #fff8">'.$examples.'</small>';
						}
					$h .= '</div>';
				$h .= '</a>';

			}

			if(
				FEATURE_GROUP and
				$eProduct->exists() === FALSE
			) {

				$h .= '<div class="dropdown-divider"></div>';

				$h .= '<a href="/selling/relation:createCollection?farm='.$eProduct['farm']['id'].'" class="dropdown-item dropdown-item-icon" data-profile="'.Product::GROUP.'">';
					$h .= self::getProfileIcon(Product::GROUP);
					$h .= '<div>';
						$h .= '<span class="product-profile-name">'.s("Créer un groupe de produits").'</span>';
					$h .= '</div>';
				$h .= '</a>';

			}

		$h .= '</div>';

		return $h;

	}

	public function getSearch(\farm\Farm $eFarm, \Search $search): string {

		$form = new \util\FormUi();

		$h = '<div id="product-search" class="util-block-search '.($search->empty(['category']) ? 'hide' : '').'">';

			$h .= $form->openAjax(\farm\FarmUi::urlSellingProducts($eFarm), ['method' => 'get', 'id' => 'form-search']);
				$h .= $form->hidden('category', $search->get('category'));
				$h .= '<div>';
					$h .= $form->select('profile', self::p('profile')->values, $search->get('profile'), ['placeholder' => s("Type")]);
					$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom du produit")]);
					$h .= $form->text('plant', $search->get('plant'), ['placeholder' => s("Espèce")]);
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.\farm\FarmUi::urlSellingProducts($eFarm).'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getList(\farm\Farm $eFarm, \Collection $cProduct, array $products, \Collection $cCategory, \Search $search) {

		$h = $this->getCategories($cCategory, $products, $search);

		if($cProduct->empty()) {

			if(
				$search->get('category')->empty() and
				$cCategory->notEmpty()
			) {
				$h .= '<div class="util-info">'.s("Sélectionnez une catégorie pour voir les produits associés !").'</div>';
			} else {
				$h .= '<div class="util-empty">'.s("Il n'y a aucun produit à afficher.").'</div>';
			}

			return $h;

		}

		$year = date('Y');
		$yearBefore = $year - 1;

		$displayStock = $cProduct->match(fn($eProduct) => $eProduct['stock'] !== NULL);

		$h .= '<div class="product-item-wrapper stick-md">';

		$h .= '<table class="product-item-table tbody-even">';

			$h .= '<thead>';

				$h .= '<tr>';

					$h .= '<th rowspan="2" class="td-checkbox">';
						$h .= '<label title="'.s("Tout cocher / Tout décocher").'">';
							$h .= '<input type="checkbox" class="batch-all" onclick="Product.toggleSelection(this)"/>';
						$h .= '</label>';
					$h .= '</th>';

					$h .= '<th rowspan="2" class="product-item-vignette"></th>';
					$h .= '<th rowspan="2">'.$search->linkSort('name', s("Nom")).'</th>';
					if($displayStock) {
						$h .= '<th rowspan="2" class="text-end hide-xl-down">'.$search->linkSort('stock', s("Stock"), SORT_DESC).'</th>';
					}
					$h .= '<th rowspan="2">'.s("Unité").'</th>';
					$h .= '<th colspan="2" class="text-center highlight hide-md-down">'.s("Ventes").'</th>';
					$h .= '<th colspan="2" class="text-center highlight">'.s("Prix de base").'</th>';
					if($eFarm->getSelling('hasVat')) {
						$h .= '<th rowspan="2" class="text-center product-item-vat">'.s("TVA").'</th>';
					}
					$h .= '<th rowspan="2" class="text-center">'.s("Activé").'</th>';
					$h .= '<th rowspan="2"></th>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<th class="text-end highlight-stick-right hide-md-down">'.$year.'</th>';
					$h .= '<th class="text-end highlight-stick-left product-item-year-before hide-md-down">'.$yearBefore.'</th>';
					$h .= '<th class="text-end highlight-stick-right">'.s("particulier").'</th>';
					$h .= '<th class="text-end highlight-stick-left">'.s("pro").'</th>';
				$h .= '</tr>';

			$h .= '</thead>';

			foreach($cProduct as $eProduct) {

				$eItemTotal = $eProduct['eItemTotal'];

				$h .= '<tbody>';

					$h .= '<tr class="'.($eProduct['status'] === Product::INACTIVE ? 'tr-disabled' : '').'">';

						$h .= '<td class="td-checkbox">';
							$h .= '<label>';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eProduct['id'].'" oninput="Product.changeSelection()"/>';
							$h .= '</label>';
						$h .= '</td>';

						$h .= '<td class="product-item-vignette">';
							$h .= new \media\ProductVignetteUi()->getCamera($eProduct, size: '4rem');
						$h .= '</td>';

						$h .= '<td class="product-item-name">';
							$h .= self::getInfos($eProduct);
						$h .= '</td>';

						if($displayStock) {
							$h .= '<td class="product-item-stock text-end hide-xl-down">';
								if($eProduct['stock'] !== NULL) {
									$h .= StockUi::getExpired($eProduct);
									$h .= '<a href="'.\farm\FarmUi::urlSellingStock($eFarm).'" title="'.StockUi::getDate($eProduct['stockUpdatedAt']).'">'.$eProduct['stock'].'</a>';
								}
							$h .= '</td>';
						}

						$h .= '<td class="product-item-unit">';
							if($eProduct->acceptPrice()) {
								$h .= \selling\UnitUi::getSingular($eProduct['unit']);
							}
						$h .= '</td>';

						$h .= '<td class="text-end highlight-stick-right hide-md-down">';
							if($eProduct->acceptPrice()) {
								if($eItemTotal->notEmpty() and $eItemTotal['year']) {
									$amount = \util\TextUi::money($eItemTotal['year'], precision: 0);
									$h .= $eFarm->canAnalyze() ? '<a href="/selling/product:analyze?id='.$eProduct['id'].'&year='.$year.'">'.$amount.'</a>' : $amount;
								} else {
									$h .= '-';
								}
							}
						$h .= '</td>';

						$h .= '<td class="text-end highlight-stick-left hide-md-down customer-item-year-before">';
							if($eProduct->acceptPrice()) {
								if($eItemTotal->notEmpty() and $eItemTotal['yearBefore']) {
									$amount = \util\TextUi::money($eItemTotal['yearBefore'], precision: 0);
									$h .= $eFarm->canAnalyze() ? '<a href="/selling/product:analyze?id='.$eProduct['id'].'&year='.$yearBefore.'">'.$amount.'</a>' : $amount;
								} else {
									$h .= '-';
								}
							}
						$h .= '</td>';

						$h .= '<td class="product-item-price highlight-stick-right text-end">';

							if($eProduct->acceptPrice()) {

								if($eProduct['private'] === FALSE) {
									$h .= \Asset::icon('x');
								} else {

									$taxes = $eFarm->getSelling('hasVat') ? ' <span class="util-annotation">'.CustomerUi::getTaxes(Customer::PRIVATE).'</span>' : '';

									if($eProduct['privatePrice'] !== NULL) {
										$value = \util\TextUi::money($eProduct['privatePrice']).$taxes;
										if($eProduct['privatePriceInitial'] !== NULL) {
											$h .= new PriceUi()->priceWithoutDiscount($eProduct['privatePriceInitial'], unit: $taxes);
										}
									} else if($eProduct['proPrice'] !== NULL) {
										$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les professionnels augmenté de la TVA.").'">'.\Asset::icon('magic').' ';
											$value .= \util\TextUi::money($eProduct->calcPrivateMagicPrice($eFarm->getSelling('hasVat'))).$taxes;
										$value .= '</span>';
									} else {
										$value = \Asset::icon('question');
									}

									$h .= $eProduct->quick('privatePrice', $value);

								}

							}

						$h .= '</td>';

						$h .= '<td class="product-item-price highlight-stick-left text-end">';
							if($eProduct->acceptPrice()) {

								if($eProduct['pro'] === FALSE) {
									$h .= \Asset::icon('x');
								} else {

									$taxes = $eFarm->getSelling('hasVat') ? ' <span class="util-annotation">'.CustomerUi::getTaxes(Customer::PRO).'</span>' : '';

									if($eProduct['proPrice']) {
										$value = \util\TextUi::money($eProduct['proPrice']).$taxes;
										if($eProduct['proPriceInitial'] !== NULL) {
											$h .= new PriceUi()->priceWithoutDiscount($eProduct['proPriceInitial'], unit: $taxes);
										}
									} else if($eProduct['privatePrice']) {
										$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les particuliers diminué de la TVA.").'">'.\Asset::icon('magic').' ';
											$value .= \util\TextUi::money($eProduct->calcProMagicPrice($eFarm->getSelling('hasVat'))).$taxes;
										$value .= '</span>';
									} else {
										$value = \Asset::icon('question');
									}

									$h .= $eProduct->quick('proPrice', $value);

								}

							}
						$h .= '</td>';

						if($eFarm->getSelling('hasVat')) {

							$h .= '<td class="text-center product-item-vat">';
								if($eProduct->acceptPrice()) {
									$h .= s("{value} %", SellingSetting::VAT_RATES[$eProduct['vat']]);
								}
							$h .= '</td>';

						}

						$h .= '<td class="product-item-status td-min-content">';
							$h .= $this->toggle($eProduct);
						$h .= '</td>';

						$h .= '<td class="product-item-actions">';
							$h .= $this->getUpdate($eProduct, 'btn-outline-secondary');
						$h .= '</td>';

					$h .= '</tr>';

				$h .= '</tbody>';

			}

		$h .= '</table>';

		$h .= '</div>';

		$h .= $this->getBatch($eFarm, $cCategory);

		return $h;

	}

	protected function getCategories(\Collection $cCategory, array $products, \Search $search): string {

		$h = '';

		if($cCategory->notEmpty()) {

			$eCategorySelected = $search->get('category');

			$h .= '<div class="tabs-item">';

				foreach($cCategory as $eCategory) {

					$url = \util\HttpUi::setArgument(LIME_REQUEST, 'category', $eCategory['id'], FALSE);

					$h .= '<a href="'.$url.'" class="tab-item '.(($eCategorySelected->notEmpty() and $eCategorySelected['id'] === $eCategory['id']) ? 'selected' : '').'">'.encode($eCategory['name']).' <small class="tab-item-count">'.($products[$eCategory['id']] ?? 0).'</small></a>';

				}

				$uncategorized = ($products[NULL] ?? 0);

				if($uncategorized > 0) {

					$url = \util\HttpUi::setArgument(LIME_REQUEST, 'category', '', FALSE);

					$h .= '<a href="'.$url.'" class="tab-item '.($eCategorySelected->empty() ? 'selected' : '').'">'.s("Non catégorisé").' <small class="tab-item-count">'.$uncategorized.'</small></a>';

				}

			$h .= '</div>';

		}

		return $h;

	}

	public function getBatch(\farm\Farm $eFarm, \Collection $cCategory): string {

		$menu = '';

		if($cCategory->count() > 0) {

			$menu .= '<a data-dropdown="top-start" class="batch-menu-category batch-menu-item">';
				$menu .= \Asset::icon('tag');
				$menu .= '<span>'.s("Catégorie").'</span>';
			$menu .= '</a>';

			$menu .= '<div class="dropdown-list bg-secondary">';
				$menu .= '<div class="dropdown-title">'.s("Changer de catégorie").'</div>';
				foreach($cCategory as $eCategory) {
					$menu .= '<a data-ajax-submit="/selling/product:doUpdateCategoryCollection" data-ajax-target="#batch-group-form" post-category="'.$eCategory['id'].'" class="dropdown-item">'.encode($eCategory['name']).'</a>';
				}
				$menu .= '<a data-ajax-submit="/selling/product:doUpdateCategoryCollection" data-ajax-target="#batch-group-form" post-category="" class="dropdown-item"><i>'.s("Non catégorisé").'</i></a>';
			$menu .= '</div>';

		}

		$menu .= '<a data-ajax-submit="/selling/product:doUpdateStatusCollection" post-status="'.Product::ACTIVE.'" data-confirm="'.s("Activer ces produits ?").'" class="batch-menu-active batch-menu-item">';
			$menu .= \Asset::icon('toggle-on');
			$menu .= '<span>'.s("Activer").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/product:doUpdateStatusCollection" post-status="'.Product::INACTIVE.'" data-confirm="'.s("Désactiver ces produits ?").'" class="batch-menu-inactive batch-menu-item">';
			$menu .= \Asset::icon('toggle-off');
			$menu .= '<span>'.s("Désactiver").'</span>';
		$menu .= '</a>';

		if(FEATURE_GROUP) {

			$menu .= '<a data-url="/selling/relation:createCollection?farm='.$eFarm['id'].'" class="batch-menu-relation batch-menu-item">';
				$menu .= \Asset::icon('plus-circle');
				$menu .= '<span>'.s("Créer une groupe").'</span>';
			$menu .= '</a>';

		}

		return \util\BatchUi::group($menu, title: s("Pour les produits sélectionnés"));

	}

	public static function getInfos(Product $eProduct, bool $includeStock = FALSE, bool $includeQuality = TRUE, bool $includeUnit = FALSE, bool $link = TRUE): string {

		\Asset::css('selling', 'product.css');

		$h = '<div class="product-item-label">';

			$h .= '<div>';

				if($link and $eProduct->canWrite()) {
					$h .= '<a href="/produit/'.$eProduct['id'].'" class="product-item-label-name">'.$eProduct->getName('html').'</a>';
				} else {
					$h .= '<span class="product-item-label-name">'.$eProduct->getName('html').'</span>';
				}

				if($includeUnit) {

					if($eProduct['unit']->notEmpty()) {
						$h .= '<span class="product-item-label-unit">'.UnitUi::getSingular($eProduct['unit']).'</span>';
					}

				}

			$h .= '</div>';

			if($includeQuality) {

				if($eProduct['quality'] !== NULL) {
					$h .= \farm\FarmUi::getQualityLogo($eProduct['quality'], '1.5rem');
				}

			}

		$h .= '</div>';

		$more = self::getDetails($eProduct);

		if($includeStock) {

			if($eProduct['stock'] !== NULL) {
				$more[] .= '<span title="'.\selling\StockUi::getDate($eProduct['stockUpdatedAt']).'">'.s("{value} en stock", \selling\StockUi::getExpired($eProduct).'<u>'.\selling\UnitUi::getValue(round($eProduct['stock']), $eProduct['unit'], short: TRUE)).'</u></span>';
			}

		}

		if($more) {
			$h .= '<div class="product-item-infos">'.implode(' | ', $more).'</div>';
		}

		return $h;


	}

	public static function getDetails(Product $eProduct): array {

		$more = [];

		if($eProduct['additional']) {
			$more[] = '<span><u>'.encode($eProduct['additional']).'</u></span>';
		}

		if($eProduct['origin']) {
			$more[] = '<span>'.s("Origine {value}", '<u>'.encode($eProduct['origin']).'</u>').'</span>';
		}

		return $more;

	}

	public static function getVignette(Product $eProduct, string $size, bool $public = FALSE, bool $withComplement = FALSE): string {

		$eProduct->expects(['id', 'vignette', 'profile']);

		$ui = new \media\ProductVignetteUi();

		$class = 'media-circle-view ';
		$style = '';

		if($eProduct['vignette'] === NULL) {

			$class .= ' media-vignette-default';
			$content = \Asset::icon('box');

		} else {

			$format = $ui->convertToFormat($size);

			$style .= 'background-image: url('.$ui->getUrlByElement($eProduct, $format).');';
			$content = '';

		}

		if($public === FALSE and $withComplement) {

			$content .= self::getVignetteComplement($eProduct);
		}

		return '<div class="'.$class.'" style="'.$ui->getSquareCss($size).'; '.$style.'">'.$content.'</div>';

	}

	public static function getVignetteComplement(Product $eProduct): string {

		if(
			$eProduct['unprocessedPlant']->notEmpty() and
			($eProduct['unprocessedPlant']['fqn'] !== NULL or $eProduct['unprocessedPlant']['vignette'] !== NULL)
		) {
			return self::getVignettePlant($eProduct);
		} else {
			return self::getVignetteProfile($eProduct);
		}

	}

	public static function getVignettePlant(Product $eProduct): string {

		return '<div class="product-vignette-plant" style="border-color: var(--unprocessed-plant);">'.\plant\PlantUi::getVignette($eProduct['unprocessedPlant'], '1.25rem').'</div>';

	}

	public static function getVignetteProfile(Product $eProduct): string {

		\Asset::css('selling', 'product.css');

		return '<div class="product-vignette-profile" style="border-coloir: var(--'.$eProduct['profile'].'); color: var(--'.$eProduct['profile'].');" title="'.self::p('profile')->values[$eProduct['profile']].'">'.self::p('profile')->icons[$eProduct['profile']].'</div>';

	}

	public function toggle(Product $eProduct) {

		return \util\TextUi::switch([
			'id' => 'product-switch-'.$eProduct['id'],
			'data-ajax' => $eProduct->canWrite() ? '/selling/product:doUpdateStatus' : NULL,
			'post-id' => $eProduct['id'],
			'post-status' => ($eProduct['status'] === Product::ACTIVE) ? Product::INACTIVE : Product::ACTIVE
		], $eProduct['status'] === Product::ACTIVE);

	}

	public function displayTitle(Product $eProduct, bool $switchComposition): string {

		$h = '<div class="util-action">';

			$h .= '<div class="util-vignette">';
				$h .= new \media\ProductVignetteUi()->getCamera($eProduct, size: '5rem');
				$h .= '<div>';
					$h .= '<h1 style="margin-bottom: 0.25rem">';
						$h .= $eProduct->getName('html');
					$h .= '</h1>';
					if($eProduct['status'] !== \selling\Product::DELETED) {
						$h .= $this->toggle($eProduct);
					}
				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div>';
				if($switchComposition) {
					$h .= SaleUi::getCompositionSwitch($eProduct['farm'], 'btn-primary').' ';
				}
				$h .= $this->getUpdate($eProduct, 'btn-primary');
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function display(Product $eProduct): string {

		if($eProduct['profile'] === Product::GROUP) {

			$h = '<div class="util-info">';
				$h .= match($eProduct['groupSelection']) {
					Product::UNIQUE => s("Les clients ne peuvent choisir qu'un seul produit dans la liste lors d'une commande."),
					Product::MULTIPLE => s("Les clients peuvent choisir plusieurs produits dans la liste lors d'une commande."),
				};
			$h .= '</div>';

			return $h;

		}

		$h = '<div class="util-block stick-xs">';
			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>'.self::p('unit')->label.'</dt>';
				$h .= '<dd>'.($eProduct['unit']->notEmpty() ? encode($eProduct['unit']['singular']) : '').'</dd>';
				if($eProduct['origin'] !== NULL) {
					$h .= '<dt>'.self::p('origin')->label.'</dt>';
					$h .= '<dd>'.($eProduct['origin'] ? encode($eProduct['origin']) : '').'</dd>';
				}
				$h .= '<dt>'.self::p('quality')->label.'</dt>';
				$h .= '<dd>'.($eProduct['quality'] ? \farm\FarmUi::getQualityLogo($eProduct['quality'], '1.5rem').' '.self::p('quality')->values[$eProduct['quality']] : '').'</dd>';
				if($eProduct['category']->notEmpty()) {
					$h .= '<dt>'.self::p('category')->label.'</dt>';
					$h .= '<dd>'.encode($eProduct['category']['name']).'</dd>';
				}
				if($eProduct['farm']->getSelling('hasVat')) {
					$h .= '<dt>'.self::p('vat')->label.'</dt>';
					$h .= '<dd>'.s("{value} %", SellingSetting::VAT_RATES[$eProduct['vat']]).'</dd>';
				}

				if($eProduct['profile'] === Product::COMPOSITION) {
					$h .= '<dt>'.s("Composition").'</dt>';
					$h .= '<dd>'.($eProduct['compositionVisibility'] === Product::PRIVATE ? s("surprise") : s("visible")).'</dd>';
				}
				if($eProduct['additional'] !== NULL) {
					$h .= '<dt>'.s("Complément d'information").'</dt>';
					$h .= '<dd>'.encode($eProduct['additional']).'</dd>';
				}
				if($eProduct['unprocessedPlant']->notEmpty()) {
					$h .= '<dt>'.self::p('unprocessedPlant')->label.'</dt>';
					$h .= '<dd>'.\plant\PlantUi::link($eProduct['unprocessedPlant']).'</dd>';
				}
				if($eProduct['processedComposition'] !== NULL) {
					$h .= '<dt>'.self::p('processedComposition')->label.'</dt>';
					$h .= '<dd>'.nl2br(encode($eProduct['processedComposition'])).'</dd>';
				}
				if($eProduct['processedPackaging'] !== NULL) {
					$h .= '<dt>'.self::p('processedPackaging')->label.'</dt>';
					$h .= '<dd>'.encode($eProduct['processedPackaging']).'</dd>';
				}
				if($eProduct['processedAllergen'] !== NULL) {
					$h .= '<dt>'.self::p('processedAllergen')->label.'</dt>';
					$h .= '<dd>'.nl2br(encode($eProduct['processedAllergen'])).'</dd>';
				}
			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	public function getAnalyze(Product $eProduct, \Collection $cItemYear): string {

		if(
			$eProduct['farm']->canAnalyze() and
			$cItemYear->notEmpty()
		) {

			return new AnalyzeUi()->getProductYear($cItemYear, NULL, $eProduct);

		} else {
			return '';
		}

	}

	public function getTabs(Product $eProduct, \Collection $cSaleComposition, \Collection $cGrid, \Collection $cItemLast): string {

		if($eProduct['profile'] === Product::GROUP) {
			return $this->getGroup($eProduct);
		}

		$h = '<div class="tabs-h" id="product-tabs" onrender="'.encode('Lime.Tab.restore(this, "product-grid")').'">';

			$h .= '<div class="tabs-item">';
				if($eProduct['profile'] === Product::COMPOSITION) {
					$h .= '<a class="tab-item '.($eProduct['profile'] === Product::COMPOSITION ? 'selected' : '').'" data-tab="product-composition" onclick="Lime.Tab.select(this)">'.s("Composition").'</a>';
				}
				$h .= '<a class="tab-item '.($eProduct['profile'] === Product::COMPOSITION ? '' : 'selected').'" data-tab="product-grid" onclick="Lime.Tab.select(this)">'.s("Prix").'</a>';
				$h .= '<a class="tab-item" data-tab="product-sales" onclick="Lime.Tab.select(this)">'.s("Dernières ventes").'</a>';
			$h .= '</div>';

			if($eProduct['profile'] === Product::COMPOSITION) {
				$h .= '<div class="tab-panel '.($eProduct['profile'] === Product::COMPOSITION ? 'selected' : '').'" data-tab="product-composition">';
					if($cSaleComposition->empty()) {
						$h .= $this->getEmptyComposition($eProduct);
					} else {
						$h .= $this->getComposition($eProduct, $cSaleComposition);
					}
				$h .= '</div>';
			}

			$h .= '<div class="tab-panel '.($eProduct['profile'] === Product::COMPOSITION ? '' : 'selected').'" data-tab="product-grid">';
				$h .= $this->getBaseGrid($eProduct);
				$h .= new \selling\GridUi()->getGridByProduct($eProduct, $cGrid);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="product-sales">';
				$h .= new \selling\ItemUi()->getByProduct($cItemLast);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getGroup(Product $eProduct): string {

		$h = '<div class="util-title mt-2">';
			$h .= '<h3>'.s("Liste des produits").'</h3>';
		$h .= '</div>';

		$h .= new \selling\RelationUi()->displayByParent($eProduct, $eProduct['cRelation']);

		return $h;

	}

	public function getEmptyComposition(Product $eProduct): string {

		$h = '<div class="util-block-help">';
			$h .= '<h4>'.s("Composez votre produit").'</h4>';
			$h .= '<p>'.s("Vous n'avez pas encore indiqué la composition de votre produit {value}.", '<u>'.encode($eProduct['name']).'</u>').'</p>';
			$h .= '<a href="/selling/sale:create?farm='.$eProduct['farm']['id'].'&compositionOf='.$eProduct['id'].'" class="btn btn-secondary">'.s("Ajouter la composition du moment").'</a>';
		$h .= '</div>';


		return $h;

	}

	public function getComposition(Product $eProduct, \Collection $cSale): string {

		$h = '<div class="util-title">';
			$h .= '<div></div>';
			$h .= '<div>';
				$h .= '<a href="/selling/sale:create?farm='.$eProduct['farm']['id'].'&compositionOf='.$eProduct['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouvelle composition").'</a>';
			$h .= '</div>';
		$h .= '</div>';

		foreach($cSale as $eSale) {
			$h .= new \selling\ItemUi()->getBySale($eSale, $eSale['cItem']);
		}

		return $h;

	}

	public function getBaseGrid(Product $eProduct): string {

		$h = '<div class="product-item-presentation-wrapper">';

			$h .= '<div class="product-item-presentation">';

				$h .= '<h3>'.s("Vente aux particuliers").'</h3>';

				$h .= '<dl class="util-presentation util-presentation-1">';

				if($eProduct['private']) {

					$taxes = $eProduct['farm']->getSelling('hasVat') ? CustomerUi::getTaxes(Customer::PRIVATE) : '';

					$h .= '<dt>'.s("Prix de base").'</dt>';
					$h .= '<dd>';
						if($eProduct['privatePrice']) {
							if($eProduct['privatePriceInitial']) {
								$value = \util\TextUi::money($eProduct['privatePriceInitial']);
							} else {
								$value = \util\TextUi::money($eProduct['privatePrice']);
							}
							$value .= ' '.$taxes.\selling\UnitUi::getBy($eProduct['unit']);
						} else if($eProduct['proPrice']) {
							$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les professionnels augmenté de la TVA, cliquez pour le personnaliser.").'">'.\Asset::icon('magic').' ';
								$value .= \util\TextUi::money($eProduct->calcPrivateMagicPrice($eProduct['farm']->getSelling('hasVat')));
								$value .= ' '.$taxes.\selling\UnitUi::getBy($eProduct['unit']);
							$value .= '</span>';
						} else {
							$value = '/';
						}
						if($eProduct['privatePrice'] and $eProduct['privatePriceInitial']) {
							$h .= new PriceUi()->priceWithoutDiscount($eProduct->quick('privatePrice', $value), isSmall: FALSE);
						} else {
							$h .= $eProduct->quick('privatePrice', $value);
						}
					$h .= '</dd>';

					if($eProduct['privatePriceInitial']) {

						$h .= '<dt>'.s("Prix remisé").'</dt>';

						$h .= '<dd>';
							$value = \util\TextUi::money($eProduct['privatePrice']);
							$value .= ' '.$taxes.\selling\UnitUi::getBy($eProduct['unit']);
							$h .= $eProduct->quick('privatePrice', $value);
						$h .= '</dd>';

					}

					$h .= '<dt>'.self::p('privateStep')->label.'</dt>';
					$h .= '<dd>';
						$value = \selling\UnitUi::getValue($eProduct['privateStep'] ?? \shop\ProductUi::getDefaultPrivateStep($eProduct), $eProduct['unit']);
						$h .= $eProduct->quick('privateStep', $value);
					$h .= '</dd>';

				} else {
					$h .= '<div class="color-muted">'.s("Pas de vente aux particuliers").'</div>';
				}

				$h .= '</dl>';

			$h .= '</div>';

			$h .= '<div class="product-item-presentation">';

				$h .= '<h3>'.s("Vente aux professionnels").'</h3>';

				$h .= '<dl class="util-presentation util-presentation-1">';

				if($eProduct['pro']) {

					$taxes = $eProduct['farm']->getSelling('hasVat') ? CustomerUi::getTaxes(Customer::PRO) : '';

					$h .= '<dt>'.s("Prix de base").'</dt>';
					$h .= '<dd>';
						if($eProduct['proPrice']) {
							if($eProduct['proPriceInitial']) {
								$value = \util\TextUi::money($eProduct['proPriceInitial']);
							} else {
								$value = \util\TextUi::money($eProduct['proPrice']);
							}
							$value .= ' '.$taxes.\selling\UnitUi::getBy($eProduct['unit']);
						} else if($eProduct['privatePrice']) {
							$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les particuliers diminué de la TVA, cliquez pour le personnaliser.").'">'.\Asset::icon('magic').' ';
								$value .= \util\TextUi::money($eProduct->calcProMagicPrice($eProduct['farm']->getSelling('hasVat')));
								$value .= ' '.$taxes.\selling\UnitUi::getBy($eProduct['unit']);
							$value .= '</span>';
						} else {
							$value = '/';
						}
						if($eProduct['proPrice'] and $eProduct['proPriceInitial']) {
							$h .= new PriceUi()->priceWithoutDiscount($eProduct->quick('proPrice', $value), isSmall: FALSE);
						} else {
							$h .= $eProduct->quick('proPrice', $value);
						}
					$h .= '</dd>';


					if($eProduct['proPriceInitial']) {

						$h .= '<dt>'.s("Prix remisé").'</dt>';

						$h .= '<dd>';
							$value = \util\TextUi::money($eProduct['proPrice']);
							$value .= ' '.$taxes.\selling\UnitUi::getBy($eProduct['unit']);
							$h .= $eProduct->quick('proPrice', $value);
						$h .= '</dd>';

					}

					if($eProduct['proPackaging']) {
						$h .= '<dt>'.self::p('proPackaging')->label.'</dt>';
						$h .= '<dd>';
							$value = \selling\UnitUi::getValue($eProduct['proPackaging'], $eProduct['unit']);
							$h .= $eProduct->quick('proPackaging', $value);
						$h .= '</dd>';
					}

					$h .= '<dt>'.self::p('proStep')->label.'</dt>';
					$h .= '<dd>';
						$value = \selling\UnitUi::getValue($eProduct['proStep'] ?? \shop\ProductUi::getDefaultProStep($eProduct), $eProduct['unit']);
						$h .= $eProduct->quick('proStep', $value);
					$h .= '</dd>';

				} else {

					$h .= '<div class="color-muted">'.s("Pas de vente aux professionnels").'</div>';

				}

				$h .= '</dl>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getUpdate(Product $eProduct, string $btn): string {

		if($eProduct->canWrite() === FALSE) {
			return '';
		}

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn '.$btn.'">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div class="dropdown-list">';
			$h .= '<div class="dropdown-title">'.encode($eProduct->getName()).'</div>';

			$h .= '<a href="/selling/product:update?id='.$eProduct['id'].'" class="dropdown-item">';
				$h .= match($eProduct['profile']) {
					Product::GROUP => s("Modifier le groupe"),
					default => s("Modifier le produit")
				};
			$h .= '</a>';

			if($eProduct['profile'] === Product::COMPOSITION) {
				$h .= '<a href="/selling/sale:create?farm='.$eProduct['farm']['id'].'&compositionOf='.$eProduct['id'].'" class="dropdown-item">'.s("Nouvelle composition").'</a>';
			}
			$h .= '<div class="dropdown-divider"></div>';
			if($eProduct->acceptDuplicate()) {
				$h .= '<a href="/selling/product:create?farm='.$eProduct['farm']['id'].'&from='.$eProduct['id'].'" class="dropdown-item">'.s("Dupliquer le produit").'</a>';
			}
			$h .= '<a data-ajax="/selling/product:doDelete" post-id="'.$eProduct['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression du produit ?").'">';
				$h .= match($eProduct['profile']) {
					Product::GROUP => s("Supprimer le groupe"),
					default => s("Supprimer le produit")
				};
			$h .= '</a>';
			if($eProduct->acceptEnableStock() or $eProduct->acceptDisableStock()) {
				$h .= '<div class="dropdown-divider"></div>';
				if($eProduct->acceptEnableStock()) {
					$h .= '<a data-ajax="/selling/product:doEnableStock" post-id='.$eProduct['id'].'" class="dropdown-item">'.\Asset::icon('box').'  '.s("Activer le suivi du stock").'</a>';
				}
				if($eProduct->acceptDisableStock()) {
					$h .= '<a data-ajax="selling/product:doDisableStock" post-id="'.$eProduct['id'].'" class="dropdown-item">'.\Asset::icon('box').'  '.s("Désactiver le suivi du stock").'</a>';
				}
			}
		$h .= '</div>';

		return $h;

	}

	public function create(Product $eProduct): \Panel {

		$eProduct->expects(['cCategory', 'cUnit']);

		$eFarm = $eProduct['farm'];

		$form = new \util\FormUi();

		if($eProduct['profile'] === NULL) {
			return $this->createProfile($eProduct);
		}

		$h = $form->openAjax('/selling/product:doCreate', ['id' => 'product-create', 'class' => 'product-write-profile']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			if($eProduct->exists()) {
				$h .= '<div class="util-block-help">';
					$h .= s("Vous pouvez maintenant paramétrer le nouveau produit que vous vous apprêtez à créer sur la base de <u>{product}</u>.", ['product' => encode($eProduct['name'])]);
				$h .= '</div>';
			}


			$h .= $form->dynamicGroup($eProduct, 'profile');
			$h .= $form->dynamicGroups($eProduct, ['name*']);

			if($eProduct['cCategory']->notEmpty()) {
				$h .= $form->dynamicGroup($eProduct, 'category');
			}

			$h .= $form->dynamicGroup($eProduct, 'unit', function(\PropertyDescriber $d) {
				$d->attributes += [
					'onchange' => 'Product.changeUnit(this, "product-unit")'
				];
			});

			$h .= $form->dynamicGroups($eProduct, ['quality']);
			$h .= $form->dynamicGroup($eProduct, 'vat');

			$h .= '<br/>';
			$h .= $this->getFieldDescription($form, $eProduct);

			$h .= '<br/>';
			$h .= $this->getFieldProfile($form, $eProduct);

			$h .= '<br/>';
			$h .= $this->getFieldPrices($form, $eProduct, 'create');

			$h .= $form->group(
				content: $form->submit(s("Créer le produit"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-product-create',
			title: $eProduct->exists() ? s("Dupliquer un produit") : s("Ajouter un produit"),
			body: $h
		);

	}

	public function createProfile(Product $eProduct): \Panel {

		$h = '<div class="util-buttons util-buttons-dark">';

			foreach(self::p('profile')->values as $profile => $value) {

				$examples = self::p('profile')->examples[$profile];

				$h .= '<a href="/selling/product:create?farm='.$eProduct['farm']['id'].'&profile='.$profile.'" class="util-button" style="border-color: var(--'.$profile.')">';

					$h .= '<div>';
						$h .= '<h4>'.$value.'</h4>';
						if($examples) {
							$h .= '<div class="util-button-text">'.$examples.'</div>';
						}
					$h .= '</div>';
					$h .= self::getProfileIcon($profile, '4rem');

				$h .= '</a>';

			}

		$h .= '</div>';

		return new \Panel(
			id: 'panel-product-create',
			title: s("Ajouter un produit"),
			body: $h
		);

	}

	public function update(Product $eProduct): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/product:doUpdate', ['id' => 'product-update', 'class' => 'product-write-profile']);

			$h .= $form->hidden('id', $eProduct['id']);

			if($eProduct['profile'] === Product::GROUP) {

				$h .= $form->dynamicGroup($eProduct, 'name', function($d) {
					$d->label = s("Nom du groupe");
				});
				$h .= $form->dynamicGroup($eProduct, 'groupSelection');

			} else {

				$h .= $form->dynamicGroup($eProduct, 'profile');
				$h .= $form->dynamicGroup($eProduct, 'name');

				if($eProduct['cCategory']->notEmpty()) {
					$h .= $form->dynamicGroup($eProduct, 'category');
				}

				$h .= $form->group(
					self::p('unit')->label,
					($eProduct['unit']->empty() or $eProduct['unit']['approximate'] === FALSE) ?
						$form->dynamicField($eProduct, 'unit') :
						$form->fake(mb_ucfirst($eProduct['unit'] ? \selling\UnitUi::getSingular($eProduct['unit']) : self::p('unit')->placeholder))
				);

				$h .= $form->dynamicGroups($eProduct, ['origin', 'quality']);
				$h .= $form->dynamicGroup($eProduct, 'vat');

				$h .= '<br/>';
				$h .= $this->getFieldDescription($form, $eProduct);

				$h .= '<br/>';
				$h .= $this->getFieldProfile($form, $eProduct);

				$h .= '<br/>';
				$h .= $this->getFieldPrices($form, $eProduct, 'update');

			}

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-product-update',
			title: match($eProduct['profile']) {
				Product::GROUP => s("Modifier un groupe"),
				default => s("Modifier un produit")
			},
			body: $h
		);

	}

	private function getFieldDescription(\util\FormUi $form, Product $eProduct): string {

		$h = '';
		$h .= '<h3>'.s("Description").'</h3>';
		$h .= '<div class="util-block bg-background-light">';
			$h .= $form->dynamicGroups($eProduct, ['additional', 'description']);
		$h .= '</div>';

		return $h;

	}

	private function getFieldProfile(\util\FormUi $form, Product $eProduct): string {

		$h = '';

		$h .= '<div class="product-write-profile-details">';

			$h .= '<h3>'.s("Caractéristiques").'</h3>';

			$h .= '<div class="util-block bg-background-light">';

				$h .= '<div data-profile="'.implode(' ', Product::getProfiles('compositionVisibility')).'">';
					$h .= $form->dynamicGroups($eProduct, ['compositionVisibility*']);
				$h .= '</div>';

				$h .= '<div data-profile="'.implode(' ', Product::getProfiles('unprocessedPlant')).'">';

					$h .= $form->group(
						self::p('unprocessedPlant')->label,
						$form->dynamicField($eProduct, 'unprocessedPlant', function($d) {
							$d->autocompleteDispatch = '#product-update';
						})
					);

				$h .= '</div>';

				foreach(['unprocessedVariety', 'processedPackaging', 'processedComposition', 'mixedFrozen', 'processedAllergen'] as $property) {

					$h .= '<div data-profile="'.implode(' ', Product::getProfiles($property)).'">';
						$h .= $form->dynamicGroup($eProduct, $property);
					$h .= '</div>';

				}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	private function getFieldPrices(\util\FormUi $form, Product $eProduct, string $for): string {

		$h = '<h3>'.s("Prix").'</h3>';

		if($for === 'create') {
			$h .= '<div class="util-block-help" data-profile="'.Product::COMPOSITION.'">'.s("Un produit composé peut être vendu soit aux particuliers, soit aux professionnels, mais pas simultanément aux deux. Le choix que vous faites maintenant ne pourra pas être modifié par la suite, et votre produit ne pourra être composé que de produits également vendus à ce type de clientèle.").'</div>';
		}

		$h .= '<div class="util-info" data-not-profile="'.Product::COMPOSITION.'">'.s("Pour une vente aux particuliers et si aucun prix de vente n'a été saisi, le prix de vente pro augmenté de la TVA sera utilisé dans ce cas, et vice-versa pour une vente aux professionnels. Ces données de base pourront toujours être personnalisées pour chaque client et vente.").'</div>';

		$h .= '<div class="hide-panel-out mb-2">'.\Asset::icon('exclamation-circle').' '.s("Les prix de base que vous donnez à vos produits ne sont pas prioritaires par rapport aux prix indiqués dans les catalogues et aux prix personnalisés de vos clients (<link>en savoir plus</link>).", ['link' => '<a href="/doc/selling:pricing">']).'</div>';

		$h .= '<br/>';

		if(
			$for === 'create' or
			$eProduct['profile'] !== PRODUCT::COMPOSITION or
			$eProduct['private']
		) {
			$h .= self::getFieldPrivate($form, $eProduct, $for);
			$h .= '<br/>';
		}

		if(
			$for === 'create' or
			$eProduct['profile'] !== PRODUCT::COMPOSITION or
			$eProduct['pro']
		) {
			$h .= self::getFieldPro($form, $eProduct, $for);
			$h .= '<br/>';
		}

		return $h;

	}

	private static function getFieldPro(\util\FormUi $form, Product $eProduct, string $for): string {

		$h = '<div class="util-block bg-background-light" data-wrapper="'.Customer::PRO.'-block">';

			$h .= $form->group(
				'<h4>'.self::p('pro')->label.'</h4>',
				($for === 'create' or $eProduct['profile'] !== Product::COMPOSITION) ? $form->dynamicField($eProduct, 'pro') : ''
			);

			$h .= $form->group(
				s("Prix de base"),
				$form->dynamicField($eProduct, 'proPrice'),
				['wrapper' => 'proPrice proPriceDiscount']
			);

			$unit = ($eProduct['unit']->notEmpty() ? encode($eProduct['unit']['singular']) : self::p('unit')->placeholder);

			$h .= $form->group(
				s("Colis de base"),
				$form->inputGroup(
					$form->dynamicField($eProduct, 'proPackaging').
					'<div class="input-group-addon" data-ref="product-unit">'.$unit.'</div>'
				)
			);

			if($for === 'update') {

				$h .= $form->group(
					self::p('proStep')->label,
					$form->inputGroup(
						$form->dynamicField($eProduct, 'proStep', function($d) {
							$d->placeholder = 1;
						}).
						'<div class="input-group-addon" data-ref="product-unit">'.$unit.'</div>'
					).$form->info(s("Les quantités achetées par les clients dans les boutiques en ligne seront un multiple de cette valeur si vous n'avez pas défini de colis de base."))
				);

			}

		$h .= '</div>';

		return $h;

	}

	private static function getFieldPrivate(\util\FormUi $form, Product $eProduct, string $for): string {

		$h = '<div class="util-block bg-background-light" data-wrapper="'.Customer::PRIVATE.'-block">';

			$h .= $form->group(
				'<h4>'.self::p('private')->label.'</h4>',
				($for === 'create' or $eProduct['profile'] !== Product::COMPOSITION) ? $form->dynamicField($eProduct, 'private') : ''
			);

			$h .= $form->group(
				s("Prix de base"),
				$form->dynamicField($eProduct, 'privatePrice'),
				['wrapper' => 'privatePrice privatePriceDiscount']
			);

			if($for === 'update') {

				$unit = ($eProduct['unit']->notEmpty() ? encode($eProduct['unit']['singular']) : self::p('unit')->placeholder);

				$h .= $form->group(
					self::p('privateStep')->label,
					$form->inputGroup(
						$form->dynamicField($eProduct, 'privateStep', function($d) use($eProduct) {
							if($eProduct->offsetExists('id')) {
								$d->placeholder = \shop\ProductUi::getDefaultPrivateStep($eProduct);
							}
						}).
						'<div class="input-group-addon" data-ref="product-unit">'.$unit.'</div>'
					).$form->info(s("Les quantités achetées par les clients dans les boutiques en ligne seront un multiple de cette valeur."))
				);

			}

		$h .= '</div>';

		return $h;

	}

	public static function getFrozenIcon(): string {
		return \Asset::icon('snow', ['style' => 'color: dodgerblue']);
	}

	public static function p(string $property): \PropertyDescriber {

		$d = Product::model()->describer($property, [
			'category' => s("Catégorie"),
			'vignette' => s("Vignette"),
			'name' => s("Nom du produit"),
			'additional' => s("Complément d'information"),
			'unprocessedPlant' => s("Espèce"),
			'unprocessedVariety' => s("Variété"),
			'mixedFrozen' => s("Surgelé").'  '.self::getFrozenIcon(),
			'processedComposition' => s("Composition"),
			'processedPackaging' => s("Conditionnement"),
			'processedAllergen' => s("Allergènes"),
			'groupSelection' => s("Les clients doivent-ils choisir un produit dans la liste ou peuvent-ils sélectionner plusieurs produits lors d'une commande ?"),
			'profile' => s("Type"),
			'origin' => s("Origine"),
			'description' => s("Présentation du produit"),
			'quality' => s("Signe de qualité"),
			'farm' => s("Ferme"),
			'unit' => s("Unité de vente"),
			'variant' => s("Variantes"),
			'private' => s("Vente aux clients particuliers"),
			'privatePrice' => s("Prix particulier"),
			'privatePriceInitial' => s("Prix particulier de base"),
			'privatePriceDiscount' => s("Prix particulier remisé"),
			'privateStep' => s("Multiple de vente"),
			'pro' => s("Vente aux clients professionnels"),
			'proPrice' => s("Prix professionnel"),
			'proPriceInitial' => s("Prix professionnel de base"),
			'proPriceDiscount' => s("Prix professionnel remisé"),
			'proPackaging' => s("Colis de base"),
			'proStep' => s("Multiple de vente"),
			'compositionVisibility' => s("Affichage de la composition aux clients"),
			'vat' => s("Taux de TVA"),
			'statut' => s("Statut"),
		]);

		switch($property) {

			case 'id' :
				new ProductUi()->query($d);
				break;

			case 'additional' :
				$d->placeholder = s("Ex. : calibrage, version...");
				break;

			case 'category' :
				$d->placeholder = s("Non catégorisé");
				$d->field = 'radio';
				$d->values = fn(Product $e) => $e['cCategory'] ?? $e->expects(['cCategory']);
				$d->attributes = [
					'columns' => 2,
				];
				break;

			case 'profile' :
				$d->field = function(\util\FormUi $form, Product $eProduct) use ($d) {

					$h = '<a data-dropdown="bottom-start" class="btn btn-primary dropdown-toggle" data-dropdown-hover="true">';
						$h .= ProductUi::getProfileIcon($eProduct['profile']).'  ';
						$h .= $form->inputRadio('profile', $eProduct['profile'], attributes: ['checked' => 'checked', 'class' => 'hide']);
						$h .= '<span class="product-profile-name">'.$d->values[$eProduct['profile']].'</span>';
					$h .= '</a>';
					$h .= ProductUi::getProfileDropdown($eProduct, fn($h) => attr('onclick', 'Product.changeProfile(this)'));

					if($eProduct->exists() === FALSE) {
						$h .= '<div class="mt-1" data-profile="'.Product::COMPOSITION.'">';
							$h .= '<div class="util-block-help">';
								$h .= '<h3>'.s("Qu'est-ce qu'un produit composé ?").'</h3>';
								$h .= '<p>'.s("Un produit composé est un produit qui rassemble plusieurs autres produits. Cela peut être par exemple un panier de légumes dont vous modifiez la composition toutes les semaines, un bouquet de fleurs que vous cultivez, une cagette de légumes pour la ratatouille...").'</p>';
								$h .= '<p>'.s("Vous pouvez choisir la composition de votre produit à l'étape suivante.").'</p>';
							$h .= '</div>';
						$h .= '</div>';
					}

					return $h;
					
				};

				$d->values = [
					Product::UNPROCESSED_PLANT => s("Produit d'origine végétale"),
					Product::UNPROCESSED_ANIMAL => s("Produit d'origine animale"),
					Product::PROCESSED_FOOD => s("Produit alimentaire transformé"),
					Product::PROCESSED_PRODUCT => s("Produit non alimentaire"),
					Product::COMPOSITION => s("Produit composé"),
					Product::GROUP => s("Groupe de produits"),
					Product::OTHER => s("Autre produit"),
				];

				$d->examples = [
					Product::UNPROCESSED_PLANT => s("Fruit, légume, fleur, plant..."),
					Product::UNPROCESSED_ANIMAL => s("Viande, oeuf, lait, animal vivant..."),
					Product::PROCESSED_FOOD => s("Pain, crèmerie, confiture..."),
					Product::PROCESSED_PRODUCT => s("Savon, lessive..."),
					Product::COMPOSITION => s("Panier de légumes, bouquet de fleurs..."),
					Product::GROUP => NULL,
					Product::OTHER => NULL,
				];

				$d->icons = [
					Product::UNPROCESSED_PLANT => \Asset::icon('leaf'),
					Product::UNPROCESSED_ANIMAL => \Asset::icon('egg'),
					Product::PROCESSED_FOOD => \Asset::icon('fork-knife'),
					Product::PROCESSED_PRODUCT => \Asset::icon('box'),
					Product::COMPOSITION => \Asset::icon('puzzle-fill'),
					Product::GROUP => \Asset::icon('database'),
					Product::OTHER => \Asset::icon('three-dots'),
				];
				break;

			case 'unprocessedPlant' :
				$d->autocompleteBody = function(\util\FormUi $form, Product $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id']
					];
				};
				new \plant\PlantUi()->query($d);
				break;

			case 'unprocessedVariety' :
				$d->placeholder = s("Ex. : Chérie");
				break;

			case 'mixedFrozen' :
				$d->field = 'switch';
				break;

			case 'groupSelection' :
				$d->field = 'radio';
				$d->attributes['mandatory'] = TRUE;
				$d->values = [
					Product::UNIQUE => s("Un seul produit dans la liste"),
					Product::MULTIPLE => s("Autant de produits dans la liste que souhaité"),
				];
				break;

			case 'unit' :
				$d->values = fn(Product $e) => isset($e['cUnit']) ? UnitUi::getField($e['cUnit']) : $e->expects(['cUnit']);
				$d->attributes = ['group' => TRUE];
				$d->placeholder = s("&lt; Non applicable &gt;");
				$d->after = fn(\util\FormUi $form, Product $e) => $e->exists() ?
					\util\FormUi::info(s("L'unité de vente ne peut être modifiée que pour une autre unité de vente à l'unité.")) :
					\util\FormUi::info(s("Les unités de vente à la quantité ne peuvent pas être modifiées par la suite, vous devrez créer un autre produit si vous changez d'avis."));
				break;

			case 'private' :
			case 'pro' :
				$d->field = 'switch';
				$d->attributes += [
					'onchange' => 'Product.changeType(input, "'.$property.'")'
				];
				break;

			case 'privateStep' :
				$d->attributes += [
					'disabled' => function(\util\FormUi $form, Product $e) {
						return $e['private'] ? NULL : 'disabled';
					}
				];
				break;

			case 'privatePrice' :

				$d->field = function(\util\FormUi $form, Product $e) {

					$taxes = $e['farm']->getSelling('hasVat') ? CustomerUi::getTaxes(Customer::PRIVATE) : '';
					$unit = ($e['unit']->notEmpty() ? encode($e['unit']['singular']) : ProductUi::p('unit')->placeholder);
					$suffix = '€ '.$taxes.' / <span data-ref="product-unit">'.$unit.'</span>';

					$price = ($e['privatePriceInitial'] ?? NULL) !== NULL ? $e['privatePriceInitial'] : $e['privatePrice'] ?? '';
					$priceDiscount = ($e['privatePriceInitial'] ?? NULL) !== NULL ? $e['privatePrice'] ?? '' : '';

					$identifier = ($e['id'] ?? '').'-private';

					$h = $form->inputGroup(
						$form->number('privatePrice', $price, [
							'step' => 0.01,
							'onfocus' => 'this.select()',
							'disabled' => $e['private'] ? NULL : 'disabled'
						]).
						$form->addon($suffix),
					);
					$h .= new PriceUi()->getDiscountLink($identifier, hasDiscountPrice: empty($priceDiscount) === FALSE);


					$h .= $form->inputGroup(
						$form->addon(s("Prix remisé")).
						$form->number('privatePriceDiscount', $priceDiscount, [
							'step' => 0.01,
							'disabled' => $e['private'] ? NULL : 'disabled'
						]).
						$form->addon($suffix).
						$form->addon(new PriceUi()->getDiscountTrashAddon($identifier)),
						['class' => 'mt-1'.(empty($priceDiscount) ? ' hide' : ''), 'data-price-discount' => $identifier, 'data-wrapper' => 'privatePriceDiscount']
					);

					return $h;

				};

				$d->attributes += [
					'wrapper' => 'privatePrice proOrPrivatePrice',
					'disabled' => function(\util\FormUi $form, Product $e) {
						return $e['private'] ? NULL : 'disabled';
					}
				];
				break;

			case 'proPackaging' :
			case 'proStep' :
				$d->attributes += [
					'disabled' => function(\util\FormUi $form, Product $e) {
						return $e['pro'] ? NULL : 'disabled';
					}
				];
				break;

			case 'proPrice' :

				$d->field = function(\util\FormUi $form, Product $e) {

					$taxes = $e['farm']->getSelling('hasVat') ? CustomerUi::getTaxes(Customer::PRO) : '';
					$unit = ($e['unit']->notEmpty() ? encode($e['unit']['singular']) : ProductUi::p('unit')->placeholder);
					$suffix = '€ '.$taxes.' / <span data-ref="product-unit">'.$unit.'</span>';

					$price = ($e['proPriceInitial'] ?? NULL) !== NULL ? $e['proPriceInitial'] : $e['proPrice'] ?? '';
					$priceDiscount = ($e['proPriceInitial'] ?? NULL) !== NULL ? $e['proPrice'] ?? '' : '';

					$identifier = ($e['id'] ?? '').'-pro';

					$h = $form->inputGroup(
						$form->number('proPrice', $price, [
							'step' => 0.01,
							'onfocus' => 'this.select()',
							'disabled' => $e['pro'] ? NULL : 'disabled'
						]).
						$form->addon($suffix),
					);
					$h .= new PriceUi()->getDiscountLink($identifier, hasDiscountPrice: empty($priceDiscount) === FALSE);


					$h .= $form->inputGroup(
						$form->addon(s("Prix remisé")).
						$form->number('proPriceDiscount', $priceDiscount, [
							'step' => 0.01,
							'disabled' => $e['pro'] ? NULL : 'disabled'
						]).
						$form->addon($suffix).
						$form->addon(new PriceUi()->getDiscountTrashAddon($identifier)),
						['class' => 'mt-1'.(empty($priceDiscount) ? ' hide' : ''), 'data-price-discount' => $identifier, 'data-wrapper' => 'proPriceDiscount']
					);

					return $h;

				};

				$d->attributes += [
					'wrapper' => 'proPrice privateOrProPrice',
					'disabled' => function(\util\FormUi $form, Product $e) {
						return $e['pro'] ? NULL : 'disabled';
					}
				];
				break;

			case 'quality' :
				$d->field = 'select';
				$d->values = \farm\FarmUi::getQualities();
				$d->placeholder = s("Aucun");
				break;

			case 'compositionVisibility' :
				$d->values = [
					Product::PUBLIC => s("Visible").'  <span class="color-muted"><small>'.s("La composition du produit est affichée aux clients").'</small></span>',
					Product::PRIVATE => s("Cachée").'  <span class="color-muted"><small>'.s("Les clients ne voient pas la composition du produit").'</small></span>'
				];
				$d->default = Product::PRIVATE;
				$d->attributes = [
					'mandatory' => TRUE
				];
				break;

			case 'origin' :
				$d->attributes = [
					'placeholder' => s("Ex. : Ferme d'à côté (63)"),
				];
				break;

			case 'vat' :
				$d->field = 'select';
				$d->values = function(Product $e) {
					return SaleUi::getVat($e['farm']);
				};
				$d->attributes = [
					'mandatory' => TRUE
				];
				break;

		}

		return $d;

	}

}
?>
