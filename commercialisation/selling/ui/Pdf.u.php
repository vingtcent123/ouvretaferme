<?php
namespace selling;

class PdfUi {

	public function __construct() {

		\Asset::css('selling', 'pdf.css');

	}

	public static function url(Pdf $e): string {

		$e->expects(['type', 'sale']);

		return '/pdf/'.$e['id'];

	}

	public function getLabels(\farm\Farm $eFarm, \Collection $cSale): string {

		$eConfiguration = $eFarm->conf();

		$items = [];

		foreach($cSale as $eSale) {

			$ccItem = $eSale['ccItem'];

			foreach($ccItem[''] as $eItem) {

				if(
					$eItem['product']->empty() or
					$eItem['product']['unprocessedPlant']->empty()
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

					$items[] = $this->getLabel($eFarm, $eSale['customer'], $eItem['product']['quality'], $eItem['name'], $eItem['additional'], $quantity, $eItem['unit']);


				}

			}

		}

		$itemsPerPage = 12;

		if(count($items) === 0) {
			$modulo = 0;
			$complete = TRUE;
		} else {

			$modulo = count($items) % $itemsPerPage;
			$complete = ($modulo > 0);

		}

		if($complete) {

			for($i = $modulo; $i < $itemsPerPage; $i++) {
				$items[] = $this->getLabel($eFarm, new Customer(), $eFarm['quality']);
			}

		}

		if($eConfiguration['pdfNaturalOrder']) {

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

	public function getLabel(\farm\Farm $eFarm, Customer $eCustomer, string $quality, ?string $name = NULL, ?string $additional = NULL, ?float $quantity = NULL, Unit $unit = new Unit()): string {

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
					$h .= encode($eFarm['legalName']).'<br/>';
					$h .= $eFarm->getLegalAddress('html');
				$h .= '</div>';
				$h .= '<div class="pdf-label-quality">';
					if($quality !== \farm\Farm::NO) {
						$h .= \Asset::image('main', $quality.'.png', ['style' => 'height: 0.75cm']);
					}
					if($quality === \farm\Farm::ORGANIC and $eFarm->getConf('organicCertifier')) {
						$h .= '<span>'.s("Certifié par").'<br/>'.$eFarm->getConf('organicCertifier').'</span>';
					}
				$h .= '</div>';
			$h .= '</div>';

			$h .= '<div class="pdf-label-content">';
				$h .= '<div>';
					$h .= '<h4>'.s("Agriculture").'</h4>';
					$h .= '<div class="pdf-label-value">'.s("France").'</div>';
				$h .= '</div>';
				$h .= '<div>';
					$h .= '<h4>'.s("Produit").'</h4>';
					$h .= '<div class="pdf-label-value">';
					if($name or $additional) {
						if($name) {
							$h .= '<div>'.encode($name).'</div>';
						}
						if($additional) {
							$h .= '<div style="font-weight: normal">'.encode($additional).'</div>';
						}
					} else {
						$h .= ' ';
					}
					$h .= '</div>';
				$h .= '</div>';
				$h .= '<div>';
					$h .= '<h4>'.s("Nombre ou masse nette").'</h4>';
					$h .= '<div class="pdf-label-value">';
					if($quantity !== NULL and $unit !== NULL) {
						$h .= \selling\UnitUi::getValue($quantity, $unit);
					} else {
						$h .= ' ';
					}
					$h .= '</div>';
				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getWatermark(): string {

		if(!OTF_DEMO) {
			return '';
		}

		$h = '<div class="pdf-document-watermark">';
			$h .= s("Démo");
		$h .= '</div>';

		return $h;

	}

	public function getDocument(Sale $eSale, string $type, ?string $number, \farm\Farm $eFarm, \Collection $cItem): string {

		$h = '<style>@page {	size: A4; margin: 1cm; }</style>';

		$h .= $this->getWatermark();

		$h .= '<div class="pdf-document-wrapper">';

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
					$dateDocument .= '<div>'.\util\DateUi::numeric($eSale['deliveryNoteDate']).'</div>';

					break;

				case Pdf::INVOICE :

					$dateDocument = '<div class="pdf-document-detail-label">'.s("Date de facturation").'</div>';
					$dateDocument .= '<div>'.\util\DateUi::numeric($eSale['invoice']['date']).'</div>';

					if($eSale['invoice']['dueDate'] !== NULL) {
						$dateDocument .= '<div class="pdf-document-detail-label">'.s("Date d'échéance").'</div>';
						$dateDocument .= '<div>'.\util\DateUi::numeric($eSale['invoice']['dueDate']).'</div>';
					}

					break;

			}

			$top = match($type) {
				Pdf::ORDER_FORM => $eSale['orderFormHeader'],
				Pdf::INVOICE => $eSale['invoice']['header'],
				Pdf::DELIVERY_NOTE => $eSale['deliveryNoteHeader'],
			};

			$h .= $this->getDocumentTop($type, $eSale, $eFarm, $number, $dateDocument, $top);

			$withPackaging = $cItem->reduce(fn($eItem, $n) => $n + (int)($eItem['packaging'] !== NULL), 0);

			if(
				$type === Pdf::INVOICE and
				$eSale['ccPdf']->notEmpty()
			) {

				$references = $this->getReferences($eSale);

				if($references) {
					$h .= '<div class="pdf-document-callback">';
						$h .= $references;
					$h .= '</div>';
				}

			}

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
								$h .= '<td colspan="'.($withPackaging ? 5 : 4).'">'.SaleUi::getShippingName().'</td>';
								$h .= '<td class="pdf-document-price">';
									$h .= \util\TextUi::money($eSale['shipping']);
								$h .= '</td>';
								if($eSale['hasVat'] and $type !== Pdf::DELIVERY_NOTE) {
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
					Pdf::ORDER_FORM => $eSale['orderFormFooter'],
					Pdf::INVOICE => $eSale['invoice']['footer'],
					Pdf::DELIVERY_NOTE => $eSale['deliveryNoteFooter']
				};

				$h .= $this->getDocumentBottom($type, $eFarm, $paymentCondition, $footer);

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getDocumentInvoice(Invoice $eInvoice, \farm\Farm $eFarm, \Collection $cSale): string {

		$h = '<style>@page {	size: A4; margin: 1cm; }</style>';

		$h .= $this->getWatermark();

		$h .= '<div class="pdf-document-wrapper">';

			$dateDocument = '<div class="pdf-document-detail-label">'.s("Date de facturation").'</div>';
			$dateDocument .= '<div>'.\util\DateUi::numeric($eInvoice['date']).'</div>';

			if($eInvoice['dueDate'] !== NULL) {
				$dateDocument .= '<div class="pdf-document-detail-label">'.s("Date d'échéance").'</div>';
				$dateDocument .= '<div>'.\util\DateUi::numeric($eInvoice['dueDate']).'</div>';
			}

			$h .= $this->getDocumentTop(Pdf::INVOICE, $eInvoice, $eFarm, $eInvoice['name'], $dateDocument, $eInvoice['header']);

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

							$references = $this->getReferences($eSale);

							$h .= '<tr class="pdf-document-item pdf-document-item-main">';
								$h .= '<td colspan="'.($eInvoice['hasVat'] ? 6 : 5).'">';
									$h .= '<div class="pdf-document-product-header">';
										$h .= '<div>'.s("Livraison du {value}", \util\DateUi::numeric($eSale['deliveredAt'])).'</div>';
										if($references) {
											$h .= '<div class="pdf-document-product-references">'.$references.'</div>';
										}
									$h .= '</div>';
								$h .= '</td>';
							$h .= '</tr>';

							foreach($eSale['vatByRate'] as $key => ['vat' => $vat, 'vatRate' => $vatRate, 'amount' => $amount]) {

								$vatRate = (float)$vatRate;

								foreach($eSale['cItem'] as $eItem) {

									if($eItem['vatRate'] !== $vatRate) {
										continue;
									}

									$h .= $this->getDetailTableItem($eSale, $eItem);

								}

							}

							if($eSale['shipping']) {
								$h .= $this->getDetailTableItem($eSale, $this->getItemShipping($eSale));
							}

							if($eSale['discount'] > 0) {

								$discountAmount = -1 * ($eSale['priceGross'] - $eSale['price']);

								$h .= '<tr class="pdf-document-item pdf-document-item-subtotal">';
									$h .= '<td class="pdf-document-product" colspan="4">';
										$h .= s("Total de la livraison avant remise");
									$h .= '</td>';
									$h .= '<td class="pdf-document-price">';
										$h .= \util\TextUi::money($eSale['priceGross']);
									$h .= '</td>';
									if($eInvoice['hasVat']) {
										$h .= '<td></td>';
									}
								$h .= '</tr>';
								$h .= '<tr class="pdf-document-item pdf-document-item-subtotal">';
									$h .= '<td class="pdf-document-product" colspan="4">';
										$h .= s("Remise <i>- {value} %</i>", $eSale['discount']);
									$h .= '</td>';
									$h .= '<td class="pdf-document-price">';
										$h .= \util\TextUi::money($discountAmount);
									$h .= '</td>';
									if($eInvoice['hasVat']) {
										$h .= '<td></td>';
									}
								$h .= '</tr>';

							}

							$h .= '<tr class="pdf-document-item pdf-document-item-subtotal">';
								$h .= '<td class="pdf-document-product" colspan="4">';
									$h .= s("Total de la livraison");
								$h .= '</td>';
								$h .= '<td class="pdf-document-price">';
									$h .= \util\TextUi::money($eSale['price']);
								$h .= '</td>';
								if($eInvoice['hasVat']) {
									$h .= '<td></td>';
								}
							$h .= '</tr>';

						}


						$h .= $this->getDocumentTotal(Pdf::INVOICE, $eFarm, $eInvoice, $eInvoice['hasVat'] ? 3 : 2, $eInvoice['hasVat'] ? 3 : 2, FALSE);

					$h .= '</tbody>';

				$h .= '</table>';

				$h .= $this->getDocumentBottom(Pdf::INVOICE, $eFarm, $eInvoice['paymentCondition'], $eInvoice['footer']);

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getReferences(Sale $eSale): string {

		$references = [];

		if(
			$eSale['ccPdf']->offsetExists(Pdf::ORDER_FORM) and
			$eSale['ccPdf'][Pdf::ORDER_FORM]->first()['crc32'] === $eSale['crc32']
		) {
			$references[] = s("Devis {value}", '<u>'.$eSale['ccPdf'][Pdf::ORDER_FORM]->first()['name'].'</u>');
		}

		if(
			$eSale['ccPdf']->offsetExists(Pdf::DELIVERY_NOTE) and
			$eSale['ccPdf'][Pdf::DELIVERY_NOTE]->first()['crc32'] === $eSale['crc32']
		) {
			$references[] = s("Bon de livraison {value}", '<u>'.$eSale['ccPdf'][Pdf::DELIVERY_NOTE]->first()['name'].'</u>');
		}

		if($references) {
			return p("Référence :", "Références :", count($references)).' '.implode(' / ', $references);
		} else {
			return '';
		}

	}

	protected function getItemShipping(Sale $eSale): Item {

		return new Item([
			'name' => SaleUi::getShippingName(),
			'description' => NULL,
			'mixedFrozen' => FALSE,
			'product' => new Product(),
			'quality' => NULL,
			'number' => NULL,
			'packaging' => NULL,
			'additional' => NULL,
			'unit' => new Unit(),
			'unitPrice' => NULL,
			'price' => $eSale['shipping'],
			'vatRate' => $eSale['shippingVatRate'],
			'composition' => new Sale(),
			'ingredientOf' => new Item()
		]);

	}

	protected function getDetailTableItem(Sale $eSale, Item $eItem): string {

		$h = '<tr class="pdf-document-item pdf-document-item-detail">';
			$h .= '<td>';
				$h .= \Asset::icon('chevron-right').' ';
				$h .= encode($eItem['name']);
				if($eItem['product']->notEmpty()) {
					if($eItem['product']['mixedFrozen']) {
						$h .= ' '.ProductUi::getFrozenIcon();
					}
					if($eItem['additional']) {
						$h .= '<small> / '.encode($eItem['additional']).'</small>';
					}
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
				$unit = \selling\UnitUi::getBy($eItem['unit'], short: TRUE);
				if($eItem['unitPrice'] !== NULL) {
					if($eItem['unitPriceInitial'] !== NULL) {
						$h .= new PriceUi()->priceWithoutDiscount($eItem['unitPriceInitial'], $unit);
					}
					$h .= \util\TextUi::money($eItem['unitPrice']);
				}
				$h .= $unit;
			$h .= '</td>';

			$h .= '<td class="pdf-document-price">';
				$h .= \util\TextUi::money($eItem['price']);
			$h .= '</td>';

			if($eSale['hasVat']) {
				$h .= '<td class="pdf-document-vat">';
					$h .= s('{value} %', $eItem['vatRate']);
				$h .= '</td>';
			}
		$h .= '</tr>';

		return $h;

	}

	protected function getDocumentTotal(string $type, \farm\Farm $eFarm, Sale|Invoice $e, int $colspan1, int $colspan2, bool $lastColumn): string {

		$h = '<tr class="pdf-document-item-total">';
			$h .= '<td class="pdf-document-item-quality">';

				if(
					$type === Pdf::INVOICE and
					$e['nature'] !== NULL
				) {

					$h .= '<div class="pdf-document-nature">';
						$h .= match($e['nature']) {
							Invoice::GOOD => s("Cette facture est constituée de livraison de biens."),
							Invoice::SERVICE => s("Cette facture est constituée de prestations de services."),
							Invoice::MIXED => s("Cette facture est constituée de livraison de biens et de prestations de services.")
						};
					$h .= '</div>';

				}

				if($e['organic'] and $e['conversion']) {

					$h .= '<div class="pdf-document-quality">';
						$h .= \Asset::image('main', 'organic.png', ['style' => 'width: 5rem; margin-right: 1rem']);
						$h .= '<div>'.s("Produits issus de l’agriculture biologique ou en conversion vers l'agriculture biologique certifiés par {value}", '<span style="white-space: nowrap">'.$eFarm->getConf('organicCertifier').'</span>').'</div>';
					$h .= '</div>';

				} else if($e['organic']) {

					$h .= '<div class="pdf-document-quality">';
						$h .= \Asset::image('main', 'organic.png', ['style' => 'width: 5rem; margin-right: 1rem']);
						$h .= '<div>'.s("Produits issus de l’agriculture biologique certifiés par {value}", '<span style="white-space: nowrap">'.$eFarm->getConf('organicCertifier').'</span>').'</div>';
					$h .= '</div>';

				} else if($e['conversion']) {

					$h .= '<div class="pdf-document-quality">';
						$h .= \Asset::image('main', 'organic.png', ['style' => 'width: 5rem; margin-right: 1rem']);
						$h .= '<div>'.s("Produits en conversion vers l’agriculture biologique certifiés par {value}", '<span style="white-space: nowrap">'.$eFarm->getConf('organicCertifier').'</span>').'</div>';
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
						$h .= '<div>'.s("Total {taxes}", ['taxes' => $e->getTaxes()]).'</div>';
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

					$taxes = $e->getTaxes();

					if(
						$e instanceof Sale and
						$e['discount'] > 0
					) {

						$discountAmount = -1 * ($e['priceGross'] - $e['price']);

						$h .= '<div class="pdf-document-total-label">';
							$h .= s("Total {taxes}<br/>avant remise", ['taxes' => $taxes]);
						$h .= '</div>';
						$h .= '<div class="pdf-document-total-value">';
							$h .= \util\TextUi::money($e['priceGross']);
						$h .= '</div>';
						$h .= '<div class="pdf-document-total-label">';
							$h .= s("Remise").' <i>- '.s("{value} %", $e['discount']).'</i>';
						$h .= '</div>';
						$h .= '<div class="pdf-document-total-value">';
							$h .= \util\TextUi::money($discountAmount);
						$h .= '</div>';

					}

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

	protected function getDocumentTop(string $type, Sale|Invoice $e, \farm\Farm $eFarm, ?string $number, string $dateDocument, ?string $top): string {

		$eCustomer = $e['customer'];
		$logo = new \media\FarmLogoUi()->getUrlByElement($eFarm, 'm');

		$h = '<div class="pdf-document-header">';

			$h .= '<div class="pdf-document-vendor '.($logo !== NULL ? 'pdf-document-vendor-with-logo' : '').'">';
				if($logo !== NULL) {
					$h .= '<div class="pdf-document-vendor-logo" style="background-image: url('.$logo.')"></div>';
				}
				$h .= '<div class="pdf-document-vendor-name">';
					$h .= encode($eFarm['legalName'] ?? '').'<br/>';
				$h .= '</div>';
				$h .= '<div class="pdf-document-vendor-address">';
					$h .= $eFarm->getLegalAddress('html');
				$h .= '</div>';
				if($eFarm['siret']) {
					$h .= '<div class="pdf-document-vendor-registration">';
						$h .= s("SIRET <u>{value}</u>", encode($eFarm['siret']));
					$h .= '</div>';
				}
				if($e['hasVat'] and $eFarm->getConf('vatNumber')) {
					$h .= '<div class="pdf-document-vendor-registration">';
						$h .= s("TVA intracommunautaire<br/><u>{value}</u>", encode($eFarm->getConf('vatNumber')));
					$h .= '</div>';
				}
			$h .= '</div>';

			$h .= '<div class="pdf-document-top">';

				$h .= '<div class="pdf-document-structure">';

					$h .= '<h2 class="pdf-document-title">'.self::getName($type, $e).'</h2>';
					$h .= '<div class="pdf-document-details">';
						if($number !== NULL) {
							$h .= '<div class="pdf-document-detail-label">'.s("Numéro").'</div>';
							$h .= '<div><b>'.$number.'</b></div>';
						}
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

					if($eCustomer['siret'] !== NULL or $eCustomer['vatNumber'] !== NULL) {
						$h .= '<br/>';
					}

					if($eCustomer['siret'] !== NULL) {
						$h .= '<div class="pdf-document-customer-registration">';
							$h .= s("SIRET <u>{value}</u>", encode($eCustomer['siret']));
						$h .= '</div>';
					}
					if($eCustomer['vatNumber'] !== NULL) {
						$h .= '<div class="pdf-document-customer-registration">';
							$h .= s("TVA intracommunautaire <u>{value}</u>", encode($eCustomer['vatNumber']));
						$h .= '</div>';
					}

				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';

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
			Pdf::ORDER_FORM => $eFarm->getConf('paymentMode'),
			Pdf::INVOICE => $eFarm->getConf('paymentMode'),
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
				$h .= '<td class="pdf-document-item-header pdf-document-packaging">'.($type === Pdf::DELIVERY_NOTE ? s("Colis<br/>livrés") : s("Colis")).'</td>';
			}
			$h .= '<td class="pdf-document-item-header pdf-document-number">'.($type === Pdf::DELIVERY_NOTE ? s("Quantité<br/>livrée") : s("Quantité")).'</td>';
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

		$details = ItemUi::getDetails($eItem);

		if($eItem['packaging']) {
			$details[] = s("Colis de {value}", \selling\UnitUi::getValue($eItem['packaging'], $eItem['unit'], TRUE));
		}

		$h = '<tr class="pdf-document-item '.$last.'">';
			$h .= '<td class=" pdf-document-product">';
				$h .= encode($eItem['name']);
				if($eItem['product']->notEmpty() and $eItem['product']['mixedFrozen']) {
					$h .= ' '.ProductUi::getFrozenIcon();
				}
				if($details) {
					$h .= '<div class="pdf-document-product-details">';
						$h .= implode(' | ', $details);
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
			$unit = \selling\UnitUi::getBy($eItem['unit'], short: TRUE);
			$h .= '<td class="pdf-document-number">';
				$h .= \selling\UnitUi::getValue($eItem['number'] * ($eItem['packaging'] ?? 1), $eItem['unit'], TRUE);
			$h .= '</td>';
			$h .= '<td class="pdf-document-unit-price">';
				if($eItem['unitPriceInitial'] !== NULL) {
					$h .= new PriceUi()->priceWithoutDiscount($eItem['unitPriceInitial'], unit: $unit);
				}
				$h .= \util\TextUi::money($eItem['unitPrice']);
				$h .= $unit;
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

	public function createOrderForm(Sale $eSale): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/sale:doGenerateDocument');

			$h .= $form->hidden('id', $eSale);
			$h .= $form->hidden('type', Pdf::ORDER_FORM);

			$h .= $form->group(
				s("Vente"),
				'<a href="/vente/'.$eSale['id'].'" class="btn btn-sm btn-outline-primary" target="_blank">'.$eSale->getNumber().'</a> '.CustomerUi::link($eSale['customer'], newTab: TRUE)
			);

			$h .= $form->dynamicGroups($eSale, ['orderFormValidUntil']);

			$h .= '<div id="sale-customize" class="hide">';
				$h .= $form->dynamicGroups($eSale, ['orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter']);
			$h .= '</div>';


			$submit = '<div class="flex-justify-space-between">';
				$submit .= $form->submit(s("Générer le devis"));
				$submit .= '<a onclick="Sale.customize(this)" class="btn btn-outline-primary">'.s("Personnaliser avant de générer").'</a>';
			$submit .= '</div>';

			$h .= $form->group(
				content: $submit
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-pdf-create-order-form',
			title: s("Générer un devis"),
			body: $h
		);

	}

	public function createDeliveryNote(Sale $eSale): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/sale:doGenerateDocument');

			$h .= $form->hidden('id', $eSale);
			$h .= $form->hidden('type', Pdf::DELIVERY_NOTE);

			$h .= $form->group(
				s("Vente"),
				'<a href="/vente/'.$eSale['id'].'" class="btn btn-sm btn-outline-primary" target="_blank">'.$eSale->getNumber().'</a> '.CustomerUi::link($eSale['customer'], newTab: TRUE)
			);

			$h .= $form->dynamicGroups($eSale, ['deliveryNoteDate']);

			$h .= '<div id="sale-customize" class="hide">';
				$h .= $form->dynamicGroups($eSale, ['deliveryNoteHeader', 'deliveryNoteFooter']);
			$h .= '</div>';


			$submit = '<div class="flex-justify-space-between">';
				$submit .= $form->submit(s("Générer le bon de livraison"));
				$submit .= '<a onclick="Sale.customize(this)" class="btn btn-outline-primary">'.s("Personnaliser avant de générer").'</a>';
			$submit .= '</div>';

			$h .= $form->group(
				content: $submit
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-pdf-create-delivery-note',
			title: s("Générer un bon de livraison"),
			body: $h
		);

	}

	public function getOrderFormMail(\farm\Farm $eFarm, Sale $eSale, string $type, ?string $template): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate($type, $eSale);
		$variables = \mail\CustomizeUi::getSaleVariables($type, $eFarm, $eSale);

		$title = s("Devis {value}", $eSale->getOrderForm($eFarm).' - '.$eSale['customer']->getLegalName());
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eFarm, $title, $content);

	}

	public function getDeliveryNoteMail(\farm\Farm $eFarm, Sale $eSale, string $type, ?string $template): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate($type, $eSale);
		$variables = \mail\CustomizeUi::getSaleVariables($type, $eFarm, $eSale);

		$title = s("Bon de livraison {value}", $eSale->getDeliveryNote($eFarm).' - '.$eSale['customer']->getLegalName());
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eFarm, $title, $content);

	}

	public function getInvoiceMail(\farm\Farm $eFarm, Invoice $eInvoice, \Collection $cSale, string $type, ?string $template): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate($type);
		$variables = \mail\CustomizeUi::getSaleVariables($type, $eFarm, $eInvoice, $cSale);

		$eCustomer = $eInvoice['customer'];

		$title = s("Facture {value}", $eInvoice['name'].' - '.($eCustomer->getLegalName()));
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eFarm, $title, $content);

	}

	public function getReminderMail(\farm\Farm $eFarm, Invoice $eInvoice, \Collection $cSale, string $type, ?string $template): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate($type);
		$variables = \mail\CustomizeUi::getSaleVariables($type, $eFarm, $eInvoice, $cSale);

		$eCustomer = $eInvoice['customer'];

		$title = s("Relance de paiement pour la facture {value}", $eInvoice['name'].' - '.($eCustomer->getLegalName()));
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eFarm, $title, $content);

	}

	public static function getName(string $type, Sale|Invoice $e, bool $short = FALSE): string {
		return [
			Pdf::DELIVERY_NOTE => $short ? SellingSetting::DELIVERY_NOTE : s("Bon de livraison"),
			Pdf::ORDER_FORM => $short ? SellingSetting::ORDER_FORM : s("Devis"),
			Pdf::INVOICE => $e->isCreditNote() ? ($short ? s("AV") : s("Avoir")) : ($short ? s("FA") : ($e['document'] === NULL ? s("Facture proforma") : s("Facture"))),
		][$type];
	}

	public function getSalesByDate(\shop\Date $eDate, \Collection $cSale, \Collection $cItem): string {

		$h = '<style>@page {	size: A4 landscape; margin: 0.5cm; }</style>';

		$h .= $this->getWatermark();

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

		$h .= $this->getWatermark();

		$h .= '<div class="pdf-sales-summary-wrapper">';

			$h .= '<h1>'.p("{value} vente", "{value} ventes", $cSale->count()).'</h1>';

			$h .= $this->getSalesSummary($cItem);

		$h .= '</div>';

		$h .= $this->getSalesContent($eFarm, $cSale);

		return $h;

	}

	protected function getSalesContent(\farm\Farm $eFarm, \Collection $cSale): string {

		$eConfiguration = $eFarm->conf();

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
						$h .= '<td>';
							if(
								$eItem['product']->notEmpty() and
								$eItem['product']['profile'] === Product::COMPOSITION
							) {
								$h .= \Asset::icon('puzzle-fill').'  ';
							}
							$h .= encode($eItem['name']);
						$h .= '</th>';
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
			$eItem['composition']->notEmpty() and
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

		if($eSale['ccItem']->empty()) {
			return [];
		}

		$eCustomer = $eSale['customer'];

		$itemsList = [];

		$cItemFormated = new \Collection();
		$nItem = 0;

		foreach($eSale['ccItem'][''] as $eItem) {

			$cItemFormated[] = $eItem;

			if(
				$eItem['composition']->notEmpty() and
				$eSale['ccItem']->offsetExists($eItem['id'])
			) {

				$cItemIngredient = $eSale['ccItem'][$eItem['id']];
				$cItemFormated->mergeCollection($cItemIngredient);
				$nItem += $cItemIngredient->count();

			} else {
				$nItem++;
			}

		}

		if($eSale['shipping']) {
			$cItemFormated[] = $this->getItemShipping($eSale);
		}

		foreach($cItemFormated as $eItem) {

			if($eItem['packaging'] !== NULL) {
				// Gérer les colis en nombre entier
				$quantity = $eItem['packaging'] * $eItem['number'];
			} else {
				$quantity = $eItem['number'];
			}

			$item = $eItem['composition']->notEmpty() ? \Asset::icon('puzzle-fill', ['class' => 'font-lg']) : \Asset::icon('circle', ['class' => 'font-lg']);
			$item .= '<div class="pdf-sales-label-content-item '.(mb_strlen($eItem['name']) > 50 ? 'pdf-sales-label-content-shrink-strong' : (mb_strlen($eItem['name']) > 40 ? 'pdf-sales-label-content-shrink' : '')).'">';
				$item .= '<div class="pdf-sales-label-content-value">';

					if($eItem['ingredientOf']->notEmpty()) {
						$item .= ' '.\Asset::icon('arrow-return-right').'  ';
					}

					if(mb_strlen($eItem['name']) >= 60) {
						$item .= encode(mb_substr($eItem['name'], 0, 55)).'...';
					} else {
						$item .= encode($eItem['name'] ?? '');
					}
					if($eItem['additional'] !== NULL) {
						$item .= '<span class="pdf-sales-label-content-size">'.encode($eItem['additional']).'</span>';
					}
				$item .= '</div>';
				if($quantity !== NULL) {
					$item .= '<div class="pdf-sales-label-content-border"></div>';
				}
			$item .= '</div>';
			$item .= '<div class="pdf-sales-label-content-quantity">';
				if($quantity !== NULL) {
					$item .= \selling\UnitUi::getValue($quantity, $eItem['unit'], short: TRUE);
				}
			$item .= '</div>';
			$item .= '<div class="pdf-sales-label-content-price">';
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

				$entry .= '<div class="pdf-sales-label-customer">';
					$entry .= '<div>';
						$entry .= '<div '.(mb_strlen($eCustomer->getName()) > 50 ? 'pdf-sales-label-customer-large' : '').'>'.encode($eCustomer->getName()).'</div>';
						if($showComment) {
							$entry .= '<div class="pdf-sales-label-comment">&laquo; '.encode($eSale['shopComment']).' &raquo;</div>';
						}
					$entry .= '</div>';
					$entry .= '<div>';
						$entry .= '<div class="pdf-sales-label-products"><span>'.s("Articles").'</span></div>';
						$entry .= '<div class="pdf-sales-label-count">'.$nItem.'</div>';
					$entry .= '</div>';

				$entry.= '</div>';

				$entry .= '<div class="pdf-sales-label-details '.($position > 0 ? 'pdf-sales-label-details-next' : '').'">';

					$entry .= '<div class="pdf-sales-label-detail">';
						$entry .= '<div class="pdf-sales-label-detail-title">'.s("Commande").'</div>';
						$entry .= '<div class="pdf-sales-label-detail-value">'.$eSale->getNumber().'</div>';
					$entry .= '</div>';

					if(count($farms) > 1) {

						$entry .= '<div class="pdf-sales-label-detail">';
							$entry .= '<div class="pdf-sales-label-detail-title">'.s("Producteur").'</div>';
							$entry .= '<div class="pdf-sales-label-detail-value">'.encode($eSale['farm']['name']).'</div>';
						$entry .= '</div>';

					}

					if(in_array($eSale['preparationStatus'], [Sale::DRAFT, Sale::CANCELED, Sale::BASKET])) {

						\Asset::css('selling', 'sale.css');

						$entry .= '<div class="pdf-sales-label-detail">';
							$entry .= '<div class="pdf-sales-label-detail-title">'.s("État").'</div>';
							$entry .= '<div class="pdf-sales-label-detail-value">';
								$entry .= '<span class="btn sale-preparation-status-'.$eSale['preparationStatus'].'-button">'.SaleUi::p('preparationStatus')->values[$eSale['preparationStatus']].'</span>';
							$entry .= '</div>';
						$entry .= '</div>';

					}

					$entry .= '<div class="pdf-sales-label-detail">';
						$entry .= '<div class="pdf-sales-label-detail-title">'.s("Date de retrait").'</div>';
						$entry .= '<div class="pdf-sales-label-detail-value">'.\util\DateUi::numeric($eSale['deliveredAt']).'</div>';
					$entry .= '</div>';
					$entry .= '<div class="pdf-sales-label-detail">';
						$entry .= '<div class="pdf-sales-label-detail-title">';
							$entry .= s("Total");
						$entry .= '</div>';
						$entry .= '<div class="pdf-sales-label-detail-value">';
							$entry .= \util\TextUi::money($eSale['priceIncludingVat']);
							if($eSale['discount']) {
								$entry .= '<br/><small style="font-weight: normal">'.s("(avec remise de {value} %)", $eSale['discount']).'</small>';
							}
						$entry .= '</div>';
					$entry .= '</div>';

					if($eSale['cPayment']->notEmpty()) {
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
									\shop\Point::HOME => '<div class="pdf-sales-label-address">'.$eSale->getDeliveryAddress('html', $eSale['farm']).'</div>',
									\shop\Point::PLACE => encode($eSale['shopPoint']['name'])
								};
							$entry .= '</div>';
						$entry .= '</div>';
					}

				$entry .= '</div>';

				$entry .= '<div class="pdf-sales-label-content pdf-sales-label-content-'.count($items).'">';

					if(count($itemsChunk) > 1) {
						$entry .= '<div class="pdf-sales-label-page"><span>'.s("Page {value}", ($position + 1).' / '.$pages).'</span></div>';
					}

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
				return ($e['name'] ?? 'Proforma').'-'.str_replace('-', '', $e['date']).'-'.$e['customer']->getName().'.pdf';

			default:
				return '';
		}
	}

}
?>
