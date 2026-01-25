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
					$h .= '<dd>'.encode($eCustomer->getName()).'</dd>';

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
						$h .= encode($eCustomer->getName());
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

	public function getSales(\Collection $cSale, string $type): string {

		$h = '<div class="stick-xs">';

			$h .= '<table class="tr-even">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Numéro").'</th>';
						if($type === Customer::PRIVATE) {
							$h .= '<th>'.s("Ferme").'</th>';
						}
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

							if($type === Customer::PRIVATE) {
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

	public function getInvoices(\Collection $cInvoice, string $type): string {

		$h = '<div class="stick-xs">';

			$h .= '<table class="tr-even">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th></th>';
						if($type === Customer::PRIVATE) {
							$h .= '<th>'.s("Ferme").'</th>';
						}
						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th class="text-end">'.s("Montant").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cInvoice as $eInvoice) {

						$h .= '<tr>';

							$h .= '<td class="text-center td-min-content">';
								if($eInvoice['content']->empty()) {
									$h .= '<span class="btn disabled">'.encode($eInvoice['number']).'</span>';
								} else {
									$h .= InvoiceUi::link($eInvoice);
								}
							$h .= '</td>';

							if($type === Customer::PRIVATE) {
								$h .= '<td>';
									$h .= '<span class="hide-xs-down">'.\farm\FarmUi::getVignette($eInvoice['farm'], '2rem').'&nbsp;&nbsp;</span>';
									$h .= \farm\FarmUi::websiteLink($eInvoice['farm']);
								$h .= '</td>';
							}

							$h .= '<td>';
								$h .= \util\DateUi::numeric($eInvoice['date']);
							$h .= '</td>';

							$h .= '<td class="text-end td-min-content">';
								$h .= SaleUi::getTotal($eInvoice);
							$h .= '</td>';

							$h .= '<td class="text-end">';

								if($eInvoice->acceptDownload()) {
									$h .= '<a href="'.InvoiceUi::url($eInvoice).'" data-ajax-navigation="never" class="btn btn-outline-secondary">'.\Asset::icon('download').' '.s("Télécharger").'</a> ';
								}
							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getForPro(Customer $eCustomer, \Collection $cSale, \Collection $cInvoice): string {

		$h = '<div class="tabs-h" id="order-pro" onrender="'.encode('Lime.Tab.restore(this, "list")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a class="tab-item selected" data-tab="order" onclick="Lime.Tab.select(this)">';
					$h .= s("Commandes");
				$h .= '</a>';
				$h .= '<a class="tab-item" data-tab="invoice" onclick="Lime.Tab.select(this)">';
					$h .= s("Factures");
				$h .= '</a>';
				$h .= '<a class="tab-item" data-tab="info" onclick="Lime.Tab.select(this)">';
					$h .= s("Coordonnées");
				$h .= '</a>';
			$h .= '</div>';

			$h .= '<div class="tab-panel selected" data-tab="order">';
				$h .= $this->getSales($cSale, Customer::PRO);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="invoice">';
				$h .= $this->getInvoices($cInvoice, Customer::PRO);
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
				$h .= '<dd>'.encode($eCustomer->getLegalName()).'</dd>';

				$h .= '<dt>'.s("Adresse").'</dt>';
				$h .= '<dd>'.($eCustomer->getInvoiceAddress('html') ?? '/').'</dd>';

				$h .= '<dt>'.s("E-mail").'</dt>';
				$h .= '<dd>'.encode($eCustomer['email'] ?? '/').'</dd>';

			$h .= '</dl>';
		$h .= '</div>';

		return $h;

	}

	public function displaySale(Sale $eSale): string {

		$h = '';

		if($eSale['shop']->notEmpty()) {
			$h .= '<h4 style="margin-bottom: 0.5rem">'.\shop\ShopUi::link($eSale['shop']).'</h4>';
		}

		$h .= '<h2>'.s("{order} du {date}", ['order' => OrderUi::getName($eSale), 'date' => \util\DateUi::numeric($eSale['createdAt'], \util\DateUi::DATE)]).'</h2>';

		$h .= '<div class="util-block stick-xs">';
			$h .= '<dl class="util-presentation util-presentation-2">';

				$h .= '<dt>'.s("Montant").'</dt>';
				$h .= '<dd>';
					$h .= SaleUi::getTotal($eSale, displayIncludingTaxes: FALSE);
				$h .= '</dd>';

				$h .= '<dt>'.s("État de la commande").'</dt>';
				$h .= '<dd>'.SaleUi::getPreparationStatusForCustomer($eSale).'</dd>';

				if($eSale['cPayment']->notEmpty()) {
					$h .= '<dt>'.s("Moyen de paiement").'</dt>';
					$h .= '<dd>';

						$h .= SaleUi::getPaymentMethodName($eSale);
						$h .= ' '.SaleUi::getPaymentStatus($eSale);

					$h .= '</dd>';
				}

				if($eSale->isPaymentOnline()) {
					$h .= '<dt>'.s("État du paiement").'</dt>';
					$h .= '<dd>'.\selling\SaleUi::getPaymentStatusForCustomer($eSale).'</dd>';
				}

				if(
					$eSale['shopDate']->notEmpty() and
					$eSale['shopDate']['deliveryDate'] !== NULL
				) {
					$h .= '<dt>'.s("Livraison").'</dt>';
					$h .= '<dd>';
						$h .= $eSale['preparationStatus'] === Sale::DELIVERED ?
							\util\DateUi::numeric($eSale['shopDate']['deliveryDate'], \util\DateUi::DATE) :
							 s("Planifiée le {value}", \util\DateUi::numeric($eSale['shopDate']['deliveryDate'], \util\DateUi::DATE));
					$h .= '</dd>';
				}

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
			$eSale['shop']->notEmpty() and
			$eSale['shopPoint']->notEmpty() and
			$eSale->isLocked() === FALSE
		) {
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
					$h .= '<dl class="util-presentation util-presentation-1">';
						$h .= '<dt>'.s("Nom").'</dt>';
						$h .= '<dd>'.$eSale['customer']->getName().'</dd>';
						$h .= '<dt>'.s("Adresse").'</dt>';
						$h .= '<dd style="line-height: 1.2">'.$eSale->getDeliveryAddress('html', $eSale['farm']).'</dd>';
						if($eSale['customer']['phone'] !== NULL) {
							$h .= '<dt>'.s("Téléphone").'</dt>';
							$h .= '<dd>'.encode($eSale['customer']['phone']).'</dd>';
						}
					$h .= '</dl>';
				$h .= '</div>';
				break;

			case \shop\Point::PLACE :
				$h .= '<p>'.s("Votre commande sera à retirer au point de retrait suivant :").'</p>';
				$h .= '<div class="util-block" style="max-width: 30rem">';
					$h .= new \shop\PointUi()->getPoint('read', $eSale['shop'], $eSale['shopPoint']);
				$h .= '</div>';
				break;

		}

		return $h;

	}

	public function getItemsBySale(Sale $eSale, \Collection $cItem, bool $withApproximate = FALSE) {

		\Asset::css('selling', 'item.css');

		$withPackaging = $cItem->reduce(fn($eItem, $n) => $n + (int)($eItem['packaging'] !== NULL), 0);
		$columns = 0;

		$h = '<table class="stick-xs tr-bordered">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th></th>';

					$columns++;
					$h .= '<th>'.ItemUi::p('name')->label.'</th>';

					$columns++;
					$h .= '<th></th>';

					if($withPackaging) {
						$columns++;
						$h .= '<th class="text-end">'.s("Colisage").'</th>';
					}

					$columns += 3;
					$h .= '<th class="text-end">'.s("Quantité").'</th>';
					$h .= '<th class="text-end">';
						$h .= ItemUi::p('unitPrice')->label;
					$h .= '</th>';
					$h .= '<th class="text-end">';
						$h .= ItemUi::p('price')->label;
					$h .= '</th>';

					if($eSale['hasVat'] and $eSale['type'] === Customer::PRO) {
						$columns++;
						$h .= '<th class="item-item-vat text-center">'.s("TVA").'</th>';
					}
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= $this->getItemsBody($eSale, $cItem, $columns, $withPackaging, $withApproximate);

			if($eSale['shipping'] !== NULL) {

				$h .= '<tbody>';
					$h .= '<tr>';

						$h .= '<td></td>';
						$h .= '<td>';
							$h .= SaleUi::getShippingName();
						$h .= '</td>';

						if($withPackaging) {

							$h .= '<td></td>';

						}

						$h .= '<td colspan="3"></td>';

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
			$h .= '<tbody>';

				if(
					$eSale['discount'] > 0 and
					$eSale['price'] !== NULL
				) {

					$discountAmount = -1 * ($eSale['priceGross'] - $eSale['price']);
					$h .= $this->getItemTotal($eSale, $withPackaging, s("Total avant remise"), \util\TextUi::money($eSale['priceGross']));
					$h .= $this->getItemTotal($eSale, $withPackaging, s("Remise <i>- {value} %</i>", $eSale['discount']), \util\TextUi::money($discountAmount));

				}

				$h .= $this->getItemTotal($eSale, $withPackaging, s("Total"), \util\TextUi::money($eSale['price'] ?? 0.0));

			$h .= '</tbody>';

		$h .= '</table>';

		return $h;

	}

	protected function getItemTotal(Sale $eSale, bool $withPackaging, string $label, string $value): string {

		$h = '<tr class="order-summary-total">';

		$h .= '<td></td>';
			$h .= '<td>';
				$h .= '<b>'.$label.'</b>';
			$h .= '</td>';

			if($withPackaging) {
				$h .= '<td></td>';
			}

			$h .= '<td colspan="3"></td>';

			$h .= '<td class="item-item-price text-end">';
				$h .= $value;
			$h .= '</td>';

			if($eSale['hasVat'] and $eSale['type'] === Customer::PRO) {
				$h .= '<td class="item-item-vat text-center"></td>';
			}

		$h .= '</tr>';

		return $h;

	}

	protected function getItemsBody(Sale $eSale, \Collection $cItem, int $columns, bool $withPackaging, bool $withApproximate = FALSE): string {

		$h = '';

		foreach($cItem as $position => $eItem) {

			$description = [];

			if($eItem['quality']) {
				$description[] = \farm\FarmUi::getQualityLogo($eItem['quality'], '1.5rem');
			}

			$h .= '<tbody>';

				$h .= '<tr>';

					$h .= '<td class="item-item-vignette">';
						if($eItem['product']->notEmpty()) {
							$h .= ProductUi::getVignette($eItem['product'], '2rem', public: TRUE);
						}
					$h .= '</td>';

					$h .= '<td>';
						$h .= encode($eItem['name']);
					$h .= '</td>';
					$h .= '<td>'.implode('  ', $description).'</td>';

					if($withPackaging) {

						$h .= '<td class="text-end">';
							if($eItem['packaging']) {
								$h .= \selling\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE);
							} else {
								$h .= '-';
							}
						$h .= '</td>';

					}

					$h .= '<td class="item-item-number text-end">';
						if($eItem['packaging']) {
							$h .= \selling\UnitUi::getValue($eItem['number'] * $eItem['packaging'], $eItem['unit'], TRUE);
						} else {
							$h .= \selling\UnitUi::getValue($eItem['number'], $eItem['unit'], TRUE);
						}
					$h .= '</td>';

					$h .= '<td class="item-item-unit-price text-end">';
						if($eItem['unit']) {
							$unit = '<span class="util-annotation">'.\selling\UnitUi::getBy($eItem['unit'], short: TRUE).'</span>';
						} else {
							$unit = '';
						}
						if($eSale['hasVat'] and $eSale['type'] === Customer::PRO) {
							$unit = $eSale->getTaxes().' '.$unit;
						}
						if($eItem['unitPriceInitial'] !== NULL) {
							$h .= new PriceUi()->priceWithoutDiscount($eItem['unitPriceInitial'], unit: $unit);
						}
						$h .= \util\TextUi::money($eItem['unitPrice']);
						$h .= ' '.$unit;
					$h .= '</td>';

					$h .= '<td class="item-item-price text-end">';
						if(
							$withApproximate and
							$eItem['product']['unit']->notEmpty() and
							$eItem['product']['unit']['approximate']
						) {
							$h .= s("environ").' ';
						}
						$h .= \util\TextUi::money($eItem['price']);
						if($eSale['hasVat'] and $eSale['type'] === Customer::PRO) {
							$h .= ' '.$eSale->getTaxes();
						}
					$h .= '</td>';

					if($eSale['hasVat'] and $eSale['type'] === Customer::PRO) {

						$h .= '<td class="item-item-vat text-center">';
							$h .= s('{value} %', $eItem['vatRate']);
						$h .= '</td>';

					}

				$h .= '</tr>';

				$h .= new ItemUi()->getComposition($eSale, $eItem, $columns);

			$h .= '</tbody>';

		}

		return $h;

	}

	public static function getName(Sale $eSale): string {

		$eSale->expects(['id']);

		return s("Commande #{value}", $eSale->getNumber());

	}

}
?>
