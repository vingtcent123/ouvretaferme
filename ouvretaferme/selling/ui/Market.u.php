<?php
namespace selling;

class MarketUi {

	public function __construct() {

		\Asset::css('selling', 'market.css');
		\Asset::js('selling', 'market.js');

	}

	public function getStats(Sale $eSaleCurrent, array $cSaleLast): string {

		[$cSaleAfter, $cSaleBefore] = $cSaleLast;

		if(
			$cSaleAfter->empty() and
			$cSaleBefore->empty() and
			$eSaleCurrent['priceIncludingVat'] === NULL
		) {
			return '';
		}

		$h = '<h2>'.s("Chiffre d'affaires").'</h2>';

		$h .= '<ul class="util-summarize util-summarize-overflow">';

		foreach($cSaleAfter as $eSale) {
			$h .= $this->getStatsSale($eSale, FALSE);
		}

		if($eSaleCurrent['priceIncludingVat'] !== NULL) {
			$h .= $this->getStatsSale($eSaleCurrent, TRUE);
		}

		foreach($cSaleBefore as $eSale) {
			$h .= $this->getStatsSale($eSale, FALSE);
		}

		$h .= '</ul>';

		$h .= '<br/>';

		return $h;

	}

	protected function getStatsSale(Sale $eSale, bool $selected): string {

		if($eSale->getTaxes()) {
			$taxes = '<small class="color-muted"> '.$eSale->getTaxes().'</small>';
		} else {
			$taxes = '';
		}

		$h = '<li '.($selected ? 'class="selected"' : '').'>';
			$h .= '<a href="'.\selling\SaleUi::urlMarket($eSale).'/ventes">';
				$h .= '<h5>'.\util\DateUi::numeric($eSale['deliveredAt']).'</h5>';
				$h .= '<div>'.\util\TextUi::money($eSale['priceIncludingVat']).$taxes.'</div>';
				$h .= '<div class="util-summarize-muted">'.p("{value} vente", "{value} ventes", $eSale['marketSales']).'</div>';
			$h .= '</a>';
		$h .= '</li>';

		return $h;

	}

	public function getHours(array $hours): string {

		if($hours === []) {
			return '';
		}

		$h = '<h2>'.s("Répartition des ventes").'</h2>';

		\Asset::jsUrl('https://cdn.jsdelivr.net/npm/chart.js');

		$turnovers = array_column($hours, 'turnover');
		$sales = array_column($hours, 'sales');

		$labels = [];
		foreach($hours as $hour) {
			$labels[] = $hour['hour'].':00';
		}

		$h .= '<div style="width: '.(100 + count($labels) * 50).'px; height: 250px">';
			$h .= '<canvas '.attr('onrender', 'Analyze.createBarLine(this, "'.s("Ventes").'", '.json_encode($turnovers).', "'.s("Clients").'", '.json_encode($sales).', '.json_encode($labels).')').'</canvas>';
		$h .= '</div>';

		$h .= '<br/>';

		return $h;

	}

	public function getBestProducts(\farm\Farm $eFarm, \Collection $cSale, \Collection $cItemProduct, \Collection $cItemStats): string {

		if($cItemProduct->empty()) {
			return '';
		}

		$sales = $cSale->count();

		$h = '<div class="util-title">';
			$h .= '<h2>'.s("Meilleures ventes").'</h2>';
			$h .= '<div>';
				if($cItemProduct->contains(fn($eItemProduct) => $eItemProduct['containsComposition'] or $eItemProduct['containsIngredient'])) {
					$h .= SaleUi::getCompositionSwitch($eFarm, 'btn-outline-primary').' ';
				}
			$h .= '</div>';
		$h .= '</div>';

		$h .= '<div class="analyze-chart-table">';
			$h .= new AnalyzeUi()->getBestProductsPie($cItemProduct);
			$h .= new AnalyzeUi()->getBestProductsTable(
				$cItemProduct,
				zoom: FALSE, expand: FALSE, hide: ['average'],
				moreTh: '<th class="text-end hide-sm-down">'.s("Fréquence<br/>de vente").'</th><th class="text-end hide-sm-down">'.s("Dernière<br/>vente").'</th>',
				moreTd: function(Item $eItemProduct) use($cItemStats, $sales) {

					if($cItemStats->offsetExists($eItemProduct['product']['id']) === FALSE) {
						return str_repeat('<td class="text-end color-muted">-</td>', 2);
					} else {

						$eItemStats = $cItemStats[$eItemProduct['product']['id']];

						$h = '<td class="text-end">'.s("{value} %", round($eItemStats['sales'] / $sales * 100)).'</td>';
						$h .= '<td class="text-end">'.substr($eItemStats['last'], 11, 5).'</td>';

						return $h;

					}


				}
			);
		$h .= '</div>';
		$h .= '<br/>';

		return $h;

	}

