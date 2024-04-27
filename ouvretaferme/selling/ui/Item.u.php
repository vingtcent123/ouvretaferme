<?php
namespace selling;

class ItemUi {

	public function __construct() {

		\Asset::css('selling', 'item.css');
		\Asset::js('selling', 'item.js');

	}

	public static function getNumber(\Collection $cItem) {
		return p('{value} article', '{value} articles', $cItem->count());
	}

	public function getBySale(Sale $eSale, \Collection $cItem) {

		$h = '';

		if($eSale['comment']) {
			$h .= '<h3>'.s("Observations").'</h3>';
			$h .= '<div class="util-block">';
				$h .= encode($eSale['comment']);
			$h .= '</div>';
		}

		if($cItem->empty()) {

			if(
				$eSale['market'] === FALSE or
				$eSale->isMarketPreparing()
			) {

				if($eSale->isMarketPreparing()) {

					$h .= '<h3>'.s("Préparation du marché").'</h3>';

					$h .= '<div class="util-info">';
						$h .= s("Ajoutez les articles à prendre pour préparer votre marché !");
					$h .= '</div>';

				}

				if($eSale->canWriteItems()) {
					$h .= '<div class="mb-1">';
						$h .= '<a href="/selling/item:add?id='.$eSale['id'].'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter un premier article").'</a>';
					$h .= '</div>';
				}

			}

		} else {

			$h .= '<div class="h-line">';

				if($eSale->isMarketPreparing()) {
					$h .= '<h3>'.s("Préparation du marché").'</h3>';
				} else {
					$h .= '<h3>'.s("Articles").'</h3>';
				}

				if(
					$eSale->canWriteItems() and
					$eSale['items'] > 0
				) {
					$h .= '<a href="/selling/item:add?id='.$eSale['id'].'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter d'autres articles").'</a>';
				}
			$h .= '</div>';

			$withPackaging = $cItem->reduce(fn($eItem, $n) => $n + (int)($eItem['packaging'] !== NULL), 0);

			$h .= '<table class="tbody-even stick-xs item-item-table '.($withPackaging ? 'item-item-table-with-packaging' : '').' mb-2">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th colspan="2">'.ItemUi::p('name')->label.'</th>';
						$h .= '<th class="text-center"></th>';
						if($withPackaging) {
							$h .= '<th class="text-end" >'.s("Colis").'</th>';
							$h .= '<th></th>';
						}
						$h .= '<th class="text-end">'.s("Quantité").'</th>';
						$h .= '<th class="text-end">';
							$h .= ItemUi::p('unitPrice')->label;
							if($eSale['hasVat']) {
								$h .= '<br/>('.SaleUi::getTaxes($eSale['taxes']).')';
							}
						$h .= '</th>';
						if(
							$eSale['market'] === FALSE or
							$eSale->isMarketPreparing() === FALSE
						) {
							$h .= '<th class="text-end">';
								$h .= ItemUi::p('price')->label;
								if($eSale['hasVat']) {
									$h .= '<br/>('.SaleUi::getTaxes($eSale['taxes']).')';
								}
							$h .= '</th>';
						}
						if($eSale['hasVat']) {
							$h .= '<th class="item-item-vat text-center">'.s("TVA").'</th>';
						}
						if($cItem->first()->canWrite()) {
							$h .= '<th class="item-item-actions"></th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';

				foreach($cItem as $eItem) {

					$h .= '<tbody>';
						$h .= '<tr>';

							$h .= '<td class="item-item-product td-min-content">';

								if($eItem['product']->notEmpty()) {
									$h .= '<a href="'.ProductUi::url($eItem['product']).'" title="'.s("Produit {value}", encode($eItem['product']->getName())).'">'.ProductUi::getVignette($eItem['product'], '2rem').'</a>';
								}

							$h .= '</td>';
							$h .= '<td class="item-item-name">';

								if($eItem->canWrite()) {
									$h .= '<span class="hide-xs-down">'.encode($eItem['name']).'</span>';
									$h .= '<a data-dropdown="bottom-end" data-dropdown-id="item-update-'.$eItem['id'].'" class="dropdown-toggle item-item-name-action hide-sm-up">';
										$h .= '<span>'.encode($eItem['name']).'</span>';
									$h .= '</a>';
								} else {
									$h .= encode($eItem['name']);
								}

								if($eItem['description']) {
									$h .= '<div class="util-annotation">'.$eItem->quick('description', encode($eItem['description'])).'</div>';
								}

							$h .= '</td>';

							$h .= '<td class="text-center">';

								if($eItem['quality']) {
									$h .= \farm\FarmUi::getQualityLogo($eItem['quality'], '2rem');
								}

							$h .= '</td>';

							if($withPackaging) {

								$h .= '<td class="text-end">';
									if($eItem['packaging']) {
										$h .= ($eItem['locked'] !== Item::NUMBER) ? '<b>'.$eItem->quick('number', $eItem['number']).'</b>' : '<span class="item-item-locked">'.\Asset::icon('lock-fill').'</span> <b>'.$eItem['number'].'</b>';
									} else {
										$h .= '-';
									}
								$h .= '</td>';
								$h .= '<td class="item-item-packaging">';
									if($eItem['packaging']) {
										$h .= 'x '.$eItem->quick('packaging', \main\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE));
									}
								$h .= '</td>';

							}

							$h .= '<td class="item-item-number text-end">';
								if($eItem['packaging']) {
									$h .= \main\UnitUi::getValue($eItem['number'] * $eItem['packaging'], $eItem['unit'], TRUE);
								} else {
									$value = \main\UnitUi::getValue($eItem['number'], $eItem['unit'], TRUE);
									$h .= ($eItem['locked'] !== Item::NUMBER) ? $eItem->quick('number', $value) : '<span class="item-item-locked">'.\Asset::icon('lock-fill').'</span> '.$value;
								}
							$h .= '</td>';

							$h .= '<td class="item-item-unit-price text-end">';

								if($eItem['unit']) {
									$unit = '<span class="util-annotation"> / '.\main\UnitUi::getSingular($eItem['unit'], short: TRUE, by: TRUE).'</span>';
								} else {
									$unit = '';
								}
								$value = \util\TextUi::money($eItem['unitPrice']).' '.$unit;
								$h .= ($eItem['locked'] !== Item::UNIT_PRICE) ? $eItem->quick('unitPrice', $value) : '<span class="item-item-locked">'.\Asset::icon('lock-fill').'</span> '.$value;

								if(
									$eSale['market'] and
									$eItem['number'] and
									$eItem['price']
								) {

									$realUnitPrice = round($eItem['price'] / $eItem['number'], 2);

									if(abs($realUnitPrice - $eItem['unitPrice']) > 0.1) {
										$h .= '<div class="color-muted text-sm">'.s("({value} en réel)", \util\TextUi::money($realUnitPrice).' '.$unit).'</div>';
									}

								}

							$h .= '</td>';

							if(
								$eSale['market'] === FALSE or
								$eSale->isMarketPreparing() === FALSE
							) {
								$h .= '<td class="item-item-price text-end">';
									$value = \util\TextUi::money($eItem['price']);
									$h .= ($eItem['locked'] !== Item::PRICE) ? $eItem->quick('price', $value) : '<span class="item-item-locked">'.\Asset::icon('lock-fill').'</span> '.$value;
								$h .= '</td>';
							}

							if($eSale['hasVat']) {

								$h .= '<td class="item-item-vat text-center">';
									$h .= $eItem->quick('vatRate', s('{value} %', $eItem['vatRate']));
								$h .= '</td>';

							}

							if($eItem->canWrite()) {
								$h .= '<td class="item-item-actions" rowspan="2">';
									$h .= $this->getUpdate($eItem);
								$h .= '</td>';
							}

						$h .= '</tr>';

					$h .= '</tbody>';

				}

			$h .= '</table>';

		}

		return $h;

	}

