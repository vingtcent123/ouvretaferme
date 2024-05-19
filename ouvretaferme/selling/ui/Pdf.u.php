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

		for($i = count($items) % $itemsPerPage; $i < 12; $i++) {
			$items[] = $this->getLabel($eFarm, new Customer(), quality: $eFarm['quality']);
		}

		$pages = count($items) / $itemsPerPage;

		$itemsOrdered = [];

		foreach($items as $key => $item) {
			$newKey = ($key % $pages) * $itemsPerPage + (int)($key / $pages);
			$itemsOrdered[$newKey] = $item;
		}

		ksort($itemsOrdered);

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

	public function getLabel(\farm\Farm $eFarm, Customer $eCustomer, ?string $name = NULL, string $quality = NULL, ?string $size = NULL, ?float $quantity = NULL, ?string $unit = NULL): string {

		$eFarm->expects(['selling']);

		$logo = (new \media\FarmLogoUi())->getUrlByElement($eFarm, 'm');
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
					$h .= encode($eFarm['selling']['legalName']).'<br/>';
					$h .= nl2br(encode($eFarm['selling']->getInvoiceAddress()));
				$h .= '</div>';
				$h .= '<div class="pdf-label-quality">';
					if($quality) {
						$h .= \Asset::image('main', $quality.'.png', ['style' => 'height: 0.75cm']);
					}
					if($quality === \farm\Farm::ORGANIC and $eFarm['selling']['organicCertifier']) {
						$h .= '<span>'.s("Certifié par").'<br/>'.$eFarm['selling']['organicCertifier'].'</span>';
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
						$h .= \main\UnitUi::getValue($quantity, $unit);
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
				Pdf::DELIVERY_NOTE => $eSale->getDeliveryNote(),
				Pdf::ORDER_FORM => $eSale->getOrderForm(),
				Pdf::INVOICE => $eSale['invoice']->getInvoice()
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
			if($type === Pdf::ORDER_FORM and $eFarm['selling']['orderFormDelivery']) {
				$dateDelivered = '<div class="pdf-document-delivery">'.s("Commande livrable le {value}", \util\DateUi::numeric($eSale['deliveredAt'])).'</div>';
			}

			if($type === Pdf::INVOICE and $eSale['priceExcludingVat'] >= 0) {
				$dateDelivered = '<div class="pdf-document-delivery">'.s("Commande livrée le {value}", \util\DateUi::numeric($eSale['deliveredAt'])).'</div>';
			}

			$h .= $this->getDocumentTop($type, $eSale, $eFarm, $number, $dateDocument, $dateDelivered);

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

				$h .= $this->getDocumentBottom($type, $eFarm, $paymentCondition);

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getDocumentInvoice(Invoice $eInvoice, \farm\Farm $eFarm, \Collection $cSale): string {

		$h = '<style>@page {	size: A4; margin: 1cm; }</style>';

		$h .= '<div class="pdf-document-wrapper">';

			$number = $eInvoice->getInvoice();

			$dateDocument = '<div class="pdf-document-detail-label">'.s("Date").'</div>';
			$dateDocument .= '<div>'.\util\DateUi::numeric($eInvoice['date']).'</div>';

			$h .= $this->getDocumentTop(Pdf::INVOICE, $eInvoice, $eFarm, $number, $dateDocument);

			$h .= '<div class="pdf-document-body">';

				$h .= '<table class="pdf-document-items">';

					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<td class="pdf-document-item-header" colspan="3">'.s("Désignation").'</td>';
							$h .= '<td class="pdf-document-item-header"></td>';
							if($eInvoice['hasVat']) {
								$h .= '<td class="pdf-document-item-header pdf-document-price">';
									$h .= s("Montant");
									$h .= '<br/>(HT)';
								$h .= '</td>';
								$h .= '<td class="pdf-document-item-header pdf-document-vat">';
									$h .= s("TVA");
								$h .= '</td>';
								$h .= '<td class="pdf-document-item-header pdf-document-price">';
									$h .= s("Montant");
									$h .= '<br/>(TTC)';
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
												$h .= s("Bon de livraison {value}", $eSale->getDeliveryNote());
											$h .= '</div>';
										}
									$h .= '</td>';
									$h .= '<td></td>';
									if($eInvoice['hasVat']) {
										$h .= '<td class="pdf-document-price">';
											$h .= \util\TextUi::money(match($eSale['taxes']) {
												Sale::INCLUDING => $amount - $vat,
												Sale::EXCLUDING => $amount
											});
										$h .= '</td>';
										$h .= '<td class="pdf-document-vat">';
											$h .= s('{value} %', $vatRate);
										$h .= '</td>';
										$h .= '<td class="pdf-document-price">';
											$h .= \util\TextUi::money(match($eSale['taxes']) {
												Sale::INCLUDING => $amount,
												Sale::EXCLUDING => $amount + $vat,
											});
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
										'unit' => NULL,
										'unitPrice' => NULL,
										'price' => $eSale['shipping']
									]));
								}

							}

						}


						$h .= $this->getDocumentTotal(Pdf::INVOICE, $eFarm, $eInvoice, $eInvoice['hasVat'] ? 3 : 2, $eInvoice['hasVat'] ? 3 : 2, FALSE);

					$h .= '</tbody>';

				$h .= '</table>';

				$h .= $this->getDocumentBottom(Pdf::INVOICE, $eFarm, $eInvoice['paymentCondition']);

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
					$h .= \main\UnitUi::getValue($eItem['number'] * ($eItem['packaging'] ?? 1), $eItem['unit'], TRUE);
				}
			$h .= '</td>';
			$h .= '<td class="pdf-document-unit-price">';
				if($eItem['unitPrice'] !== NULL) {
					$h .= \util\TextUi::money($eItem['unitPrice']);
				}
				if($eItem['unit']) {
					$h .= ' / '.\main\UnitUi::getSingular($eItem['unit'], TRUE);
				}
			$h .= '</td>';
			if($eSale['hasVat'] and $eSale['taxes'] === Sale::INCLUDING) {
				$h .= '<td colspan="2"></td>';
			}
			$h .= '<td class="pdf-document-price">';
				$h .= \util\TextUi::money($eItem['price']);
			$h .= '</td>';
			if($eSale['hasVat'] and $eSale['taxes'] === Sale::EXCLUDING) {
				$h .= '<td colspan="2"></td>';
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
						$h .= '<div>'.s("Produits issus de l’agriculture biologique ou en conversion vers l'agriculture biologique certifiés par {value}", '<span style="white-space: nowrap">'.$eFarm['selling']['organicCertifier'].'</span>').'</div>';
					$h .= '</div>';

				} else if($e['organic']) {

					$h .= '<div class="pdf-document-quality">';
						$h .= \Asset::image('main', 'organic.png', ['style' => 'width: 5rem; margin-right: 1rem']);
						$h .= '<div>'.s("Produits issus de l’agriculture biologique certifiés par {value}", '<span style="white-space: nowrap">'.$eFarm['selling']['organicCertifier'].'</span>').'</div>';
					$h .= '</div>';

				} else if($e['conversion']) {

					$h .= '<div class="pdf-document-quality">';
						$h .= \Asset::image('main', 'organic.png', ['style' => 'width: 5rem; margin-right: 1rem']);
						$h .= '<div>'.s("Produits en conversion vers l’agriculture biologique certifiés par {value}", '<span style="white-space: nowrap">'.$eFarm['selling']['organicCertifier'].'</span>').'</div>';
					$h .= '</div>';

				}

			$h .= '</td>';
			$h .= '<td class="pdf-document-item-vat-rates" colspan="'.$colspan1.'">';

				if(
					$e['hasVat'] and
					$type === Pdf::INVOICE and
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

	protected function getDocumentTop(string $type, Sale|Invoice $e, \farm\Farm $eFarm, string $number, string $dateDocument, ?string $dateDelivered = NULL): string {

		$eCustomer = $e['customer'];
		$logo = (new \media\FarmLogoUi())->getUrlByElement($eFarm, 'm');

		$h = '<div class="pdf-document-header">';

			$h .= '<div class="pdf-document-vendor '.($logo !== NULL ? 'pdf-document-vendor-with-logo' : '').'">';
				if($logo !== NULL) {
					$h .= '<div class="pdf-document-vendor-logo" style="background-image: url('.$logo.')"></div>';
				}
				$h .= '<div class="pdf-document-vendor-name">';
					$h .= encode($eFarm['selling']['legalName']).'<br/>';
				$h .= '</div>';
				$h .= '<div class="pdf-document-vendor-address">';
					$h .= nl2br(encode($eFarm['selling']->getInvoiceAddress()));
				$h .= '</div>';
				if($eFarm['selling']['invoiceRegistration']) {
					$h .= '<div class="pdf-document-vendor-registration">';
						$h .= s("SIRET <u>{value}</u>", encode($eFarm['selling']['invoiceRegistration']));
					$h .= '</div>';
				}
				if($e['hasVat'] and $eFarm['selling']['invoiceVat']) {
					$h .= '<div class="pdf-document-vendor-registration">';
						$h .= s("TVA intracommunautaire<br/><u>{value}</u>", encode($eFarm['selling']['invoiceVat']));
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
						$h .= encode($eCustomer['legalName'] ?? $eCustomer['name']).'<br/>';
					$h .= '</div>';

					if($eCustomer->hasInvoiceAddress()) {
						$h .= '<div class="pdf-document-customer-address">';
							$h .= nl2br(encode($eCustomer->getInvoiceAddress()));
						$h .= '</div>';
					}

					$email = $eCustomer['email'] ?? $eCustomer['user']['email'] ?? NULL;

					if($email) {
						$h .= '<div class="pdf-document-customer-email">';
							$h .= encode($email);
						$h .= '</div>';
					}

				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';

		$h .= $dateDelivered;

		if($type === Pdf::ORDER_FORM and $eFarm['selling']['orderFormHeader']) {
			$h .= '<div class="pdf-document-custom-top">'. (new \editor\EditorUi())->value($eFarm['selling']['orderFormHeader']).'</div>';
		}

		if($type === Pdf::INVOICE and $eFarm['selling']['invoiceHeader']) {
			$h .= '<div class="pdf-document-custom-top">'. (new \editor\EditorUi())->value($eFarm['selling']['invoiceHeader']).'</div>';
		}

		return $h;

	}

	protected function getDocumentBottom(string $type, \farm\Farm $eFarm, ?string $paymentCondition): string {

		$h = '';

		if($type === Pdf::ORDER_FORM and $eFarm['selling']['orderFormFooter']) {
			$h .= '<div class="pdf-document-custom-bottom">'.(new \editor\EditorUi())->value($eFarm['selling']['orderFormFooter']).'</div>';
		}

		if($type === Pdf::INVOICE and $eFarm['selling']['invoiceFooter']) {
			$h .= '<div class="pdf-document-custom-bottom">'.(new \editor\EditorUi())->value($eFarm['selling']['invoiceFooter']).'</div>';
		}

		if($paymentCondition) {

			$h .= '<div class="pdf-document-payment">';
				$h .= '<h4>'.s("Conditions de paiement").'</h4>';
				$h .= (new \editor\EditorUi())->value($paymentCondition);
			$h .= '</div>';

		}

		$paymentMode = match($type) {
			Pdf::ORDER_FORM => $eFarm['selling']['paymentMode'],
			Pdf::INVOICE => $eFarm['selling']['paymentMode'],
			Pdf::DELIVERY_NOTE => NULL
		};

		if($paymentMode) {

			$h .= '<div class="pdf-document-payment">';
				$h .= '<h4>'.s("Moyens de paiement").'</h4>';
				$h .= (new \editor\EditorUi())->value($paymentMode);
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
			if($eSale['hasVat'] and $type === Pdf::INVOICE) {
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
		if($eItem['packaging']) {
			$details[] = s("Colis de {value}", \main\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE));
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
				$h .= \main\UnitUi::getValue($eItem['number'] * ($eItem['packaging'] ?? 1), $eItem['unit'], TRUE);
			$h .= '</td>';
			$h .= '<td class="pdf-document-unit-price">';
				$h .= \util\TextUi::money($eItem['unitPrice']);
				if($eItem['unit']) {
					$h .= ' / '.\main\UnitUi::getSingular($eItem['unit'], TRUE);
				}
			$h .= '</td>';
			$h .= '<td class="pdf-document-price">';
				$h .= \util\TextUi::money($eItem['price']);
			$h .= '</td>';
			if($eSale['hasVat'] and $type === Pdf::INVOICE) {
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

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SALE_ORDER_FORM);
		$variables = \mail\CustomizeUi::getSaleVariables(\mail\Customize::SALE_ORDER_FORM, $eFarm, $eSale);

		$title = s("Devis {value}", $variables['number'].' - '.$variables['customer']);
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eFarm, $title, $content);

	}

	public function getDeliveryNoteMail(\farm\Farm $eFarm, Sale $eSale, ?string $template): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SALE_DELIVERY_NOTE);
		$variables = \mail\CustomizeUi::getSaleVariables(\mail\Customize::SALE_DELIVERY_NOTE, $eFarm, $eSale);

		$title = s("Bon de livraison {value}", $variables['number'].' - '.$variables['customer']);
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eFarm, $title, $content);

	}

	public function getInvoiceMail(\farm\Farm $eFarm, Invoice $eInvoice, \Collection $cSale, ?string $template): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SALE_INVOICE);
		$variables = \mail\CustomizeUi::getSaleVariables(\mail\Customize::SALE_INVOICE, $eFarm, $eInvoice, $cSale);

		$eCustomer = $eInvoice['customer'];

		$title = s("Facture {value}", $eInvoice->getInvoice().' - '.($eCustomer['legalName'] ?? $eCustomer['name']));
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

			$h .= '<h1>'.$eDate['shop']['name'].'</h1>';
			$h .= '<h2>'.s("Vente du {value}", \util\DateUi::numeric($eDate['deliveryDate'])).' | '.p("{value} commande", "{value} commandes", $cSale->count()).'</h2>';

			$h .= $this->getSalesSummary($cItem);

		$h .= '</div>';

		$h .= $this->getSalesContent($cSale);

		return $h;

	}

	public function getSales(\Collection $cSale, \Collection $cItem): string {

		$h = '<style>@page {	size: A4 landscape; margin: 0.5cm; }</style>';

		if($cSale->count() > 1) {

			$h .= '<div class="pdf-sales-summary-wrapper">';

				$h .= '<h1>'.p("{value} vente", "{value} ventes", $cSale->count()).'</h1>';

				$h .= $this->getSalesSummary($cItem);

			$h .= '</div>';

		}

		$h .= $this->getSalesContent($cSale);

		return $h;

	}

	protected function getSalesContent(\Collection $cSale): string {

		$items = [];

		foreach($cSale as $eSale) {
			$items = array_merge($items, $this->getSaleLabel($eSale));
		}

		$itemsPerPage = 4;

		$itemsChunk = array_chunk($items, $itemsPerPage);

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

		$h = '<table class="pdf-sales-summary tr-bordered tr-even">';

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
						$h .= '<td>'.$eItem['name'].'</th>';
						$h .= '<td>';
							if($eItem['quality']) {
								$h .= \Asset::image('main', $eItem['quality'].'.png', ['style' => 'height: 0.4cm']);
							}
						$h .= '</th>';
						$h .= '<td class="pdf-sales-summary-quantity text-end">'.round($eItem['quantity'], 2).'</td>';
						$h .= '<td class="td-min-content">'.\main\UnitUi::getSingular($eItem['unit'], short: TRUE).'</td>';
						$h .= '<td class="text-end">'.\util\TextUi::money($eItem['price']).'</td>';
						$h .= '<td></td>';
					$h .= '</tr>';
				}
			$h .= '</tbody>';

		$h .= '</table>';

		return $h;

	}

	public function getSaleLabel(\selling\Sale $eSale): array {

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

			$entry = '<div class="pdf-sales-label-item">';

				$entry .= '<div class="pdf-sales-label-customer">';
					$entry .= '<span>'.encode($eCustomer['name']).'</span>';

					if(count($itemsChunck) > 1) {
						$entry .= '<span class="pdf-sales-label-page">'.($position + 1).' / '.$pages.'</span>';
					}

				$entry.= '</div>';

				$entry .= '<div class="pdf-sales-label-details '.($position > 0 ? 'pdf-sales-label-details-next' : '').'">';

					if($position === 0) {

						$entry .= '<div class="pdf-sales-label-detail">';
							$entry .= '<div class="pdf-sales-label-detail-title">'.s("Commande").'</div>';
							$entry .= '<div class="pdf-sales-label-detail-value">'.$eSale['id'].'</div>';
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

						if($eSale['paymentMethod']) {
							$entry .= '<div class="pdf-sales-label-detail">';
								$entry .= '<div class="pdf-sales-label-detail-title">'.s("Moyen de paiement").'</div>';
								$entry .= '<div class="pdf-sales-label-detail-value">';
									$entry .= \selling\SaleUi::p('paymentMethod')->values[$eSale['paymentMethod']];
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
										\shop\Point::HOME => '<div class="pdf-sales-label-address">'.nl2br(encode($eSale->getDeliveryAddress())).'</div>',
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

				$entry .= '<div class="pdf-sales-label-content">';
					$entry .= implode('', $items);
				$entry .= '</div>';

			$entry .= '</div>';

			$entries[] = $entry;

		}

		return $entries;

	}

}
?>