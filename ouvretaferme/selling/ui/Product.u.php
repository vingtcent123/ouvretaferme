<?php
namespace selling;

class ProductUi {

	public function __construct() {

		\Asset::css('selling', 'product.css');
		\Asset::js('selling', 'product.js');

	}

	public static function link(Product $eProduct, bool $newTab = FALSE): string {
		return '<a href="'.self::url($eProduct).'" '.($newTab ? 'target="_blank"' : '').'>'.$eProduct->getName().'</a>';
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

	public function getPanelHeader(Product $eProduct): string {

		return '<div class="product-panel-header">'.encode($eProduct->getName()).'</div>';

	}

	public function getSearch(\farm\Farm $eFarm, \Search $search): string {

		$form = new \util\FormUi();

		$h = '<div id="product-search" class="util-block-search '.($search->empty() ? 'hide' : '').'">';

			$h .= $form->openAjax(\farm\FarmUi::urlSellingProduct($eFarm), ['method' => 'get', 'id' => 'form-search']);
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

	public function getList(\farm\Farm $eFarm, \Collection $cProduct, \Search $search) {

		if($cProduct->empty()) {
			return '<div class="util-info">'.s("Il n'y a aucun produit à afficher.").'</div>';
		}

		$year = date('Y');
		$yearBefore = $year - 1;

		$h = '<div class="product-item-wrapper stick-xs">';

		$h .= '<table class="product-item-table tr-bordered tr-even">';

			$h .= '<thead>';

				$h .= '<tr>';
					$h .= '<th rowspan="2" class="product-item-vignette"></th>';
					$h .= '<th rowspan="2" colspan="2">'.$search->linkSort('name', s("Nom")).'</th>';
					$h .= '<th colspan="2" class="text-center">'.s("Ventes").'</th>';
					$h .= '<th colspan="2" class="text-center">'.s("Prix de base").'</th>';
					if($eFarm['selling']['hasVat']) {
						$h .= '<th rowspan="2" class="text-center product-item-vat">'.s("TVA").'</th>';
					}
					$h .= '<th rowspan="2" class="product-item-plant">'.self::p('plant')->label.'</th>';
					$h .= '<th rowspan="2" class="text-center">'.s("Activé").'</th>';
					$h .= '<th rowspan="2"></th>';
				$h .= '</tr>';

				$h .= '<tr>';
					$h .= '<th class="text-end">'.$year.'</th>';
					$h .= '<th class="text-end product-item-year-before">'.$yearBefore.'</th>';
					$h .= '<th class="text-end">'.s("particulier").'</th>';
					$h .= '<th class="text-end">'.s("pro").'</th>';
				$h .= '</tr>';

			$h .= '</thead>';

			$h .= '<tbody>';

			foreach($cProduct as $eProduct) {

				$eItemTotal = $eProduct['eItemTotal'];

				$h .= '<tr>';
				
					$h .= '<td class="product-item-vignette">';
						$h .= (new \media\ProductVignetteUi())->getCamera($eProduct, size: '4rem');
					$h .= '</td>';

					$h .= '<td class="product-item-name">';
						$h .= $this->getInfos($eProduct);
					$h .= '</td>';

					$h .= '<td class="product-item-unit">';
						$h .= \main\UnitUi::getSingular($eProduct['unit']);
					$h .= '</td>';

					$h .= '<td class="text-end">';
						if($eItemTotal->notEmpty() and $eItemTotal['year']) {
							$amount = \util\TextUi::money($eItemTotal['year'], precision: 0);
							$h .= $eFarm->canAnalyze() ? '<a href="/selling/product:analyze?id='.$eProduct['id'].'&year='.$year.'">'.$amount.'</a>' : $amount;
						} else {
							$h .= '-';
						}
					$h .= '</td>';

					$h .= '<td class="text-end customer-item-year-before">';
						if($eItemTotal->notEmpty() and $eItemTotal['yearBefore']) {
							$amount = \util\TextUi::money($eItemTotal['yearBefore'], precision: 0);
							$h .= $eFarm->canAnalyze() ? '<a href="/selling/product:analyze?id='.$eProduct['id'].'&year='.$yearBefore.'">'.$amount.'</a>' : $amount;
						} else {
							$h .= '-';
						}
					$h .= '</td>';

					$h .= '<td class="product-item-price text-end">';
						if($eProduct['private'] === FALSE) {
							$h .= '<span class="color-muted">'.s("non vendu").'</span>';
						} else {

							$taxes = $eFarm['selling']['hasVat'] ? CustomerUi::getTaxes(Customer::PRIVATE) : '';

							if($eProduct['privatePrice']) {
								$value = \util\TextUi::money($eProduct['privatePrice']).$taxes;
							} else if($eProduct['proPrice']) {
								$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les pros augmenté de la TVA.").'">'.\Asset::icon('magic').' ';
									$value .= \util\TextUi::money($eProduct['proPrice'] + $eProduct->calcProVat()).$taxes;
								$value .= '</span>';
							} else {
								$value = '-';
							}

							$h .= $eProduct->quick('privatePrice', $value);

						}
					$h .= '</td>';

					$h .= '<td class="product-item-price text-end">';
						if($eProduct['pro'] === FALSE) {
							$h .= '<span class="color-muted">'.s("non vendu").'</span>';
						} else {

							$taxes = $eFarm['selling']['hasVat'] ? CustomerUi::getTaxes(Customer::PRO) : '';

							if($eProduct['proPrice']) {
								$value = \util\TextUi::money($eProduct['proPrice']).$taxes;
							} else if($eProduct['privatePrice']) {
								$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les particuliers diminué de la TVA.").'">'.\Asset::icon('magic').' ';
									$value .= \util\TextUi::money($eProduct['privatePrice'] - $eProduct->calcPrivateVat()).$taxes;
								$value .= '</span>';
							} else {
								$value = '-';
							}

							$h .= $eProduct->quick('proPrice', $value);

						}
					$h .= '</td>';

					if($eFarm['selling']['hasVat']) {

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

		return $h;

	}

	public function getInfos(Product $eProduct): string {

		$h = '<a href="/produit/'.$eProduct['id'].'">'.encode($eProduct->getName()).'</a>';
		$more = [];

		if($eProduct['size']) {
			$more[] = '<span>'.s("Calibre {value}", '<u>'.encode($eProduct['size']).'</u>').'</span>';
		}

		if($eProduct['quality'] !== NULL) {
			$more[] = \farm\FarmUi::getQualityLogo($eProduct['quality'], '1.5rem');
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

		return '<div class="'.$class.'" style="'.$ui->getSquareCss($size).'; '.$style.'">'.$content.'</div>';

	}

	public function toggle(Product $eProduct) {

		return \util\TextUi::switch([
			'id' => 'product-switch-'.$eProduct['id'],
			'data-ajax' => $eProduct->canWrite() ? '/selling/product:doUpdateStatus' : NULL,
			'post-id' => $eProduct['id'],
			'post-status' => ($eProduct['status'] === Product::ACTIVE) ? Product::INACTIVE : Product::ACTIVE
		], $eProduct['status'] === Product::ACTIVE);

	}

	public function display(Product $eProduct, \Collection $cItemTurnover): string {

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
				$h .= '<dt>'.self::p('quality')->label.'</dt>';
				$h .= '<dd>'.($eProduct['quality'] ? \farm\FarmUi::getQualityLogo($eProduct['quality'], '1.5rem').' '.self::p('quality')->values[$eProduct['quality']] : '').'</dd>';
				$h .= '<dt>'.self::p('size')->label.'</dt>';
				$h .= '<dd>'.($eProduct['size'] ? encode($eProduct['size']) : '').'</dd>';
				$h .= '<dt>'.self::p('unit')->label.'</dt>';
				$h .= '<dd>'.($eProduct['unit'] ? self::p('unit')->values[$eProduct['unit']] : \Asset::icon('slash')).'</dd>';
				if($eProduct['farm']['selling']['hasVat']) {
					$h .= '<dt>'.self::p('vat')->label.'</dt>';
					$h .= '<dd>'.s("{value} %", \Setting::get('selling\vatRates')[$eProduct['vat']]).'</dd>';
				}
			$h .= '</dl>';
		$h .= '</div>';

		if(
			$eProduct['farm']->canAnalyze() and
			$cItemTurnover->notEmpty()
		) {

			$h .= (new AnalyzeUi())->getProductTurnover($cItemTurnover, NULL, $eProduct);

		}

		return $h;

	}

	public function getTabs(Product $eProduct, \farm\Farm $eFarm, \Collection $cGrid, \Collection $cItemLast): string {

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
				$h .= (new \selling\ItemUi())->getByProduct($eFarm, $cItemLast);
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

					$taxes = $eProduct['farm']['selling']['hasVat'] ? CustomerUi::getTaxes(Customer::PRIVATE) : '';

					$h .= '<dt>'.s("Prix de base").'</dt>';
					$h .= '<dd>';
						if($eProduct['privatePrice']) {
							$value = \util\TextUi::money($eProduct['privatePrice']);
							$value .= ' '.$taxes.' / '.\main\UnitUi::getSingular($eProduct['unit'], by: TRUE);
						} else if($eProduct['proPrice']) {
							$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les pros augmenté de la TVA, cliquez pour le personnaliser.").'">'.\Asset::icon('magic').' ';
								$value .= \util\TextUi::money($eProduct['proPrice'] + $eProduct->calcProVat());
								$value .= ' '.$taxes.' / '.\main\UnitUi::getSingular($eProduct['unit'], by: TRUE);
							$value .= '</span>';
						} else {
							$value = '/';
						}
						$h .= $eProduct->quick('privatePrice', $value);
					$h .= '</dd>';
					$h .= '<dt>'.self::p('privateStep')->label.'</dt>';
					$h .= '<dd>';
						if($eProduct['privateStep']) {
							$value = \main\UnitUi::getValue($eProduct['privateStep'], $eProduct['unit']);
						} else {
							$value = '/';
						}
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

					$taxes = $eProduct['farm']['selling']['hasVat'] ? CustomerUi::getTaxes(Customer::PRO) : '';

					$h .= '<dt>'.s("Prix de base").'</dt>';
					$h .= '<dd>';
						if($eProduct['proPrice']) {
							$value = \util\TextUi::money($eProduct['proPrice']);
							$value .= ' '.$taxes.' / '.\main\UnitUi::getSingular($eProduct['unit'], by: TRUE);
						} else if($eProduct['privatePrice']) {
							$value = '<span class="color-muted" title="'.s("Prix calculé à partir du prix pour les particuliers diminué de la TVA, cliquez pour le personnaliser.").'">'.\Asset::icon('magic').' ';
								$value .= \util\TextUi::money($eProduct['privatePrice'] - $eProduct->calcPrivateVat());
								$value .= ' '.$taxes.' / '.\main\UnitUi::getSingular($eProduct['unit'], by: TRUE);
							$value .= '</span>';
						} else {
							$value = '/';
						}
						$h .= $eProduct->quick('proPrice', $value);
					$h .= '</dd>';
					$h .= '<dt>'.self::p('proPackaging')->label.'</dt>';
					$h .= '<dd>';
						if($eProduct['proPackaging']) {
							$value = \main\UnitUi::getValue($eProduct['proPackaging'], $eProduct['unit']);
						} else {
							$value = '/';
						}
						$h .= $eProduct->quick('proPackaging', $value);
					$h .= '</dd>';

				} else {

					$h .= '<div class="color-muted">'.s("Pas de vente aux pros").'</div>';

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
		$h .= '</div>';

		return $h;

	}

	public function create(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();

		$eProduct = new Product([
			'farm' => $eFarm,
			'quality' => $eFarm['quality'],
			'vat' => $eFarm['selling']['defaultVat'],
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

			$h .= $form->dynamicGroups($eProduct, ['name*', 'unit', 'variety', 'size', 'description', 'quality', 'vat'], [
				'unit' => function(\PropertyDescriber $d) {
					$d->attributes += [
						'callbackRadioAttributes' => function() {
							return ['oninput' => 'Product.changeUnit(this, "product-unit")'];
						}
					];
				}
			]);

			$h .= '<br/>';
			$h .= self::getFieldPrices($form, $eProduct);

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

			$h .= $form->dynamicGroups($eProduct, ['name']);
			$h .= $form->group(
				self::p('unit')->label,
				'<div class="form-control disabled">'.mb_ucfirst($eProduct['unit'] ? \main\UnitUi::getSingular($eProduct['unit']) : self::p('unit')->placeholder).'</div>'
			);
			$h .= $form->dynamicGroups($eProduct, ['variety', 'size', 'description', 'quality', 'vat']);

			$h .= '<br/>';
			$h .= self::getFieldPrices($form, $eProduct);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un produit"),
			body: $h
		);

	}

	private static function getFieldPrices(\util\FormUi $form, Product $eProduct): string {

		$eProduct->expects([
			'farm' => ['selling']
		]);

		$h = '<h3>'.s("Clientèle").'</h3>';
		$h .= '<div class="util-info">'.s("Pour une vente aux particuliers et si aucun prix de vente n'a été saisi, le prix de vente pro augmenté de la TVA sera utilisé dans ce cas, et vice-versa pour une vente aux pros. Ces données de base pourront toujours être personnalisées pour chaque client et vente.").'</div>';

		$h .= self::getFieldPrivate($form, $eProduct);
		$h .= '<br/>';
		$h .= self::getFieldPro($form, $eProduct);
		$h .= '<br/>';

		return $h;

	}

	private static function getFieldPro(\util\FormUi $form, Product $eProduct): string {

		$h = '<div class="util-block-dark" data-wrapper="'.Customer::PRO.'-block">';

			$h .= $form->group(
				'<h4>'.self::p('pro')->label.'</h4>',
				$form->dynamicField($eProduct, 'pro')
			);

			$taxes = $eProduct['farm']['selling']['hasVat'] ? '/ '.CustomerUi::getTaxes(Customer::PRO) : '';
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

		$h .= '</div>';

		return $h;

	}

	private static function getFieldPrivate(\util\FormUi $form, Product $eProduct): string {

		$h = '<div class="util-block-dark" data-wrapper="'.Customer::PRIVATE.'-block">';

			$h .= $form->group(
				'<h4>'.self::p('private')->label.'</h4>',
				$form->dynamicField($eProduct, 'private')
			);

			$taxes = $eProduct['farm']['selling']['hasVat'] ? '/ '.CustomerUi::getTaxes(Customer::PRIVATE) : '';
			$unit = ($eProduct['unit'] ? self::p('unit')->values[$eProduct['unit']] : self::p('unit')->placeholder);

			$h .= $form->group(
				s("Prix de base"),
				$form->inputGroup(
					$form->dynamicField($eProduct, 'privatePrice').
					'<div class="input-group-addon">€ '.$taxes.' / <span data-ref="product-unit">'.$unit.'</span></div>'
				),
				['wrapper' => 'privatePrice']
			);

			$h .= $form->group(
				self::p('privateStep')->label,
				$form->inputGroup(
					$form->dynamicField($eProduct, 'privateStep').
					'<div class="input-group-addon" data-ref="product-unit">'.$unit.'</div>'
				).$form->info(s("Les quantités achetées par les clients dans les <i>Boutiques en ligne</i> seront toujours un multiple de cette valeur. Laissez ce champ vide si vous souhaitez conserver les valeurs par défaut"))
			);

		$h .= '</div>';

		return $h;

	}

	public static function getStep(Product $eProduct): float {

		return $eProduct['privateStep'] ?? self::getDefaultStep($eProduct);

	}

	public static function getDefaultStep(Product $eProduct): float {

		return match($eProduct['unit']) {

			Product::GRAM => 100,
			Product::KG => 0.5,
			default => 1,

		};

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Product::model()->describer($property, [
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
			'pro' => s("Vente aux clients pros"),
			'proPrice' => s("Prix pro"),
			'proPackaging' => s("Colis de base"),
			'vat' => s("Taux de TVA"),
			'statut' => s("Statut"),
		]);

		switch($property) {

			case 'name' :
				$d->placeholder = s("Ex. : Pomme de terre");
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