	public function getByProduct(\farm\Farm $eFarm, \Collection $cItem) {

		if($cItem->empty()) {

			$h = '<p class="util-info">';
				$h .= s("Vous n'avez encore jamais vendu ce produit.");
			$h .= '</p>';

			return $h;

		}

		$h = '<table class="tr-even stick-xs">';

			$h .= '<tr>';
				$h .= '<th>'.s("Date").'</th>';
				$h .= '<th class="text-center">'.s("Vente").'</th>';
				$h .= '<th>'.s("Client").'</th>';
				$h .= '<th class="text-end">'.s("Quantité").'</th>';
				$h .= '<th class="text-end item-product-unit-price">'.ItemUi::p('unitPrice')->label.'</th>';
				$h .= '<th class="text-end">'.ItemUi::p('price')->label.'</th>';
			$h .= '</tr>';

			foreach($cItem as $eItem) {

				$h .= '<tr>';

					$h .= '<td>';
						$h .= \util\DateUi::numeric($eItem['deliveredAt']);
					$h .= '</td>';

					$h .= '<td class="text-center">';
						$h .= '<a href="/vente/'.$eItem['sale']['id'].'" class="btn btn-sm btn-outline-primary">'.$eItem['sale']->getNumber().'</a> ';
					$h .= '</td>';

					$h .= '<td>';
						$h .= CustomerUi::link($eItem['customer']);
					$h .= '</td>';

					$h .= '<td class="text-end">';
						$h .= \main\UnitUi::getValue(round($eItem['quantity'], 2), $eItem['unit'], TRUE);
					$h .= '</td>';

					$h .= '<td class="text-end item-product-unit-price">';
						if($eItem['unit']) {
							$unit = '<span class="util-annotation"> / '.\main\UnitUi::getSingular($eItem['unit'], short: TRUE, by: TRUE).'</span>';
						} else {
							$unit = '';
						}
						$h .= \util\TextUi::money($eItem['unitPrice']).' '.$unit;
					$h .= '</td>';

					$h .= '<td class="text-end">';
						if($eItem['price'] !== NULL) {
							$h .= \util\TextUi::money($eItem['price']);
							$h .= $eFarm['selling']['hasVat'] ?' '.$eItem['sale']->getTaxes() : '';
						}
					$h .= '</td>';

				$h .= '</tr>';

			}

		$h .= '</table>';

		return $h;

	}