	public function getList(Sale $eSaleParent, \Collection $cSale, ?Sale $eSaleSelected = NULL): string {

		$h = '';

		foreach($cSale as $eSale) {

			$h .= '<a href="'.\selling\SaleUi::urlMarket($eSaleParent).'/vente/'.$eSale['id'].'" class="market-sales-item market-sales-item-'.$eSale['preparationStatus'].' '.(($eSaleSelected and $eSaleSelected['id'] === $eSale['id']) ? 'selected' : '').'">';

				$h .= $this->getCircle($eSale);

				$h .= '<div>';
					if($eSale['customer']->empty()) {
						$h .= s("Anonyme à {time}", ['time' => \util\DateUi::numeric($eSale['createdAt'], \util\DateUi::TIME)]);
					} else {
						$h .= s("{user} à {time}", ['user' => encode($eSale['customer']->getName()), 'time' => \util\DateUi::numeric($eSale['createdAt'], \util\DateUi::TIME)]);
					}
					$h .= '<br/><small id="market-sale-'.$eSale['id'].'-price">'.\util\TextUi::money($eSale['priceIncludingVat'] ?? 0).'</small>';
				$h .= '</div>';
				$h .= '<div class="market-sales-owner" title="'.s("Vente créée par {value}", $eSale['createdBy']->getName()).'">';
					$h .= \user\UserUi::getVignette($eSale['createdBy'], '1.5rem');
				$h .= '</div>';

			$h .= '</a>';

		}

		return $h;

	}

	public function displayItems(Sale $eSale, \Collection $cItemMarket): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/market:doUpdatePrices', ['id' => 'market-item-form']);

			$h .= $form->hidden('id', $eSale['id']);

			$h .= '<div class="util-title">';
				$h .= '<h2>'.s("Articles proposés à la vente").'</h2>';
				if($eSale->acceptCreateItems()) {
					$h .= '<div>';
						$h .= '<a href="/selling/item:select?sale='.$eSale['id'].'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter un article").'</a>';
					$h .= '</div>';
				}
			$h .= '</div>';

			if($cItemMarket->empty()) {

				$h .= '<div class="util-empty">';
					$h .= s("Vous ne proposez pas encore d'article à la vente.");
				$h .= '</div>';

			} else {

				$h .= '<div id="market-item-list" class="market-item-wrapper">';

					foreach($cItemMarket as $eItemMarket) {
						$h .= $this->getItemMarket($eItemMarket, $form);
					}

				$h .= '</div>';

				$h .= $this->getItemBanner($form);

			}

		$h .= '</form>';

