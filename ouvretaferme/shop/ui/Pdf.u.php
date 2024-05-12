<?php
namespace shop;

class PdfUi {

	public function __construct() {

		\Asset::css('shop', 'pdf.css');

	}

	public function getSales(Date $eDate, \Collection $cSale, \Collection $cItem): string {

		$items = [];

		foreach($cSale as $eSale) {
			$items = array_merge($items, $this->getLabel($eDate, $eSale));
		}

		$itemsPerPage = 4;

		$itemsChunk = array_chunk($items, $itemsPerPage);

		if($itemsChunk === []) {
			$itemsChunk[] = [];
		}

		$h = '<style>@page {	margin: 0.5cm; }</style>';

		$h .= $this->getSalesSummary($eDate, $cSale, $cItem);

		foreach($itemsChunk as $itemsByN) {

			$h .= '<div class="shop-pdf-label-wrapper">';

				$h .= implode('', $itemsByN);

			$h .= '</div>';

		}

		return $h;

	}

	protected function getSalesSummary(Date $eDate, \Collection $cSale, \Collection $cItem): string {

		$h = '<div class="shop-pdf-summary-wrapper">';

			$h .= '<h1>'.$eDate['shop']['name'].'</h1>';
			$h .= '<h2>'.s("Vente du {value}", \util\DateUi::numeric($eDate['deliveryDate'])).' | '.p("{value} commande", "{value} commandes", $cSale->count()).'</h2>';

			$h .= '<table class="shop-pdf-summary tr-bordered tr-even">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th colspan="2">'.s("Produit").'</th>';
						$h .= '<th class="text-end" colspan="2">'.s("Quantité").'</th>';
						$h .= '<th class="text-end">'.s("Montant").'</th>';
						$h .= '<th class="shop-pdf-summary-comment">'.s("Observations").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';
					foreach($cItem as $eItem) {
						$h .= '<tr>';
							$h .= '<td>'.$eItem['name'].'</th>';
							$h .= '<td>';
								if($eItem['quality']) {
									$h .= \Asset::image('main', $eItem['quality'].'.png', ['style' => 'height: 0.4cm']);
								}
							$h .= '</th>';
							$h .= '<td class="shop-pdf-summary-quantity text-end">'.round($eItem['quantity'], 2).'</td>';
							$h .= '<td class="td-min-content">'.\main\UnitUi::getSingular($eItem['unit'], short: TRUE).'</td>';
							$h .= '<td class="text-end">'.\util\TextUi::money($eItem['price']).'</td>';
							$h .= '<td></td>';
						$h .= '</tr>';
					}
				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getLabel(Date $eDate, \selling\Sale $eSale): array {

		$eCustomer = $eSale['customer'];

		$itemsList = [];

		foreach($eSale['cItem'] as $eItem) {

			if($eItem['packaging'] !== NULL) {
				// Gérer les colis en nombre entier
				$quantity = $eItem['packaging'] * $eItem['number'];
			} else {
				$quantity = $eItem['number'];
			}

			$item = '<div class="'.(mb_strlen($eItem['name']) > 50 ? 'shop-pdf-label-content-shrink-strong' : (mb_strlen($eItem['name']) > 40 ? 'shop-pdf-label-content-shrink' : '')).'">';
				if(mb_strlen($eItem['name']) >= 60) {
					$item .= mb_substr($eItem['name'], 0, 55).'...';
				} else {
					$item .= encode($eItem['name'] ?? '');
				}
			$item .= '</div>';
			$item .= '<div>';
				$item .= \main\UnitUi::getValue($quantity, $eItem['unit'], short: TRUE);
			$item .= '</div>';
			$item .= '<div>';
				$item .= \util\TextUi::money($eItem['price']);
			$item .= '</div>';

			$itemsList[] = $item;

		}

		$itemsChunck = array_chunk($itemsList, 15);
		$pages = count($itemsChunck);

		$entries = [];

		foreach($itemsChunck as $position => $items) {

			$entry = '<div class="shop-pdf-label-item">';

				$entry .= '<div class="shop-pdf-label-customer">';
					$entry .= '<span>'.encode($eCustomer['name']).'</span>';

					if(count($itemsChunck) > 1) {
						$entry .= '<span class="shop-pdf-label-page">'.($position + 1).' / '.$pages.'</span>';
					}

				$entry.= '</div>';

				$entry .= '<div class="shop-pdf-label-details '.($position > 0 ? 'shop-pdf-label-details-next' : '').'">';

					if($position === 0) {

						$entry .= '<div class="shop-pdf-label-detail">';
							$entry .= '<div class="shop-pdf-label-detail-title">'.s("Commande").'</div>';
							$entry .= '<div class="shop-pdf-label-detail-value">'.$eSale['id'].'</div>';
						$entry .= '</div>';
						$entry .= '<div class="shop-pdf-label-detail">';
							$entry .= '<div class="shop-pdf-label-detail-title">'.s("Date de retrait").'</div>';
							$entry .= '<div class="shop-pdf-label-detail-value">'.\util\DateUi::numeric($eDate['deliveryDate']).'</div>';
						$entry .= '</div>';
						$entry .= '<div class="shop-pdf-label-detail">';
							$entry .= '<div class="shop-pdf-label-detail-title">'.s("Produits").'</div>';
							$entry .= '<div class="shop-pdf-label-detail-value">'.$eSale['cItem']->count().'</div>';
						$entry .= '</div>';
						$entry .= '<div class="shop-pdf-label-detail">';
							$entry .= '<div class="shop-pdf-label-detail-title">'.s("Montant").'</div>';
							$entry .= '<div class="shop-pdf-label-detail-value">'.\util\TextUi::money($eSale['priceIncludingVat']).'</div>';
						$entry .= '</div>';
						$entry .= '<div class="shop-pdf-label-detail">';
							$entry .= '<div class="shop-pdf-label-detail-title">'.s("Moyen de paiement").'</div>';
							$entry .= '<div class="shop-pdf-label-detail-value">';
								$entry .= $eSale['paymentMethod'] ? \selling\SaleUi::p('paymentMethod')->values[$eSale['paymentMethod']] : '?';
							$entry .= '</div>';
						$entry .= '</div>';

						if($eSale->isPaymentOnline()) {
							$entry .= '<div class="shop-pdf-label-detail">';
								$entry .= '<div class="shop-pdf-label-detail-title">'.s("Paiement").'</div>';
								$entry .= '<div class="shop-pdf-label-detail-value">';
									$entry .= \selling\SaleUi::getPaymentStatusForCustomer($eSale, withColors: TRUE);
								$entry .= '</div>';
							$entry .= '</div>';
						}

						if($eSale['shopPoint']->notEmpty()) {
							$entry .= '<div class="shop-pdf-label-detail">';
								$entry .= '<div class="shop-pdf-label-detail-title">'.PointUi::p('type')->values[$eSale['shopPoint']['type']].'</div>';
								$entry .= '<div class="shop-pdf-label-detail-value">';
									$entry .= match($eSale['shopPoint']['type']) {
										\shop\Point::HOME => '<div class="shop-pdf-label-address">'.nl2br(encode($eSale->getDeliveryAddress())).'</div>',
										\shop\Point::PLACE => encode($eSale['shopPoint']['name'])
									};
								$entry .= '</div>';
							$entry .= '</div>';
						}

					} else {

						$entry .= '<div class="shop-pdf-label-detail">';
							$entry .= '<div class="shop-pdf-label-detail-title">'.s("Suite de commande").'</div>';
							$entry .= '<div class="shop-pdf-label-detail-value">'.$eSale['id'].'</div>';
						$entry .= '</div>';

					}

				$entry .= '</div>';

				$entry .= '<div class="shop-pdf-label-content">';
					$entry .= implode('', $items);
				$entry .= '</div>';

			$entry .= '</div>';

			$entries[] = $entry;

		}

		return $entries;

	}

}
?>