	public function getByDeliverDay(\farm\Farm $eFarm, string $date, \Collection $cSale, \Collection $ccItemProduct, \Collection $ccItemSale) {

		if($cSale->empty()) {
			$h = '<div class="util-info">'.s("Il n'y a aucune commande à préparer pour ce jour").'</div>';
		} else {

			$h = '';

			$h .= '<div class="tabs-h" id="item-preparation-wrapper">';

				$h .= '<div class="tabs-item">';
					$h .= '<a class="tab-item selected" data-tab="summary" onclick="Lime.Tab.select(this)">';
						$h .= s("Synthèse");
					$h .= '</a>';
					$h .= '<a class="tab-item" data-tab="product" onclick="Lime.Tab.select(this)">';
						$h .= s("Par produit");
					$h .= '</a>';
					$h .= '<a class="tab-item" data-tab="sale" onclick="Lime.Tab.select(this)">';
						$h .= s("Par vente");
					$h .= '</a>';
				$h .= '</div>';

				$h .= '<div id="item-preparation">';
					$h .= '<div data-tab="summary" class="tab-panel selected">';
						$h .= $this->getItemsBySummary($cSale, $ccItemProduct);
						$h .= '<h3>'.s("État des ventes").'</h3>';
						$h .= (new SaleUi())->getList($eFarm, $cSale);
					$h .= '</div>';
					$h .= '<div data-tab="product" class="tab-panel">';
						$h .= $this->getItemsByProduct($cSale, $ccItemProduct);
					$h .= '</div>';
					$h .= '<div data-tab="sale" class="tab-panel">';
						$h .= $this->getItemsBySale($cSale, $ccItemSale);
					$h .= '</div>';
				$h .= '</div>';

			$h .= '</div>';

		}

		return new \Panel(
			title: s("Commandes pour le {value}", lcfirst(\util\DateUi::getDayName(date('N', strtotime($date)))).' '.\util\DateUi::textual($date, \util\DateUi::DAY_MONTH)),
			body: $h
		);

	}

