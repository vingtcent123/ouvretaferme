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

	public function getBySale(Sale $eSale, \Collection $cItem, bool $isPreparing = FALSE) {

		$eItemCreate = new Item([
			'sale' => $eSale,
			'farm' => $eSale['farm']
		]);

		$h = '<div class="mb-2">';

		if($eSale->isComposition()) {
			$h .= '<div class="util-title">';
				$h .= '<div>';
					$h .= '<h3 class="mb-1">';
						if(
							$eSale['compositionEndAt'] === NULL or
							$eSale['deliveredAt'] === $eSale['compositionEndAt']
						) {
							$h .= s("Composition du {value}", \util\DateUi::numeric($eSale['deliveredAt']));
						} else {
							$h .= s("Composition du {from}<small> au {to}</small>", ['from' => \util\DateUi::numeric($eSale['deliveredAt']), 'to' => \util\DateUi::numeric($eSale['compositionEndAt']), 'small' => '<small class="color-muted" style="font-weight: normal">']);
						}
						if($eSale->acceptUpdateComposition() === FALSE) {
							$h .= ' '.\Asset::icon('lock-fill');
						}

					$h .= '</h3>';
					$h .= '<h4>';

						$value = '<span class="util-badge bg-primary">'.match($eSale['taxes']) {
							Sale::EXCLUDING => \util\TextUi::money($eSale['priceExcludingVat'] ?? 0),
							Sale::INCLUDING => \util\TextUi::money($eSale['priceIncludingVat'] ?? 0),
						}.'</span> <small class="color-muted">'.SaleUi::getTaxes($eSale['taxes']).'</small>';

						$h .= s("Valeur de {value}", $value);

					$h .= '</h4>';
				$h .= '</div>';
				if($eSale->acceptUpdateComposition()) {
					$h .= new SaleUi()->getUpdate($eSale, 'btn-outline-primary');
				}
			$h .= '</div>';
		}

		if(
			$eSale->acceptCreateItems() and
			$eItemCreate->canCreate()
		) {

			$form = new \util\FormUi();

			$eItem = new Item([
				'farm' => $eSale['farm'],
				'sale' => $eSale
			]);

			$new = '<div id="item-create-'.$eSale['id'].'" data-sale="'.$eSale['id'].'">';

				$new .= $form->dynamicField($eItem, 'product', function($d) use($eSale) {
					$d->autocompleteDispatch = '#item-create-'.$eSale['id'];
					$d->placeholder = s("Ajouter un produit");
					$d->attributes['class'] = 'form-control-lg';
				});

				if($eSale->isComposition() === FALSE) {
					$new .= $this->getCreateArticle($eSale);
				}

			$new .= '</div>';

		} else {
			$new = '';
		}

		$h .= new \selling\SaleUi()->getStats($eSale);

		if($eSale['comment']) {
			$h .= '<div class="util-block mb-2">';
				$h .= '<h4>'.s("Commentaire interne").'</h4>';
				$h .= nl2br(encode($eSale['comment']));
			$h .= '</div>';
		}

		if($eSale['shopComment']) {
			$h .= '<div class="util-block">';
				$h .= '<h4>'.s("Commentaire laissé par le client").'</h4>';
				$h .= nl2br(encode($eSale['shopComment']));
			$h .= '</div>';
		}

		if($eSale->isComposition() === FALSE) {
			$h .= '<div class="util-title">';

				$h .= '<h3>';

					if($eSale->isMarketPreparing()) {
						$h .= s("Articles disponibles dans la caisse");
						$articles = $cItem->count();
					} else if($isPreparing) {
						$h .= s("Articles à préparer");
						$articles = $cItem->find(fn($eItem) => $eItem['prepared'] === FALSE)->count();
					} else {
						$h .= s("Articles");
						$articles = $cItem->count() + ($eSale['shipping'] !== NULL ? 1 : 0);
					}


					$h .= '  <span class="util-badge bg-primary" id="item-count">'.$articles.'</span>';

				$h .= '</h3>';

				if(
					$eSale->acceptCreateItems() and
					$eItemCreate->canCreate()
				) {
					$h .= '<a href="/selling/item:createCollection?sale='.$eSale['id'].'" class="btn btn-outline-primary">';
						$h .= \Asset::icon('plus-circle').' '.s("Ajouter plusieurs produits");
					$h .= '</a>';
				}

			$h .= '</div>';

		}

		if($cItem->empty()) {

			if($eSale->acceptCreateItems()) {

				$h .= '<p class="util-empty">';

					if($eSale->isMarket()) {
						$h .= s("Il n'y a pas encore d'article à vendre !");
					} else if($eSale->isComposition()) {
						$h .= s("Il n'y a pas encore d'article !");
					} else {
						$h .= s("Il n'y a pas encore d'article dans cette vente !");
					}

				$h .= '</p>';

				$h .= $new;

			} else {
				$h .= '<div class="util-empty">';
					$h .= s("Aucun article n'a été ajouté à cette vente.");
				$h .= '</div>';
			}


		} else {

			$withVat = (
				$isPreparing === FALSE and
				$eSale['hasVat'] and
				$eSale->isComposition() === FALSE
			);
			$withPackaging = $cItem->contains(fn($eItem) => ($eItem['packaging'] !== NULL));
			$columns = 0;

			foreach($cItem as $eItem) {
				$h .= new MerchantUi()->get('/selling/item:doUpdateMerchant', $eSale, $eItem, showDelete: FALSE);
			}

			$h .= '<div class="stick-xs">';

				$h .= '<table class="tbody-even item-item-wrapper">';

					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<th class="item-item-vignette">';
								if($isPreparing) {
									$h .= s("Préparé");
								}
							$h .= '</th>';

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
								$eSale->isMarket() === FALSE or
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
							if($withVat) {
								$columns++;
								$h .= '<th class="item-item-vat text-center hide-sm-down">'.s("TVA").'</th>';
							}

							$h .= '<th></th>';
						$h .= '</tr>';
					$h .= '</thead>';

					$h .= $this->getItemsBody($eSale, $cItem, $isPreparing, $columns, $withPackaging, $withVat);

					if($eSale['shipping'] !== NULL) {

						$h .= '<tbody>';
							$h .= '<tr>';
								$h .= '<td class="item-item-vignette">'.\Asset::icon('truck').'</td>';
								$h .= '<td colspan="'.(2 + (int)$withPackaging).'">'.SaleUi::getShippingName().'</td>';
								$h .= '<td class="hide-sm-down" colspan="2"></td>';

								$h .= '<td class="item-item-price text-end">';
									$shipping = \util\TextUi::money($eSale['shipping']);

									if($eSale->isLocked() === FALSE) {
										$h .= $eSale->quick('shipping', $shipping);
									} else {
										$h .= $shipping;
									}
								$h .= '</td>';

								if($withVat) {
									$h .= '<td class="item-item-vat text-center hide-sm-down">';
										$h .= s("{value} %", $eSale['shippingVatRate']);
									$h .= '</td>';
								}

								$h .= '<td class="text-end">';
									$h .= '<a href="/selling/sale:update?id='.$eSale['id'].'" class="btn btn-sm btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
								$h .= '</td>';
							$h .= '</tr>';
						$h .= '</tbody>';

					}

			$h .= '</table>';

			$h .= '</div>';

			$h .= $new;

		}

		$h .= '</div>';

		return $h;

	}

	protected function getCreateArticle(Sale $eSale): string {

		$h = '<div class="item-add-scratch">';
			$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle">'.s("Ajouter un article sans référence de produit").'</a>';
			$h .= '<div class="dropdown-list">';
				$h .= '<div class="dropdown-title">'.s("Nouvel article").'</div>';
				$h .= '<a href="/selling/item:create?sale='.$eSale['id'].'&nature='.Item::GOOD.'" class="dropdown-item">'.s("Livraison de bien").'</a>';
				$h .= '<a href="/selling/item:create?sale='.$eSale['id'].'&nature='.Item::SERVICE.'" class="dropdown-item">'.s("Prestation de service").'</a>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	protected function getItemsBody(Sale $eSale, \Collection $cItem, bool $isPreparing, int $columns, bool $withPackaging, bool $withVat): string {

		$h = '';

		foreach($cItem as $eItem) {

			if($isPreparing) {

				$vignetteBig = \util\TextUi::switch([
					'id' => 'item-prepared-switch-'.$eItem['id'],
					'data-ajax' => '/selling/item:doUpdatePrepared',
					'post-id' => $eItem['id'],
					'post-prepared' => $eItem['prepared'] ? FALSE : TRUE
				], $eItem['prepared']);

				if($eItem['product']->notEmpty()) {
					$vignetteSmall = ProductUi::getVignette($eItem['product'], '1.5rem');
				} else {
					$vignetteSmall = '';
				}

			} else {

				if($eItem['product']->notEmpty()) {
					$vignetteBig = ProductUi::getVignette($eItem['product'], '2.75rem');
				} else {
					$vignetteBig = '';
				}

				$vignetteSmall = '';

			}

			if($eItem['quality']) {
				$quality = \farm\FarmUi::getQualityLogo($eItem['quality'], '1.5rem');
			} else {
				$quality = '';
			}


			if($eItem['product']->notEmpty()) {

				$product = $vignetteSmall.' ';

				if($eItem['product']->canRead()) {
					$product .= '<a href="'.ProductUi::url($eItem['product']).'" class="item-item-product-link">'.encode($eItem['name']).'</a>';
				} else {
					$product .= encode($eItem['name']);
				}

				if($eItem['product']['mixedFrozen']) {
					$product .= ' '.ProductUi::getFrozenIcon();
				}

			} else {
				$product = encode($eItem['name']);
			}

			$details = self::getDetails($eItem);

			if($eItem['nature'] === Item::SERVICE) {
				$details[] = s("Prestation de service");
			}

			if($details) {
				$product .= '<div class="item-item-product-description">';
					$product .= implode(' | ', $details);
				$product .= '</div>';
			}

			$h .= '<tbody>';

				$h .= '<tr class="item-item-line-1">';
					$h .= '<td class="item-item-vignette" rowspan="2">'.$vignetteBig.'</td>';
					$h .= '<td class="hide-md-up" colspan="'.($columns - 2 - (int)$withVat).'" style="border-bottom: 1px dashed var(--border)">';
						$h .= '<div class="item-item-product">';
							$h .= '<div>'.$vignetteSmall.' '.$product.'</div>';
							if($quality) {
								$h .= '<span class="item-item-product-quality">'.$quality.'</span>';
							}
						$h .= '</div>';
					$h .= '</td>';
					$h .= '<td class="item-item-empty hide-sm-down" colspan="'.$columns.'"></td>';
					if($eItem->canWrite()) {
						$h .= '<td class="item-item-actions td-min-content" rowspan="2">';
							$h .= $this->getUpdate($eItem);
						$h .= '</td>';
					} else {
						$h .= '<td rowspan="2"></td>';
					}
				$h .= '</tr>';
				$h .= '<tr class="item-item-line-2">';

					$h .= '<td class="td-min-content hide-sm-down" style="line-height: 1.2">'.$product.'</td>';
					$h .= '<td class="hide-sm-down">';
						$h .= $quality;
					$h .= '</td>';

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
									if($eItem->canUpdate()) {
										$h .= '<a onclick="Merchant.show(this)" class="util-quick" data-item="'.$eItem['id'].'" data-property="packaging">'.\selling\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE).'</a>';
									} else {
										$h .= \selling\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE);
									}
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

						$value = '';
						if($eItem['unitPriceInitial'] !== NULL) {
							$value .= new PriceUi()->priceWithoutDiscount($eItem['unitPriceInitial'], unit: ' '.$unit);
						}
						$value .= \util\TextUi::money($eItem['unitPrice']).' '.$unit;

						if($eItem['locked'] === Item::UNIT_PRICE) {
							$h .= '<span class="item-item-locked">'.\Asset::icon('lock-fill').'</span> '.$value;
						} else if($eItem->canUpdate() === FALSE) {
							$h .= $value;
						} else {
							$h .= '<a onclick="Merchant.show(this)" class="util-quick" data-item="'.$eItem['id'].'" data-property="unit-price">'.$value.'</a>';
						}

						if(
							$eSale->isMarket() and
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
						$eSale->isMarket() === FALSE or
						$eSale->isMarketPreparing() === FALSE
					) {
						$h .= '<td class="item-item-price text-end">';
							if($eItem['price'] === NULL) {
								$h .= '?';
							} else {
								$value = \util\TextUi::money($eItem['price']);

								if($eItem['locked'] === Item::PRICE) {
									$h .= '<span class="item-item-locked">'.\Asset::icon('lock-fill').'</span> '.$value;
								} else if($eItem->canUpdate() === FALSE) {
									$h .= $value;
								} else {
									$h .= '<a onclick="Merchant.show(this)" class="util-quick" data-item="'.$eItem['id'].'" data-property="price">'.$value.'</a>';
								}
							}

						$h .= '</td>';
					}

					if($withVat) {

						$h .= '<td class="item-item-vat text-center hide-sm-down">';
							$h .= $eItem->quick('vatRate', s('{value} %', $eItem['vatRate']));
						$h .= '</td>';

					}

				$h .= '</tr>';

				if($eItem['number'] !== NULL) {
					$h .= $this->getComposition($eSale, $eItem, $columns);
				}

			$h .= '</tbody>';

		}

		return $h;

	}

	public static function getDetails(Item $eItem): array {

		$more = [];

		if($eItem['additional']) {
			$more[] = '<span><u>'.encode($eItem['additional']).'</u></span>';
		}

		if($eItem['reference']) {
			$more[] = '<span><u>'.s("Référence {value}", encode($eItem['reference'])).'</u></span>';
		}

		if($eItem['origin']) {
			$more[] = '<span>'.s("Origine {value}", '<u>'.encode($eItem['origin']).'</u>').'</span>';
		}

		return $more;

	}

	public function getComposition(Sale $eSale, Item $eItem, int $columns): string {

		$h = '';

		if($eItem['composition']->notEmpty()) {

			if($eItem['cItemIngredient']->empty()) {

				$h .= '<tr class="item-item-composition">';
					$h .= '<td></td>';
					$h .= '<td colspan="'.($columns + 1).'" class="color-muted">'.s("Pas de composition connue au {value}", \util\DateUi::numeric($eSale['deliveredAt'])).'</td>';
				$h .= '</tr>';

			} else {

				foreach($eItem['cItemIngredient'] as $eItemIngredient) {

					$h .= '<tr class="item-item-composition">';
						$h .= '<td></td>';
						$h .= '<td colspan="2">'.ProductUi::getVignette($eItemIngredient['product'], '1.5rem').' '.encode($eItemIngredient['name']).'</td>';
						$h .= '<td class="item-item-composition-number text-end">'.\selling\UnitUi::getValue($eItemIngredient['number'] * ($eItemIngredient['packaging'] ?? 1), $eItemIngredient['unit'], TRUE).'</td>';
						$h .= '<td colspan="'.($columns - 2).'"></td>';
					$h .= '</tr>';

				}

			}

		}

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
							$h .= '<a href="/vente/'.$eItem['sale']['id'].'" class="btn btn-sm btn-outline-primary">'.$eItem['sale']['document'].'</a> ';
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

	public function getSummary(\farm\Farm $eFarm, ?string $date, \Collection $cSale, \Collection $ccItemProduct, \Collection $ccItemSale, \Collection $cPaymentMethod) {

		if($cSale->empty()) {
			$h = '<div class="util-empty">'.s("Il n'y a aucune commande à préparer pour ce jour").'</div>';
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
						$h .= new SaleUi()->getList($eFarm, $cSale, hide: ['paymentMethod'], cPaymentMethod: $cPaymentMethod);
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

		$title = $date ?
			s("Commandes pour le {value}", lcfirst(\util\DateUi::getDayName(date('N', strtotime($date)))).' '.\util\DateUi::textual($date, \util\DateUi::DAY_MONTH)) :
			s("Commandes sélectionnées");

		if(
			$ccItemProduct->contains(fn($eItem) => $eItem['containsComposition'] or $eItem['containsIngredient'], depth: 2)
		) {
			$header = '<div style="display: flex; justify-content: space-between; align-items: center">';
				$header .= '<h2 class="panel-title">'.$title.'</h2>';
				$header .= SaleUi::getCompositionSwitch($eFarm, 'btn-outline-primary');
			$header .= '</div>';
		} else {
			$header = '<h2 class="panel-title">'.$title.'</h2>';
		}

		return new \Panel(
			id: 'panel-item-summary',
			header: $header,
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
							if($eProduct['additional']) {
								$h .= ' <br class="hide-lg-up"/><small class="color-muted"><u>'.encode($eProduct['additional']).'</u></small>';
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
						if($eProduct['additional']) {
							$h .= '<div><small class="color-muted"><u>'.encode($eProduct['additional']).'</u></small></div>';
						}
					$h .= '</div>';
				$h .= '</div>';

				$h .= '<ul class="item-day-sales">';

					foreach($cItem as $eItem) {

						$customer = '<a href="'.SaleUi::url($eItem['sale']).'" class="btn btn-xs sale-preparation-status-'.$cSale[$eItem['sale']['id']]['preparationStatus'].'-button">'.$eItem['sale']['id'].'</a> ';
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

			$h .= '<div class="item-day-one">';
				$h .= '<div class="item-day-product">';
					$h .= '<a href="/vente/'.$eSale['id'].'" class="btn btn-sm sale-preparation-status-'.$eSale['preparationStatus'].'-button">'.$eSale['document'].'</a> ';
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
				$h .= '<a href="'.\farm\FarmUi::urlSellingProducts($eFarm).'" class="btn btn-secondary">'.s("Renseigner mes produits").'</a>';
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
				dialogOpen: $form->openAjax('/selling/item:doCreateCollection', ['class' => 'panel-dialog']),
				dialogClose: $form->close(),
				body: $h,
				footer: $form->submit(s("Ajouter les produits"), ['class' => 'btn btn-primary btn-lg'])
			);

		}

	}

	public function getCreateList(\Collection $cProduct, \Collection $cCategory, \Closure $list): string {

		$h = '<div id="item-create-wrapper">';

			if($cCategory->empty()) {
				$h .= $list($cProduct);
			} else {

				$ccProduct = $cProduct->reindex(['category']);

				$h .= '<div class="tabs-h" id="item-create-tabs" onrender="'.encode('Lime.Tab.restore(this)').'">';

					$h .= '<div class="tabs-item">';

						foreach($cCategory as $eCategory) {

							if($ccProduct->offsetExists($eCategory['id']) === FALSE) {
								continue;
							}

							$products = $ccProduct[$eCategory['id']]->find(fn($eProduct) => $eProduct['checked'] ?? FALSE)->count();

							$h .= '<a class="tab-item" data-tab="'.$eCategory['id'].'" onclick="Lime.Tab.select(this)">';
								$h .= '<span class="util-badge bg-danger mr-1">';
									$h .= \Asset::icon('exclamation-circle-fill');
								$h .= '</span>';
								$h .= encode($eCategory['name']);
								$h .= '<span class="tab-item-count">';
									if($products > 0) {
										$h .= $products;
									}
								$h .= '</span>';
							$h .= '</a>';
							$h .= '<style>';
							$h .= '#item-create-tabs:has(.tab-panel[data-tab="'.$eCategory['id'].'"] .form-error-wrapper) .tab-item[data-tab="'.$eCategory['id'].'"] .util-badge { display: block; }';
							$h .= '</style>';

						}

						if($ccProduct->offsetExists('')) {

							$products = $ccProduct['']->find(fn($eProduct) => $eProduct['checked'] ?? FALSE)->count();

							$h .= '<a class="tab-item" data-tab="empty" onclick="Lime.Tab.select(this)">';
								$h .= '<span class="util-badge bg-danger mr-1">';
									$h .= \Asset::icon('exclamation-circle-fill');
								$h .= '</span>';
								$h .= s("Non catégorisé");
								$h .= '<span class="tab-item-count">';
									if($products > 0) {
										$h .= $products;
									}
								$h .= '</span>';
							$h .= '</a>';
							$h .= '<style>';
							$h .= '#item-create-tabs:has(.tab-panel[data-tab="empty"] .form-error-wrapper) .tab-item[data-tab="empty"] .util-badge { display: block; }';
							$h .= '</style>';

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

			}

		$h .= '</div>';

		return $h;

	}

	public static function getCreateByCategory(\util\FormUi $form, Sale $eSale, \Collection $cProduct): string {

		$hasPackaging = ($eSale['type'] === Sale::PRO);
		$hasQuantity = ($eSale->isMarket() === FALSE or $eSale['preparationStatus'] !== Sale::SELLING);
		$hasPrice = ($eSale->isMarket() === FALSE);
		$hasStock = $cProduct->match(fn($eProduct) => $eProduct['stock'] !== NULL);

		$h = '<div class="items-products items-products-'.$eSale['type'].' util-grid-header">';

			$h .= '<div style="grid-column: span 3">';
				$h .= s("Produit");
			$h .= '</div>';

			$h .= '<div class="items-products-fields">';
				if($hasPackaging) {
					$h .= '<div class="items-products-packaging">'.s("Colisage").'</div>';
				}
				$h .= '<div class="items-products-unit-price">';
					$h .= s("Prix unitaire");
					if($eSale['hasVat']) {
						$h .= ' <span class="util-annotation">'.$eSale->getTaxes().'</span>';
					}
				$h .= '</div>';
				if($hasQuantity) {
					$h .= '<div class="items-products-number">'.s("Quantité vendue").'</div>';
				}
				if($hasPrice) {
					$h .= '<div class="items-products-price">';
						$h .= s("Montant total");
						if($eSale['hasVat']) {
							$h .= ' <span class="util-annotation">'.$eSale->getTaxes().'</span>';
						}
					$h .= '</div>';
				}
				if($hasStock) {
					$h .= '<div class="items-products-stock">';
						$h .= s("Stock");
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

			$h .= '<div class="items-products items-products-'.$eSale['type'].' item-write">';

				$h .= '<label class="items-products-select">';
					$h .= $form->hidden('discount['.$eProduct['id'].']', $eItem['sale']['discount']);
					$h .= $form->hidden('unit['.$eProduct['id'].']', $eProduct['unit']);
					$h .= $form->hidden('quality['.$eProduct['id'].']', $eProduct['quality']);
					$h .= $form->hidden('locked['.$eProduct['id'].']', Item::PRICE);

					if($eSale['type'] == Customer::PRIVATE) {
						$h .= $form->hidden('packaging['.$eProduct['id'].']', '');
					}

					if($eSale['hasVat']) {
						$h .= $form->hidden('vatRate['.$eProduct['id'].']', SellingSetting::getVatRate($eSale['farm'], $eProduct['vat']));
					}

					$h .= $form->inputCheckbox('product['.$eProduct['id'].']', $eProduct['id'], $attributes);
				$h .= '</label>';
				$h .= '<label for="'.$attributes['id'].'">';
					$h .= \selling\ProductUi::getVignette($eProduct, '3rem');
				$h .= '</label>';
				$h .= '<label class="items-products-info" for="'.$attributes['id'].'">';
					$h .= \selling\ProductUi::getInfos($eProduct, includeUnit: TRUE, link: FALSE);
					$h .= ItemUi::getGrid($eItem);
				$h .= '</label>';

				$h .= '<div class="items-products-fields">';

					if($hasPackaging) {

						$h .= '<div class="items-products-packaging" data-wrapper="packaging['.$eProduct['id'].']">';
							$h .= '<h4>'.s("Colisage").'</h4>';
							$h .= self::getPackagingField($form, 'packaging['.$eProduct['id'].']', $eItem);
						$h .= '</div>';


					}
					$h .= '<div class="items-products-unit-price" data-wrapper="unitPrice['.$eProduct['id'].']">';

						$h .= '<h4>'.s("Prix unitaire").'</h4>';
						$h .= $form->dynamicField($eItem, 'unitPrice['.$eProduct['id'].']*');

					$h .= '</div>';

					if($hasQuantity) {
						$h .= '<div class="items-products-number" data-wrapper="number['.$eProduct['id'].']">';
							$h .= '<h4>'.s("Quantité vendue").'</h4>';
							$h .= $form->dynamicField($eItem, $eSale->isMarket() ? 'number['.$eProduct['id'].']' : 'number['.$eProduct['id'].']*');
						$h .= '</div>';
					}

					if($hasPrice) {
						$h .= '<div class="items-products-price" data-wrapper="price['.$eProduct['id'].']">';
							$h .= '<h4>'.s("Montant total").'</h4>';
							$h .= $form->dynamicField($eItem, 'price['.$eProduct['id'].']*', function(\PropertyDescriber $d) use($eItem) {
								$d->append = s("€");
							});
						$h .= '</div>';
					}

					if($hasStock) {
						$h .= '<div class="items-products-stock">';
							if($eProduct['stock'] !== NULL) {
								$h .= '<h4>'.s("Stock").'</h4>';
								$h .= '<div>';
									$h .= StockUi::getExpired($eProduct);
									$h .= '<a href="'.\farm\FarmUi::urlSellingStock($eSale['farm']).'" title="'.StockUi::getDate($eProduct['stockUpdatedAt']).'">'.$eProduct['stock'].'</a>';
								$h .= '</div>';
							}
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
				$h .= $form->submit($submitText, ['data-waiter' => s("Création en cours..."), 'class' => 'btn btn-primary btn-lg']);
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

			$h .= $this->getCreateArticle($eSale);

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
		$buttonPersonnalize = '';

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/item:doCreateCollection', ['onrender' => 'this.qs("[name^=\'number\']").focus()']);

			$h .= $form->hidden('sale', $eSale['id']);

			if($eProduct->notEmpty()) {

				$h .= '<div class="item-write">';

					$grid = ItemUi::getGrid($eItem);

					if($grid) {
						$h .= $grid;
						$h .= '<br/>';
					}

					$h .= $form->group(
						s("Produit"),
						ProductUi::getVignette($eProduct, '3rem').'  '.ProductUi::link($eProduct, TRUE)
					);

					$h .= $form->dynamicGroup($eItem, 'quality[0]');

					$h .= $form->hidden('product[0]', $eProduct['id']);
					$h .= $form->hidden('unit[0]', $eProduct['unit']);
					$h .= $form->hidden('locked[0]', Item::PRICE);

					if($eItem['sale']['hasVat']) {
						$h .= $form->hidden('vatRate[0]', SellingSetting::getVatRate($eSale['farm'], $eProduct['vat']));
						$h .= $form->group(
							s("Taux de TVA"),
							$form->fake(s("{value} %", SellingSetting::getVatRate($eSale['farm'], $eProduct['vat'])))
						);
					}

					if($eItem['sale']['type'] === Customer::PRO) {
						$h .= self::getPackagingGroup($form, 'packaging[0]', $eItem);
					} else {
						$h .= $form->hidden('packaging[0]', '');
					}

					$h .= $form->dynamicGroups($eItem, $eItem['sale']->isMarket() ?
						($eItem['sale']['preparationStatus'] !== Sale::SELLING ? ['unitPrice[0]*', 'number[0]'] : ['unitPrice[0]*']) :
						['unitPrice[0]*', 'number[0]*', 'price[0]*']);

				$h .= '</div>';

			} else {

				$h .= '<div class="item-write">';

					$h .= '<div class="util-block-help">';
						$h .= s("Les articles ajoutés sans référence de produit n'apparaissent pas dans la liste des commandes à préparer.");
						$h .= ' '.s("Pour plus de commodité et faciliter la gestion de votre gamme, si cet article est amené à être vendu fréquemment, vous devriez l'<link>enregistrer au préalable comme un produit</link>.", ['link' => '<a href="/selling/product:create?farm='.$eItem['sale']['farm']['id'].'">']);
					$h .= '</div>';

					$h .= $form->hidden('product[0]', '');
					$h .= $form->hidden('locked[0]', Item::PRICE);
					$h .= $form->hidden('nature[0]', $eItem['nature']);

					$h .= $form->group(
						s("Nature"),
						'<b>'.self::p('nature')->values[$eItem['nature']].'</b>'
					);

					$h .= $form->dynamicGroup($eItem, 'name[0]*');

					if($eItem['nature'] === Item::GOOD) {

						$h .= $form->dynamicGroup($eItem, 'quality[0]');

						if($eItem['sale']['type'] === Customer::PRO) {
							$eItem['unit'] = new Unit();
							$h .= self::getPackagingGroup($form, 'packaging[0]', $eItem);
						} else {
							$h .= $form->hidden('packaging[0]', '');
						}

					}

					$h .= $form->dynamicGroups($eItem, $eItem['sale']->isMarket() ?
						($eItem['sale']['preparationStatus'] !== Sale::SELLING ? ['unit[0]', 'unitPrice[0]*', 'number[0]'] : ['unit[0]', 'unitPrice[0]*']) :
						['unit[0]', 'unitPrice[0]*', 'number[0]*', 'price[0]*']);

					if($eItem['sale']['hasVat']) {
						$h .= $form->group(
							self::p('vatRate')->label,
							$form->select('vatRate[0]', SaleUi::getVatRates($eItem['farm']), $eItem['vatRate'])
						);
					}

					if($eSale['farm']->hasAccounting()) {
						$h .= $form->dynamicGroup($eItem, 'account[0]',  function($d) use($form) {
							$d->group['class'] = 'hide';
						});
						$buttonPersonnalize = '<a onclick="Item.showAccountingField(this);" class="btn btn-primary bg-accounting">'.s("Personnaliser pour la comptabilité").'</a>';
					}

				$h .= '</div>';

			}

			$h .= $form->group(
				content: '<div class="flex-justify-space-between">'.$form->submit(s("Ajouter")).$buttonPersonnalize.'</div>'
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

			$h .= $form->group(
				s("Nature"),
				'<b>'.self::p('nature')->values[$eItem['nature']].'</b>'
			);

			$h .= $form->dynamicGroups($eItem, ['name', 'additional']);

			if($eItem['nature'] === Item::GOOD) {

				$h .= $form->dynamicGroup($eItem, 'origin');
				$h .= $form->dynamicGroup($eItem, 'quality');

				if($eItem['sale']->isPro()) {
					$h .= self::getPackagingGroup($form, 'packaging', $eItem);
				}

			}

			$h .= $form->dynamicGroup($eItem, 'unitPrice');
			$h .= $form->dynamicGroup($eItem, 'number');

			if($eItem['sale']->isMarket() === FALSE) {
				$h .= $form->dynamicGroup($eItem, 'price');
			}

			if($eItem['sale']['hasVat']) {
				$h .= $form->dynamicGroup($eItem, 'vatRate');
			}

			if($eItem['farm']->hasAccounting()) {
				$h .= '<br /><h3><span class="util-badge bg-accounting">'.s("Comptabilité").'</span></h3>';
					$h .= '<div class="util-block bg-background-light">'.$form->dynamicGroup($eItem, 'account').'</div>';
			}

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-item-update',
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

	public static function getGrid(Item $eItem): string {

		if(
			$eItem->exists() === FALSE and
			$eItem['grid']->notEmpty()
		) {

			$h = '<div class="color-muted font-sm">';

				$h .= \Asset::icon('info-circle').' ';

				if($eItem['grid']['customer']->notEmpty()) {
					$h .= s("Prix personnalisé appliqué");
				} else {
					$h .= s("Prix {value} appliqué", CustomerGroupUi::link($eItem['grid']['group']));
				}

			$h .= '</div>';

			return $h;

		} else {
			return '';
		}

	}

	public function updateAccount(\farm\Farm $eFarm, \Collection $cItem): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/item:doUpdateAccountCollection', ['id' => 'item-update-account']);

		$h .= $form->hidden('farm', $eFarm['id']);
		$h .= $form->group(
			s("Numéro de compte"),
			$form->dynamicField(new Item(['farm' => $eFarm, 'account' => new \account\Account()]), 'account'),
		);

		foreach($cItem as $eItem) {
			$h .= $form->hidden('ids[]', $eItem['id']);
		}

		$h .= $form->submit(s("Enregistrer"));

		$h .= $form->close();

		return new \Panel(
			id: 'panel-item-update-account',
			title: s("Numéro de compte des articles sélectionnés").'<h3><span class="util-badge bg-accounting">'.s("Comptabilité").'</span></h3>',
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Item::model()->describer($property, [
			'name' => s("Désignation"),
			'additional' => s("Complément d'information"),
			'nature' => s("Nature de la prestation"),
			'origin' => s("Origine"),
			'product' => s("Produit"),
			'quality' => s("Signe de qualité"),
			'packaging' => s("Colisage"),
			'unit' => s("Unité de vente"),
			'unitPrice' => s("Prix unitaire"),
			'unitPriceDiscount' => s("Prix remisé"),
			'price' => s("Montant"),
			'number' => s("Quantité vendue"),
			'vatRate' => s("Taux de TVA"),
			'account' => s("Numéro de compte"),
		]);

		switch($property) {

			case 'product' :
				$d->autocompleteBody = function(\util\FormUi $form, Item $e) {
					$e->expects([
						'farm',
						'sale' => ['type', 'profile']
					]);
					return [
						'farm' => $e['farm']['id'],
						'type' => $e['sale']['type'],
						'withComposition' => ($e['sale']['profile'] !== Sale::COMPOSITION),
					];
				};
				new ProductUi()->query($d);
				break;

			case 'nature' :
				$d->values = [
					Item::GOOD => s("Livraison de bien"),
					Item::SERVICE => s("Prestation de service"),
				];
				break;

			case 'origin' :
				$d->attributes = [
					'placeholder' => s("Ex. : Ferme d'à côté (63)"),
				];
				break;

			case 'additional' :
				$d->placeholder = s("Ex. : calibrage, version...");
				break;

			case 'quality' :
				$d->field = 'select';
				$d->values = \farm\FarmUi::getQualities();
				$d->attributes['mandatory'] = TRUE;
				break;

			case 'packaging' :
				$d->attributes = function(\util\FormUi $form, Item $eItem) use($property) {

					if($eItem['sale']->isMarket()) {
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

				$d->append = function(\util\FormUi $form, Item $e) {

					$unit = s("€ {taxes}", ['taxes' => $e['sale']->getTaxes()]);
					$unit .= \selling\UnitUi::getBy($e['unit'], short: $e['unitShort'] ?? FALSE);

					return $form->addon($unit);

				};

				$d->default = fn(Item $e) => $e['unitPriceInitial'] ?? $e['unitPrice'] ?? '';

				$d->attributes = [
					'step' => 0.01,
					'onfocus' => 'this.select()',
					'oninput' => 'Item.recalculateLock(this)'
				];

				$d->group = fn() => ['wrapper' => $d->name.' '.str_replace('unitPrice', 'unitPriceDiscount', $d->name)];

				$d->after = function(\util\FormUi $form, Item $e) use ($d) {

					$unit = s("€ {taxes}", ['taxes' => $e['sale']->getTaxes()]);
					$unit .= \selling\UnitUi::getBy($e['unit'], short: $e['unitShort'] ?? FALSE);

					$unitPriceDiscount = ($e['unitPriceInitial'] ?? NULL) !== NULL ? $e['unitPrice'] ?? '' : '';

					$identifier = $e['id'] ?? $e['product']['id'] ?? 0;

					$h = new PriceUi()->getDiscountLink($identifier, hasDiscountPrice: empty($unitPriceDiscount) === FALSE);

					$h .= $form->inputGroup(
						$form->number(str_replace('unitPrice', 'unitPriceDiscount', $d->name), $unitPriceDiscount, [
							'step' => 0.01,
							'oninput' => 'Item.recalculateLock(this)'
						]).
						$form->addon($unit).
						$form->addon(new PriceUi()->getDiscountTrashAddon($identifier)),
						['class' => (empty($unitPriceDiscount) ? ' hide' : ''), 'data-price-discount' => $identifier, 'data-wrapper' => 'unitPriceDiscount']
					);

					return $h;

				};
				break;

			case 'vatRate' :
				$d->append = s("%");
				break;

			case 'price' :
				self::applyLocking($d, Item::PRICE);

				$d->append = fn(\util\FormUi $form, Item $eItem) => $form->addon(s("€ {taxes}", ['taxes' => $eItem['sale']->getTaxes()]));
				break;

			case 'account':
				$d->autocompleteBody = function(\util\FormUi $form, Item $e) {
					return [
					];
				};
				$d->group += ['wrapper' => 'account'];
				$d->autocompleteDefault = fn(Item $e) => $e['account'] ?? NULL;
				$classPrefixes = ['classPrefixes[0]' => \account\AccountSetting::PRODUCT_SOLD_ACCOUNT_CLASS];
				$index = 0;
				foreach(\account\AccountSetting::WAITING_ACCOUNT_CLASSES as $class) {
					$index++;
					$classPrefixes['classPrefixes['.$index.']'] = $class;
				}
				new \account\AccountUi()->query($d, GET('farm', 'farm\Farm'), query: $classPrefixes);
				break;

		}

		return $d;

	}

	protected static function applyLocking(\PropertyDescriber $d, string $property) {

		$d->prepend = function(\util\FormUi $form, Item $eItem) use($property) {

			if(
				$eItem->isQuick() or
				$eItem['sale']->isMarket()
			) {
				return NULL;
			}

			$h = '<a onclick="Item.lock(this)" data-locked="'.$property.'" class="input-group-addon '.($eItem['locked'] === $property ? 'item-write-locked' : '').'">'.\Asset::icon('lock-fill').\Asset::icon('unlock').'</a>';
			return $h;
		};
		$d->attributes = function(\util\FormUi $form, Item $eItem) use($property) {

			if(
				$eItem->isQuick() or
				$eItem['sale']->isMarket()
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
