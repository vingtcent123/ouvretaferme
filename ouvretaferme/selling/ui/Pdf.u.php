<?php
namespace selling;

class PdfUi {

	public function __construct() {

		\Asset::css('selling', 'pdf.css');

	}

	public static function url(Pdf $e): string {

		$e->expects(['type', 'sale']);

		return match($e['type']) {

			Pdf::ORDER_FORM => SaleUi::url($e['sale']).'/devis',
			Pdf::DELIVERY_NOTE => SaleUi::url($e['sale']).'/bon-livraison',

		};

	}

	public function getLabels(\farm\Farm $eFarm, \Collection $cSale): string {

		$eConfiguration = $eFarm->selling();

		$items = [];

		foreach($cSale as $eSale) {

			$cItem = $eSale['cItem'];

			foreach($cItem as $eItem) {

				if(
					$eItem['product']->empty() or
					$eItem['product']['plant']->empty()
				) {
					continue;
				}

				if($eItem['packaging'] !== NULL) {
					$labels = $eItem['number'];
				} else {
					$labels = 1;
				}

				for($position = 0; $position < ceil($labels); $position++) {

					if($eItem['packaging'] !== NULL) {
						// Gérer les colis en nombre entier
						$quantity = round($eItem['packaging'] * min(1, $eItem['number'] - $position), 2);
					} else {
						$quantity = $eItem['number'];
					}

					$items[] = $this->getLabel($eFarm, $eSale['customer'], $eItem['name'], $eItem['product']['quality'], $eItem['product']['size'], $quantity, $eItem['unit']);


				}

			}

		}

		$itemsPerPage = 12;

		if($eConfiguration['pdfNaturalOrder']) {

			$modulo = count($items) % $itemsPerPage;

			if($modulo > 0) {

				for($i = $modulo; $i < $itemsPerPage; $i++) {
					$items[] = $this->getLabel($eFarm, new Customer(), quality: $eFarm['quality']);
				}

			}

			$pages = count($items) / $itemsPerPage;

			$itemsOrdered = [];

			foreach($items as $key => $item) {
				$newKey = ($key % $pages) * $itemsPerPage + (int)($key / $pages);
				$itemsOrdered[$newKey] = $item;
			}

			ksort($itemsOrdered);

		} else {
			$itemsOrdered = $items;
		}

		$itemsChunk = array_chunk($itemsOrdered, $itemsPerPage);

		if($itemsChunk === []) {
			$itemsChunk[] = [];
		}

		$h = '<style>@page {	size: A4; margin: 0.75cm; }</style>';

		foreach($itemsChunk as $itemsByN) {

			$h .= '<div class="pdf-label-wrapper">';

				$h .= implode('', $itemsByN);

			$h .= '</div>';

		}

		return $h;

	}