	public function getItemsBySummary(\Collection $cSale, \Collection $ccItem): string {

		$middle = ceil($ccItem->count() / 2);
		$ccItemFirst = $ccItem->slice(0, $middle);
		$ccItemLast = $ccItem->slice($middle);

		$h = '<div class="item-day-summary">';

		foreach([$ccItemFirst, $ccItemLast] as $ccItemChunk) {

			$h .= '<table class="tr-bordered">';

				$h .= '<tbody>';

				foreach($ccItemChunk as $cItem) {

					$eProduct = $cItem->first()['product'];
					$total = $cItem->reduce(fn($eItem, $v) => $v + ($eItem['packaging'] ?? 1) * $eItem['number'], 0);

					$h .= '<tr>';
						$h .= '<td class="td-min-content">'.ProductUi::getVignette($eProduct, '2.5rem').'</td>';
						$h .= '<td class="item-day-product-name">';
							$h .= encode($eProduct->getName());
							if($eProduct['size']) {
								$h .= ' <br class="hide-lg-up"/><small class="color-muted">'.s("Calibre {value}", '<u>'.encode($eProduct['size']).'</u>').'</small>';
							}
						$h .= '</td>';
						$h .= '<td class="text-end" style="padding-right: 1rem">';
							$h .= '&nbsp;<span class="annotation" style="color: var(--order)">'.\main\UnitUi::getValue(round($total, 2), $cItem->first()['unit'], TRUE).'</span>';
						$h .= '</td>';
					$h .= '</tr>';


				}

				$h .= '</tbody>';
			$h .= '</table>';

		}

		$h .= '</div>';

		return $h;

	}

	public function getItemsByProduct(\Collection $cSale, \Collection $ccItem): string {

		$h = '<div class="item-day-wrapper">';

		foreach($ccItem as $cItem) {

			$eProduct = $cItem->first()['product'];
			$total = $cItem->reduce(fn($eItem, $v) => $v + ($eItem['packaging'] ?? 1) * $eItem['number'], 0);

			$h .= '<div class="item-day-product">';
				$h .= ProductUi::getVignette($eProduct, '2.5rem');
				$h .= '<div class="item-day-product-name">';
					$h .= encode($eProduct->getName());
					$h .= '&nbsp;<span class="annotation" style="color: var(--order)">'.\main\UnitUi::getValue(round($total, 2), $cItem->first()['unit'], TRUE).'</span>';
					if($eProduct['size']) {
						$h .= '<div><small class="color-muted">'.s("Calibre {value}", '<u>'.encode($eProduct['size']).'</u>').'</small></div>';
					}
				$h .= '</div>';
			$h .= '</div>';

			$h .= '<ul class="item-day-sales">';

				foreach($cItem as $eItem) {

					$color = match($cSale[$eItem['sale']['id']]['preparationStatus']) {
						Sale::CONFIRMED => 'order',
						Sale::PREPARED => 'secondary',
						Sale::DELIVERED => 'success'
					};

					$customer = '<a href="'.SaleUi::url($eItem['sale']).'" class="btn btn-xs btn-outline-'.$color.'">'.$eItem['sale']['id'].'</a> ';
					$customer .= '<a href="'.SaleUi::url($eItem['sale']).'">'.CustomerUi::name($eItem['customer']).'</a>';

					$h .= '<li>';

					if($eItem['packaging']) {
						$h .= p("<b>{number} colis</b> de {quantity} {arrow} {customer}", "<b>{number} colis</b> de {quantity} {arrow} {customer}", $eItem['number'], ['number' => $eItem['number'], 'arrow' => \Asset::icon('arrow-right'), 'quantity' => '<b>'.\main\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE).'</b>', 'customer' => $customer]);
					} else {
						$h .= s("{quantity} {arrow} {customer}", ['quantity' => '<b>'.\main\UnitUi::getValue($eItem['number'], $eItem['unit'], TRUE).'</b>', 'customer' => $customer, 'arrow' => \Asset::icon('arrow-right')]);
					}

					$h .= '</li>';

				}

			$h .= '</ul>';


		}
		$h .= '</div>';

