<?php
namespace selling;

class OrderUi {

	public function __construct() {

		\Asset::css('selling', 'order.css');
		\Asset::css('selling', 'sale.css');

	}

	public function getFarmHeader(\farm\Farm $eFarm, Customer $eCustomer, string $back = ''): string {

		$h = '<div class="util-vignette">';

			$h .= \farm\FarmUi::getVignette($eFarm, '10rem');

			$h .= '<div>';
				$h .= '<h1>'.encode($eFarm['name']).'</h1>';

				$h .= '<dl class="util-presentation util-presentation-1">';

					$h .= '<dt>'.s("Numéro de client").'</dt>';
					$h .= '<dd>'.$eCustomer['id'].'</dd>';

					$h .= '<dt>'.s("Compte client").'</dt>';
					$h .= '<dd>'.encode($eCustomer['name']).'</dd>';

				$h .= '</dl>';
				$h .= '<br/>';
				$h .= $back;
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getPro(\Collection $cCustomer): string {

		$h = '<div class="order-pro-grid">';

		foreach($cCustomer as $eCustomer) {

			$h .= '<a href="/commandes/professionnels/'.$eCustomer['farm']['id'].'" class="order-pro-item">';

				$h .= '<div class="order-pro-item-vignette">';
					$h .= \farm\FarmUi::getVignette($eCustomer['farm'], '6rem');
				$h .= '</div>';
				$h .= '<div class="order-pro-item-content">';
					$h .= '<h5>';
						$h .= encode($eCustomer['name']);
					$h .= '</h5>';
					$h .= '<h4>';
						$h .= encode($eCustomer['farm']['name']);
					$h .= '</h4>';
					$h .= '<div class="order-pro-item-infos">';

						if($eCustomer['nSale'] > 0) {
							$h .= p("{value} commande", "{value} commandes", $eCustomer['nSale']);
						}

					$h .= '</div>';

				$h .= '</div>';

			$h .= '</a>';

		}

		$h .= '</div>';

		return $h;

	}

	public function getPrivate(\Collection $cCustomer): string {

		$h = '<div class="order-private-grid">';

		foreach($cCustomer as $eCustomer) {

			if($eCustomer['farm']['url']) {
				$h .= '<a href="'.encode($eCustomer['farm']['url']).'" class="order-private-item" target="_blank">';
			} else {
				$h .= '<div class="order-private-item">';
			}

				$h .= \farm\FarmUi::getVignette($eCustomer['farm'], '3rem').' ';
				$h .= encode($eCustomer['farm']['name']);

			$h .= $eCustomer['farm']['url'] ? '</a>' : '</div>';

		}

		$h .= '</div>';

		return $h;

	}

	public function getListForPrivate(\Collection $cSale, bool $showFarm = TRUE): string {

		$h = '<div class="stick-xs">';

			$h .= '<table class="tr-bordered">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Numéro").'</th>';
						if($showFarm) {
							$h .= '<th>'.s("Ferme").'</th>';
						}
						$h .= '<th>'.s("Réception").'</th>';
						$h .= '<th>'.s("Statut").'</th>';
						$h .= '<th class="text-center hide-xs-down">'.s("Produits").'</th>';
						$h .= '<th class="text-end">'.s("Montant").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cSale as $eSale) {

						$h .= '<tr class="';
							if($eSale['preparationStatus'] === Sale::CANCELED) {
								$h .= 'color-muted ';
							}
						$h .= '">';

							$h .= '<td class="td-min-content text-center">';
								$h .= '<a href="/commande/'.$eSale['id'].'" class="btn btn-sm '.($eSale['deliveredAt'] === currentDate() ? 'btn-primary' : 'btn-outline-primary').'">'.$eSale->getNumber().'</a>';
							$h .= '</td>';

							if($showFarm) {
								$h .= '<td>';
									$h .= '<span class="hide-xs-down">'.\farm\FarmUi::getVignette($eSale['farm'], '2rem').'&nbsp;&nbsp;</span>';
									$h .= \farm\FarmUi::websiteLink($eSale['farm']);
								$h .= '</td>';
							}

							$h .= '<td class="sale-item-delivery">';
								$h .= '<div>';
									if($eSale['preparationStatus'] === Sale::DELIVERED) {
										$h .= \util\DateUi::numeric($eSale['deliveredAt']);
									} else {
										if($eSale['deliveredAt'] !== NULL) {
											$h .= \util\DateUi::numeric($eSale['deliveredAt']);
										} else {
											$h .= s("Non planifiée");
										}
									}
								$h .= '</div>';
							$h .= '</td>';

							$h .= '<td class="sale-item-status">';
								$h .= SaleUi::getPreparationStatusForCustomer($eSale);
							$h .= '</td>';

							$h .= '<td class="text-center hide-xs-down">';
								$h .= $eSale['items'];
							$h .= '</td>';

							$h .= '<td class="sale-item-price text-end">';
								$h .= SaleUi::getTotal($eSale, displayIncludingTaxes: FALSE);
							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getListForPro(Customer $eCustomer, \Collection $cSale): string {

		$h = '<div class="tabs-h" id="tasks-week-tabs" onrender="'.encode('Lime.Tab.restore(this, "list")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="order" onclick="Lime.Tab.select(this)">';
					$h .= s("Commandes");
				$h .= '</a>';
				$h .= '<a class="tab-item" data-tab="info" onclick="Lime.Tab.select(this)">';
					$h .= s("Coordonnées");
				$h .= '</a>';
			$h .= '</div>';

			$h .= '<div class="tab-panel selected" data-tab="order">';
				$h .= $this->getOrdersForPro($cSale);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="info">';
				$h .= $this->getInfosForPro($eCustomer);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getInfosForPro(Customer $eCustomer): string {

		$h = '<div class="util-info">'.\Asset::icon('lock-fill').' '.s("Les coordonnées de votre compte client ont été saisies par la ferme {farm}, contactez la ferme si vous souhaitez les mettre à jour.", ['farm' => encode($eCustomer['farm']['name'])]).'</div>';

		$h .= '<h3>'.s("Facturation").'</h3>';

		$h .= '<div class="util-block stick-xs">';
			$h .= '<dl class="util-presentation util-presentation-1">';

				$h .= '<dt>'.s("Nom").'</dt>';
				$h .= '<dd>'.encode($eCustomer['legalName'] ?? $eCustomer['name']).'</dd>';

				$h .= '<dt>'.s("Adresse").'</dt>';
				$h .= '<dd>'.nl2br(encode($eCustomer->getInvoiceAddress() ?? '/')).'</dd>';

				$h .= '<dt>'.s("E-mail").'</dt>';
				$h .= '<dd>'.encode($eCustomer['email'] ?? '/').'</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	protected function getOrdersForPro(\Collection $cSale): string {

		$h = '<div class="util-overflow-sm stick-xs">';

			$h .= '<table class="tr-bordered">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Numéro").'</th>';
						$h .= '<th>'.s("Livraison").'</th>';
						$h .= '<th>'.s("Statut").'</th>';
						$h .= '<th class="text-center hide-xs-down">'.s("Produits").'</th>';
						$h .= '<th class="text-end">'.s("Montant").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cSale as $eSale) {

						$h .= '<tr class="';
							if($eSale['preparationStatus'] === Sale::CANCELED) {
								$h .= 'color-muted ';
							}
						$h .= '">';

							$h .= '<td class="td-min-content text-center">';
								$h .= '<a href="/commande/'.$eSale['id'].'" class="btn btn-sm '.($eSale['deliveredAt'] === currentDate() ? 'btn-primary' : 'btn-outline-primary').'">'.$eSale->getNumber().'</a>';
							$h .= '</td>';

							$h .= '<td class="sale-item-delivery">';
								$h .= '<div>';
									if($eSale['preparationStatus'] === Sale::DELIVERED) {
										$h .= \util\DateUi::numeric($eSale['deliveredAt']);
									} else {
										if($eSale['deliveredAt'] !== NULL) {
											$h .= \util\DateUi::numeric($eSale['deliveredAt']);
										} else {
											$h .= s("Non planifiée");
										}
									}
								$h .= '</div>';
							$h .= '</td>';

							$h .= '<td class="sale-item-status">';
								$h .= SaleUi::getPreparationStatusForCustomer($eSale);
							$h .= '</td>';

							$h .= '<td class="text-center hide-xs-down">';
								$h .= $eSale['items'];
							$h .= '</td>';

							$h .= '<td class="sale-item-price text-end">';
								$h .= SaleUi::getTotal($eSale);
							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function displaySale(Sale $eSale): string {

		$h = '<h2>'.s("{order} du {date}", ['order' => OrderUi::getName($eSale), 'date' => \util\DateUi::numeric($eSale['createdAt'], \util\DateUi::DATE)]).'</h2>';

		$h .= '<div class="util-block stick-xs">';
			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>'.s("Montant").'</dt>';
				$h .= '<dd>';
					$h .= SaleUi::getTotal($eSale, displayIncludingTaxes: FALSE);
				$h .= '</dd>';

				$h .= '<dt>'.s("État de la commande").'</dt>';
				$h .= '<dd>'.SaleUi::getPreparationStatusForCustomer($eSale).'</dd>';

				if($eSale['paymentMethod'] !== NULL) {
					$h .= '<dt>'.s("Moyen de paiement").'</dt>';
					$h .= '<dd>'.\selling\SaleUi::p('paymentMethod')->values[$eSale['paymentMethod']].'</dd>';
				}

				if($eSale->isPaymentOnline()) {
					$h .= '<dt>'.s("État du paiement").'</dt>';
					$h .= '<dd>'.\selling\SaleUi::getPaymentStatusForCustomer($eSale).'</dd>';
				}

				$h .= '<dt>'.s("Livraison").'</dt>';
				$h .= '<dd>';
					$h .= $eSale['preparationStatus'] === Sale::DELIVERED ?
						\util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE) :
						($eSale['deliveredAt'] ? s("Planifiée le {value}", \util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE)) : s("Non planifié"));
				$h .= '</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		if(
			$eSale['hasVat'] and
			$eSale['items'] > 0 and
			$eSale['type'] === Sale::PRO
		) {

			$h .= '<ul class="util-summarize">';
				$h .= '<li>';
					$h .= '<h5>'.s("Montant HT").'</h5>';
					$h .= \util\TextUi::money($eSale['priceExcludingVat']);
				$h .= '</li>';
				$h .= '<li>';
					$h .= '<h5>'.s("TVA").'</h5>';
					$h .= \util\TextUi::money($eSale['vat']);
				$h .= '</li>';
				$h .= '<li>';
					$h .= '<h5>'.s("Montant TTC").'</h5>';
					$h .= \util\TextUi::money($eSale['priceIncludingVat']);
				$h .= '</li>';
			$h .= '</ul>';

		}

		if(
			$eSale['from'] === Sale::SHOP and
			$eSale->isClosed() === FALSE
		) {
			$h .= '<h3>'.encode($eSale['shop']['name']).'</h3>';
			$h .= $this->getPointBySale($eSale);
			$h .= '<br/>';
		}

		return $h;

	}

	public function getPointBySale(Sale $eSale) {

		$h = '';

		switch($eSale['shopPoint']['type']) {

			case \shop\Point::HOME :
				$h .= '<p>'.s("Vous avez choisi la livraison à domicile à l'adresse suivante :").'</p>';
				$h .= '<div class="util-block" style="max-width: 30rem">';
					$h .= '<address>';
						$h .= nl2br(encode($eSale->getDeliveryAddress()));
					$h .= '</address>';
				$h .= '</div>';
				break;

			case \shop\Point::PLACE :
				$h .= '<p>'.s("Votre commande sera à retirer au point de retrait suivant :").'</p>';
				$h .= '<div class="util-block" style="max-width: 30rem">';
					$h .= (new \shop\PointUi())->getPoint('read', $eSale['shop'], $eSale['shopPoint']);
				$h .= '</div>';
				break;

		}

		return $h;

	}

	public function getItemsBySale(Sale $eSale, \Collection $cItem) {

		\Asset::css('selling', 'item.css');

		$h = '<div class="h-line">';
			$h .= '<h3>'.s("Articles commandés").'</h3>';
		$h .= '</div>';

		$withPackaging = $cItem->reduce(fn($eItem, $n) => $n + (int)($eItem['packaging'] !== NULL), 0);

		$h .= '<table class="tbody-even stick-xs item-item-table '.($withPackaging ? 'item-item-table-with-packaging' : '').'">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th>'.ItemUi::p('name')->label.'</th>';
					if($withPackaging) {
						$h .= '<th class="text-end">'.s("Colis").'</th>';
					}
					$h .= '<th class="text-end">'.s("Quantité").'</th>';
					$h .= '<th class="text-end">';
						$h .= ItemUi::p('unitPrice')->label;
						if($eSale['hasVat'] and $eSale['type'] === Customer::PRO) {
							$h .= '<br/>('.SaleUi::getTaxes($eSale['taxes']).')';
						}
					$h .= '</th>';
					$h .= '<th class="text-end">';
						$h .= ItemUi::p('price')->label;
						if($eSale['hasVat'] and $eSale['type'] === Customer::PRO) {
							$h .= '<br/>('.SaleUi::getTaxes($eSale['taxes']).')';
						}
					$h .= '</th>';
					if($eSale['hasVat'] and $eSale['type'] === Customer::PRO) {
						$h .= '<th class="item-item-vat text-center">'.s("TVA").'</th>';
					}
				$h .= '</tr>';
			$h .= '</thead>';

			foreach($cItem as $eItem) {

				$h .= '<tbody>';
					$h .= '<tr>';

						$h .= '<td class="item-item-name">';
							if($eItem['product']->notEmpty()) {
								$h .= ProductUi::getVignette($eItem['product'], '2rem').'  ';
							}
							$h .= encode($eItem['name']);
						$h .= '</td>';

						if($withPackaging) {

							$h .= '<td class="text-end">';
								if($eItem['packaging']) {
									$h .= $eItem['number'];
								} else {
									$h .= '-';
								}
							$h .= '</td>';

						}

						$h .= '<td class="item-item-number text-end">';
							if($eItem['packaging']) {
								$h .= \main\UnitUi::getValue($eItem['number'] * $eItem['packaging'], $eItem['unit'], TRUE);
							} else {
								$h .= \main\UnitUi::getValue($eItem['number'], $eItem['unit'], TRUE);
							}
						$h .= '</td>';

						$h .= '<td class="item-item-unit-price text-end">';
							if($eItem['unit']) {
								$unit = '<span class="util-annotation"> / '.\main\UnitUi::getSingular($eItem['unit'], short: TRUE, by: TRUE).'</span>';
							} else {
								$unit = '';
							}
							$h .= \util\TextUi::money($eItem['unitPrice']).' '.$unit;
						$h .= '</td>';

						$h .= '<td class="item-item-price text-end">';
							$h .= \util\TextUi::money($eItem['price']);
						$h .= '</td>';

						if($eSale['hasVat'] and $eSale['type'] === Customer::PRO) {

							$h .= '<td class="item-item-vat text-center">';
								$h .= s('{value} %', $eItem['vatRate']);
							$h .= '</td>';

						}

					$h .= '</tr>';

					$values = [];

					if($eItem['packaging']) {
						$values[] = s("Taille du colis : {value}", \main\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE));
					}
					if($eItem['description']) {
						$values[] = s("Description : {value}", encode($eItem['description']));
					}

					if($values) {

						$colspan = 4;

						if($eSale['taxes'] and $eSale['type'] === Customer::PRO) {
							$colspan++;
						}

						if($withPackaging) {
							$colspan++;
						}

						$h .= '<tr>';
							$h .= '<td class="item-item-description util-annotation" colspan="'.$colspan.'">';

								if($values) {
									$h .= implode(' | ', $values);
								}

							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			}

			if($eSale['shipping'] !== NULL) {


				$h .= '<tbody>';
					$h .= '<tr>';

						$h .= '<td class="item-item-name">';
							$h .= SaleUi::getShippingName();
						$h .= '</td>';

						if($withPackaging) {

							$h .= '<td></td>';

						}

						$h .= '<td></td>';
						$h .= '<td></td>';

						$h .= '<td class="item-item-price text-end">';
							$h .= \util\TextUi::money($eSale['shipping']);
						$h .= '</td>';

						if($eSale['hasVat'] and $eSale['type'] === Customer::PRO) {

							$h .= '<td class="item-item-vat text-center">';
								$h .= s('{value} %', $eSale['shippingVatRate']);
							$h .= '</td>';

						}

					$h .= '</tr>';

				$h .= '</tbody>';

			}

		$h .= '</table>';

		return $h;

	}

	public static function getName(Sale $eSale): string {

		$eSale->expects(['id']);

		return s("Commande #{value}", $eSale->getNumber());

	}

}
?>
