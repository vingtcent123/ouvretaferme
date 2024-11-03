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

		$d->placeholder ??= s("Tapez un nom de produit...");
		$d->multiple = $multiple;
		$d->group += ['wrapper' => 'product'];

		$d->autocompleteUrl = '/selling/product:query';
		$d->autocompleteResults = function(Product $e) {
			return self::getAutocomplete($e);
		};

	}

	public static function getAutocomplete(Product $eProduct): array {

		\Asset::css('media', 'media.css');

		$infos = [];

		if($eProduct['size']) {
			$infos[] = encode($eProduct['size']);
		}

		$item = self::getVignette($eProduct, '2.5rem');
		$item .= '<div>';
			$item .= encode($eProduct->getName());
			if($eProduct['unit']) {
				$item .= ' / '.\main\UnitUi::getSingular($eProduct['unit']);
			}
			$item .= '<br/>';
			if($infos) {
				$item .= '<small class="color-muted">'.implode(' | ', $infos).'</small>';
			}
		$item .= '</div>';

		return [
			'value' => $eProduct['id'],
			'itemHtml' => $item,
			'itemText' => $eProduct->getName()
		];

	}

	public static function getPanelHeader(Product $eProduct): string {

		return '<div class="panel-header-subtitle">'.self::getVignette($eProduct, '2rem').'  '.encode($eProduct->getName()).'</div>';

	}

	public function getSearch(\farm\Farm $eFarm, \Search $search): string {

		$form = new \util\FormUi();

		$h = '<div id="product-search" class="util-block-search stick-xs '.($search->empty(['category']) ? 'hide' : '').'">';

			$h .= $form->openAjax(\farm\FarmUi::urlSellingProduct($eFarm), ['method' => 'get', 'id' => 'form-search']);
				$h .= $form->hidden('category', $search->get('category'));
				$h .= '<div>';
					$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom du produit")]);
					$h .= $form->text('plant', $search->get('plant'), ['placeholder' => s("Espèce")]);
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.\farm\FarmUi::urlSellingProduct($eFarm).'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getList(\farm\Farm $eFarm, \Collection $cProduct, array $products, \Collection $cCategory, \Search $search) {

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

		if($cProduct->empty()) {

			if(
				$search->get('category')->empty() and
				$cCategory->notEmpty()
			) {
				$h .= '<div class="util-info">'.s("Sélectionnez une catégorie pour voir les produits associés !").'</div>';
			} else {
				$h .= '<div class="util-info">'.s("Il n'y a aucun produit à afficher.").'</div>';
			}

			return $h;

		}

		$year = date('Y');
		$yearBefore = $year - 1;

		$displayStock = $cProduct->match(fn($eProduct) => $eProduct['stock'] !== NULL);

		$h .= '<div class="product-item-wrapper stick-xs">';

		$h .= '<table class="product-item-table tr-bordered tr-even">';

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
						$h .= '<th rowspan="2" class="text-end">'.$search->linkSort('stock', s("Stock"), SORT_DESC).'</th>';
					}
					$h .= '<th rowspan="2">'.s("Unité").'</th>';
					$h .= '<th colspan="2" class="text-center highlight hide-xs-down">'.s("Ventes").'</th>';
					$h .= '<th colspan="2" class="text-center highlight">'.s("Prix de base").'</th>';
					if($eFarm->getSelling('hasVat')) {
						$h .= '<th rowspan="2" class="text-center product-item-vat">'.s("TVA").'</th>';
					}
					$h .= '<th rowspan="2" class="product-item-plant">'.self::p('plant')->label.'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Activé").'</th>';
					$h .= '<th rowspan="2"></th>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<th class="text-end highlight-stick-right hide-xs-down">'.$year.'</th>';
					$h .= '<th class="text-end highlight-stick-left product-item-year-before hide-xs-down">'.$yearBefore.'</th>';
					$h .= '<th class="text-end highlight-stick-right">'.s("particulier").'</th>';
					$h .= '<th class="text-end highlight-stick-left">'.s("pro").'</th>';
				$h .= '</tr>';

			$h .= '</thead>';

			$h .= '<tbody>';

			foreach($cProduct as $eProduct) {

				$eItemTotal = $eProduct['eItemTotal'];

				$h .= '<tr>';

					$h .= '<td class="td-checkbox">';
						$h .= '<label>';
							$h .= '<input type="checkbox" name="batch[]" value="'.$eProduct['id'].'" oninput="Product.changeSelection()"/>';
						$h .= '</label>';
					$h .= '</td>';
				
					$h .= '<td class="product-item-vignette">';
						$h .= (new \media\ProductVignetteUi())->getCamera($eProduct, size: '4rem');
					$h .= '</td>';

					$h .= '<td class="product-item-name">';
						$h .= self::getInfos($eProduct);
					$h .= '</td>';

					if($displayStock) {
						$h .= '<td class="product-item-stock text-end">';
							if($eProduct['stock'] !== NULL) {
								$h .= StockUi::getExpired($eProduct);
								$h .= '<a href="'.\farm\FarmUi::urlSellingStock($eFarm).'" title="'.StockUi::getDate($eProduct['stockUpdatedAt']).'">'.$eProduct['stock'].'</a>';
							}
						$h .= '</td>';
					}

					$h .= '<td class="product-item-unit">';
						$h .= \main\UnitUi::getSingular($eProduct['unit']);
					$h .= '</td>';

					$h .= '<td class="text-end highlight-stick-right hide-xs-down">';
						if($eItemTotal->notEmpty() and $eItemTotal['year']) {
							$amount = \util\TextUi::money($eItemTotal['year'], precision: 0);
							$h .= $eFarm->canAnalyze() ? '<a href="/selling/product:analyze?id='.$eProduct['id'].'&year='.$year.'">'.$amount.'</a>' : $amount;
						} else {
							$h .= '-';
						}
					$h .= '</td>';

					$h .= '<td class="text-end highlight-stick-left hide-xs-down customer-item-year-before">';
						if($eItemTotal->notEmpty() and $eItemTotal['yearBefore']) {
							$amount = \util\TextUi::money($eItemTotal['yearBefore'], precision: 0);
							$h .= $eFarm->canAnalyze() ? '<a href="/selling/product:analyze?id='.$eProduct['id'].'&year='.$yearBefore.'">'.$amount.'</a>' : $amount;
						} else {
							$h .= '-';
						}
					$h .= '</td>';

					$h .= '<td class="product-item-price highlight-stick-right text-end">';
						if($eProduct['private'] === FALSE) {
							$h .= '-';
						} else {

							$taxes = $eFarm->getSelling('hasVat') ? ' <span class="util-annotation">'.CustomerUi::getTaxes(Customer::PRIVATE).'</span>' : '';

							if($eProduct['privatePrice']) {
								$value = \util\TextUi::money($eProduct['privatePrice']).$taxes;
							} else if($eProduct['proPrice']) {
								$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les professionnels augmenté de la TVA.").'">'.\Asset::icon('magic').' ';
									$value .= \util\TextUi::money($eProduct->calcPrivateMagicPrice($eFarm->getSelling('hasVat'))).$taxes;
								$value .= '</span>';
							} else {
								$value = '-';
							}

							$h .= $eProduct->quick('privatePrice', $value);

						}
					$h .= '</td>';

					$h .= '<td class="product-item-price highlight-stick-left text-end">';
						if($eProduct['pro'] === FALSE) {
							$h .= '-';
						} else {

							$taxes = $eFarm->getSelling('hasVat') ? ' <span class="util-annotation">'.CustomerUi::getTaxes(Customer::PRO).'</span>' : '';

							if($eProduct['proPrice']) {
								$value = \util\TextUi::money($eProduct['proPrice']).$taxes;
							} else if($eProduct['privatePrice']) {
								$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les particuliers diminué de la TVA.").'">'.\Asset::icon('magic').' ';
									$value .= \util\TextUi::money($eProduct->calcProMagicPrice($eFarm->getSelling('hasVat'))).$taxes;
								$value .= '</span>';
							} else {
								$value = '-';
							}

							$h .= $eProduct->quick('proPrice', $value);

						}
					$h .= '</td>';

					if($eFarm->getSelling('hasVat')) {

						$h .= '<td class="text-center product-item-vat">';
							$h .= s("{value} %", \Setting::get('selling\vatRates')[$eProduct['vat']]);
						$h .= '</td>';

					}

					$h .= '<td class="product-item-plant">';
						if($eProduct['plant']->notEmpty()) {
							$h .= \plant\PlantUi::link($eProduct['plant']);
						}
					$h .= '</td>';

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

	public static function getInfos(Product $eProduct, bool $includeStock = FALSE, bool $includeQuality = TRUE): string {

		$h = '<a href="/produit/'.$eProduct['id'].'">'.encode($eProduct->getName()).'</a>';
		$more = [];

		if($eProduct['size']) {
			$more[] = '<span><u>'.encode($eProduct['size']).'</u></span>';
		}

		if($includeQuality) {

			if($eProduct['quality'] !== NULL) {
				$more[] = \farm\FarmUi::getQualityLogo($eProduct['quality'], '1.5rem');
			}

		}

		if($includeStock) {

			if($eProduct['stock'] !== NULL) {
				$more[] .= '<span title="'.\selling\StockUi::getDate($eProduct['stockUpdatedAt']).'"><u>'.s("{value} en stock", \selling\StockUi::getExpired($eProduct).' '.\main\UnitUi::getValue(round($eProduct['stock']), $eProduct['unit'], short: TRUE)).'</u></span>';
			}

		}

		if($more) {
			$h .= '<div class="product-item-infos">'.implode('', $more).'</div>';
		}

		return $h;


	}

	public static function getVignette(Product $eProduct, string $size): string {

		$eProduct->expects(['id', 'vignette']);

		$ui = new \media\ProductVignetteUi();

		$class = 'media-circle-view ';
		$style = '';

		if($eProduct['vignette'] === NULL) {

			$class .= ' media-vignette-default';
			$content = mb_substr($eProduct->getName(), 0, 2);

		} else {

			$format = $ui->convertToFormat($size);

			$style .= 'background-image: url('.$ui->getUrlByElement($eProduct, $format).');';
			$content = '';

		}

		return '<div class="'.$class.'" style="'.$ui->getSquareCss($size).'; '.$style.'">'.encode($content).'</div>';

	}

	public function toggle(Product $eProduct) {

		return \util\TextUi::switch([
			'id' => 'product-switch-'.$eProduct['id'],
			'data-ajax' => $eProduct->canWrite() ? '/selling/product:doUpdateStatus' : NULL,
			'post-id' => $eProduct['id'],
			'post-status' => ($eProduct['status'] === Product::ACTIVE) ? Product::INACTIVE : Product::ACTIVE
		], $eProduct['status'] === Product::ACTIVE);

	}

	public function display(Product $eProduct, \Collection $cItemYear): string {

		$h = '<div class="util-vignette">';

			$h .= (new \media\ProductVignetteUi())->getCamera($eProduct, size: '6rem');

			$h .= '<div>';
				$h .= '<div class="util-action">';
					$h .= '<h1>'.encode($eProduct->getName()).'</h1>';
					$h .= '<div>';
						$h .= $this->getUpdate($eProduct, 'btn-primary');
					$h .= '</div>';
				$h .= '</div>';
				$h .= '<div class="util-action-subtitle">';
					$h .= $this->toggle($eProduct);
				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';

		$h .= '<div class="util-block stick-xs">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.self::p('plant')->label.'</dt>';
				$h .= '<dd>'.($eProduct['plant']->empty() ? '' : \plant\PlantUi::link($eProduct['plant'])).'</dd>';
				$h .= '<dt>'.self::p('unit')->label.'</dt>';
				$h .= '<dd>'.($eProduct['unit'] ? self::p('unit')->values[$eProduct['unit']] : \Asset::icon('slash')).'</dd>';
				$h .= '<dt>'.self::p('size')->label.'</dt>';
				$h .= '<dd>'.($eProduct['size'] ? encode($eProduct['size']) : '').'</dd>';
				$h .= '<dt>'.self::p('quality')->label.'</dt>';
				$h .= '<dd>'.($eProduct['quality'] ? \farm\FarmUi::getQualityLogo($eProduct['quality'], '1.5rem').' '.self::p('quality')->values[$eProduct['quality']] : '').'</dd>';
				if($eProduct['category']->notEmpty()) {
					$h .= '<dt>'.self::p('category')->label.'</dt>';
					$h .= '<dd>'.encode($eProduct['category']['name']).'</dd>';
				}
				if($eProduct['farm']->getSelling('hasVat')) {
					$h .= '<dt>'.self::p('vat')->label.'</dt>';
					$h .= '<dd>'.s("{value} %", \Setting::get('selling\vatRates')[$eProduct['vat']]).'</dd>';
				}
			$h .= '</dl>';
		$h .= '</div>';

		if(
			$eProduct['farm']->canAnalyze() and
			$cItemYear->notEmpty()
		) {

			$h .= (new AnalyzeUi())->getProductYear($cItemYear, NULL, $eProduct);

		}

		return $h;

	}

	public function getTabs(Product $eProduct, \Collection $cGrid, \Collection $cItemLast): string {

		$h = '<div class="tabs-h" id="product-tabs" onrender="'.encode('Lime.Tab.restore(this, "product-grid")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="product-grid" onclick="Lime.Tab.select(this)">'.s("Grille tarifaire").'</a>';
				$h .= '<a class="tab-item" data-tab="product-sales" onclick="Lime.Tab.select(this)">'.s("Dernières ventes").'</a>';
			$h .= '</div>';

			$h .= '<div class="tab-panel selected" data-tab="product-grid">';
				$h .= (new \selling\ProductUi())->getBaseGrid($eProduct);
				$h .= (new \selling\GridUi())->getGridByProduct($eProduct, $cGrid);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="product-sales">';
				$h .= (new \selling\ItemUi())->getByProduct($cItemLast);
			$h .= '</div>';

		$h .= '</div>';

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
						if($eProduct['privatePrice']) {
							$value = \util\TextUi::money($eProduct['privatePrice']);
							$value .= ' '.$taxes.' / '.\main\UnitUi::getSingular($eProduct['unit'], by: TRUE);
						} else if($eProduct['proPrice']) {
							$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les professionnels augmenté de la TVA, cliquez pour le personnaliser.").'">'.\Asset::icon('magic').' ';
								$value .= \util\TextUi::money($eProduct->calcPrivateMagicPrice($eProduct['farm']->getSelling('hasVat')));
								$value .= ' '.$taxes.' / '.\main\UnitUi::getSingular($eProduct['unit'], by: TRUE);
							$value .= '</span>';
						} else {
							$value = '/';
						}
						$h .= $eProduct->quick('privatePrice', $value);
					$h .= '</dd>';

					$h .= '<dt>'.self::p('privateStep')->label.'</dt>';
					$h .= '<dd>';
						$value = \main\UnitUi::getValue($eProduct['privateStep'] ?? \shop\ProductUi::getDefaultPrivateStep($eProduct), $eProduct['unit']);
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
						if($eProduct['proPrice']) {
							$value = \util\TextUi::money($eProduct['proPrice']);
							$value .= ' '.$taxes.' / '.\main\UnitUi::getSingular($eProduct['unit'], by: TRUE);
						} else if($eProduct['privatePrice']) {
							$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les particuliers diminué de la TVA, cliquez pour le personnaliser.").'">'.\Asset::icon('magic').' ';
								$value .= \util\TextUi::money($eProduct->calcProMagicPrice($eProduct['farm']->getSelling('hasVat')));
								$value .= ' '.$taxes.' / '.\main\UnitUi::getSingular($eProduct['unit'], by: TRUE);
							$value .= '</span>';
						} else {
							$value = '/';
						}
						$h .= $eProduct->quick('proPrice', $value);
					$h .= '</dd>';

					if($eProduct['proPackaging']) {
						$h .= '<dt>'.self::p('proPackaging')->label.'</dt>';
						$h .= '<dd>';
							$value = \main\UnitUi::getValue($eProduct['proPackaging'], $eProduct['unit']);
							$h .= $eProduct->quick('proPackaging', $value);
						$h .= '</dd>';
					}

					$h .= '<dt>'.self::p('proStep')->label.'</dt>';
					$h .= '<dd>';
						$value = \main\UnitUi::getValue($eProduct['proStep'] ?? \shop\ProductUi::getDefaultProStep($eProduct), $eProduct['unit']);
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

	public function create(\farm\Farm $eFarm, \Collection $cCategory): \Panel {

		$form = new \util\FormUi();

		$eProduct = new Product([
			'farm' => $eFarm,
			'cCategory' => $cCategory,
			'quality' => $eFarm['quality'],
			'vat' => $eFarm->getSelling('defaultVat'),
			'private' => TRUE,
			'pro' => TRUE,
			'unit' => Product::model()->getDefaultValue('unit')
		]);

		$h = '';

		$h .= $form->openAjax('/selling/product:doCreate', ['id' => 'product-create']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eFarm, TRUE)
			);

			$h .= $form->group(
				self::p('plant')->label,
				$form->dynamicField($eProduct, 'plant', function($d) {
					$d->autocompleteDispatch = '#product-create';
				})
			);

			$h .= $form->dynamicGroup($eProduct, 'name*');

			if($eProduct['cCategory']->notEmpty()) {
				$h .= $form->dynamicGroup($eProduct, 'category');
			}

			$h .= $form->dynamicGroups($eProduct, ['unit', 'variety', 'size', 'description', 'quality', 'vat'], [
				'unit' => function(\PropertyDescriber $d) {
					$d->attributes += [
						'callbackRadioAttributes' => function() {
							return ['oninput' => 'Product.changeUnit(this, "product-unit")'];
						}
					];
				}
			]);

			$h .= '<br/>';
			$h .= self::getFieldPrices($form, $eProduct, 'create');

			$h .= $form->group(
				content: $form->submit(s("Créer le produit"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Ajouter un produit"),
			body: $h
		);

	}

	public function update(Product $eProduct): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/product:doUpdate', ['id' => 'product-update']);

			$h .= $form->hidden('id', $eProduct['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eProduct['farm'], TRUE)
			);

			$h .= $form->group(
				self::p('plant')->label,
				$form->dynamicField($eProduct, 'plant', function($d) {
					$d->autocompleteDispatch = '#product-update';
				})
			);

			$h .= $form->dynamicGroup($eProduct, 'name');

			if($eProduct['cCategory']->notEmpty()) {
				$h .= $form->dynamicGroup($eProduct, 'category');
			}

			$h .= $form->group(
				self::p('unit')->label,
				$form->fake(mb_ucfirst($eProduct['unit'] ? \main\UnitUi::getSingular($eProduct['unit']) : self::p('unit')->placeholder))
			);
			$h .= $form->dynamicGroups($eProduct, ['variety', 'size', 'description', 'quality', 'vat']);

			$h .= '<br/>';
			$h .= self::getFieldPrices($form, $eProduct, 'update');

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un produit"),
			body: $h
		);

	}

	private static function getFieldPrices(\util\FormUi $form, Product $eProduct, string $for): string {

		$h = '<h3>'.s("Clientèle").'</h3>';
		$h .= '<div class="util-info">'.s("Pour une vente aux particuliers et si aucun prix de vente n'a été saisi, le prix de vente pro augmenté de la TVA sera utilisé dans ce cas, et vice-versa pour une vente aux professionnels. Ces données de base pourront toujours être personnalisées pour chaque client et vente.").'</div>';

		$h .= self::getFieldPrivate($form, $eProduct, $for);
		$h .= '<br/>';
		$h .= self::getFieldPro($form, $eProduct, $for);
		$h .= '<br/>';

		return $h;

	}

	private static function getFieldPro(\util\FormUi $form, Product $eProduct, string $for): string {

		$h = '<div class="util-block bg-background-light" data-wrapper="'.Customer::PRO.'-block">';

			$h .= $form->group(
				'<h4>'.self::p('pro')->label.'</h4>',
				$form->dynamicField($eProduct, 'pro')
			);

			$taxes = $eProduct['farm']->getSelling('hasVat') ? '/ '.CustomerUi::getTaxes(Customer::PRO) : '';
			$unit = ($eProduct['unit'] ? self::p('unit')->values[$eProduct['unit']] : self::p('unit')->placeholder);

			$h .= $form->group(
				s("Prix de base"),
				$form->inputGroup(
					$form->dynamicField($eProduct, 'proPrice').
					'<div class="input-group-addon">€ '.$taxes.' / <span data-ref="product-unit">'.$unit.'</span></div>'
				),
				['wrapper' => 'proTaxes proPrice']
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
				$form->dynamicField($eProduct, 'private')
			);

			$taxes = $eProduct['farm']->getSelling('hasVat') ? '/ '.CustomerUi::getTaxes(Customer::PRIVATE) : '';
			$unit = ($eProduct['unit'] ? self::p('unit')->values[$eProduct['unit']] : self::p('unit')->placeholder);

			$h .= $form->group(
				s("Prix de base"),
				$form->inputGroup(
					$form->dynamicField($eProduct, 'privatePrice').
					'<div class="input-group-addon">€ '.$taxes.' / <span data-ref="product-unit">'.$unit.'</span></div>'
				),
				['wrapper' => 'privatePrice']
			);

			if($for === 'update') {

				$h .= $form->group(
					self::p('privateStep')->label,
					$form->inputGroup(
						$form->dynamicField($eProduct, 'privateStep', function($d) use ($eProduct) {
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

	public static function p(string $property): \PropertyDescriber {

		$d = Product::model()->describer($property, [
			'category' => s("Catégorie"),
			'plant' => s("Espèce"),
			'vignette' => s("Vignette"),
			'name' => s("Nom du produit"),
			'variety' => s("Variété"),
			'size' => s("Calibre"),
			'description' => s("Description"),
			'quality' => s("Signe de qualité"),
			'farm' => s("Ferme"),
			'unit' => s("Unité de vente"),
			'private' => s("Vente aux clients particuliers"),
			'privatePrice' => s("Prix particulier"),
			'privateStep' => s("Multiple de vente"),
			'pro' => s("Vente aux clients professionnels"),
			'proPrice' => s("Prix professionnel"),
			'proPackaging' => s("Colis de base"),
			'proStep' => s("Multiple de vente"),
			'vat' => s("Taux de TVA"),
			'statut' => s("Statut"),
		]);

		switch($property) {

			case 'id' :
				(new ProductUi())->query($d);
				break;

			case 'name' :
				$d->placeholder = s("Ex. : Pomme de terre");
				break;

			case 'category' :
				$d->placeholder = s("Non catégorisé");
				$d->field = 'radio';
				$d->values = fn(Product $e) => $e['cCategory'] ?? $e->expects(['cCategory']);
				$d->attributes = [
					'columns' => 2,
				];
				break;

			case 'plant' :
				$d->after = \util\FormUi::info(s("Sélectionnez l'espèce à laquelle est rattaché ce produit s'il est directement tiré du champ."));
				$d->autocompleteBody = function(\util\FormUi $form, Product $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id']
					];
				};
				(new \plant\PlantUi())->query($d);
				break;

			case 'unit' :
				$d->values = \main\UnitUi::getList(noWrap: FALSE);
				$d->attributes = [
					'columns' => 3
				];
				$d->after = \util\FormUi::info(s("L'unité de vente ne pourra pas être modifiée par la suite. Si vous choisissez de modifier le conditionnement, vous devrez créer un autre produit."));
				break;

			case 'variety' :
				$d->after = \util\FormUi::info(s("N'indiquez la variété que si elle apporte une information supplémentaire utile à vos clients par rapport au nom du produit que vous souhaitez communiquer à vos clients."));
				$d->placeholder = s("Ex. : Chérie");
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

			case 'proPrice' :
			case 'proPackaging' :
			case 'proStep' :
				$d->attributes += [
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

			case 'size' :
				$d->attributes = [
					'placeholder' => s("Ex. : 14-21 cm"),
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
