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

		if($eSale->acceptCreateItems()) {

			$form = new \util\FormUi();

			$eItem = new Item([
				'farm' => $eSale['farm'],
				'sale' => $eSale
			]);

			$new = '<div id="item-create" data-sale="'.$eSale['id'].'">';

				$new .= $form->dynamicField($eItem, 'product', function($d) {
					$d->autocompleteDispatch = '#item-create';
					$d->placeholder = s("Ajouter un produit");
					$d->attributes['class'] = 'form-control-lg';
				});

				if($eSale->isComposition() === FALSE) {
					$new .= '<div class="item-add-scratch">'.\Asset::icon('chevron-right').' <a href="/selling/item:create?sale='.$eSale['id'].'">'.s("Ajouter un article sans référence de produit").'</a></div>';
				}

			$new .= '</div>';

		} else {
			$new = '';
		}

		if($eSale['comment']) {
			$h .= '<h3>'.s("Observations").'</h3>';
			$h .= '<div class="util-block">';
				$h .= encode($eSale['comment']);
			$h .= '</div>';
		}

		$h .= '<div class="h-line">';

			if($eSale->isComposition()) {
				$h .= '<h3>'.s("Composition du {value}", \util\DateUi::numeric($eSale['deliveredAt'])).'</h3>';
			} else {

				if($eSale->isMarketPreparing()) {
					$h .= '<h3>'.s("Préparation du marché").'</h3>';
				} else {
					$h .= '<h3>'.s("Articles").'</h3>';
				}
				if($eSale->acceptCreateItems()) {
					$h .= '<a href="/selling/item:createCollection?sale='.$eSale['id'].'" class="btn btn-outline-primary">';
						$h .= \Asset::icon('plus-circle').' '.s("Ajouter plusieurs produits");
					$h .= '</a>';
				}

			}
		$h .= '</div>';

		if($cItem->empty()) {

			if($eSale->acceptCreateItems()) {

				$h .= '<p class="util-empty">';

					if($eSale['market']) {
						$h .= s("Il n'y a pas encore d'article à vendre pendant votre marché !");
					} {
						$h .= s("Il n'y a pas encore d'article dans cette vente !");
					}

				$h .= '</p>';

				$h .= $new;

			} else {
				$h .= '<div class="util-info">';
					$h .= s("Aucun article n'a été ajouté à cette vente.");
				$h .= '</div>';
			}


		} else {


			$withPackaging = $cItem->reduce(fn($eItem, $n) => $n + (int)($eItem['packaging'] !== NULL), 0);
			$columns = 0;

			foreach($cItem as $eItem) {
				$h .= (new MerchantUi())->get('/selling/item:doUpdateMerchant', $eSale, $eItem, showDelete: FALSE);
			}

			$h .= '<div class="stick-xs">';

				$h .= '<table class="tbody-even item-item-table '.($withPackaging ? 'item-item-table-with-packaging' : '').'">';

					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<th class="item-item-vignette"></th>';

							$columns++;
							$h .= '<th class="hide-sm-down">'.ItemUi::p('name')->label.'</th>';

							$columns++;
							$h .= '<th class="hide-sm-down"></th>';

							if($withPackaging) {
								$columns++;
								$h .= '<th>'.s("Colis").'</th>';
							}

							$columns++;
							$h .= '<th class="text-end">'.s("Quantité").'</th>';

							$columns++;
							$h .= '<th class="text-end">';
								$h .= ItemUi::p('unitPrice')->label;
								if($eSale['hasVat']) {
										$h .= ' <span class="util-annotation">'.$eSale->getTaxes().'</span>';
								}
							$h .= '</th>';

							if(
								$eSale['market'] === FALSE or
								$eSale->isMarketPreparing() === FALSE
							) {
								$columns++;
								$h .= '<th class="text-end">';
									$h .= ItemUi::p('price')->label;
									if($eSale['hasVat']) {
										$h .= ' <span class="util-annotation">'.$eSale->getTaxes().'</span>';
									}
								$h .= '</th>';
							}
							if($eSale['hasVat']) {
								$columns++;
								$h .= '<th class="item-item-vat text-center hide-sm-down">'.s("TVA").'</th>';
							}

						$h .= '</tr>';
					$h .= '</thead>';

					foreach($cItem as $eItem) {

						if($eItem['product']->notEmpty()) {
							$vignette = ProductUi::getVignette($eItem['product'], '2.5rem');
						} else {
							$vignette = '';
						}

						$description = [];

						if($eItem['quality']) {
							$description[] = \farm\FarmUi::getQualityLogo($eItem['quality'], '1.5rem');
						}

						$product = encode($eItem['name']);

						if($eItem['product']->notEmpty()) {

							$details = ProductUi::getDetails($eItem['product']);

							if($details) {
								$product .= '<br/><small class="color-muted">'.implode(' | ', $details).'</small>';
							}

						}

						$h .= '</div>';

						$h .= '<tbody>';
							$h .= '<tr class="item-item-line-1">';
								$h .= '<td class="item-item-vignette" rowspan="2">'.$vignette.'</td>';
								$h .= '<td class="hide-md-up" colspan="'.$columns.'" style="border-bottom: 1px dashed var(--border)">';
									$h .= '<div class="item-item-product">';
										$h .= '<div>'.$product.'</div>';
										if($description) {
											$h .= '<span class="item-item-product-description">'.implode('', $description).'</span>';
										}
									$h .= '</div>';
								$h .= '</td>';
								$h .= '<td class="item-item-empty hide-sm-down" colspan="'.$columns.'"></td>';
								if($eItem->canWrite()) {
									$h .= '<td class="item-item-actions td-min-content" rowspan="2">';
										$h .= $this->getUpdate($eItem);
									$h .= '</td>';
								}
							$h .= '</tr>';
							$h .= '<tr class="item-item-line-2">';

								$h .= '<td class="td-min-content hide-sm-down" style="line-height: 1.2">'.$product.'</td>';
								$h .= '<td class="hide-sm-down">'.implode('  ', $description).'</td>';

								if($withPackaging) {

									$h .= '<td class="item-item-packaging">';

										if($eItem['packaging']) {

											$value = '<b>'.$eItem['number'].'</b>';

											if($eItem['locked'] === Item::NUMBER) {
												$h .= '<span class="item-item-locked">'.\Asset::icon('lock-fill').'</span> '.$value;
											} else {
												$h .= '<a onclick="Merchant.show(this)" class="util-quick" data-item="'.$eItem['id'].'" data-property="'.Item::NUMBER.'">'.$value.'</a>';
											}

											$h .= '<span class="item-item-packaging-size"> x ';
												$h .= '<a onclick="Merchant.show(this)" class="util-quick" data-item="'.$eItem['id'].'" data-property="packaging">'.\selling\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE).'</a>';
											$h .= '</span>';
										} else {
											$h .= '-';
										}

									$h .= '</td>';

								}

								$h .= '<td class="item-item-number text-end">';
									if($eItem['packaging']) {
										$h .= '<span class="item-item-locked">'.\Asset::icon('lock-fill').'</span> '.\selling\UnitUi::getValue($eItem['number'] * $eItem['packaging'], $eItem['unit'], TRUE);
									} else {
										$value = \selling\UnitUi::getValue($eItem['number'], $eItem['unit'], TRUE);

										if($eItem['locked'] === Item::NUMBER) {
											$h .= '<span class="item-item-locked">'.\Asset::icon('lock-fill').'</span> '.$value;
										} else if($eItem->canUpdate() === FALSE) {
											$h .= $value;
										} else {
											$h .= '<a onclick="Merchant.show(this)" class="util-quick" data-item="'.$eItem['id'].'" data-property="number">'.$value.'</a>';
										}

									}

								$h .= '</td>';

								$h .= '<td class="item-item-unit-price text-end">';

									if($eItem['unit']) {
										$unit = '<span class="util-annotation">'.\selling\UnitUi::getBy($eItem['unit'], short: TRUE).'</span>';
									} else {
										$unit = '';
									}
									$value = \util\TextUi::money($eItem['unitPrice']).' '.$unit;

									if($eItem['locked'] === Item::UNIT_PRICE) {
										$h .= '<span class="item-item-locked">'.\Asset::icon('lock-fill').'</span> '.$value;
									} else if($eItem->canUpdate() === FALSE) {
										$h .= $value;
									} else {
										$h .= '<a onclick="Merchant.show(this)" class="util-quick" data-item="'.$eItem['id'].'" data-property="unit-price">'.$value.'</a>';
									}

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

										if($eItem['locked'] === Item::PRICE) {
											$h .= '<span class="item-item-locked">'.\Asset::icon('lock-fill').'</span> '.$value;
										} else if($eItem->canUpdate() === FALSE) {
											$h .= $value;
										} else {
											$h .= '<a onclick="Merchant.show(this)" class="util-quick" data-item="'.$eItem['id'].'" data-property="price">'.$value.'</a>';
										}

									$h .= '</td>';
								}

								if($eSale['hasVat']) {

									$h .= '<td class="item-item-vat text-center hide-sm-down">';
										$h .= $eItem->quick('vatRate', s('{value} %', $eItem['vatRate']));
									$h .= '</td>';

								}

							$h .= '</tr>';

						$h .= '</tbody>';

					}

				$h .= '</table>';

			$h .= '</div>';

			$h .= $new;

		}

		if($eSale['market'] and $eSale['preparationStatus'] === Sale::DRAFT) {
			$h .= '<div class="util-block bg-order color-white" style="display: flex; align-items: center; justify-content: space-between">';
				$h .= '<h4 class="mb-0">'.s("Votre préparation de marché est complète ?").'</h4>';
				$h .= '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::CONFIRMED.'" class="btn btn-transparent">'.s("Confirmer la vente").'</a>';
			$h .= '</div>';
		}

		$h .= '<br/>';

		return $h;

	}

	public function getByProduct(\Collection $cItem) {

		if($cItem->empty()) {

			$h = '<p class="util-empty">';
				$h .= s("Vous n'avez encore jamais vendu ce produit.");
			$h .= '</p>';

			return $h;

		}

		$h = '<table class="tr-even stick-xs">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th>'.s("Date").'</th>';
					$h .= '<th class="text-center">'.s("Vente").'</th>';
					$h .= '<th>'.s("Client").'</th>';
					$h .= '<th class="text-end">'.s("Quantité").'</th>';
					$h .= '<th class="text-end item-product-unit-price">'.ItemUi::p('unitPrice')->label.'</th>';
					$h .= '<th class="text-end">'.ItemUi::p('price')->label.'</th>';
				$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';

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
							$h .= \selling\UnitUi::getValue($eItem['quantity'], $eItem['unit'], TRUE);
						$h .= '</td>';

						$h .= '<td class="text-end item-product-unit-price">';
							if($eItem['unit']) {
								$unit = '<span class="util-annotation">'.\selling\UnitUi::getBy($eItem['unit'], short: TRUE).'</span>';
							} else {
								$unit = '';
							}
							$h .= \util\TextUi::money($eItem['unitPrice']).' '.$unit;
						$h .= '</td>';

						$h .= '<td class="text-end">';
							if($eItem['price'] !== NULL) {
								$h .= \util\TextUi::money($eItem['price']);
								$h .= $eItem['sale']['hasVat'] ? ' <span class="util-annotation">'.$eItem['sale']->getTaxes().'</span>' : '';
							}
						$h .= '</td>';

					$h .= '</tr>';

				}

			$h .= '</tbody>';

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

			$h .= '<table>';

				$h .= '<tbody>';

				foreach($ccItemChunk as $cItem) {

					$eProduct = $cItem->first()['product'];
					$total = $cItem->reduce(fn($eItem, $v) => ($eItem['number'] !== NULL) ? (($v ?? 0) + ($eItem['packaging'] ?? 1) * $eItem['number']) : $v, NULL);

					$h .= '<tr>';
						$h .= '<td class="td-min-content">'.ProductUi::getVignette($eProduct, '3rem').'</td>';
						$h .= '<td class="item-day-product-name">';
							$h .= encode($eProduct->getName());
							if($eProduct['size']) {
								$h .= ' <br class="hide-lg-up"/><small class="color-muted"><u>'.encode($eProduct['size']).'</u></small>';
							}
						$h .= '</td>';
						$h .= '<td class="text-end" style="padding-right: 1rem">';
							$h .= '&nbsp;<span class="annotation" style="color: var(--order)">'.\selling\UnitUi::getValue($total, $cItem->first()['unit'], TRUE).'</span>';
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
			$total = $cItem->reduce(fn($eItem, $v) => $eItem['number'] !== NULL ? (($v ?? 0) + ($eItem['packaging'] ?? 1) * $eItem['number']) : $v, NULL);

			$h .= '<div class="item-day-one">';
				$h .= '<div class="item-day-product">';
					$h .= ProductUi::getVignette($eProduct, '3rem');
					$h .= '<div class="item-day-product-name">';
						$h .= encode($eProduct->getName());
						$h .= '&nbsp;<span class="annotation" style="color: var(--order)">'.\selling\UnitUi::getValue($total, $cItem->first()['unit'], TRUE).'</span>';
						if($eProduct['size']) {
							$h .= '<div><small class="color-muted"><u>'.encode($eProduct['size']).'</u></small></div>';
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
						$customer .= '<a href="'.SaleUi::url($eItem['sale']).'">'.encode($eItem['customer']->getName()).'</a>';

						$h .= '<li>';

						if($eItem['packaging']) {
							$h .= p("<b>{number} colis</b> de {quantity} {arrow} {customer}", "<b>{number} colis</b> de {quantity} {arrow} {customer}", $eItem['number'], ['number' => $eItem['number'], 'arrow' => \Asset::icon('arrow-right'), 'quantity' => '<b>'.\selling\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE).'</b>', 'customer' => $customer]);
						} else {
							$h .= s("{quantity} {arrow} {customer}", ['quantity' => '<b>'.\selling\UnitUi::getValue($eItem['number'], $eItem['unit'], TRUE).'</b>', 'customer' => $customer, 'arrow' => \Asset::icon('arrow-right')]);
						}

						$h .= '</li>';

					}

				$h .= '</ul>';
			$h .= '</div>';


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

			$h .= '<div class="item-day-one">';
				$h .= '<div class="item-day-product">';
					$h .= '<a href="/vente/'.$eSale['id'].'" class="btn btn-sm btn-outline-'.$color.'">'.$eSale->getNumber().'</a> ';
					$h .= CustomerUi::link($eCustomer);
				$h .= '</div>';

				$h .= '<ul class="item-day-sales">';

					foreach($cItem as $eItem) {

						$h .= '<li>';

						if($eItem['packaging']) {
							$h .= p("<b>{number} colis</b> de {quantity}", "<b>{number} colis</b> de {quantity}", $eItem['number'], ['number' => $eItem['number'], 'quantity' => '<b>'.\selling\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE).'</b>']);
						} else {
							$h .= '<b>'.\selling\UnitUi::getValue($eItem['number'], $eItem['unit'], TRUE).'</b>';
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
			$h .= '</div>';


		}
		$h .= '</div>';

		return $h;

	}

	protected function getUpdate(Item $eItem): string {

		$h = '<a data-dropdown-id="item-update-'.$eItem['id'].'" data-dropdown="bottom-end" class="dropdown-toggle btn btn-sm btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div data-dropdown-id="item-update-'.$eItem['id'].'-list" class="dropdown-list">';
			$h .= '<div class="dropdown-title">'.encode($eItem['name']).'</div>';
			$h .= '<a href="/selling/item:update?id='.$eItem['id'].'" class="dropdown-item">'.s("Modifier l'article").'</a>';
			$h .= '<a data-ajax="/selling/item:doDelete" post-id="'.$eItem['id'].'" class="dropdown-item" data-confirm="'.s("Supprimer l'article de la vente ?").'">'.s("Supprimer l'article").'</a>';
		$h .= '</div>';

		return $h;

	}

	public function createCollectionBySale(\farm\Farm $eFarm, Sale $e): \Panel {

		$e->expects(['cProduct', 'cCategory']);

		$form = new \util\FormUi([
			'columnBreak' => 'sm'
		]);

		$title = s("Ajouter des produits");

		if($e['cProduct']->empty()) {

			$h = '<div class="util-block-help">';
				$h .= '<p>'.s("Vous devez d'abord renseigner les produits que vous souhaitez proposer à la vente dans votre ferme !").'</p>';
				$h .= '<a href="'.\farm\FarmUi::urlSellingProduct($eFarm).'" class="btn btn-secondary">'.s("Renseigner mes produits").'</a>';
			$h .= '</div>';

			return new \Panel(
				id: 'panel-item-create-collection',
				title: $title,
				body: $h
			);

		} else {

			$h = $form->hidden('sale', $e['id']);
			$h .= $form->dynamicField($e, 'productsList');

			return new \Panel(
				id: 'panel-item-create-collection',
				title: $title,
				dialogOpen: $form->openAjax('/selling/item:doCreateCollection', ['class' => 'panel-dialog container']),
				dialogClose: $form->close(),
				body: $h,
				footer: $form->submit(s("Ajouter les produits"), ['class' => 'btn btn-primary btn-lg'])
			);

		}

	}

	public function getCreateList(\Collection $cProduct, \Collection $cCategory, \Closure $list): string {

		if($cCategory->empty()) {
			return $list($cProduct);
		}

		$ccProduct = $cProduct->reindex(['category']);

		$h = '<div class="tabs-h" id="item-create-tabs" onrender="'.encode('Lime.Tab.restore(this)').'">';

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

			$h .= '<div class="tabs-panel stick-sm">';

				foreach($ccProduct as $category => $cProduct) {

					$h .= '<div class="tab-panel" data-tab="'.($category ?: 'empty').'">';
						$h .= $list($cProduct);
					$h .= '</div>';

				}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public static function getCreateByCategory(\util\FormUi $form, Sale $eSale, \Collection $cProduct): string {

		$hasPackaging = ($eSale['type'] === Sale::PRO);
		$hasQuantity = ($eSale['market'] === FALSE or $eSale['preparationStatus'] !== Sale::SELLING);
		$hasPrice = ($eSale['market'] === FALSE);

		$class = 'items-products items-products-'.((int)$hasQuantity + (int)$hasPackaging + (int)$hasPrice).'';

		$h = '<div class="'.$class.' util-grid-header">';

			$h .= '<div style="grid-column: span 3">';
				$h .= s("Produit");
			$h .= '</div>';

			$h .= '<div class="items-products-fields">';
				if($hasPackaging) {
					$h .= '<div>'.s("Colisage").'</div>';
				}
				$h .= '<div>';
						$h .= s("Prix unitaire");
						if($eSale['hasVat']) {
							$h .= ' <span class="util-annotation">'.$eSale->getTaxes().'</span>';
						}
					$h .= '</div>';
				if($hasQuantity) {
					$h .= '<div>'.s("Quantité vendue").'</div>';
				}
				if($hasPrice) {
					$h .= '<div>';
						$h .= s("Montant total");
						if($eSale['hasVat']) {
							$h .= ' <span class="util-annotation">'.$eSale->getTaxes().'</span>';
						}
					$h .= '</div>';
				}
			$h .= '</div>';
		$h .= '</div>';

		foreach($cProduct as $eProduct) {

			$eItem = $eProduct['item'];
			$eItem['unitShort'] = TRUE;

			$attributes = [
				'id' => 'items-products-checkbox-'.$eProduct['id'],
				'onclick' => 'Item.selectProduct(this)'
			];

			$h .= '<div class="'.$class.' item-write">';

				$h .= '<label class="items-products-select">';
					$h .= $form->hidden('discount['.$eProduct['id'].']', $eItem['sale']['discount']);
					$h .= $form->hidden('unit['.$eProduct['id'].']', $eProduct['unit']);
					$h .= $form->hidden('quality['.$eProduct['id'].']', $eProduct['quality']);
					$h .= $form->hidden('locked['.$eProduct['id'].']', Item::PRICE);

					if($eSale['type'] == Customer::PRIVATE) {
						$h .= $form->hidden('packaging['.$eProduct['id'].']', '');
					}

					if($eSale['hasVat']) {
						$h .= $form->hidden('vatRate['.$eProduct['id'].']', \Setting::get('selling\vatRates')[$eProduct['vat']]);
					}

					$h .= $form->inputCheckbox('product['.$eProduct['id'].']', $eProduct['id'], $attributes);
				$h .= '</label>';
				$h .= '<label for="'.$attributes['id'].'">';
					$h .= \selling\ProductUi::getVignette($eProduct, '2rem');
				$h .= '</label>';
				$h .= '<label class="items-products-info" for="'.$attributes['id'].'">';
					$h .= \selling\ProductUi::getInfos($eProduct, includeUnit: TRUE, link: FALSE);
				$h .= '</label>';

				$h .= '<div class="items-products-fields">';

					if($hasPackaging) {

						$h .= '<div>';
							$h .= '<h4>'.s("Colisage").'</h4>';
							$h .= self::getPackagingField($form, 'packaging['.$eProduct['id'].']', $eItem);
						$h .= '</div>';


					}
					$h .= '<div data-wrapper="unitPrice['.$eProduct['id'].']">';

						$h .= '<h4>'.s("Prix unitaire").'</h4>';
						$h .= $form->dynamicField($eItem, 'unitPrice['.$eProduct['id'].']*', function(\PropertyDescriber $d) use ($form) {
							$d->append = $form->addon(s("€"));
						});

					$h .= '</div>';

					if($hasQuantity) {
						$h .= '<div data-wrapper="number['.$eProduct['id'].']">';
							$h .= '<h4>'.s("Quantité vendue").'</h4>';
							$h .= $form->dynamicField($eItem, $eSale['market'] ? 'number['.$eProduct['id'].']' : 'number['.$eProduct['id'].']*');
						$h .= '</div>';
					}

					if($hasPrice) {
						$h .= '<div data-wrapper="price['.$eProduct['id'].']">';
							$h .= '<h4>'.s("Montant total").'</h4>';
							$h .= $form->dynamicField($eItem, 'price['.$eProduct['id'].']*', function(\PropertyDescriber $d) use ($eItem) {
								$d->append = s("€");
							});
						$h .= '</div>';
					}
				$h .= '</div>';
			$h .= '</div>';

		}

		return $h;

	}

	public static function getCreateSubmit(\selling\Sale $eSale, \util\FormUi $form, string $submitText): string {

		$h = '<div class="items-submit">';
			$h .= '<div>';
				$h .= $form->submit($submitText, ['class' => 'btn btn-primary btn-lg']);
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<div class="items-submit-icon">'.\Asset::icon('basket').'</div>';
				$h .= '<span id="items-submit-articles">0</span>';
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<div class="items-submit-icon">'.\Asset::icon('currency-euro').'</div>';
				$h .= '<span id="items-submit-price">'.\util\TextUi::money(0).'</span>';
				$h .= ' '.SaleUi::getTaxes($eSale['taxes']);
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function selectBySale(Sale $eSale): \Panel {

		$eSale->expects(['farm']);

		$form = new \util\FormUi();

		$eItem = new Item([
			'farm' => $eSale['farm'],
			'sale' => $eSale,
		]);

		$h = $form->openAjax('/selling/item:create', ['method' => 'get']);

			$h .= $form->hidden('sale', $eSale['id']);

			$h .= $form->group(
				s("À partir d'un produit"),
				$form->dynamicField($eItem, 'product', function($d) {
					$d->attributes = [
						'data-autocomplete-select' => 'submit'
					];
				}),
				['class' => 'form-group-highlight']
			);

			$h .= '<div class="item-add-scratch">'.\Asset::icon('chevron-right').' <a href="/selling/item:create?sale='.$eSale['id'].'">'.s("Ajouter un article sans référence de produit").'</a></div>';

		$h .= $form->close();

		return new \Panel(
			id: 'panel-item-create',
			title: s("Ajouter un article"),
			subTitle: SaleUi::getPanelHeader($eSale),
			body: $h
		);

	}

	public function createBySale(Sale $eSale, Item $eItem): \Panel {

		$eSale->expects(['farm']);

		$eProduct = $eItem['product'];

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/item:doCreateCollection');

			$h .= $form->hidden('sale', $eSale['id']);

			if($eSale['discount'] > 0) {
				$h .= '<div class="util-success hide">';
					$h .= s("Le prix unitaire inclut la remise de {discount} % applicable à cette vente.", ['discount' => $eSale['discount']]);
				$h .= '</div>';
			}

			if($eProduct->notEmpty()) {

				$h .= '<div class="item-write">';

					$h .= $form->group(
						s("Produit"),
						ProductUi::getVignette($eProduct, '3rem').'  '.ProductUi::link($eProduct, TRUE)
					);

					$h .= $form->dynamicGroup($eItem, 'quality[0]');

					$h .= $form->hidden('product[0]', $eProduct['id']);
					$h .= $form->hidden('discount[0]', $eItem['sale']['discount']);
					$h .= $form->hidden('unit[0]', $eProduct['unit']);
					$h .= $form->hidden('locked[0]', Item::PRICE);

					if($eItem['sale']['hasVat']) {
						$h .= $form->hidden('vatRate[0]', \Setting::get('selling\vatRates')[$eProduct['vat']]);
						$h .= $form->group(
							s("Taux de TVA"),
							$form->fake(s("{value} %", \Setting::get('selling\vatRates')[$eProduct['vat']]))
						);
					}

					if($eItem['sale']['type'] === Customer::PRO) {
						$h .= self::getPackagingGroup($form, 'packaging[0]', $eItem);
					} else {
						$h .= $form->hidden('packaging[0]', '');
					}

					$h .= $form->dynamicGroups($eItem, $eItem['sale']['market'] ?
						($eItem['sale']['preparationStatus'] !== Sale::SELLING ? ['unitPrice[0]*', 'number[0]'] : ['unitPrice[0]*']) :
						['unitPrice[0]*', 'number[0]*', 'price[0]*'], [
							'unitPrice[]' => function(\PropertyDescriber $d) use ($eItem) {
								if($eItem['sale']['discount'] > 0 and $eItem['unitPrice'] !== NULL) {
									$d->after = \util\FormUi::info(s("Prix de base : {value}", \util\TextUi::money($eItem['baseUnitPrice'])));
								}
							}
					]);

				$h .= '</div>';

			} else {

				$h .= '<div class="item-write">';

					$h .= '<div class="util-block-help">';
						$h .= s("Les articles ajoutés sans référence de produit n'apparaissent pas dans la liste des commandes à préparer.");
						$h .= ' '.s("Pour plus de commodité et faciliter la gestion de votre gamme, si cet article est amené à être vendu fréquemment, vous devriez l'<link>enregistrer au préalable comme un produit</link>.", ['link' => '<a href="/selling/product:create?farm='.$eItem['sale']['farm']['id'].'">']);
					$h .= '</div>';

					$h .= $form->hidden('product[0]', '');
					$h .= $form->hidden('locked[0]', Item::PRICE);
					$h .= $form->dynamicGroup($eItem, 'name[0]*');

					if($eItem['sale']['type'] === Customer::PRO) {
						$eItem['unit'] = new Unit();
						$h .= self::getPackagingGroup($form, 'packaging[0]', $eItem);
					} else {
						$h .= $form->hidden('packaging[0]', '');
					}

					$h .= $form->dynamicGroups($eItem, $eItem['sale']['market'] ?
						($eItem['sale']['preparationStatus'] !== Sale::SELLING ? ['unit[0]', 'unitPrice[0]*', 'number[0]'] : ['unit[0]', 'unitPrice[0]*']) :
						['unit[0]', 'unitPrice[0]*', 'number[0]*', 'price[0]*']);

					if($eItem['sale']['hasVat']) {
						$h .= $form->group(
							self::p('vatRate')->label,
							$form->select('vatRate[0]', SaleUi::getVatRates($eItem['farm']), $eItem['vatRate'])
						);
					}

				$h .= '</div>';

			}

			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-item-create',
			title: $eProduct->notEmpty() ?
				s("Ajouter un produit") :
				s("Ajouter un article"),
			subTitle: SaleUi::getPanelHeader($eSale),
			body: $h
		);

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
					ProductUi::getVignette($eItem['product'], '3rem').'  '.ProductUi::link($eItem['product'])
				);
			}

			$h .= $form->dynamicGroups($eItem, ['name', 'quality']);

			if($eItem['sale']->isPro()) {
				$h .= self::getPackagingGroup($form, 'packaging', $eItem);
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

	public static function getPackagingGroup(\util\FormUi $form, string $name, Item $eItem): string {

		$h = $form->group(
			self::p('packaging')->label,
			self::getPackagingField($form, $name, $eItem)
		);

		return $h;

	}

	public static function getPackagingField(\util\FormUi $form, string $name, Item $eItem): string {

		$eItem->expects(['unit']);

		$field = '<div class="item-write-packaging">';
			$field .= $form->inputGroup(
				$form->dynamicField($eItem, $name).
				$form->addon(\selling\UnitUi::getSingular($eItem['unit']->empty() ? 'empty' : $eItem['unit'], short: $eItem['unitShort'] ?? FALSE))
			);
			$field .= '<a onclick="Item.removePackaging(this)" title="'.s("Supprimer le colisage").'" class="btn btn-primary">'.\Asset::icon('trash').'</a>';
		$field .= '</div>';

		$h = '<div class="item-write-packaging-field '.($eItem['packaging'] ? '' : 'hide').'">'.$field.'</div>';
		$h .= '<div class="item-write-packaging-link  '.($eItem['packaging'] ? 'hide' : '').'">'.\Asset::icon('plus-circle').' <a onclick="Item.addPackaging(this)">'.s("Définir un colisage").'</a></div>';

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

			case 'packaging' :
				$d->attributes = function(\util\FormUi $form, Item $eItem) use($property) {

					if($eItem['sale']['market']) {
						return [];
					} else {
						return [
							'oninput' => 'Item.recalculateLock(this)',
							'onclick' => 'this.select()'
						];
					}

				};
				break;

			case 'number' :
				self::applyLocking($d, Item::NUMBER);

				$d->append = function(\util\FormUi $form, Item $eItem) {

					$h = '<span class="item-write-packaging-label '.($eItem['packaging'] ? '' : 'hide').'">'.s("colis").'</span>';
					$h .= '<span class="item-write-unit-label '.($eItem['packaging'] ? 'hide' : '').'">'.\selling\UnitUi::getSingular($eItem['unit'], short: $eItem['unitShort'] ?? FALSE).'</span>';

					return $form->addon($h);

				};
				break;

			case 'unit' :
				$d->values = fn(Item $e) => isset($e['cUnit']) ? UnitUi::getField($e['cUnit']) : $e->expects(['cUnit']);
				$d->attributes = ['group' => TRUE];
				$d->placeholder = s("&lt; Non applicable &gt;");
				break;

			case 'unitPrice' :
				self::applyLocking($d, Item::UNIT_PRICE);

				$d->append = function(\util\FormUi $form, Item $eItem) {
					$h = s("€ {taxes}", ['taxes' => $eItem['sale']->getTaxes()]);
					$h .= \selling\UnitUi::getBy($eItem['unit'], short: $eItem['unitShort'] ?? FALSE);
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
				'oninput' => 'Item.recalculateLock(this)',
				'onclick' => 'this.select()'
			];

			if($eItem['locked'] === $property) {
				$attributes['class'] = 'disabled';
			}

			return $attributes;

		};

	}

}
?>