		return $h;

	}

	public function getItemMarket(Item $eItem, \util\FormUi $form): string {

		$h = '<div class="market-item">';

			$h .= $this->getItemProduct($eItem);

			$h .= '<div class="market-item-text">';

					$h .= '<div style="margin-bottom: 0.25rem">'.s("Prix unitaire").'</div>';
					$h .= '<div>';
						$h .= $form->inputGroup(
							$form->number('unitPrice['.$eItem['id'].']', attributes: ['placeholder' => $eItem['unitPrice'], 'step' => 0.01, 'class' => 'text-end']).
							$form->addon('<small>€ '.\selling\UnitUi::getBy($eItem['unit'], short: TRUE).'</small>')
						);
				$h .= '</div>';

				$h .= '<div class="market-item-fields">';
						$h .= '<div>'.s("Vendu").'</div>';
						$h .= '<div>';
							$h .= \selling\UnitUi::getValue($eItem['number'], $eItem['unit'], short: TRUE);
						$h .= '</div>';
						$h .= '<div>'.s("Montant").'</div>';
						$h .= '<div>';
							$h .= \util\TextUi::money($eItem['price']);
						$h .= '</div>';
				$h .= '</div>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getItemBanner(\util\FormUi $form): string {

		$h = '<div class="market-banner hide" id="market-item-banner" onrender="Market.itemListUpdate()">';
			$h .= '<div>';
				$h .= '<div class="market-banner-icon">'.\Asset::icon('pencil').'</div>';
				$h .= '<span id="market-item-banner-one">'.s("1 prix modifié").'</span>';
				$h .= '<span id="market-item-banner-more">'.s("{value} prix modifiés", '<span id="market-item-banner-items"></span>').'</span>';
			$h .= '</div>';
			$h .= '<div style="display: flex;">';
				$h .= $form->submit(s("Enregistrer"), ['class' => 'btn btn-transparent']);
				$h .= '&nbsp;';
				$h .= '<a onclick="Market.itemEmpty()" class="btn btn-danger">';
					$h .= s("Annuler");
				$h .= '</a>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function displaySale(Sale $eSale, \Collection $cItemSale, Sale $eSaleMarket, \Collection $cItemMarket, \Collection $cMethod): string {

		$eSaleMarket->expects(['preparationStatus']);

		$h = '<div class="market-customer">';
			$h .= '<div class="util-title">';

				$h .= '<h2>'.s("Vente de {date}", ['date' => \util\DateUi::numeric($eSale['createdAt'], \util\DateUi::TIME)]).'</h2>';

				if($eSaleMarket['preparationStatus'] !== \selling\Sale::DELIVERED) {

					switch($eSale['preparationStatus']) {

						case Sale::DRAFT :

							if($cItemSale->empty()) {
								$h .= '<div>';
									$h .= '<a data-ajax="/selling/market:doDelete" post-id="'.$eSale['id'].'" class="btn btn-danger" data-confirm="'.s("Voulez-vous réellement supprimer cette vente ?").'">'.s("Supprimer la vente").'</a>';
								$h .= '</div>';
							} else {
								$h .= '<div>';
									$h .= '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::DELIVERED.'" class="btn btn-success" data-confirm="'.s("Voulez-vous réellement terminer cette vente ?").'">'.s("Terminer la vente").'</a> ';
									$h .= '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::CANCELED.'" class="btn btn-muted" data-confirm="'.s("Voulez-vous réellement annuler cette vente ?").'">'.s("Annuler la vente").'</a>';
								$h .= '</div>';
							}

							break;

						case Sale::CANCELED :
						case Sale::DELIVERED :
							$h .= '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::DRAFT.'" class="btn btn-outline-primary" data-confirm="'.s("Voulez-vous réellement remettre cette vente en cours ?").'">'.s("Repasser en cours").'</a> ';

							break;

					}

				}

			$h .= '</div>';

			$h .= '<div class="util-block stick-xs">';
				$h .= '<dl class="market-customer-details util-presentation util-presentation-2">';
					$h .= '<dt>'.s("Client").'</dt>';
					$h .= '<dd>';
						if($eSale['customer']->empty()) {
							$h .= '<a href="/selling/sale:updateCustomer?id='.$eSale['id'].'">'.encode($eSale['customer']->getName()).'</a>';
						} else {
							$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle">'.encode($eSale['customer']->getName()).'</a>';
							$h .= '<div class="dropdown-list bg-secondary">';
								$h .= '<a href="'.CustomerUi::url($eSale['customer']).'" class="dropdown-item">'.s("Voir le client").'</a>';
								$h .= '<a href="/selling/sale:updateCustomer?id='.$eSale['id'].'" class="dropdown-item">'.s("Changer de client").'</a>';
							$h .= '</div>';
						}
					$h .= '</dd>';
					$h .= '<dt>'.s("Créée par").'</dt>';
					$h .= '<dd>'.\user\UserUi::getVignette($eSale['createdBy'], '1.5rem').' '.$eSale['createdBy']->getName().'</dd>';
					$h .= '<dt>'.s("État").'</dt>';
					$h .= '<dd>'.$this->getCircle($eSale).' '.$this->getStatus($eSale).'</dd>';
					$h .= '<dt>'.s("Moyen de paiement").'</dt>';
					$h .= '<dd>';
						$hasAtLeastOnePaymentMethod = ($eSale['cPayment']->count() > 0 and $eSale['cPayment']->first()['method']->exists());
						if($hasAtLeastOnePaymentMethod) {
							$h .= '<div>';
							foreach($eSale['cPayment'] as $ePayment) {
								$h .= '<div>';
									$h .= encode($ePayment['method']['name']);
									if($ePayment['amountIncludingVat'] !== NULL) {
										$amount = \util\TextUi::money($ePayment['amountIncludingVat']);
									} else {
										$amount = s("Non calculé");
									}
									$h .= ' : '.$ePayment->quick('amountIncludingVat', $amount);
									$h .= '<a data-ajax="/selling/sale:doFillPaymentMethod" post-id="'.$eSale['id'].'" post-payment-method="'.$ePayment['method']['id'].'" class="btn btn-outline-border ml-1" title="'.s("Compléter automatiquement").'">'.\Asset::icon('magic').'</a>';
								$h .= '</div>';
							}
							$h .= '</div>';
							$h .= '<a data-dropdown="bottom-start" class="dropdown-toggle">';
								$h .= '<span style="font-weight: normal">'.s("Changer les moyens de paiement").'</span>';
							$h .= '</a>';
						} else {
							$h .= '<a data-dropdown="bottom-start" class="dropdown-toggle">';
								if($eSale['cPayment']->count() > 0 and $eSale['cPayment']->first()['method']->exists()) {
									$h .= $eSale['cPayment']->first()['method']['name'];
								} else {
									$h .= '<span style="font-weight: normal">...</span>';
								}
							$h .= '</a>';
						}
						$h .= '<div class="dropdown-list bg-secondary">';
							$h .= '<div class="dropdown-title">'.s("Moyen de paiement").'</div>';
							foreach($cMethod as $eMethod) {
								$has = $eSale['cPayment']->find(fn($ePayment) => (($ePayment['method']['id'] ?? NULL) === $eMethod['id']))->count() > 0;
								$h .= '<a data-ajax="/selling/sale:doUpdatePaymentMethod" post-id="'.$eSale['id'].'" post-payment-method="'.$eMethod['id'].'" class="dropdown-item" post-action="'.($has ? 'remove' : 'add').'">';
									if($hasAtLeastOnePaymentMethod) {
										$h .= $has ? \Asset::icon('x-lg') : \Asset::icon('plus-lg');
										$h .= '  ';
									}
									$h .= encode($eMethod['name']);
								$h .= '</a>';
							}
						$h .= '</div>';
					$h .= '</dd>';
					if(
						$eSale['customer']->notEmpty() and
						$eSale['customer']['discount'] > 0
					) {
						$h .= '<dt>'.s("Remise commerciale").'</dt>';
						$h .= '<dd title="'.s("La remise commerciale s'applique sur les saisies réalisées après l'affectation de la vente à ce client").'">';
							$h .= s("{value} %", $eSale['customer']['discount']);
						$h .= '</dd>';
					}
				$h .= '</dl>';
			$h .= '</div>';

		$h .= '</div>';

		if($eSale['items'] > 0) {

			$money = (
				$eSale['preparationStatus'] !== Sale::CANCELED and
				($eSale['cPayment']->count() === 0 or count($eSale['cPayment']->filter(fn($ePayment) => $ePayment['method']['fqn'] === \payment\MethodLib::CASH)) > 0)
			);

			$h .= new SaleUi()->getSummary($eSale, onlyIncludingVat: TRUE, includeMoney: $money);

		}

		$h .= $this->displaySaleItems($eSale, $cItemSale, $cItemMarket);

		return $h;

	}

	protected function displaySaleItems(Sale $eSale, \Collection $cItemSale, \Collection $cItemMarket): string {

		$discount = $eSale['customer']->empty() ? 0 : $eSale['customer']['discount'];

		$h = '<div id="market-item-sale" class="market-item-wrapper market-item-'.$eSale['preparationStatus'].'">';

			foreach($cItemMarket as $eItemMarket) {

				if($eItemMarket['product']->notEmpty()) {
					$eItemSale = $cItemSale[$eItemMarket['product']['id']] ?? new Item();
				} else {
					$eItemSale = $cItemSale->find(fn($eItemTry) => $eItemTry['name'] === $eItemMarket['name'], limit: 1, default: new Item());
				}

				$eItemMarket['unitPrice'] = round($eItemMarket['unitPrice'] * (1 - $discount / 100), 2);

				$h .= $this->getSaleItem($eSale, $eItemMarket, $eItemSale);
				$h .= new MerchantUi()->get('/selling/market:doUpdateSale', $eSale, $eItemSale->empty() ? new Item([
					'id' => $eItemMarket['id'],
					'name' => $eItemMarket['name'],
					'unit' => $eItemMarket['unit'],
					'unitPrice' => $eItemMarket['unitPrice'],
					'number' => NULL,
					'price' => NULL,
					'packaging' => NULL,
					'locked' => '',
				]) : $eItemSale);

			}

		$h .= '</div>';

		return $h;

	}

	public function getSaleItem(Sale $eSale, Item $eItemMarket, Item $eItemSale): string {

		$eItemReference = $eItemSale->empty() ? $eItemMarket : $eItemSale;

		$locked = $eItemSale->empty() ? '' : $eItemSale['locked'];
		$tag = ($eSale['preparationStatus'] === Sale::DRAFT) ? 'a' : 'div';
		$onclick = ($eSale['preparationStatus'] === Sale::DRAFT) ? 'onclick="Merchant.show(this)"' : 'div';

		$h = '<'.$tag.' class="market-item '.($eItemSale->empty() ? '' : 'market-item-highlight').'" '.$onclick.' data-locked="'.$locked.'" data-item="'.$eItemReference['id'].'">';

			$more = \util\TextUi::money($eItemReference['unitPrice']).' <span class="util-annotation">'.\selling\UnitUi::getBy($eItemReference['unit'], short: TRUE).'</span>';

			$h .= $this->getItemProduct($eItemMarket, $more);

			$h .= '<div class="market-item-text '.($eItemSale->empty() ? 'market-item-text-empty' : '').'">';

				$h .= '<div class="market-item-fields">';
					$h .= '<div>'.s("Vendu").'</div>';
					$h .= '<div>';
						if($eItemSale->notEmpty()) {
							$h .= \selling\UnitUi::getValue($eItemSale['number'], $eItemSale['unit'], TRUE);
						} else {
							$h .= '/';
						}
					$h .= '</div>';
					$h .= '<div>'.s("Montant").'</div>';
					$h .= '<div>';
						if($eItemSale->notEmpty()) {
							$h .= \util\TextUi::money($eItemSale['price']);
						} else {
							$h .= '/';
						}
					$h .= '</div>';
				$h .= '</div>';

			$h .= '</div>';

		$h .= '</'.$tag.'>';

		return $h;

	}

	protected function getItemProduct(Item $eItem, string $more = ''): string {

		$eProduct = $eItem['product'];

		$h = '<div class="market-item-product">';

			$h .= '<div>';

				$h .= '<h4>';

					$h .= encode($eItem['name']);

				$h .= '</h4>';

				$h .= $more;

			$h .= '</div>';

			if($eProduct->notEmpty() and $eProduct['vignette'] !== NULL) {
				$h .= ProductUi::getVignette($eProduct, '3rem');
			}

		$h .= '</div>';

		return $h;

	}
	
	protected function getCircle(Sale $eSale): string {
		
		return match($eSale['preparationStatus']) {
			\selling\Sale::DRAFT => \Asset::icon('circle-fill', ['class' => 'color-todo']),
			\selling\Sale::DELIVERED => \Asset::icon('circle-fill', ['class' => 'color-success']),
			\selling\Sale::CANCELED => \Asset::icon('circle-fill', ['class' => 'color-muted'])
		};
		
	}

	protected function getStatus(Sale $eSale): string {

		return match($eSale['preparationStatus']) {
			\selling\Sale::DRAFT => s("En cours"),
			\selling\Sale::DELIVERED => s("Terminée"),
			\selling\Sale::CANCELED => s("Annulée")
		};

	}

}
?>