	public function getLabel(\farm\Farm $eFarm, Customer $eCustomer, ?string $name = NULL, ?string $quality = NULL, ?string $size = NULL, ?float $quantity = NULL, Unit $unit = new Unit()): string {

		$logo = new \media\FarmLogoUi()->getUrlByElement($eFarm, 'm');
		$colorCustomer = ($eCustomer->notEmpty() and $eCustomer['color']);

		$color = $colorCustomer ? 'color: '.$eCustomer['color'] : '';
		$borderColor = $colorCustomer ? 'border-right-color: '.$eCustomer['color'] : '';

		$h = '<div class="pdf-label-item '.($colorCustomer ? 'pdf-label-color' : '').'" style="'.$borderColor.'">';

			if($eCustomer->notEmpty()) {
				$h .= '<div class="pdf-label-customer" style="'.$color.'"><span>'.encode($eCustomer['name']).'</span></div>';
			}

			$h .= '<div class="pdf-label-vendor">';
				$h .= '<div class="pdf-label-farm">'.s("Producteur").'</div>';
				if($logo !== NULL) {
					$h .= '<div class="pdf-label-logo" style="background-image: url('.$logo.')"></div>';
				}
				$h .= '<div class="pdf-label-address">';
					$h .= encode($eFarm->getSelling('legalName')).'<br/>';
					$h .= $eFarm->selling()->getInvoiceAddress('html');
				$h .= '</div>';
				$h .= '<div class="pdf-label-quality">';
					if($quality) {
						$h .= \Asset::image('main', $quality.'.png', ['style' => 'height: 0.75cm']);
					}
					if($quality === \farm\Farm::ORGANIC and $eFarm->getSelling('organicCertifier')) {
						$h .= '<span>'.s("Certifié par").'<br/>'.$eFarm->getSelling('organicCertifier').'</span>';
					}
				$h .= '</div>';
			$h .= '</div>';

			$h .= '<div class="pdf-label-content">';
				$h .= '<div>'.s("Agriculture").'</div>';
				$h .= '<div>'.s("France").'</div>';
				$h .= '<div>'.s("Produit").'</div>';
				$h .= '<div>'.encode($name ?? '').'</div>';
				$h .= '<div>'.s("Calibre").'</div>';
				$h .= '<div>'.encode($size ?? '').'</div>';
				$h .= '<div class="pdf-label-content-last">'.s("Nombre<br/>ou masse nette").'</div>';
				$h .= '<div class="pdf-label-content-last">';
					if($quantity !== NULL and $unit !== NULL) {
						$h .= \selling\UnitUi::getValue($quantity, $unit);
					}
				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getDocument(Sale $eSale, string $type, \farm\Farm $eFarm, \Collection $cItem): string {

		$h = '<style>@page {	size: A4; margin: 1cm; }</style>';

		$h .= '<div class="pdf-document-wrapper">';

			$number = match($type) {
				Pdf::DELIVERY_NOTE => $eSale->getDeliveryNote($eFarm),
				Pdf::ORDER_FORM => $eSale->getOrderForm($eFarm),
				Pdf::INVOICE => $eSale['invoice']['name']
			};

			switch($type) {

				case Pdf::ORDER_FORM :

					$dateDocument = '<div class="pdf-document-detail-label">'.s("Date d'émission").'</div>';
					$dateDocument .= '<div>'.\util\DateUi::numeric(currentDate()).'</div>';

					if($eSale['orderFormValidUntil'] !== NULL) {
						$dateDocument .= '<div class="pdf-document-detail-label">'.s("Date d'échéance").'</div>';
						$dateDocument .= '<div>'.\util\DateUi::numeric($eSale['orderFormValidUntil']).'</div>';
					}

					break;
	
				case Pdf::DELIVERY_NOTE :

					$dateDocument = '<div class="pdf-document-detail-label">'.s("Date de livraison").'</div>';
					$dateDocument .= '<div>'.\util\DateUi::numeric($eSale['deliveredAt']).'</div>';

					break;

				case Pdf::INVOICE :

					$dateDocument = '<div class="pdf-document-detail-label">'.s("Date").'</div>';
					$dateDocument .= '<div>'.\util\DateUi::numeric($eSale['invoice']['date']).'</div>';

					break;

			}

			$dateDelivered = NULL;
			if($type === Pdf::ORDER_FORM and $eFarm->getSelling('orderFormDelivery')) {
				$dateDelivered = '<div class="pdf-document-delivery">'.s("Commande livrable le {value}", \util\DateUi::numeric($eSale['deliveredAt'])).'</div>';
			}

			if($type === Pdf::INVOICE and $eSale['priceExcludingVat'] >= 0) {
				$dateDelivered = '<div class="pdf-document-delivery">'.s("Commande livrée le {value}", \util\DateUi::numeric($eSale['deliveredAt'])).'</div>';
			}

			$top = match($type) {
				Pdf::ORDER_FORM => $eFarm->getSelling('orderFormHeader'),
				Pdf::INVOICE => $eSale['invoice']['header'],
				Pdf::DELIVERY_NOTE => NULL,
			};

			$h .= $this->getDocumentTop($type, $eSale, $eFarm, $number, $dateDocument, $dateDelivered, $top);

			$withPackaging = $cItem->reduce(fn($eItem, $n) => $n + (int)($eItem['packaging'] !== NULL), 0);

			$h .= '<div class="pdf-document-body">';

				$h .= '<table class="pdf-document-items '.($withPackaging ? 'pdf-document-items-with-packaging' : '').'">';

					$h .= '<thead>';
						$h .= $this->getTableTitle($eSale, $type, $withPackaging);
					$h .= '</thead>';
					$h .= '<tbody>';

						$position = 0;

						foreach($cItem as $eItem) {

							$position++;

							$h .= $this->getTableItem($eSale, $type, $withPackaging, $eItem, $position === $cItem->count());

						}

						if($eSale['shipping']) {

							$h .= '<tr>';
								$h .= '<td colspan="'.($withPackaging ? 4 : 3).'"></td>';
								$h .= '<td colspan="2">';

									$h .= '<div class="pdf-document-total">';

										if($eSale['shipping']) {
											$h .= '<div class="pdf-document-total-label">';
												$h .= s("Frais de<br/>livraison").' '.$eSale->getTaxes();
											$h .= '</div>';
											$h .= '<div class="pdf-document-total-value">';
												$h .= \util\TextUi::money($eSale['shipping']);
											$h .= '</div>';
										}

									$h .= '</div>';

								$h .= '</td>';
								if($eSale['hasVat'] and $type === Pdf::INVOICE) {
									$h .= '<td class="pdf-document-vat">';
										$h .= s("{value} %", $eSale['shippingVatRate']);
									$h .= '</td>';
								}
							$h .= '</tr>';

						}

						$h .= $this->getDocumentTotal($type, $eFarm, $eSale, $withPackaging ? 3 : 2, 2, $type === Pdf::INVOICE);

					$h .= '</tbody>';

				$h .= '</table>';

				$paymentCondition = match($type) {
					Pdf::ORDER_FORM => $eSale['orderFormPaymentCondition'],
					Pdf::INVOICE => $eSale['invoice']['paymentCondition'],
					Pdf::DELIVERY_NOTE => NULL
				};

				$footer = match($type) {
					Pdf::ORDER_FORM => $eFarm->getSelling('orderFormFooter'),
					Pdf::INVOICE => $eSale['invoice']['footer'],
					Pdf::DELIVERY_NOTE => NULL
				};

				$h .= $this->getDocumentBottom($type, $eFarm, $paymentCondition, $footer);

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getDocumentInvoice(Invoice $eInvoice, \farm\Farm $eFarm, \Collection $cSale): string {

		$h = '<style>@page {	size: A4; margin: 1cm; }</style>';

		$h .= '<div class="pdf-document-wrapper">';

			$dateDocument = '<div class="pdf-document-detail-label">'.s("Date").'</div>';
			$dateDocument .= '<div>'.\util\DateUi::numeric($eInvoice['date']).'</div>';

			$h .= $this->getDocumentTop(Pdf::INVOICE, $eInvoice, $eFarm, $eInvoice['name'], $dateDocument, NULL, $eInvoice['header']);

			$h .= '<div class="pdf-document-body">';

				$h .= '<table class="pdf-document-items">';

					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<td class="pdf-document-item-header" colspan="3">'.s("Désignation").'</td>';
							$h .= '<td class="pdf-document-item-header"></td>';
							if($eInvoice['hasVat']) {
								$h .= '<td class="pdf-document-item-header pdf-document-price">';
									$h .= s("Montant {value}", $eInvoice->getTaxes());
								$h .= '</td>';
								$h .= '<td class="pdf-document-item-header pdf-document-vat">';
									$h .= s("TVA");
								$h .= '</td>';
							} else {
								$h .= '<td class="pdf-document-item-header pdf-document-price">';
									$h .= s("Montant");
								$h .= '</td>';
							}
						$h .= '</tr>';
					$h .= '</thead>';
					$h .= '<tbody>';

						foreach($cSale as $eSale) {

							foreach($eSale['vatByRate'] as $key => ['vat' => $vat, 'vatRate' => $vatRate, 'amount' => $amount]) {

								$vatRate = (float)$vatRate;

								$h .= '<tr class="pdf-document-item pdf-document-item-main">';
									$h .= '<td class="pdf-document-product" colspan="3">';
										$h .= s("Livraison du {value}", \util\DateUi::numeric($eSale['deliveredAt']));
										if($eSale['cPdf']->offsetExists(Pdf::DELIVERY_NOTE)) {
											$h .= '<div class="pdf-document-product-details">';
												$h .= s("Bon de livraison {value}", $eSale->getDeliveryNote($eFarm));
											$h .= '</div>';
										}
									$h .= '</td>';
									$h .= '<td></td>';
									if($eInvoice['hasVat']) {
										$h .= '<td class="pdf-document-price">';
											$h .= \util\TextUi::money($amount);
										$h .= '</td>';
										$h .= '<td class="pdf-document-vat">';
											$h .= s('{value} %', $vatRate);
										$h .= '</td>';
									} else {
										$h .= '<td class="pdf-document-price">';
											$h .= \util\TextUi::money($amount);
										$h .= '</td>';
									}
								$h .= '</tr>';

								foreach($eSale['cItem'] as $eItem) {

									if($eItem['vatRate'] !== $vatRate) {
										continue;
									}

									$h .= $this->getDetailTableItem($eSale, $eItem);

								}

								if(
									$eSale['shipping'] and
									$eSale['shippingVatRate'] === $vatRate
								) {
									$h .= $this->getDetailTableItem($eSale, new Item([
										'name' => SaleUi::getShippingName(),
										'product' => new Product(),
										'quality' => NULL,
										'number' => NULL,
										'packaging' => NULL,
										'unit' => new Unit(),
										'unitPrice' => NULL,
										'price' => $eSale['shipping']
									]));
								}

							}

						}


						$h .= $this->getDocumentTotal(Pdf::INVOICE, $eFarm, $eInvoice, $eInvoice['hasVat'] ? 3 : 2, $eInvoice['hasVat'] ? 3 : 2, FALSE);

					$h .= '</tbody>';

				$h .= '</table>';

				$h .= $this->getDocumentBottom(Pdf::INVOICE, $eFarm, $eInvoice['paymentCondition'], $eInvoice['footer']);

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getDetailTableItem(Sale $eSale, Item $eItem): string {

		$h = '<tr class="pdf-document-item pdf-document-item-detail">';
			$h .= '<td>';
				$h .= \Asset::icon('chevron-right').' ';
				$h .= encode($eItem['name']);
				if($eItem['product']->notEmpty() and $eItem['product']['size']) {
					$h .= '<small> / '.s("Calibre {value}", encode($eItem['product']['size'])).'</small>';
				}
			$h .= '</td>';
			$h .= '<td class="td-min-content">';
				if($eItem['quality']) {
					$h .= \farm\FarmUi::getQualityLogo($eItem['quality'], '0.5cm');
				}
			$h .= '</td>';
			$h .= '<td class="pdf-document-number">';
				if($eItem['number'] !== NULL) {
					$h .= \selling\UnitUi::getValue($eItem['number'] * ($eItem['packaging'] ?? 1), $eItem['unit'], TRUE);
				}
			$h .= '</td>';
			$h .= '<td class="pdf-document-unit-price">';
				if($eItem['unitPrice'] !== NULL) {
					$h .= \util\TextUi::money($eItem['unitPrice']);
				}
				$h .= \selling\UnitUi::getBy($eItem['unit'], short: TRUE);
			$h .= '</td>';

			$h .= '<td class="pdf-document-price">';
				$h .= \util\TextUi::money($eItem['price']);
			$h .= '</td>';

			if($eSale['hasVat']) {
				$h .= '<td></td>';
			}
		$h .= '</tr>';

		return $h;

	}

	protected function getDocumentTotal(string $type, \farm\Farm $eFarm, Sale|Invoice $e, int $colspan1, int $colspan2, bool $lastColumn): string {

		$h = '<tr class="pdf-document-item-total">';
			$h .= '<td class="pdf-document-item-quality">';

				if($e['organic'] and $e['conversion']) {

					$h .= '<div class="pdf-document-quality">';
						$h .= \Asset::image('main', 'organic.png', ['style' => 'width: 5rem; margin-right: 1rem']);
						$h .= '<div>'.s("Produits issus de l’agriculture biologique ou en conversion vers l'agriculture biologique certifiés par {value}", '<span style="white-space: nowrap">'.$eFarm->getSelling('organicCertifier').'</span>').'</div>';
					$h .= '</div>';

				} else if($e['organic']) {

					$h .= '<div class="pdf-document-quality">';
						$h .= \Asset::image('main', 'organic.png', ['style' => 'width: 5rem; margin-right: 1rem']);
						$h .= '<div>'.s("Produits issus de l’agriculture biologique certifiés par {value}", '<span style="white-space: nowrap">'.$eFarm->getSelling('organicCertifier').'</span>').'</div>';
					$h .= '</div>';

				} else if($e['conversion']) {

					$h .= '<div class="pdf-document-quality">';
						$h .= \Asset::image('main', 'organic.png', ['style' => 'width: 5rem; margin-right: 1rem']);
						$h .= '<div>'.s("Produits en conversion vers l’agriculture biologique certifiés par {value}", '<span style="white-space: nowrap">'.$eFarm->getSelling('organicCertifier').'</span>').'</div>';
					$h .= '</div>';

				}

			$h .= '</td>';
			$h .= '<td class="pdf-document-item-vat-rates" colspan="'.$colspan1.'">';

				if(
					$e['hasVat'] and
					$type !== Pdf::DELIVERY_NOTE and
					$e['vatByRate']
				) {

					$h .= '<div class="pdf-document-vat-rates">';
						$h .= '<div>'.s("Taux TVA").'</div>';
						$h .= '<div>'.s("Total", $e->getTaxes()).'</div>';
						$h .= '<div>'.s("TVA").'</div>';
						foreach($e['vatByRate'] as ['vat' => $vat, 'vatRate' => $vatRate, 'amount' => $amount]) {
							$h .= '<div>'.s("{value} %", $vatRate).'</div>';
							$h .= '<div>'.\util\TextUi::money($amount).'</div>';
							$h .= '<div>'.\util\TextUi::money($vat).'</div>';
						}
					$h .= '</div>';

				}

			$h .= '</td>';
			$h .= '<td colspan="'.$colspan2.'">';

				$h .= '<div class="pdf-document-total">';

					if($e['hasVat']) {

						if($type !== Pdf::DELIVERY_NOTE) {

							$h .= '<div class="pdf-document-total-label">';
								$h .= s("Total HT");
							$h .= '</div>';
							$h .= '<div class="pdf-document-total-value">';
								$h .= \util\TextUi::money($e['priceExcludingVat']);
							$h .= '</div>';
							$h .= '<div class="pdf-document-total-label">';
								$h .= s("Total TVA");
							$h .= '</div>';
							$h .= '<div class="pdf-document-total-value">';
								$h .= \util\TextUi::money($e['vat']);
							$h .= '</div>';
							$h .= '<div class="pdf-document-total-label">';
								$h .= s("Total TTC");
							$h .= '</div>';
							$h .= '<div class="pdf-document-total-value">';
								$h .= \util\TextUi::money($e['priceIncludingVat']);
							$h .= '</div>';

						} else {

							$taxes = $e->getTaxes();

							$h .= '<div class="pdf-document-total-label">';
								$h .= s("Total {value}", $taxes);
							$h .= '</div>';
							$h .= '<div class="pdf-document-total-value">';
								$h .= \util\TextUi::money($e['price']);
							$h .= '</div>';

						}

					} else {
						$h .= '<div class="pdf-document-total-label">';
							$h .= s("Total");
						$h .= '</div>';
						$h .= '<div class="pdf-document-total-value">';
							$h .= \util\TextUi::money($e['priceIncludingVat']);
						$h .= '</div>';
					}

				$h .= '</div>';

			$h .= '</td>';
			if($lastColumn) {
				$h .= '<td></td>';
			}
		$h .= '</tr>';

		return $h;
		
	}

	protected function getDocumentTop(string $type, Sale|Invoice $e, \farm\Farm $eFarm, string $number, string $dateDocument, ?string $dateDelivered, ?string $top): string {

		$eCustomer = $e['customer'];
		$logo = new \media\FarmLogoUi()->getUrlByElement($eFarm, 'm');

		$h = '<div class="pdf-document-header">';

			$h .= '<div class="pdf-document-vendor '.($logo !== NULL ? 'pdf-document-vendor-with-logo' : '').'">';
				if($logo !== NULL) {
					$h .= '<div class="pdf-document-vendor-logo" style="background-image: url('.$logo.')"></div>';
				}
				$h .= '<div class="pdf-document-vendor-name">';
					$h .= encode($eFarm->getSelling('legalName')).'<br/>';
				$h .= '</div>';
				$h .= '<div class="pdf-document-vendor-address">';
					$h .= $eFarm->selling()->getInvoiceAddress('html');
				$h .= '</div>';
				if($eFarm->getSelling('invoiceRegistration')) {
					$h .= '<div class="pdf-document-vendor-registration">';
						$h .= s("SIRET <u>{value}</u>", encode($eFarm->getSelling('invoiceRegistration')));
					$h .= '</div>';
				}
				if($e['hasVat'] and $eFarm->getSelling('invoiceVat')) {
					$h .= '<div class="pdf-document-vendor-registration">';
						$h .= s("TVA intracommunautaire<br/><u>{value}</u>", encode($eFarm->getSelling('invoiceVat')));
					$h .= '</div>';
				}
			$h .= '</div>';

			$h .= '<div class="pdf-document-top">';

				$h .= '<div class="pdf-document-structure">';

					$h .= '<h2 class="pdf-document-title">'.self::getName($type, $e).'</h2>';
					$h .= '<div class="pdf-document-details">';
						$h .= '<div class="pdf-document-detail-label">'.s("Numéro").'</div>';
						$h .= '<div><b>'.$number.'</b></div>';
						$h .= $dateDocument;
					$h .= '</div>';

				$h .= '</div>';

				$h .= '<div class="pdf-document-customer">';

					$h .= '<div class="pdf-document-customer-name">';
						$h .= encode($eCustomer->getLegalName()).'<br/>';
					$h .= '</div>';

					if($eCustomer->hasInvoiceAddress()) {
						$h .= '<div class="pdf-document-customer-address">';
							$h .= $eCustomer->getInvoiceAddress('html');
						$h .= '</div>';
					}

					$email = $eCustomer['email'] ?? $eCustomer['user']['email'] ?? NULL;

					if($email) {
						$h .= '<div class="pdf-document-customer-email">';
							$h .= encode($email);
						$h .= '</div>';
					}

					if($eCustomer['invoiceRegistration'] !== NULL or $eCustomer['invoiceVat'] !== NULL) {
						$h .= '<br/>';
					}

					if($eCustomer['invoiceRegistration'] !== NULL) {
						$h .= '<div class="pdf-document-customer-registration">';
							$h .= s("SIRET <u>{value}</u>", encode($eCustomer['invoiceRegistration']));
						$h .= '</div>';
					}
					if($eCustomer['invoiceVat'] !== NULL) {
						$h .= '<div class="pdf-document-customer-registration">';
							$h .= s("TVA intracommunautaire <u>{value}</u>", encode($eCustomer['invoiceVat']));
						$h .= '</div>';
					}

				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';

		$h .= $dateDelivered;

		if($top !== NULL) {
			$h .= '<div class="pdf-document-custom-top">'. new \editor\EditorUi()->value($top).'</div>';
		}

		return $h;

	}

	protected function getDocumentBottom(string $type, \farm\Farm $eFarm, ?string $paymentCondition, ?string $footer): string {

		$h = '';

		if($footer !== NULL) {
			$h .= '<div class="pdf-document-custom-bottom">'.new \editor\EditorUi()->value($footer).'</div>';
		}

		if($paymentCondition) {

			$h .= '<div class="pdf-document-payment">';
				$h .= '<h4>'.s("Conditions de paiement").'</h4>';
				$h .= new \editor\EditorUi()->value($paymentCondition);
			$h .= '</div>';

		}

		$paymentMode = match($type) {
			Pdf::ORDER_FORM => $eFarm->getSelling('paymentMode'),
			Pdf::INVOICE => $eFarm->getSelling('paymentMode'),
			Pdf::DELIVERY_NOTE => NULL
		};

		if($paymentMode) {

			$h .= '<div class="pdf-document-payment">';
				$h .= '<h4>'.s("Moyens de paiement").'</h4>';
				$h .= new \editor\EditorUi()->value($paymentMode);
			$h .= '</div>';

		}

		return $h;

	}

	protected function getTableTitle(Sale $eSale, string $type, bool $withPackaging): string {

		if($eSale['hasVat']) {
			$taxes = '<br/>('.$eSale->getTaxes().')';
		} else {
			$taxes = '';
		}

		$h = '<tr>';
			$h .= '<td class="pdf-document-item-header">'.s("Désignation").'</td>';
			$h .= '<td class="pdf-document-item-header"></td>';
			if($withPackaging) {
				$h .= '<td class="pdf-document-item-header pdf-document-packaging">'.s("Colis<br/>livrés").'</td>';
			}
			$h .= '<td class="pdf-document-item-header pdf-document-number">'.s("Quantité<br/>livrée").'</td>';
			$h .= '<td class="pdf-document-item-header pdf-document-unit-price">';
				$h .= s("Prix unitaire");
				$h .= $taxes;
			$h .= '</td>';
			$h .= '<td class="pdf-document-item-header pdf-document-price">';
				$h .= s("Montant");
				$h .= $taxes;
			$h .= '</td>';
			if($eSale['hasVat'] and $type !== Pdf::DELIVERY_NOTE) {
				$h .= '<td class="pdf-document-item-header pdf-document-vat">';
					$h .= s("TVA");
				$h .= '</td>';
			}
		$h .= '</tr>';

		return $h;

	}

	protected function getTableItem(Sale $eSale, string $type, bool $withPackaging, Item $eItem, bool $isLast): string {

		$last = ($isLast ? 'pdf-document-item-last' : '');

		$details = [];
		if($eItem['product']->notEmpty() and $eItem['product']['size']) {
			$details[] = s("Calibre {value}", encode($eItem['product']['size']));
		}
		if($eItem['product']->notEmpty() and $eItem['product']['origin']) {
			$details[] = s("Origine {value}", encode($eItem['product']['origin']));
		}
		if($eItem['packaging']) {
			$details[] = s("Colis de {value}", \selling\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE));
		}

		$h = '<tr class="pdf-document-item '.$last.'">';
			$h .= '<td class=" pdf-document-product">';
				$h .= encode($eItem['name']);
				if($details) {
					$h .= '<div class="pdf-document-product-details">';
						$h .= implode(' / ', $details);
					$h .= '</div>';
				}
			$h .= '</td>';
			$h .= '<td>';
				if($eItem['quality']) {
					$h .= \farm\FarmUi::getQualityLogo($eItem['quality'], '0.75cm');
				}
			$h .= '</td>';
			if($withPackaging) {
				$h .= '<td class="pdf-document-packaging">';
					if($eItem['packaging']) {
						$h .= $eItem['number'];
					} else {
						$h .= '<span class="color-muted">-</span>';
					}
				$h .= '</td>';
			}
			$h .= '<td class="pdf-document-number">';
				$h .= \selling\UnitUi::getValue($eItem['number'] * ($eItem['packaging'] ?? 1), $eItem['unit'], TRUE);
			$h .= '</td>';
			$h .= '<td class="pdf-document-unit-price">';
				$h .= \util\TextUi::money($eItem['unitPrice']);
				$h .= \selling\UnitUi::getBy($eItem['unit'], short: TRUE);
			$h .= '</td>';
			$h .= '<td class="pdf-document-price">';
				$h .= \util\TextUi::money($eItem['price']);
			$h .= '</td>';
			if($eSale['hasVat'] and $type !== Pdf::DELIVERY_NOTE) {
				$h .= '<td class="pdf-document-vat">';
					$h .= s('{value} %', $eItem['vatRate']);
				$h .= '</td>';
			}
		$h .= '</tr>';

		return $h;

	}

	public function createOrderForm(Sale $eSale, Pdf $ePdf): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/sale:doGenerateDocument');

			$h .= $form->hidden('id', $eSale);
			$h .= $form->hidden('type', Pdf::ORDER_FORM);

			$h .= $form->group(
				s("Vente"),
				'<a href="/vente/'.$eSale['id'].'" class="btn btn-sm btn-outline-primary" target="_blank">'.$eSale->getNumber().'</a> '.CustomerUi::link($eSale['customer'], newTab: TRUE)
			);

			$h .= $form->dynamicGroups($eSale, ['orderFormValidUntil', 'orderFormPaymentCondition']);


			$h .= $form->group(
				content: $form->submit($ePdf->empty() ? s("Générer le devis") : s("Regénérer le devis"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-pdf-create-order-form',
			title: s("Générer un devis"),
			body: $h
		);

	}

	public function getOrderFormMail(\farm\Farm $eFarm, Sale $eSale, ?string $template): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SALE_ORDER_FORM, $eSale);
		$variables = \mail\CustomizeUi::getSaleVariables(\mail\Customize::SALE_ORDER_FORM, $eFarm, $eSale);

		$title = s("Devis {value}", $variables['number'].' - '.$variables['customer']);
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eFarm, $title, $content);

	}