		return $h;

	}

	public function getItemsBySale(\Collection $cSale, \Collection $ccItem): string {

		$h = '<div class="item-day-wrapper">';

		foreach($ccItem as $cItem) {

			$eSale = $cItem->first()['sale'];
			$eCustomer = $cItem->first()['customer'];

			$color = match($cSale[$eSale['id']]['preparationStatus']) {
				Sale::CONFIRMED => 'order',
				Sale::PREPARED => 'secondary',
				Sale::DELIVERED => 'success'
			};

			$h .= '<div class="item-day-product">';
				$h .= '<a href="/vente/'.$eSale['id'].'" class="btn btn-sm btn-outline-'.$color.'">'.$eSale->getNumber().'</a> ';
				$h .= CustomerUi::link($eCustomer);
			$h .= '</div>';

			$h .= '<ul class="item-day-sales">';

				foreach($cItem as $eItem) {

					$h .= '<li>';

					if($eItem['packaging']) {
						$h .= p("<b>{number} colis</b> de {quantity}", "<b>{number} colis</b> de {quantity}", $eItem['number'], ['number' => $eItem['number'], 'quantity' => '<b>'.\main\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE).'</b>']);
					} else {
						$h .= '<b>'.\main\UnitUi::getValue($eItem['number'], $eItem['unit'], TRUE).'</b>';
					}

					$h .= ' '.\Asset::icon('arrow-right').' ';

					if($eItem['product']->notEmpty()) {
						$h .= ProductUi::link($eItem['product']);
					} else {
						$h .= encode($eItem['name']);
					}

					$h .= '</li>';

				}

			$h .= '</ul>';


		}
		$h .= '</div>';

		return $h;

	}

	protected function getUpdate(Item $eItem): string {

		$h = '<a data-dropdown-id="item-update-'.$eItem['id'].'" data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div data-dropdown-id="item-update-'.$eItem['id'].'-list" class="dropdown-list">';
			$h .= '<div class="dropdown-title">'.encode($eItem['name']).'</div>';
			$h .= '<a href="/selling/item:update?id='.$eItem['id'].'" class="dropdown-item">'.s("Modifier l'article").'</a>';
			$h .= '<a data-ajax="/selling/item:doDelete" post-id="'.$eItem['id'].'" class="dropdown-item" data-confirm="'.s("Supprime l'article de la vente ?").'">'.s("Supprimer l'article").'</a>';
		$h .= '</div>';

		return $h;

	}

	public function add(Sale $eSale): \Panel {

		$eSale->expects(['farm']);

		$form = new \util\FormUi();

		$eItem = new Item([
			'farm' => $eSale['farm'],
			'sale' => $eSale
		]);

		$h = $form->group(
			s("À partir d'un produit"),
			$form->dynamicField($eItem, 'product', function($d) {
				$d->autocompleteDispatch = '#item-add';
			}),
			['class' => 'form-group-highlight']
		);
		$h .= '<div class="item-add-scratch">'.\Asset::icon('chevron-right').' <a data-ajax="/selling/item:one" post-id="'.$eSale['id'].'">'.s("Ajouter un article sans référence de produit").'</a></div>';

		$h .= $form->hidden('id', $eSale['id']);

		if($eSale['discount'] > 0) {
			$h .= '<div id="item-add-discount" class="util-success hide">';
				$h .= s("Le prix unitaire inclut la remise de {discount} % applicable à cette vente.", ['discount' => $eSale['discount']]);
			$h .= '</div>';
		}

		$h .= '<div id="item-add-list">';
		$h .= '</div>';

		$footer = '<div class="item-add-submit">';
			$footer .= $form->submit(s("Ajouter"));
			$footer .= '<div class="item-add-submit-stats">';
				$footer .= '<span class="btn btn-border btn-readonly">'.s("Articles {arrow} {value}", ['value' => '<span data-ref="product-item-count"></span>', 'arrow' => \Asset::icon('arrow-right')]).'</span>';
				if($eSale['market'] === FALSE) {
					$footer .= ' ';
					$footer .= '<span class="btn btn-border btn-readonly">'.s("Prix total {arrow} {value} €", ['value' => '<span data-ref="product-price-sum"></span>', 'arrow' => \Asset::icon('arrow-right')]);
					$footer .= ' '.$eSale->getTaxes().'</span>';
				}
			$footer .= '</div>';
		$footer .= '</div>';

		return new \Panel(
			title: s("Ajouter des articles"),
			subTitle: (new SaleUi())->getPanelHeader($eSale),
			dialogOpen: $form->openAjax('/selling/item:doAdd', ['class' => 'panel-dialog container', 'id' => 'item-add']),
			dialogClose: $form->close(),
			body: $h,
			footer: $footer,
			close: 'reload'
		);

	}

	public static function addOne(Item $eItem, Grid $eGrid): string {

		$eItem->expects(['farm', 'product', 'sale']);

		$eProduct = $eItem['product'];
		$eCustomer = $eItem['customer'];

		$form = new \util\FormUi();

		$h = '<div class="item-add-one" data-product="'.($eProduct->empty() ? '' : $eProduct['id']).'">';

			$h .= '<div class="h-line">';
				$h .= '<h3>'.s("Article n°{value}", '<span data-ref="product-number"></span>').'</h3>';
				$h .= '<div class="item-add-one-actions">';
					if($eItem['sale']['market'] === FALSE) {
						$h .= '<div class="item-add-one-price btn btn-border btn-readonly">';
							$h .= s("{value} €", ['value' => '<span data-ref="product-price"></span>']);
							$h .= ' '.$eItem['sale']->getTaxes();
							$h .= \Asset::icon('check-lg', ['class' => 'color-success', 'style' => 'margin-left: 1rem']);
						$h .= '</div>';
					}
					$h .= '<a onclick="Item.removeFromFrom(this)" class="btn btn-outline-secondary">'.\Asset::icon('trash').'</a>';
				$h .= '</div>';
			$h .= '</div>';

			if($eProduct->notEmpty()) {

				$eItem['packaging'] = NULL;
				$eItem['unitPrice'] = NULL;
				$eItem['unit'] = $eProduct['unit'];

				if($eGrid->notEmpty()) {
					$eItem['packaging'] = $eGrid['packaging'];
					$eItem['unitPrice'] = $eGrid['price'];
				}

				if($eItem['sale']['type'] === Customer::PRO) {
					$eItem['packaging'] ??= $eProduct['proPackaging'];
				}

				$eItem['unitPrice'] ??= $eProduct[$eItem['sale']['type'].'Price'];
				$eItem['unitPrice'] ??= match($eItem['sale']['type']) {
					Customer::PRO => $eProduct['privatePrice'] ? $eProduct['privatePrice'] - $eProduct->calcPrivateVat() : NULL,
					Customer::PRIVATE => $eProduct['proPrice'] ? $eProduct['proPrice'] + $eProduct->calcProVat() : NULL,
				};

				// La réduction s'applique uniquement pour les produits qui disposent d'un prix pour ce type de client (particulier / professionnel)
				if($eItem['sale']['discount'] > 0 and $eItem['unitPrice'] !== NULL) {
					$eItem['baseUnitPrice'] = $eItem['unitPrice'];
					$eItem['unitPrice'] = round($eItem['unitPrice'] * (1 - $eItem['sale']['discount'] / 100), 2);
				}

				$h .= '<div class="item-write">';

					$h .= $form->group(
						s("Produit"),
						ProductUi::link($eProduct, TRUE)
					);

					$h .= $form->dynamicGroup($eItem, 'quality[]');

					$h .= $form->hidden('product[]', $eProduct['id']);
					$h .= $form->hidden('discount[]', $eItem['sale']['discount']);
					$h .= $form->hidden('name[]', $eProduct->getName());
					$h .= $form->hidden('unit[]', $eProduct['unit']);
					$h .= $form->hidden('locked[]', Item::PRICE);

					if($eItem['sale']['hasVat']) {
						$h .= $form->hidden('vatRate[]', \Setting::get('selling\vatRates')[$eProduct['vat']]);
						$h .= $form->group(s("Taux de TVA"), '<div class="form-control disabled">'.s("{value} %", \Setting::get('selling\vatRates')[$eProduct['vat']]).'</div>');
					}

					if($eItem['sale']['type'] === Customer::PRO) {
						$h .= self::getPackagingField($form, 'packaging[]', $eItem);
					} else {
						$h .= $form->hidden('packaging[]', '');
					}

					$h .= $form->dynamicGroups($eItem, $eItem['sale']['market'] ?
						($eItem['sale']['preparationStatus'] !== Sale::SELLING ? ['unitPrice[]', 'number[]'] : ['unitPrice[]']) :
						['unitPrice[]', 'number[]', 'price[]'], [
							'unitPrice[]' => function(\PropertyDescriber $d) use ($eItem) {
								if($eItem['sale']['discount'] > 0 and $eItem['unitPrice'] !== NULL) {
									$d->after = \util\FormUi::info(s("Prix de base : {value}", \util\TextUi::money($eItem['baseUnitPrice'])));
								}
							}
					]);

				$h .= '</div>';

			} else {

				$eItem['unit'] = NULL;
				$eItem['packaging'] = NULL;

				$h .= '<div class="item-write">';

					$h .= '<div class="util-info">';
						$h .= s("Les articles ajoutés sans référence de produit n'apparaissent dans la liste des commandes à préparer.");
					$h .= '</div>';

					$h .= '<div class="util-warning">';
						$h .= s("Pour plus de commodité et faciliter la gestion de votre gamme, si cet article est amené à être vendu fréquemment, vous devriez l'<link>enregistrer au préalable comme un produit</link>.", ['link' => '<a href="/selling/product:create?farm='.$eItem['sale']['farm']['id'].'">']);
					$h .= '</div>';

					$h .= $form->hidden('product[]', '');
					$h .= $form->hidden('locked[]', Item::PRICE);
					$h .= $form->dynamicGroup($eItem, 'name[]');

					if($eItem['sale']['type'] === Customer::PRO) {
						$eItem['unit'] = NULL;
						$h .= self::getPackagingField($form, 'packaging[]', $eItem);
					} else {
						$h .= $form->hidden('packaging[]', '');
					}

					$h .= $form->dynamicGroups($eItem, $eItem['sale']['market'] ?
						($eItem['sale']['preparationStatus'] !== Sale::SELLING ? ['unit[]', 'unitPrice[]', 'number[]'] : ['unit[]', 'unitPrice[]']) :
						['unit[]', 'unitPrice[]', 'number[]', 'price[]']);

					if($eItem['sale']['hasVat']) {
						$h .= $form->group(
							self::p('vatRate')->label,
							$form->select('vatRate[]', SaleUi::getVatRates($eItem['farm']), $eItem['vatRate'])
						);
					}

				$h .= '</div>';

			}


		$h .= '</div>';

		return $h;

	}

	public function update(Item $eItem): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/item:doUpdate', ['class' => 'item-write']);

			$h .= $form->hidden('id', $eItem['id']);
			$h .= $form->hidden('locked', $eItem['locked']);

			if($eItem['product']->notEmpty()) {
				$h .= $form->group(
					self::p('product')->label,
					ProductUi::getVignette($eItem['product'], '4rem').' '.ProductUi::link($eItem['product'])
				);
			}

			$h .= $form->dynamicGroups($eItem, ['name', 'quality', 'description']);

			if($eItem['customer']->isPro()) {
				$h .= self::getPackagingField($form, 'packaging', $eItem);
			}

			$h .= $form->dynamicGroups($eItem, $eItem['sale']['market'] ?
				['number', 'unitPrice'] :
				['number', 'unitPrice', 'price']
			);

			if($eItem['sale']['hasVat']) {
				$h .= $form->dynamicGroup($eItem, 'vatRate');
			}

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un article"),
			body: $h
		);

	}

	public static function getPackagingField(\util\FormUi $form, string $name, Item $eItem): string {

		$eItem->expects(['unit']);

		$field = '<div class="item-write-packaging">';
			$field .= $form->inputGroup(
				$form->dynamicField($eItem, $name).
				$form->addon($eItem['unit'] ? \main\UnitUi::getNeutral($eItem['unit']) : s("unité(s)"))
			);
			$field .= '<a onclick="Item.removePackaging(this)" title="'.s("Supprimer le colisage").'" class="btn btn-primary">'.\Asset::icon('trash').'</a>';
		$field .= '</div>';

		$h = $form->group(
			self::p('packaging')->label,
			'<div class="item-write-packaging-field '.($eItem['packaging'] ? '' : 'hide').'">'.$field.'</div>'.
			'<div class="item-write-packaging-link  '.($eItem['packaging'] ? 'hide' : '').'">'.s("Aucun").'&nbsp;&nbsp;-&nbsp;&nbsp;'.\Asset::icon('plus-circle').' <a onclick="Item.addPackaging(this)">'.s("Définir un colisage").'</a></div>'
		);

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Item::model()->describer($property, [
			'name' => s("Désignation"),
			'description' => s("Description"),
			'product' => s("Produit"),
			'quality' => s("Signe de qualité"),
			'packaging' => s("Colisage"),
			'unit' => s("Unité de vente"),
			'unitPrice' => s("Prix unitaire"),
			'price' => s("Montant"),
			'number' => s("Quantité vendue"),
			'vatRate' => s("Taux de TVA")
		]);

		switch($property) {

			case 'product' :
				$d->autocompleteBody = function(\util\FormUi $form, Item $e) {
					$e->expects([
						'farm',
						'sale' => ['type']
					]);
					return [
						'farm' => $e['farm']['id'],
						'type' => $e['sale']['type'],
					];
				};
				(new ProductUi())->query($d);
				break;

			case 'quality' :
				$d->field = 'select';
				$d->values = \farm\FarmUi::getQualities();;
				$d->placeholder = s("Aucun");
				break;

			case 'unit' :
				$d->values = \main\UnitUi::getList(noWrap: FALSE);
				$d->field = 'select';
				$d->placeholder = s("&lt; Non applicable &gt;");
				break;

			case 'packaging' :
				$d->attributes = function(\util\FormUi $form, Item $eItem) use($property) {

					if($eItem['sale']['market']) {
						return [];
					} else {
						return [
							'oninput' => 'Item.recalculateLock(this)'
						];
					}

				};
				break;

			case 'number' :
				self::applyLocking($d, Item::NUMBER);

				$d->append = function(\util\FormUi $form, Item $eItem) {
					$h = '<span class="item-write-packaging-label '.($eItem['packaging'] ? '' : 'hide').'">'.s("colis").'</span>';
					$h .= '<span class="item-write-unit-label '.($eItem['packaging'] ? 'hide' : '').'">'.\main\UnitUi::getNeutral($eItem['unit']).'</span>';
					return $form->addon($h);
				};
				break;

			case 'unitPrice' :
				self::applyLocking($d, Item::UNIT_PRICE);

				$d->append = function(\util\FormUi $form, Item $eItem) {
					if($eItem['unit'] !== NULL) {
						$h = s("€ {taxes} / {unit}", ['taxes' => $eItem['sale']->getTaxes(), 'unit' => \main\UnitUi::getSingular($eItem['unit'])]);
					} else {
						$h = s("€ {taxes}", ['taxes' => $eItem['sale']->getTaxes()]);
					}
					return $form->addon($h);
				};
				break;

			case 'vatRate' :
				$d->append = s("%");
				break;

			case 'price' :
				self::applyLocking($d, Item::PRICE);

				$d->append = fn(\util\FormUi $form, Item $eItem) => $form->addon(s("€ {taxes}", ['taxes' => $eItem['sale']->getTaxes()]));
				break;

		}

		return $d;

	}

	protected static function applyLocking(\PropertyDescriber $d, string $property) {

		$d->prepend = function(\util\FormUi $form, Item $eItem) use($property) {

			if(
				$eItem->isQuick() or
				$eItem['sale']['market']
			) {
				return NULL;
			}

			$h = '<a onclick="Item.lock(this)" data-locked="'.$property.'" class="input-group-addon '.($eItem['locked'] === $property ? 'item-write-locked' : '').'">'.\Asset::icon('lock-fill').\Asset::icon('unlock').'</a>';
			return $h;
		};
		$d->attributes = function(\util\FormUi $form, Item $eItem) use($property) {

			if(
				$eItem->isQuick() or
				$eItem['sale']['market']
			) {
				return [];
			}

			$attributes = [
				'oninput' => 'Item.recalculateLock(this)'
			];

			if($eItem['locked'] === $property) {
				$attributes['class'] = 'disabled';
			}

			return $attributes;

		};

	}

}
?>
