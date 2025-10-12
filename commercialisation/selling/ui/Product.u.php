<?php
namespace selling;

use util\DateUi;

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

		$item = self::getVignette($eProduct, '2.5rem');
		$item .= '<div>';
			$item .= $eProduct->getName('html');
			$item .= \selling\UnitUi::getBy($eProduct['unit']);
			$item .= '<br/>';
			if($details) {
				$item .= '<small class="color-muted">'.implode(' | ', $details).'</small>';
			}
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

	public function getSearch(\farm\Farm $eFarm, \Search $search): string {

		$form = new \util\FormUi();

		$h = '<div id="product-search" class="util-block-search '.($search->empty(['category']) ? 'hide' : '').'">';

			$h .= $form->openAjax(\farm\FarmUi::urlSellingProducts($eFarm), ['method' => 'get', 'id' => 'form-search']);
				$h .= $form->hidden('category', $search->get('category'));
				$h .= '<div>';
					$h .= $form->select('composition', [
						'simple' => s("Produits simples"),
						'composed' => s("Produits composés")
					], $search->get('composition'), ['placeholder' => s("Type")]);
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

		$h .= '<table class="product-item-table tr-even">';

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
					$h .= '<th colspan="2" class="text-center highlight">'.s("Grille tarifaire").'</th>';
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

			$h .= '<tbody>';

			foreach($cProduct as $eProduct) {

				$eItemTotal = $eProduct['eItemTotal'];

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
						$h .= \selling\UnitUi::getSingular($eProduct['unit']);
					$h .= '</td>';

					$h .= '<td class="text-end highlight-stick-right hide-md-down">';
						if($eItemTotal->notEmpty() and $eItemTotal['year']) {
							$amount = \util\TextUi::money($eItemTotal['year'], precision: 0);
							$h .= $eFarm->canAnalyze() ? '<a href="/selling/product:analyze?id='.$eProduct['id'].'&year='.$year.'">'.$amount.'</a>' : $amount;
						} else {
							$h .= '-';
						}
					$h .= '</td>';

					$h .= '<td class="text-end highlight-stick-left hide-md-down customer-item-year-before">';
						if($eItemTotal->notEmpty() and $eItemTotal['yearBefore']) {
							$amount = \util\TextUi::money($eItemTotal['yearBefore'], precision: 0);
							$h .= $eFarm->canAnalyze() ? '<a href="/selling/product:analyze?id='.$eProduct['id'].'&year='.$yearBefore.'">'.$amount.'</a>' : $amount;
						} else {
							$h .= '-';
						}
					$h .= '</td>';

					$h .= '<td class="product-item-price highlight-stick-right text-end">';
						if($eProduct['private'] === FALSE) {
							$h .= \Asset::icon('x');
						} else {

							$taxes = $eFarm->getSelling('hasVat') ? ' <span class="util-annotation">'.CustomerUi::getTaxes(Customer::PRIVATE).'</span>' : '';

							$field = 'privatePrice';
							if($eProduct['privatePrice'] !== NULL) {
								$value = \util\TextUi::money($eProduct['privatePrice']).$taxes;
								if($eProduct['privatePriceInitial'] !== NULL) {
									$field = 'privatePriceDiscount';
									$h .= new PriceUi()->priceWithoutDiscount($eProduct['privatePriceInitial'], unit: $taxes);
								}
							} else if($eProduct['proPrice'] !== NULL) {
								$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les professionnels augmenté de la TVA.").'">'.\Asset::icon('magic').' ';
									$value .= \util\TextUi::money($eProduct->calcPrivateMagicPrice($eFarm->getSelling('hasVat'))).$taxes;
								$value .= '</span>';
							} else {
								$value = \Asset::icon('question');
							}

							$h .= $eProduct->quick($field, $value);

						}
					$h .= '</td>';

					$h .= '<td class="product-item-price highlight-stick-left text-end">';
						if($eProduct['pro'] === FALSE) {
							$h .= \Asset::icon('x');
						} else {

							$taxes = $eFarm->getSelling('hasVat') ? ' <span class="util-annotation">'.CustomerUi::getTaxes(Customer::PRO).'</span>' : '';

							$field = 'proPrice';
							if($eProduct['proPrice']) {
								$value = \util\TextUi::money($eProduct['proPrice']).$taxes;
								if($eProduct['proPriceInitial'] !== NULL) {
									$field = 'proPriceDiscount';
									$h .= new PriceUi()->priceWithoutDiscount($eProduct['proPriceInitial'], unit: $taxes);
								}
							} else if($eProduct['privatePrice']) {
								$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les particuliers diminué de la TVA.").'">'.\Asset::icon('magic').' ';
									$value .= \util\TextUi::money($eProduct->calcProMagicPrice($eFarm->getSelling('hasVat'))).$taxes;
								$value .= '</span>';
							} else {
								$value = \Asset::icon('question');
							}

							$h .= $eProduct->quick($field, $value);

						}
					$h .= '</td>';

					if($eFarm->getSelling('hasVat')) {

						$h .= '<td class="text-center product-item-vat">';
							$h .= s("{value} %", SellingSetting::VAT_RATES[$eProduct['vat']]);
						$h .= '</td>';

					}

					$h .= '<td class="product-item-status td-min-content">';
						$h .= $this->toggle($eProduct);
					$h .= '</td>';

					$h .= '<td class="product-item-actions">';
						$h .= $this->getUpdate($eProduct, 'btn-outline-secondary');
					$h .= '</td>';

				$h .= '</tr>';

			}

			$h .= '</tbody>';

		$h .= '</table>';

		$h .= '</div>';

		$h .= $this->getBatch($cCategory);

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

	public function getBatch(\Collection $cCategory): string {

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
			$h .= '<div class="product-item-infos">'.implode('', $more).'</div>';
		}

		return $h;


	}

	public static function getDetails(Product $eProduct): array {

		$more = [];

		if($eProduct['unprocessedSize']) {
			$more[] = '<span><u>'.encode($eProduct['unprocessedSize']).'</u></span>';
		}

		if($eProduct['origin']) {
			$more[] = '<span>'.s("Origine {value}", '<u>'.encode($eProduct['origin']).'</u>').'</span>';
		}

		return $more;

	}

	public static function getVignette(Product $eProduct, string $size, bool $public = FALSE, bool $withPlant = FALSE): string {

		$eProduct->expects(['id', 'vignette', 'composition']);

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

		if($public === FALSE) {
			$content .= self::getVignetteComplement($eProduct, $withPlant);
		}

		return '<div class="'.$class.'" style="'.$ui->getSquareCss($size).'; '.$style.'">'.$content.'</div>';

	}

	public static function getVignetteComplement(Product $eProduct, bool $withPlant = FALSE): string {

		if($eProduct['composition']) {
			return self::getVignetteComposition();
		}

		if($withPlant and $eProduct['unprocessedPlant']->notEmpty()) {
			return self::getVignettePlant($eProduct['unprocessedPlant']);
		}

		return '';

	}

	public static function getVignetteComposition(): string {

		\Asset::css('selling', 'product.css');

		return '<div class="product-vignette-composition">'.\Asset::icon('puzzle-fill').'</div>';

	}

	public static function getVignettePlant(\plant\Plant $ePlant): string {

		return '<div class="product-vignette-plant">'.\plant\PlantUi::getVignette($ePlant, '1.25rem').'</div>';

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
					$h .= $this->toggle($eProduct);
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

	public function display(Product $eProduct, \Collection $cItemYear): string {

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
				if($eProduct['composition']) {
					$h .= '<dt>'.s("Composition").'</dt>';
					$h .= '<dd>'.($eProduct['compositionVisibility'] === Product::PRIVATE ? s("surprise") : s("visible")).'</dd>';
				}
				if($eProduct['unprocessedPlant']->notEmpty()) {
					$h .= '<dt>'.self::p('unprocessedPlant')->label.'</dt>';
					$h .= '<dd>'.\plant\PlantUi::link($eProduct['unprocessedPlant']).'</dd>';
				}
				if($eProduct['unprocessedSize'] !== NULL) {
					$h .= '<dt>'.self::p('unprocessedSize')->label.'</dt>';
					$h .= '<dd>'.encode($eProduct['unprocessedSize']).'</dd>';
				}
				if($eProduct['processedComposition'] !== NULL) {
					$h .= '<dt>'.self::p('processedComposition')->label.'</dt>';
					$h .= '<dd>'.nl2br(encode($eProduct['processedComposition'])).'</dd>';
				}
				if($eProduct['processedAllergen'] !== NULL) {
					$h .= '<dt>'.self::p('processedAllergen')->label.'</dt>';
					$h .= '<dd>'.nl2br(encode($eProduct['processedAllergen'])).'</dd>';
				}
			$h .= '</dl>';
		$h .= '</div>';

		if(
			$eProduct['farm']->canAnalyze() and
			$cItemYear->notEmpty()
		) {

			$h .= new AnalyzeUi()->getProductYear($cItemYear, NULL, $eProduct);

		}

		return $h;

	}

	public function getTabs(Product $eProduct, \Collection $cSaleComposition, \Collection $cGrid, \Collection $cItemLast): string {

		$h = '<div class="tabs-h" id="product-tabs" onrender="'.encode('Lime.Tab.restore(this, "product-grid")').'">';

			$h .= '<div class="tabs-item">';
				if($eProduct['composition']) {
					$h .= '<a class="tab-item '.($eProduct['composition'] ? 'selected' : '').'" data-tab="product-composition" onclick="Lime.Tab.select(this)">'.s("Composition").'</a>';
				}
				$h .= '<a class="tab-item '.($eProduct['composition'] ? '' : 'selected').'" data-tab="product-grid" onclick="Lime.Tab.select(this)">'.s("Grille tarifaire").'</a>';
				$h .= '<a class="tab-item" data-tab="product-sales" onclick="Lime.Tab.select(this)">'.s("Dernières ventes").'</a>';
			$h .= '</div>';

			if($eProduct['composition']) {
				$h .= '<div class="tab-panel '.($eProduct['composition'] ? 'selected' : '').'" data-tab="product-composition">';
					if($cSaleComposition->empty()) {
						$h .= $this->getEmptyComposition($eProduct);
					} else {
						$h .= $this->getComposition($eProduct, $cSaleComposition);
					}
				$h .= '</div>';
			}

			$h .= '<div class="tab-panel '.($eProduct['composition'] ? '' : 'selected').'" data-tab="product-grid">';
				$h .= $this->getBaseGrid($eProduct);
				$h .= new \selling\GridUi()->getGridByProduct($eProduct, $cGrid);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="product-sales">';
				$h .= new \selling\ItemUi()->getByProduct($cItemLast);
			$h .= '</div>';

		$h .= '</div>';

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

				$h .= '<h3>'.s("Particuliers").'</h3>';

				$h .= '<dl class="util-presentation util-presentation-1">';

				if($eProduct['private']) {

					$taxes = $eProduct['farm']->getSelling('hasVat') ? CustomerUi::getTaxes(Customer::PRIVATE) : '';

					$h .= '<dt>'.s("Prix de base").'</dt>';
					$h .= '<dd>';
						$field = 'privatePrice';
						if($eProduct['privatePrice']) {
							if($eProduct['privatePriceInitial']) {
								$value = \util\TextUi::money($eProduct['privatePriceInitial']);
								$field = 'privatePriceInitial';
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
							$h .= new PriceUi()->priceWithoutDiscount($eProduct->quick($field, $value), isSmall: FALSE);
						} else {
							$h .= $eProduct->quick($field, $value);
						}
					$h .= '</dd>';

					if($eProduct['privatePriceInitial']) {

						$h .= '<dt>'.s("Prix remisé").'</dt>';

						$h .= '<dd>';
							$value = \util\TextUi::money($eProduct['privatePrice']);
							$value .= ' '.$taxes.\selling\UnitUi::getBy($eProduct['unit']);
							$h .= $eProduct->quick('privatePriceDiscount', $value);
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

				$h .= '<h3>'.s("Pros").'</h3>';

				$h .= '<dl class="util-presentation util-presentation-1">';

				if($eProduct['pro']) {

					$taxes = $eProduct['farm']->getSelling('hasVat') ? CustomerUi::getTaxes(Customer::PRO) : '';

					$h .= '<dt>'.s("Prix de base").'</dt>';
					$h .= '<dd>';
						$field = 'proPrice';
						if($eProduct['proPrice']) {
							if($eProduct['proPriceInitial']) {
								$value = \util\TextUi::money($eProduct['proPriceInitial']);
								$field = 'proPriceInitial';
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
							$h .= new PriceUi()->priceWithoutDiscount($eProduct->quick($field, $value), isSmall: FALSE);
						} else {
							$h .= $eProduct->quick($field, $value);
						}
					$h .= '</dd>';


					if($eProduct['proPriceInitial']) {

						$h .= '<dt>'.s("Prix remisé").'</dt>';

						$h .= '<dd>';
							$value = \util\TextUi::money($eProduct['proPrice']);
							$value .= ' '.$taxes.\selling\UnitUi::getBy($eProduct['unit']);
							$h .= $eProduct->quick('proPriceDiscount', $value);
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
			$h .= '<a href="/selling/product:update?id='.$eProduct['id'].'" class="dropdown-item">'.s("Modifier le produit").'</a>';
			if($eProduct['composition']) {
				$h .= '<a href="/selling/sale:create?farm='.$eProduct['farm']['id'].'&compositionOf='.$eProduct['id'].'" class="dropdown-item">'.s("Nouvelle composition").'</a>';
			}
			$h .= '<div class="dropdown-divider"></div>';
			$h .= '<a href="/selling/product:create?farm='.$eProduct['farm']['id'].'&from='.$eProduct['id'].'" class="dropdown-item">'.s("Dupliquer le produit").'</a>';
			$h .= '<a data-ajax="/selling/product:doDelete" post-id="'.$eProduct['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression du produit ?").'">'.s("Supprimer le produit").'</a>';
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

	public function create(Product $eProduct, bool $createFirst = FALSE): \Panel {

		$eProduct->expects(['cCategory', 'cUnit']);

		$eFarm = $eProduct['farm'];

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/product:doCreate', ['id' => 'product-create']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->hidden('composition', $eProduct['composition']);

			if($eProduct->exists()) {
				$h .= '<div class="util-block-help">';
					$h .= s("Vous pouvez maintenant paramétrer le nouveau produit que vous vous apprêtez à créer sur la base de <u>{product}</u>.", ['product' => encode($eProduct['name'])]);
				$h .= '</div>';
			}

			if($createFirst === FALSE) {

				$tabs = '<div class="tabs-item">';
					$tabs .= '<a data-ajax="/selling/product:create?farm='.$eFarm['id'].'" data-ajax-method="get" class="tab-item '.($eProduct['composition'] ? '' : 'selected').'">'.s("Produit simple").'</a>';
					$tabs .= '<a data-ajax="/selling/product:create?farm=' . $eFarm['id'] . '&composition=1" data-ajax-method="get" class="tab-item ' . ($eProduct['composition'] ? 'selected' : '') . '" xmlns="http://www.w3.org/1999/html"><span><big>'.\Asset::icon('puzzle-fill').'</big> '.s("Produit composé").'</span></a>';
				$tabs .= '</div>';

				if($eProduct['composition']) {
					$tabs .= '<div class="util-block-help">'.s("Un produit composé est un produit qui rassemble plusieurs autres produits. Cela peut être par exemple un panier de légumes dont vous modifiez la composition toutes les semaines, un bouquet de fleurs que vous cultivez, une cagette de légumes pour la ratatouille...").'</div>';
				}

			} else {
				$tabs = '';
			}

			$h .= $form->group(
				content: $tabs
			);

			$h .= $form->dynamicGroup($eProduct, 'name*');

			if($eProduct['cCategory']->notEmpty()) {
				$h .= $form->dynamicGroup($eProduct, 'category');
			}

			$h .= $form->dynamicGroup($eProduct, 'unit', function(\PropertyDescriber $d) {
				$d->attributes += [
					'onchange' => 'Product.changeUnit(this, "product-unit")'
				];
			});

			$h .= $form->dynamicGroups($eProduct, ['description', 'quality']);

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

	public function update(Product $eProduct): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/product:doUpdate', ['id' => 'product-update']);

			$h .= $form->hidden('id', $eProduct['id']);

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

			$h .= $form->dynamicGroups($eProduct, ['description', 'origin', 'quality']);

			$h .= '<br/>';
			$h .= $this->getFieldProfile($form, $eProduct);

			$h .= '<br/>';
			$h .= $this->getFieldPrices($form, $eProduct, 'update');

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-product-update',
			title: s("Modifier un produit"),
			body: $h
		);

	}

	private function getFieldProfile(\util\FormUi $form, Product $eProduct): string {

		$h = '';

		if($eProduct['composition']) {

			$h .= '<div class="util-block bg-background-light">';
				$h .= $form->group(content: '<h4>'.s("Panier").'</h4>');
				$h .= $form->dynamicGroups($eProduct, ['compositionVisibility*']);
			$h .= '</div>';

		} else {

			$h .= '<div class="product-write-profile">';
				$h .= $form->dynamicGroup($eProduct, 'profile');
				$h .= '<div class="util-block bg-background-light product-write-profile-details">';
					$h .= '<div data-profile="'.implode(' ', Product::getProfiles('unprocessedPlant')).'">';

						$h .= $form->group(
							self::p('unprocessedPlant')->label,
							$form->dynamicField($eProduct, 'unprocessedPlant', function($d) {
								$d->autocompleteDispatch = '#product-update';
							})
						);

					$h .= '</div>';

					foreach(['unprocessedVariety', 'unprocessedSize', 'processedComposition', 'mixedFrozen', 'processedAllergen'] as $property) {

						$h .= '<div data-profile="'.implode(' ', Product::getProfiles($property)).'">';
							$h .= $form->dynamicGroup($eProduct, $property);
						$h .= '</div>';

					}

				$h .= '</div>';
			$h .= '</div>';

		}

		return $h;

	}

	private function getFieldPrices(\util\FormUi $form, Product $eProduct, string $for): string {

		$h = '<h3>'.s("Grille tarifaire").'</h3>';

		if($eProduct['composition']) {

			if($for === 'create') {
				$h .= '<div class="util-block-help">'.s("Un produit composé peut être vendu soit aux particuliers, soit aux professionnels, mais pas simultanément aux deux. Le choix que vous faites maintenant ne pourra pas être modifié par la suite, et votre produit ne pourra être composé que de produits également vendus à ce type de clientèle.").'</div>';
			}

		} else {
			$h .= '<div class="util-info">'.s("Pour une vente aux particuliers et si aucun prix de vente n'a été saisi, le prix de vente pro augmenté de la TVA sera utilisé dans ce cas, et vice-versa pour une vente aux professionnels. Ces données de base pourront toujours être personnalisées pour chaque client et vente.").'</div>';
		}

		$h .= $form->dynamicGroup($eProduct, 'vat');
		$h .= '<br/>';

		if(
			$for === 'create' or
			$eProduct['composition'] === FALSE or
			$eProduct['private']
		) {
			$h .= self::getFieldPrivate($form, $eProduct, $for);
			$h .= '<br/>';
		}

		if(
			$for === 'create' or
			$eProduct['composition'] === FALSE or
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
				($eProduct['composition'] === FALSE or $for === 'create') ? $form->dynamicField($eProduct, 'pro') : ''
			);

			$taxes = $eProduct['farm']->getSelling('hasVat') ? '/ '.CustomerUi::getTaxes(Customer::PRO) : '';
			$unit = ($eProduct['unit']->notEmpty() ? encode($eProduct['unit']['singular']) : self::p('unit')->placeholder);
			$taxesAndUnit = '<div class="input-group-addon">€ '.$taxes.' / <span data-ref="product-unit">'.$unit.'</span></div>';

			$hasDiscountPrice = ($eProduct['proPriceInitial'] ?? NULL) !== NULL;

			$inputGroup = $form->inputGroup(
				$form->dynamicField($eProduct, 'proPrice', function($d) use($eProduct, $form, $hasDiscountPrice) {
					if($hasDiscountPrice) {
						$d->default = fn() => $eProduct['proPriceInitial'];
					}
				}).
				$taxesAndUnit
			);
			$discountAddon = new PriceUi()->getDiscountLink(($eProduct['id'] ?? '').'-pro', $hasDiscountPrice);

			$h .= $form->group(
				s("Prix de base"),
				content: $inputGroup.$discountAddon,
				attributes: ['wrapper' => 'proTaxes proPrice proOrPrivatePrice']
			);

			$h .= $form->group(
				content: $form->inputGroup(
					$form->dynamicField($eProduct, 'proPriceDiscount', function($d) use($eProduct, $form, $hasDiscountPrice) {
						if($hasDiscountPrice) {
							$d->default = fn() => $eProduct['proPrice'];
						}
					})
				),
				attributes: ['wrapper' => 'proPriceDiscount', 'data-price-discount' => ($eProduct['id'] ?? '').'-pro'] + ($hasDiscountPrice ? [] : ['class' => 'hide']),
			);

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
				($eProduct['composition'] === FALSE or $for === 'create') ? $form->dynamicField($eProduct, 'private') : ''
			);

			$taxes = $eProduct['farm']->getSelling('hasVat') ? '/ '.CustomerUi::getTaxes(Customer::PRIVATE) : '';
			$unit = ($eProduct['unit']->notEmpty() ? encode($eProduct['unit']['singular']) : self::p('unit')->placeholder);

			$hasDiscountPrice = ($eProduct['privatePriceInitial'] ?? NULL) !== NULL;

			$inputGroup = $form->inputGroup(
				$form->dynamicField($eProduct, 'privatePrice', function($d) use($eProduct, $form, $hasDiscountPrice) {
					if($hasDiscountPrice) {
						$d->default = fn() => $eProduct['privatePriceInitial'];
					}
				}).
				'<div class="input-group-addon">€ '.$taxes.' / <span data-ref="product-unit">'.$unit.'</span></div>',
			);
			$discountAddon = new PriceUi()->getDiscountLink(($eProduct['id'] ?? '').'-private', $hasDiscountPrice);

			$h .= $form->group(
				s("Prix de base"),
				content: $inputGroup.$discountAddon,
				attributes: ['wrapper' => 'privatePrice proOrPrivatePrice']
			);

			$h .= $form->group(
				content: $form->dynamicField($eProduct, 'privatePriceDiscount', function($d) use($eProduct, $form, $hasDiscountPrice) {
					if($hasDiscountPrice) {
						$d->default = fn() => $eProduct['privatePrice'];
					}
				}),
				attributes: ['wrapper' => 'privatePriceDiscount', 'data-price-discount' => ($eProduct['id'] ?? '').'-private'] + ($hasDiscountPrice ? [] : ['class' => 'hide']),
			);

			if($for === 'update') {

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
			'unprocessedPlant' => s("Espèce"),
			'unprocessedVariety' => s("Variété"),
			'unprocessedSize' => s("Calibre"),
			'mixedFrozen' => s("Surgelé").'  '.self::getFrozenIcon(),
			'processedComposition' => s("Composition"),
			'processedAllergen' => s("Allergènes"),
			'profile' => '<h3>'.s("Caractéristiques").'</h3>',
			'origin' => s("Origine"),
			'description' => s("Description"),
			'quality' => s("Signe de qualité"),
			'farm' => s("Ferme"),
			'unit' => s("Unité de vente"),
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

			case 'name' :
				$d->placeholder = fn($eProduct) => $eProduct['composition'] ? s("Ex. : Panier familial") : s("Ex. : Pomme de terre");
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
				$d->placeholder = s("Non concerné");
				$d->field = 'radio';
				$d->values = [
					Product::UNPROCESSED_PLANT => s("Produit brut d'origine végétale").'  <span class="color-muted"><small>'.s("Fruits, légumes, fleurs, plants...").'</small></span>',
					Product::UNPROCESSED_ANIMAL => s("Produit brut d'origine animale").'  <span class="color-muted"><small>'.s("Viandes, oeufs, animaux vivants...").'</small></span>',
					Product::PROCESSED_FOOD => s("Produit alimentaire transformé").'  <span class="color-muted"><small>'.s("Pains, charcuteries, boissons, confitures, ...").'</small></span>',
					Product::PROCESSED_PRODUCT => s("Hygiène, santé, entretien ou cosmétique").'  <span class="color-muted"><small>'.s("Savons, lessives, ...").'</small></span>',
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

			case 'unit' :
				$d->values = fn(Product $e) => isset($e['cUnit']) ? UnitUi::getField($e['cUnit']) : $e->expects(['cUnit']);
				$d->attributes = ['group' => TRUE];
				$d->placeholder = s("&lt; Non applicable &gt;");
				$d->after = fn(\util\FormUi $form, Product $e) => $e->exists() ?
					\util\FormUi::info(s("L'unité de vente ne peut être modifiée que pour une autre unité de vente à l'unité.")) :
					\util\FormUi::info(s("Les unités de vente au poids ne peuvent pas être modifiées par la suite, vous devrez créer un autre produit si vous changez d'avis."));
				break;

			case 'private' :
			case 'pro' :
				$d->field = 'switch';
				$d->attributes += [
					'onchange' => 'Product.changeType(input, "'.$property.'")'
				];
				break;

			case 'privatePrice' :
			case 'privateStep' :
				$d->attributes += [
					'disabled' => function(\util\FormUi $form, Product $e) {
						return $e['private'] ? NULL : 'disabled';
					}
				];
				break;

			case 'privatePriceDiscount':
				$d->field = function(\util\FormUi $form, Product $eProduct) {
					return $form->number(
						$this->name,
						($eProduct['privatePriceInitial'] ?? NULL) !== NULL ? $eProduct['privatePrice'] : NULL,
						['step' => 0.01, 'disabled' => $eProduct['private'] ? NULL : 'disabled'],
					);
				};
				$d->groupLabel = FALSE;
				$d->group = function(Product $e) {
					return ['data-price-discount' => $e['product']['id'], 'class' => $e['privatePriceInitial'] !== NULL ? '' : 'hide'];
				};
				$d->prepend = s("Prix remisé");
				$d->append = function(\util\FormUi $form, Product $eProduct) {
					if($eProduct->isQuick()) {
						return NULL;
					}
					$taxes = $eProduct['farm']->getSelling('hasVat') ? '/ '.CustomerUi::getTaxes(Customer::PRIVATE) : '';
					$unit = ($eProduct['unit']->notEmpty() ? encode($eProduct['unit']['singular']) : self::p('unit')->placeholder);
					$h = '<div class="input-group-addon">€ '.$taxes.' / <span data-ref="product-unit">'.$unit.'</span></div>';

					$trash = new PriceUi()->getDiscountTrashAddon(($eProduct['id'] ?? '').'-private');
					$h .= '<div class="input-group-addon">'.$trash.'</div>';
					return $h;
				};
				break;

			case 'proPrice' :
			case 'proPackaging' :
			case 'proStep' :
				$d->attributes += [
					'disabled' => function(\util\FormUi $form, Product $e) {
						return $e['pro'] ? NULL : 'disabled';
					}
				];
				break;

			case 'proPriceDiscount':
				$d->field = function(\util\FormUi $form, Product $eProduct) {
					return $form->number(
						$this->name,
						($eProduct['proPriceInitial'] ?? NULL) !== NULL ? $eProduct['proPrice'] : NULL,
						['step' => 0.01, 'disabled' => $eProduct['pro'] ? NULL : 'disabled'],
					);
				};
				$d->groupLabel = FALSE;
				$d->group = function(Product $e) {
					return ['data-price-discount' => $e['product']['id'], 'class' => $e['proPriceInitial'] !== NULL ? '' : 'hide'];
				};
				$d->prepend = s("Prix remisé");
				$d->append = function(\util\FormUi $form, Product $eProduct) {
					if($eProduct->isQuick()) {
						return NULL;
					}

					$taxes = $eProduct['farm']->getSelling('hasVat') ? '/ '.CustomerUi::getTaxes(Customer::PRO) : '';
					$unit = ($eProduct['unit']->notEmpty() ? encode($eProduct['unit']['singular']) : self::p('unit')->placeholder);
					$h = '<div class="input-group-addon">€ '.$taxes.' / <span data-ref="product-unit">'.$unit.'</span></div>';

					$trash = new PriceUi()->getDiscountTrashAddon(($eProduct['id'] ?? '').'-pro');
					$h .= '<div class="input-group-addon">'.$trash.'</div>';

					return $h;
				};
				break;

			case 'quality' :
				$d->field = 'select';
				$d->values = \farm\FarmUi::getQualities();
				$d->placeholder = s("Aucun");
				break;

			case 'unprocessedSize' :
				$d->attributes = [
					'placeholder' => s("Ex. : 14-21 cm"),
				];
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