	public function getDeliveryNoteMail(\farm\Farm $eFarm, Sale $eSale, ?string $template): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SALE_DELIVERY_NOTE, $eSale);
		$variables = \mail\CustomizeUi::getSaleVariables(\mail\Customize::SALE_DELIVERY_NOTE, $eFarm, $eSale);

		$title = s("Bon de livraison {value}", $variables['number'].' - '.$variables['customer']);
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eFarm, $title, $content);

	}

	public function getInvoiceMail(\farm\Farm $eFarm, Invoice $eInvoice, \Collection $cSale, ?string $template): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SALE_INVOICE);
		$variables = \mail\CustomizeUi::getSaleVariables(\mail\Customize::SALE_INVOICE, $eFarm, $eInvoice, $cSale);

		$eCustomer = $eInvoice['customer'];

		$title = s("Facture {value}", $eInvoice['name'].' - '.($eCustomer->getLegalName()));
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eFarm, $title, $content);

	}

	public static function getTexts(string $type): array {
		return [
			Pdf::DELIVERY_NOTE => [
				'generate' => s("Générer le bon de livraison"),
				'generateConfirm' => s("Générer le bon de livraison maintenant ?"),
				'generateNew' => s("Regénérer le bon de livraison"),
				'generateNewConfirm' => s("Générer un nouveau bon de livraison à jour ?"),
				'sendConfirm' => s("Confirmer l'envoi du bon de livraison au client par e-mail ?"),
				'deleteConfirm' => s("Voulez-vous vraiment supprimer ce bon de livraison ?"),
			],
			Pdf::ORDER_FORM => [
				'generate' => s("Générer un devis"),
				'generateConfirm' => NULL,
				'generateNew' => s("Regénérer le devis"),
				'generateNewConfirm' => NULL,
				'sendConfirm' => s("Confirmer l'envoi du devis au client par e-mail ?"),
				'deleteConfirm' => s("Voulez-vous vraiment supprimer ce devis ?"),
			],
			Pdf::INVOICE => [
				'generate' => s("Générer une facture"),
				'generateConfirm' => NULL,
				'generateNew' => s("Regénérer la facture"),
				'generateNewConfirm' => NULL,
				'sendConfirm' => s("Confirmer l'envoi de la facture au client par e-mail ?"),
				'deleteConfirm' => s("Voulez-vous vraiment supprimer cette facture ?"),
			],
		][$type];
	}

	public static function getName(string $type, Sale|Invoice $e, bool $short = FALSE): string {
		return [
			Pdf::DELIVERY_NOTE => $short ? s("BL") : s("Bon de livraison"),
			Pdf::ORDER_FORM => $short ? s("DE") : s("Devis"),
			Pdf::INVOICE => $e->isCreditNote() ? ($short ? s("AV") : s("Avoir")) : ($short ? s("FA") : s("Facture")),
		][$type];
	}

	public function getSalesByDate(\shop\Date $eDate, \Collection $cSale, \Collection $cItem): string {

		$h = '<style>@page {	size: A4 landscape; margin: 0.5cm; }</style>';

		$h .= '<div class="pdf-sales-summary-wrapper">';

			$h .= '<h1>'.encode($eDate['shop']['name']).'</h1>';
			$h .= '<h2>'.s("Livraison du {value}", \util\DateUi::numeric($eDate['deliveryDate'])).' | '.p("{value} commande", "{value} commandes", $cSale->count()).'</h2>';

			$h .= $this->getSalesSummary($cItem);

		$h .= '</div>';

		$h .= $this->getSalesContent($eDate['farm'], $cSale);

		return $h;

	}

	public function getSales(\farm\Farm $eFarm, \Collection $cSale, \Collection $cItem): string {

		$h = '<style>@page {	size: A4 landscape; margin: 0.5cm; }</style>';

		$h .= '<div class="pdf-sales-summary-wrapper">';

			$h .= '<h1>'.p("{value} vente", "{value} ventes", $cSale->count()).'</h1>';

			$h .= $this->getSalesSummary($cItem);

		$h .= '</div>';

		$h .= $this->getSalesContent($eFarm, $cSale);

		return $h;

	}

	protected function getSalesContent(\farm\Farm $eFarm, \Collection $cSale): string {

		$eConfiguration = $eFarm->selling();

		$items = [];
		$farms = array_count_values($cSale->getColumnCollection('farm')->getIds());

		foreach($cSale as $eSale) {
			$items = array_merge($items, $this->getSaleLabel($eSale, $farms));
		}

		$itemsPerPage = 4;

		if($eConfiguration['pdfNaturalOrder']) {

			for($i = count($items) % $itemsPerPage; $i < $itemsPerPage; $i++) {
				$items[] = '<div></div>';
			}

			$pages = count($items) / $itemsPerPage;

			$itemsOrdered = [];

			foreach($items as $key => $item) {
				$newKey = ($key % $pages) * $itemsPerPage + (int)($key / $pages);
				$itemsOrdered[$newKey] = $item;
			}

			ksort($itemsOrdered);

		} else {
			$itemsOrdered = $items;
		}

		$itemsChunk = array_chunk($itemsOrdered, $itemsPerPage);

		if($itemsChunk === []) {
			$itemsChunk[] = [];
		}

		$h = '';

		foreach($itemsChunk as $itemsByN) {

			$h .= '<div class="pdf-sales-label-wrapper">';
				$h .= implode('', $itemsByN);
			$h .= '</div>';

		}

		return $h;

	}

	protected function getSalesSummary(\Collection $cItem): string {

		$h = '<table class="pdf-sales-summary tr-even">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th colspan="2">'.s("Produit").'</th>';
					$h .= '<th class="text-end" colspan="2">'.s("Quantité").'</th>';
					$h .= '<th class="text-end">'.s("Montant").'</th>';
					$h .= '<th class="pdf-sales-summary-comment">'.s("Observations").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';
				foreach($cItem as $eItem) {
					$h .= '<tr>';
						$h .= '<td>'.encode($eItem['name']).'</th>';
						$h .= '<td>';
							if($eItem['quality']) {
								$h .= \Asset::image('main', $eItem['quality'].'.png', ['style' => 'height: 0.4cm']);
							}
						$h .= '</th>';
						$h .= '<td class="pdf-sales-summary-quantity text-end">'.($eItem['quantity'] === NULL ? '?' : round($eItem['quantity'], 2)).'</td>';
						$h .= '<td class="td-min-content">'.\selling\UnitUi::getSingular($eItem['unit'], short: TRUE).'</td>';
						$h .= '<td class="text-end">';
							if($eItem['price'] !== NULL) {
								$h .= \util\TextUi::money($eItem['price']);
							}
						$h.= '</td>';
						$h .= '<td>';
							$h .= $this->getItemComposition($eItem);
						$h .= '</td>';
					$h .= '</tr>';
				}
			$h .= '</tbody>';

		$h .= '</table>';

		return $h;

	}

	protected function getItemComposition(Item $eItem): string {


		$h = '';

		if(
			$eItem['productComposition'] and
			$eItem['cItemIngredient']->notEmpty()
		) {

			$h .= '<div class="pdf-sales-summary-composition">';

				$h .= '<table class="mb-0">';

					$h .= '<tr>';
						$h .= '<th>'.s("Composition").'</th>';
						$h .= '<th class="text-center" colspan="2">'.s("Total").'</th>';
					$h .= '</tr>';

					foreach($eItem['cItemIngredient'] as $eItemIngredient) {

						$quantity = $eItemIngredient['number'] * ($eItemIngredient['packaging'] ?? 1);

						$h .= '<tr>';
							$h .= '<td>';
								$h .= ProductUi::getVignette($eItemIngredient['product'], '1.5rem').' '.encode($eItemIngredient['name']);
								$h .= '  <small><b>'.\selling\UnitUi::getValue($quantity, $eItemIngredient['unit'], TRUE).'</b></small>';
							$h .= '</td>';
							$h .= '<td class="pdf-sales-summary-quantity text-end">';
								$h .= $eItem['quantity'] * $quantity;
							$h .= '</td>';
							$h .= '<td>';
								$h .= \selling\UnitUi::getSingular($eItemIngredient['unit'], TRUE);
							$h .= '</td>';
						$h .= '</tr>';

					}

				$h .= '</table>';

			$h .= '</div>';

		}

		return $h;

	}

	public function getSaleLabel(\selling\Sale $eSale, array $farms): array {

		$eCustomer = $eSale['customer'];

		$itemsList = [];

		foreach($eSale['cItem'] as $eItem) {

			if($eItem['packaging'] !== NULL) {
				// Gérer les colis en nombre entier
				$quantity = $eItem['packaging'] * $eItem['number'];
			} else {
				$quantity = $eItem['number'];
			}

			$item = '<div class="'.(mb_strlen($eItem['name']) > 50 ? 'pdf-sales-label-content-shrink-strong' : (mb_strlen($eItem['name']) > 40 ? 'pdf-sales-label-content-shrink' : '')).'">';
				if(mb_strlen($eItem['name']) >= 60) {
					$item .= mb_substr($eItem['name'], 0, 55).'...';
				} else {
					$item .= encode($eItem['name'] ?? '');
				}
			$item .= '</div>';
			$item .= '<div>';
				$item .= \selling\UnitUi::getValue($quantity, $eItem['unit'], short: TRUE);
			$item .= '</div>';
			$item .= '<div>';
				if($eItem['price'] !== NULL) {
					$item .= \util\TextUi::money($eItem['price']);
				}
			$item .= '</div>';

			$itemsList[] = $item;

		}

		$itemsChunk = array_chunk($itemsList, 15);
		$pages = count($itemsChunk);

		$entries = [];

		foreach($itemsChunk as $position => $items) {

			$showComment = ($eSale['shopComment'] !== NULL and $position === 0);

			$entry = '<div class="pdf-sales-label-item">';

				$entry .= '<div class="pdf-sales-label-customer '.($showComment ? 'pdf-sales-label-customer-with-comment' : '').'">';
					$entry .= '<span '.(mb_strlen($eCustomer->getName()) > 50 ? 'pdf-sales-label-customer-large' : '').'>'.encode($eCustomer->getName()).'</span>';

					if($showComment) {
						$entry .= '<span class="pdf-sales-label-comment">&laquo; '.encode($eSale['shopComment']).' &raquo;</span>';
					}

					if(count($itemsChunk) > 1) {
						$entry .= '<span class="pdf-sales-label-page">'.($position + 1).' / '.$pages.'</span>';
					} else {
						$entry .= '<span></span>';
					}

				$entry.= '</div>';

				$entry .= '<div class="pdf-sales-label-details '.($position > 0 ? 'pdf-sales-label-details-next' : '').'">';

					if(count($farms) > 1) {

						$entry .= '<div class="pdf-sales-label-detail">';
							$entry .= '<div class="pdf-sales-label-detail-title">'.s("Producteur").'</div>';
							$entry .= '<div class="pdf-sales-label-detail-value">'.encode($eSale['farm']['name']).'</div>';
						$entry .= '</div>';

					}

					if($position === 0) {

						$entry .= '<div class="pdf-sales-label-detail">';
							$entry .= '<div class="pdf-sales-label-detail-title">'.s("Commande").'</div>';
							$entry .= '<div class="pdf-sales-label-detail-value">'.$eSale->getNumber().'</div>';
						$entry .= '</div>';

						if(in_array($eSale['preparationStatus'], [Sale::DRAFT, Sale::CANCELED, Sale::BASKET])) {

							\Asset::css('selling', 'sale.css');

							$entry .= '<div class="pdf-sales-label-detail">';
								$entry .= '<div class="pdf-sales-label-detail-title">'.s("État").'</div>';
								$entry .= '<div class="pdf-sales-label-detail-value">';
									$entry .= '<span class="sale-preparation-status-label sale-preparation-status-'.$eSale['preparationStatus'].'">'.SaleUi::p('preparationStatus')->values[$eSale['preparationStatus']].'</span>';
								$entry .= '</div>';
							$entry .= '</div>';

						}

						$entry .= '<div class="pdf-sales-label-detail">';
							$entry .= '<div class="pdf-sales-label-detail-title">'.s("Date de retrait").'</div>';
							$entry .= '<div class="pdf-sales-label-detail-value">'.\util\DateUi::numeric($eSale['deliveredAt']).'</div>';
						$entry .= '</div>';
						$entry .= '<div class="pdf-sales-label-detail">';
							$entry .= '<div class="pdf-sales-label-detail-title">'.s("Produits").'</div>';
							$entry .= '<div class="pdf-sales-label-detail-value">'.$eSale['cItem']->count().'</div>';
						$entry .= '</div>';
						$entry .= '<div class="pdf-sales-label-detail">';
							$entry .= '<div class="pdf-sales-label-detail-title">'.s("Montant").'</div>';
							$entry .= '<div class="pdf-sales-label-detail-value">'.\util\TextUi::money($eSale['priceIncludingVat']).'</div>';
						$entry .= '</div>';

						if($eSale['paymentMethod']->notEmpty()) {
							$entry .= '<div class="pdf-sales-label-detail">';
								$entry .= '<div class="pdf-sales-label-detail-title">'.s("Moyen de paiement").'</div>';
								$entry .= '<div class="pdf-sales-label-detail-value">';
									$entry .= SaleUi::getPaymentMethodName($eSale);
								$entry .= '</div>';
							$entry .= '</div>';
						}

						if($eSale->isPaymentOnline()) {
							$entry .= '<div class="pdf-sales-label-detail">';
								$entry .= '<div class="pdf-sales-label-detail-title">'.s("Paiement").'</div>';
								$entry .= '<div class="pdf-sales-label-detail-value">';
									$entry .= \selling\SaleUi::getPaymentStatusForCustomer($eSale, withColors: TRUE);
								$entry .= '</div>';
							$entry .= '</div>';
						}

						if($eSale['shopPoint']->notEmpty()) {
							$entry .= '<div class="pdf-sales-label-detail">';
								$entry .= '<div class="pdf-sales-label-detail-title">'.\shop\PointUi::p('type')->values[$eSale['shopPoint']['type']].'</div>';
								$entry .= '<div class="pdf-sales-label-detail-value">';
									$entry .= match($eSale['shopPoint']['type']) {
										\shop\Point::HOME => '<div class="pdf-sales-label-address">'.$eSale->getDeliveryAddress('html').'</div>',
										\shop\Point::PLACE => encode($eSale['shopPoint']['name'])
									};
								$entry .= '</div>';
							$entry .= '</div>';
						}

					} else {

						$entry .= '<div class="pdf-sales-label-detail">';
							$entry .= '<div class="pdf-sales-label-detail-title">'.s("Suite de commande").'</div>';
							$entry .= '<div class="pdf-sales-label-detail-value">'.$eSale['id'].'</div>';
						$entry .= '</div>';

					}

				$entry .= '</div>';

				$entry .= '<div class="pdf-sales-label-content pdf-sales-label-content-'.count($items).'">';
					$entry .= implode('', $items);
				$entry .= '</div>';

			$entry .= '</div>';

			$entries[] = $entry;

		}

		return $entries;

	}

	public function getFilename(string $type, \farm\Farm $eFarm, \Element $e): string {

		switch($type) {
			case \selling\Pdf::ORDER_FORM:
				return $e->getOrderForm($eFarm).'-'.str_replace('-', '', $e['deliveredAt']).'-'.$e['customer']->getName().'.pdf';

			case \selling\Pdf::DELIVERY_NOTE:
				return $e->getDeliveryNote($eFarm).'-'.str_replace('-', '', $e['deliveredAt']).'-'.$e['customer']->getName().'.pdf';

			case \selling\Pdf::INVOICE:
				return $e['name'].'-'.str_replace('-', '', $e['date']).'-'.$e['customer']->getName().'.pdf';

			default:
				return '';
		}
	}

}
?>
