<?php
namespace selling;

class SaleUi {

	public function __construct() {

		\Asset::css('selling', 'sale.css');
		\Asset::js('selling', 'sale.js');

	}

	public static function url(Sale $eSale): string {

		$eSale->expects(['id']);

		return '/vente/'.$eSale['id'];

	}

	public static function link(Sale $eSale, bool $newTab = FALSE): string {
		return '<a href="'.self::url($eSale).'" class="btn btn-sm btn-outline-primary" '.($newTab ? 'target="_blank"' : '').'>'.$eSale['document'].'</a>';
	}

	public static function urlMarket(Sale $eSale): string {

		$eSale->expects(['id']);

		return self::url($eSale).'/marche';

	}

	public static function getName(Sale $eSale): string {

		$eSale->expects(['id', 'priceExcludingVat', 'market']);

		if($eSale['market']) {
			return s("Marché #{value}", $eSale->getNumber());
		} else if($eSale['priceExcludingVat'] < 0) {
			return s("Avoir #{value}", $eSale->getNumber());
		} else {
			return s("Vente #{value}", $eSale->getNumber());
		}

	}

	public static function getShippingName(): string {
		return s("Frais de livraison");
	}

	public static function getTaxes(string $taxes): string {

		return match($taxes) {
			Sale::INCLUDING => s("TTC"),
			Sale::EXCLUDING => s("HT"),
		};

	}

	public static function getTotal(Sale|Invoice $eSale, bool $displayIncludingTaxes = TRUE): string {

		if($eSale['taxes'] === Sale::INCLUDING) {
			$taxes = $displayIncludingTaxes ? ' '.$eSale->getTaxes() : '';
		} else {
			$taxes = ' '.$eSale->getTaxes();
		}

		return match($eSale['taxes']) {
			Sale::EXCLUDING => $eSale['priceExcludingVat'] ? \util\TextUi::money($eSale['priceExcludingVat']).$taxes : '-',
			Sale::INCLUDING => $eSale['priceIncludingVat'] ? \util\TextUi::money($eSale['priceIncludingVat']).$taxes : '-'
		};

	}

	public function getPanelHeader(Sale $eSale): string {

		return '<div class="sale-panel-header">'.self::getName($eSale).'</div>';

	}

	public function getSearch(\Search $search): string {

		$h = '<div id="sale-search" class="util-block-search '.($search->empty(['ids']) ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$statuses = SaleUi::p('preparationStatus')->values;
			unset($statuses[Sale::BASKET], $statuses[Sale::SELLING]);

			$paymentMethods = SaleUi::p('paymentMethod')->values;
			$paymentMethods[Sale::ONLINE_CARD] = s("Carte bancaire avec Stripe");

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';
					$h .= $form->text('document', $search->get('document'), ['placeholder' => s("Numéro")]);
					$h .= $form->select('preparationStatus', $statuses, $search->get('preparationStatus'), ['placeholder' => s("État")]);
					$h .= $form->select('paymentMethod', $paymentMethods, $search->get('paymentMethod'), ['placeholder' => s("Moyen de paiement")]);
					$h .= $form->text('customerName', $search->get('customerName'), ['placeholder' => s("Client")]);
					$h .= $form->month('deliveredAt', $search->get('deliveredAt'), ['placeholder' => s("Mois")]);
				$h .= '</div>';
				$h .= '<div>';
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getNextSales(\farm\Farm $eFarm, ?string $type, array $nextSales): string {

		if($nextSales === []) {
			return '';
		}

		$h = '<ul class="util-summarize util-summarize-overflow">';

			foreach($nextSales as ['deliveredAt' => $date, 'turnover' => $turnover]) {

				$h .= '<li>';
					$h .= '<a href="/selling/item:getDeliveredAt?farm='.$eFarm['id'].'&date='.$date.''.($type ? '&type='.$type : '').'" style="'.($date < currentDate() ? 'opacity: 0.5' : '').'">';
						$h .= '<h5>'.\util\DateUi::numeric($date).'</h5>';
						$h .= '<div>';
							if($turnover > 0) {
								$h .= \util\TextUi::money($turnover, precision: 0);
							} else {
								$h .= '?';
							}
						$h .= '</div>';
					$h .= '</a>';
				$h .= '</li>';

			}

		$h .= '</ul>';

		return $h;

	}

	public function getList(\farm\Farm $eFarm, \Collection $cSale, ?int $nSale = NULL, ?\Search $search = NULL, array $hide = [], array $dynamicHide = [], array $show = [], ?int $page = NULL, ?\Closure $link = NULL): string {

		if($cSale->empty()) {

			$h = '<div class="util-info">';
				$h .= s("Il n'y a aucune vente à afficher.");
			$h .= '</div>';

			return $h;

		}

		$link ??= fn($eSale) => '/vente/'.$eSale['id'];

		$h = '<div class="util-overflow-md stick-md">';

		$columns = 5;

		$hasSubtitles = (
			$cSale->count() > 10 and
			($search !== NULL and str_starts_with($search->getSort(), 'preparationStatus'))
		);
		$previousSubtitle = NULL;

		$h .= '<table class="sale-item-table tr-bordered tr-even">';

			$h .= '<thead>';

				$h .= '<tr>';

					$h .= '<th class="td-min-content">';
						if($hasSubtitles === FALSE) {
							$h .= '<label title="'.s("Tout cocher / Tout décocher").'">';
								$h .= '<input type="checkbox" class="batch-all" onclick="Sale.toggleSelection(this)"/>';
							$h .= '</label>';
						}
					$h .= '</th>';

					$label = s("#");
					$h .= '<th class="text-center td-min-content">'.($search ? $search->linkSort('id', $label, SORT_DESC) : $label).'</th>';

					if(in_array('customer', $hide) === FALSE) {
						$label = s("Client");
						$h .= '<th>'.($search ? $search->linkSort('customer', $label) : $label).'</th>';
						$columns++;
					}
					if(in_array('preparationStatus', $hide) === FALSE) {
						$label = s("État");
						$h .= '<th>'.($search ? $search->linkSort('preparationStatus', $label) : $label).'</th>';
						$columns++;
					}
					if(in_array('deliveredAt', $hide) === FALSE) {
						$label = s("Vente");
						$h .= '<th>'.($search ? $search->linkSort('deliveredAt', $label, SORT_DESC) : $label).'</th>';
						$columns++;
					}
					if(in_array('createdAt', $show)) {
						$label = s("Créée à");
						$h .= '<th>'.($search ? $search->linkSort('createdAt', $label, SORT_DESC) : $label).'</th>';
						$columns++;
					}
					if(in_array('items', $hide) === FALSE) {
						$label = s("Articles");
						$h .= '<th class="text-center '.($dynamicHide['items'] ?? 'hide-sm-down').'">'.($search ? $search->linkSort('items', $label, SORT_DESC) : $label).'</th>';
						$columns++;
					}
					$label = s("Montant");
					$h .= '<th class="text-end">'.($search ? $search->linkSort('priceExcludingVat', $label, SORT_DESC) : $label).'</th>';
					if(in_array('documents', $hide) === FALSE) {
						$h .= '<th class="text-center"  colspan="3">'.s("Documents").'</th>';
						$columns++;
					}
					if(in_array('point', $show)) {
						$h .= '<th>'.s("Livraison").'</th>';
						$columns++;
					}
					if(in_array('paymentMethod', $hide) === FALSE) {
						$h .= '<th class="'.($dynamicHide['paymentMethod'] ?? 'hide-sm-down').'">'.s("Paiement").'</th>';
						$columns++;
					}
					if(in_array('actions', $hide) === FALSE) {
						$h .= '<th></th>';
					}
				$h .= '</tr>';

			$h .= '</thead>';
			$h .= '<tbody>';

				foreach($cSale as $eSale) {

					if($hasSubtitles) {

						$currentSubtitle = ($eSale['preparationStatus'] === Sale::DRAFT) ? Sale::DRAFT : $eSale['deliveredAt'];

						if($currentSubtitle !== $previousSubtitle) {

							if($previousSubtitle !== NULL) {
								$h .= '</tbody>';
								$h .= '<tbody>';
							}

							$h .= '<tr>';
								$h .= '<th class="td-min-content sale-item-select">';
									$h .= '<label title="'.s("Cocher ces ventes / Décocher ces ventes").'">';
										$h .= '<input type="checkbox" class="batch-all" onclick="Sale.toggleDaySelection(this)"/>';
									$h .= '</label>';
								$h .= '</th>';
								$h .= '<td colspan="'.$columns.'" class="sale-item-date">';
									$h .= match($currentSubtitle) {
										Sale::DRAFT => s("Brouillon"),
										currentDate() => s("Aujourd'hui"),
										default => \util\DateUi::textual($currentSubtitle)
									};
								$h .= '</td>';
							$h .= '</tr>';

							$previousSubtitle = $currentSubtitle;


						}

					}

					$batch = [];

					if($eSale->canStatusConfirmed() === FALSE) {
						$batch[] = 'not-confirmed';
					}

					if($eSale->canStatusCancel() === FALSE) {
						$batch[] = 'not-canceled';
					}

					if($eSale->canStatusDelivered() === FALSE) {
						$batch[] = 'not-delivered';
					}

					if($eSale->canDeleteSale() === FALSE) {
						$batch[] = 'not-delete';
					}

					$h .= '<tr class="';
						if($eSale['preparationStatus'] === Sale::CANCELED) {
							$h .= 'color-muted ';
						}
					$h .= '">';

						$h .= '<td class="td-min-content sale-item-select">';
							$h .= '<label>';
								$h .= '<input type="checkbox" name="batch[]" value="'.$eSale['id'].'" oninput="Sale.changeSelection()" data-batch="'.implode(' ', $batch).'"/>';
							$h .= '</label>';
						$h .= '</td>';

						$h .= '<td class="td-min-content text-center">';
							if($eSale['marketParent']->notEmpty()) {
								$h .= '<span class="btn btn-sm btn-disabled">'.$eSale->getNumber().'</span>';
							} else {
								$h .= '<a href="'.$link($eSale).'" class="btn btn-sm '.($eSale['deliveredAt'] === currentDate() ? 'btn-primary' : 'btn-outline-primary').'">'.$eSale->getNumber().'</a>';
							}
						$h .= '</td>';

						if(in_array('customer', $hide) === FALSE) {
							$h .= '<td class="sale-item-name">';
								$h .= CustomerUi::link($eSale['customer']);
								if($eSale['customer']->notEmpty()) {
									$h .= '<div class="util-annotation">';
										$h .= CustomerUi::getCategory($eSale['customer']);
									$h .= '</div>';
								}
							$h .= '</td>';
						}

						if(in_array('status', $hide) === FALSE) {

							$h .= '<td class="sale-item-status">';
								$h .= $this->getPreparationStatusForUpdate($eSale);
							$h .= '</td>';

						}

						if(in_array('deliveredAt', $hide) === FALSE) {

							$h .= '<td class="sale-item-delivery">';
								$h .= '<div>';
									if($eSale->canWriteDeliveredAt() === FALSE) {
										$h .= $eSale['deliveredAt'] ? \util\DateUi::numeric($eSale['deliveredAt']) : s("Non planifiée");
									} else {
										if($eSale['deliveredAt'] !== NULL) {
											$h .= $eSale->quick('deliveredAt', \util\DateUi::numeric($eSale['deliveredAt']));
										} else {
											$h .= $eSale->quick('deliveredAt', s("Non planifiée"));
										}
									}
								$h .= '</div>';
								$h .= '<div class="sale-item-delivery-source util-annotation">';
									if($eSale['shop']->notEmpty()) {
										$h .= '<a href="'.\shop\ShopUi::adminDateUrl($eSale['farm'], $eSale['shop'], $eSale['shopDate']).'">'.encode($eSale['shop']['name']).'</a>';
									} else if($eSale['marketParent']->notEmpty()) {
										$h .= '<a href="'.SaleUi::url($eSale['marketParent']).'">'.encode($eSale['marketParent']['customer']['name']).'</a>';;
									}
								$h .= '</div>';
							$h .= '</td>';

						}

						if(in_array('createdAt', $show)) {

							$h .= '<td class="sale-item-created-at">';
								$h .= \util\DateUi::numeric($eSale['createdAt'], \util\DateUi::TIME);
							$h .= '</td>';

						}

						if(in_array('items', $hide) === FALSE) {
							$h .= '<td class="text-center '.($dynamicHide['items'] ?? 'hide-sm-down').'">';
								$h .= $eSale['items'];
							$h .= '</td>';
						}

						$h .= '<td class="sale-item-price text-end">';
							$h .= SaleUi::getTotal($eSale);
						$h .= '</td>';

						if(in_array('documents', $hide) === FALSE) {

							if($eSale['preparationStatus'] === Sale::BASKET) {

								$h .= '<td class="sale-item-basket" colspan="3">';
									if($eSale['shopDate']->canOrder()) {
										$h .= s("Vente à l'état de panier et non confirmée par le client.");
									} else {
										$h .= s("Vente restée à l'état de panier et non confirmée par le client.");
									}
								$h .= '</td>';

							} else {
								$h .= $this->getDocuments($eSale, $eSale['cPdf'] ?? new \Collection(), 'list');
							}

						}

						if(in_array('point', $show)) {

							$h .= '<td class="sale-item-point">';

								if($eSale['shopPoint']->notEmpty()) {

									$eSale['shopPoint']->expects(['type', 'name']);

									$h .= [
										\shop\Point::HOME => s("Domicile"),
										\shop\Point::PLACE => s("Point de retrait")
									][$eSale['shopPoint']['type']];
									$h .= '<div class="util-annotation">';
										$h .= match($eSale['shopPoint']['type']) {
											\shop\Point::HOME => '<a href="'.$eSale->getDeliveryAddressLink().'" target="_blank" class="color-muted">'.nl2br(encode($eSale->getDeliveryAddress())).'</a>',
											\shop\Point::PLACE => encode($eSale['shopPoint']['name'])
										};
									$h .= '</div>';

								}
							$h .= '</td>';

						}

						if(in_array('paymentMethod', $hide) === FALSE) {

							$h .= '<td class="sale-item-payment-type '.($dynamicHide['paymentMethod'] ?? 'hide-sm-down').'">';

								if($eSale->isPaymentOnline()) {
									$h .= '<div>'.SaleUi::p('paymentMethod')->values[$eSale['paymentMethod']].'</div>';
									$h .= '<div>'.SaleUi::getPaymentStatus($eSale).'</div>';
								} else if($eSale['invoice']->notEmpty()) {
									if($eSale['invoice']->isCreditNote()) {
										$h .= '<div>'.s("Avoir").'</div>';
									} else {
										$h .= '<div>'.s("Facture").'</div>';
										$h .= '<div>'.InvoiceUi::getPaymentStatus($eSale['invoice']).'</div>';
									}
								} else if($eSale['paymentMethod'] === Sale::TRANSFER) {
									$h .= '<div>'.s("Virement bancaire").'</div>';
									$h .= '<div>'.SaleUi::getPaymentStatus($eSale).'</span></div>';
								} else if(in_array($eSale['paymentMethod'], [Sale::CASH, Sale::CHECK, Sale::CARD])) {
									$h .= self::p('paymentMethod')->values[$eSale['paymentMethod']];
								} else {
									$h .= '/';
								}

							$h .= '</td>';

						}

						if(in_array('actions', $hide) === FALSE) {

							$h .= '<td class="sale-item-actions">';
								$h .= $this->getUpdate($eSale, 'btn-outline-secondary');
							$h .= '</td>';

						}

					$h .= '</tr>';

				}

			$h .= '</tbody>';
		$h .= '</table>';

		$h .= '</div>';

		if($nSale !== NULL and $page !== NULL) {
			$h .= \util\TextUi::pagination($page, $nSale / 100);
		}


		$h .= $this->getBatch();

		return $h;

	}

	public function getBatch(): string {

		$form = new \util\FormUi();

		$h = '<div id="batch-several" class="util-bar hide">';

			$h .= $form->open('batch-several-form');

			$h .= '<div class="batch-ids hide"></div>';

			$h .= '<div class="batch-title">';
				$h .= '<h4>'.s("Pour la sélection").' (<span id="batch-menu-count"></span>)</h4>';
				$h .= '<a onclick="Sale.hideSelection()" class="btn btn-transparent">'.s("Annuler").'</a>';
			$h .= '</div>';

			$h .= '<div class="batch-menu">';
				$h .= '<div class="util-bar-menu">';

					$h .= '<a data-ajax-submit="/selling/sale:doUpdateConfirmedCollection" data-confirm="'.s("Marquer ces ventes comme confirmées ?").'" class="batch-menu-confirmed util-bar-menu-item">';
						$h .= '<span class="sale-preparation-status-label sale-preparation-status-batch sale-preparation-status-confirmed">'.self::p('preparationStatus')->shortValues[Sale::CONFIRMED].'</span>';
						$h .= '<span>'.s("Confirmé").'</span>';
					$h .= '</a>';

					$h .= '<a data-ajax-submit="/selling/sale:doUpdateDeliveredCollection" data-confirm="'.s("Marquer ces ventes comme livrées ?").'" class="batch-menu-delivered util-bar-menu-item">';
						$h .= '<span class="sale-preparation-status-label sale-preparation-status-batch sale-preparation-status-delivered">'.self::p('preparationStatus')->shortValues[Sale::DELIVERED].'</span>';
						$h .= '<span>'.s("Livré").'</span>';
					$h .= '</a>';

					$h .= '<a data-ajax-submit="/selling/sale:doUpdateCancelCollection" data-confirm="'.s("Annuler ces ventes ?").'" class="batch-menu-cancel util-bar-menu-item">';
						$h .= '<span class="sale-preparation-status-label sale-preparation-status-batch sale-preparation-status-draft">'.self::p('preparationStatus')->shortValues[Sale::CANCELED].'</span>';
						$h .= '<span>'.s("Annuler").'</span>';
					$h .= '</a>';

					$h .= '<a data-ajax-submit="/selling/sale:doExportCollection" data-ajax-navigation="never" class="util-bar-menu-item">';
						$h .= \Asset::icon('filetype-pdf');
						$h .= '<span>'.s("Exporter").'</span>';
					$h .= '</a>';

					$h .= '<a data-ajax-submit="/selling/sale:doDeleteCollection" data-confirm="'.s("Confirmer la suppression de ces ventes ?").'" class="batch-menu-delete util-bar-menu-item">';
						$h .= \Asset::icon('trash');
						$h .= '<span>'.s("Supprimer").'</span>';
					$h .= '</a>';

				$h .= '</div>';
			$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	protected function getDocuments(Sale $eSale, \Collection $cPdf, string $origin): string {

		if($eSale['items'] > 0) {

			$list = [];

			foreach([Pdf::ORDER_FORM, Pdf::DELIVERY_NOTE, Pdf::INVOICE] as $type) {

				if($eSale->canDocument($type) === FALSE) {
					$list[] = NULL;
					continue;
				}

				$ePdf = $cPdf[$type] ?? new Pdf();

				$emailedAt = match($type) {
					Pdf::DELIVERY_NOTE, Pdf::ORDER_FORM => $ePdf->empty() ? NULL : $ePdf['emailedAt'],
					Pdf::INVOICE => $eSale['invoice']->empty() ? NULL : $eSale['invoice']['emailedAt']
				};

				$canSend = match($type) {
					Pdf::DELIVERY_NOTE, Pdf::ORDER_FORM => $ePdf->empty() ? FALSE : $ePdf->canSend(),
					Pdf::INVOICE => $eSale['invoice']->empty() ? FALSE : $eSale['invoice']->acceptSend()
				};

				$label = PdfUi::getName($type, $eSale);
				$shortLabel = PdfUi::getName($type, $eSale, TRUE);
				$texts = PdfUi::getTexts($type);

				$canGenerate = $eSale->canGenerateDocument($type);
				$canRegenerate = $eSale->canRegenerateDocument($type);

				$urlGenerate = match($type) {
					Pdf::DELIVERY_NOTE => 'data-ajax="/selling/sale:doGenerateDocument" post-id="'.$eSale['id'].'" post-type="'.$type.'"',
					Pdf::ORDER_FORM => 'href="/selling/sale:generateOrderForm?id='.$eSale['id'].'"',
					Pdf::INVOICE => 'href="/selling/invoice:create?customer='.$eSale['customer']['id'].'&sales[]='.$eSale['id'].'&origin=sales"',
				};

				if($canRegenerate) {

					$urlRegenerate = match($type) {
						Pdf::DELIVERY_NOTE => 'data-ajax="/selling/sale:doGenerateDocument" post-id="'.$eSale['id'].'" post-type="'.$type.'"',
						Pdf::ORDER_FORM => 'href="/selling/sale:generateOrderForm?id='.$eSale['id'].'"',
						Pdf::INVOICE => 'href="/selling/invoice:regenerate?id='.$eSale['invoice']['id'].'"',
					};

				} else {
					$urlRegenerate = NULL;
				}

				if($ePdf->empty()) {

					if(
						$canGenerate and
						$eSale->canManage()
					) {

						$document = '<a '.$urlGenerate.' class="btn sale-document sale-document-new" title="'.$texts['generate'].'" '.attr('data-confirm', $texts['generateConfirm']).'>';
							$document .= '<div class="sale-document-name">'.$shortLabel.'</div>';
							$document .= '<div class="sale-document-status">';
								$document .= \Asset::icon('plus');
							$document .= '</div>';
						$document .= '</a> ';

					} else {
						$document = NULL;
					}

				} else {

					$dropdown = match($origin) {
						'list' => 'bottom-end',
						'element' => 'bottom-start',
					};

					$document = '<a class="btn sale-document" title="'.$label.'" data-dropdown="'.$dropdown.'">';
						$document .= '<div class="sale-document-name">'.$shortLabel.'</div>';
						$document .= '<div class="sale-document-status">';

							if($emailedAt) {
								$document .= \Asset::icon('check-all');
							} else {
								$document .= \Asset::icon('check');
							}

						$document .= '</div>';
						if(
							$type === Pdf::INVOICE and
							$ePdf['used'] > 1
						) {
							$document .= '<div class="sale-document-count">';
								$document .= $ePdf['used'];
							$document .= '</div>';

						}
					$document .= '</a> ';

					$document .= '<div class="dropdown-list bg-primary">';
						$document .= '<div class="dropdown-title">';
							$document .= $label.'<br/>';
							$document .= '<small>';
								$date = \util\DateUi::numeric($ePdf['createdAt'], \util\DateUi::DATE_HOUR_MINUTE);
								$document .= s("Généré le {value}", $date);
								if($ePdf['used'] > 1) {
									$document .= '<br/>'.s("Généré à partir de {value} ventes", $ePdf['used']);
								}
							$document .= '</small>';
						$document .= '</div>';

						if($ePdf['content']->notEmpty()) {

							if($type === Pdf::INVOICE) {
								$document .= '<a href="'.InvoiceUi::url($eSale['invoice']).'" data-ajax-navigation="never" class="dropdown-item">'.s("Télécharger").'</a>';
							} else {
								$document .= '<a href="'.PdfUi::url($ePdf).'" data-ajax-navigation="never" class="dropdown-item">'.s("Télécharger").'</a>';
							}

						} else {
							$document .= '<span class="dropdown-item">';
								$document .= '<span class="sale-document-forbidden">'.s("Télécharger").'</span>';
								$document .= ' <span class="sale-document-expired">'.s("Document expiré").'</span>';
							$document .= '</span>';
						}

						if($eSale->canManage()) {

							if($texts['generateNew'] !== NULL) {

								if($canRegenerate) {
									$class = '';
								} else {
									$class = 'sale-document-forbidden';
								}

								$document .= '<a '.$urlRegenerate.' class="dropdown-item '.$class.'" '.attr('data-confirm', $texts['generateNewConfirm']).'>'.$texts['generateNew'].'</a>';

							}

							if($emailedAt) {
								$document .= '<div class="dropdown-divider"></div>';
								$document .= ' <div class="dropdown-item">'.\Asset::icon('check-all').'&nbsp;&nbsp;'.s("Envoyé par e-mail le {value}", \util\DateUi::numeric($emailedAt, \util\DateUi::DATE)).'</div>';
							} else {

								$document .= '<div class="dropdown-divider"></div>';

								if($canSend) {
									$text = s("Envoyer au client par e-mail").'</a>';
								} else {
									$text = '<span class="sale-document-forbidden">'.s("Envoyer au client par e-mail").'</span>';
								}

								$urlSend = match($type) {
									Pdf::DELIVERY_NOTE, Pdf::ORDER_FORM => 'data-ajax="/selling/sale:doSendDocument" post-id="'.$eSale['id'].'" post-type="'.$type.'"',
									Pdf::INVOICE => 'data-ajax="/selling/invoice:doSend" post-id="'.$eSale['invoice']['id'].'"'
								};

								$document .= '<a '.$urlSend.' data-confirm="'.$texts['sendConfirm'].'" class="dropdown-item">'.$text.'</a>';

							}

							$document .= '<div class="dropdown-divider"></div>';

							$urlDelete = match($type) {
								Pdf::DELIVERY_NOTE, Pdf::ORDER_FORM => 'data-ajax="/selling/sale:doDeleteDocument" post-id="'.$ePdf['id'].'"',
								Pdf::INVOICE => 'data-ajax="/selling/invoice:doDelete" post-id="'.$eSale['invoice']['id'].'"',
							};

							$document .= ' <a '.$urlDelete.' data-confirm="'.$texts['deleteConfirm'].'" class="dropdown-item">'.s("Supprimer le document").'</a>';

							if($ePdf['expiresAt'] !== NULL) {
								$document .= '<span class="dropdown-item sale-document-expires">'.s("Le fichier PDF de ce document<br/>expirera automatiquement le {value}.", \util\DateUi::numeric($ePdf['expiresAt'], \util\DateUi::DATE)).'</span>';
							}

						}

					$document .= '</div>';

				}

				$list[] = $document;

			}

		} else {
			$list = [NULL, NULL, NULL];
		}

		if($origin === 'list') {

			$h = '';

			foreach($list as $position => $document) {
				$h .= '<td class="text-center td-min-content sale-document-cell-'.$position.'">'.($document ?? '').'</td>';
			}

		} else {

			if(array_filter($list)) {
				$h = '<div class="sale-documents sale-documents-'.$origin.'">';
					$h .= implode('', $list);
				$h .= '</div>';
			} else {
				$h = '';
			}

		}

		return $h;

	}

	public function getPreparationStatusForUpdate(Sale $eSale, bool $shortText = TRUE): string {

		$text = $shortText ? self::p('preparationStatus')->shortValues[$eSale['preparationStatus']] : self::p('preparationStatus')->values[$eSale['preparationStatus']];

		$h = '<span class="sale-preparation-status-label sale-preparation-status-'.$eSale['preparationStatus'].'" title="'.self::p('preparationStatus')->values[$eSale['preparationStatus']].'">'.$text.'</span>';

		if(
			$eSale->canWrite() === FALSE or
			$eSale->canWritePreparationStatus() === FALSE or
			$eSale['marketParent']->notEmpty()
		) {
			return $h;
		}

		$buttonsStyle = [
			Sale::CANCELED => 'btn-outline-muted',
			Sale::CONFIRMED => 'btn-outline-order',
			Sale::PREPARED => 'btn-outline-done',
			Sale::SELLING => 'btn-outline-selling',
			Sale::DELIVERED => 'btn-outline-success',
		];

		$button = fn(string $preparationStatus, ?string $confirm = NULL) => ' <a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.$preparationStatus.'" class="sale-preparation-status-action '.$buttonsStyle[$preparationStatus].'" title="'.self::p('preparationStatus')->values[$preparationStatus].'" '.($confirm ? attr('data-confirm', $confirm) : '').'>'.($shortText ? self::p('preparationStatus')->shortValues[$preparationStatus] : self::p('preparationStatus')->values[$preparationStatus]).'</a>';

		$wrapper = function($content) {
			return ' '.\Asset::icon('caret-right-fill').$content;
		};

		if($eSale['market']) {

			switch($eSale['preparationStatus']) {

				case Sale::DRAFT :
					$h .= $wrapper(
						$button(Sale::CONFIRMED)
					);
					break;

				case Sale::CONFIRMED :
					$h .= $wrapper(
						$button(Sale::SELLING, s("Vous allez commencer votre marché ! Les quantités des produits que vous avez saisis pour préparer ce marché seront remises à zéro et vous pourrez commencer à enregistrer les commandes des clients. C'est parti ?"))
					);
					break;

				case Sale::SELLING :
					$h .= $wrapper(
						' <a href="'.SaleUi::urlMarket($eSale).'" class="sale-preparation-status-action btn-outline-selling">'.\Asset::icon('shop-window').'  '.s("Console de vente").'</a>'
					);
					break;

			};

		} else {

			switch($eSale['preparationStatus']) {

				case Sale::BASKET :
					if($eSale['shopDate']->canOrder() === FALSE) {
						$h .= $wrapper(
							$button(Sale::CONFIRMED).
							$button(Sale::CANCELED)
						);
					}

					break;

				case Sale::DRAFT :
					$h .= $wrapper(
						$button(Sale::CONFIRMED)
					);
					break;

				case Sale::CONFIRMED :
					$h .= $wrapper(
						$button(Sale::PREPARED).
						$button(Sale::DELIVERED)
					);
					break;

				case Sale::PREPARED :
					$h .= $wrapper(
						$button(Sale::DELIVERED)
					);
					break;

			};

		}

		return $h;

	}

	public static function getPreparationStatusForCustomer(Sale $eSale): string {

		switch($eSale['preparationStatus']) {

			case Sale::DELIVERED :
			case Sale::CANCELED :
				return '<span class="sale-preparation-status-'.$eSale['preparationStatus'].'">'.self::p('preparationStatus')->values[$eSale['preparationStatus']].'</span>';

			case Sale::BASKET :
				return '<span class="color-danger">'.s("Non confirmée").'</span>';

			default :
				return '<span class="sale-preparation-status-'.Sale::CONFIRMED.'">'.s("Confirmé").'</span>';

		};

	}

	public static function getPaymentStatus(Sale $eSale): string {

		if($eSale['paymentMethod'] === Sale::TRANSFER and $eSale['invoice']->empty()) {
			return '<span class="util-badge sale-payment-status sale-payment-status-not-paid">'.s("Facture à éditer").'</span>';
		}

		return '<span class="util-badge sale-payment-status sale-payment-status-'.$eSale['paymentStatus'].'">'.self::p('paymentStatus')->values[$eSale['paymentStatus']].'</span>';

	}

	public static function getPaymentStatusForCustomer(Sale $eSale, bool $withColors = FALSE): string {

		switch($eSale['paymentStatus']) {

			case Sale::UNDEFINED :
				$color = '--text';
				$text = s("Non payé");
				break;

			case Sale::WAITING :
				$color = '--warning';
				$text = s("En cours de validation");
				break;

			case Sale::PROCESSING :
				$color = '--warning';
				$text = s("En cours de paiement");
				break;

			case Sale::PAID :
				$color = '--success';
				$text = \Asset::icon('check').' '.s("Payé");
				break;

			case Sale::FAILED :
				$color = '--danger';
				$text = \Asset::icon('exclamation-triangle-fill').' '.s("Échec");
				break;

		};

		if($withColors) {
			return '<span style="color: var('.$color.')">'.$text.'</span>';
		} else {
			return $text;
		}

	}

	public function getLabels(\farm\Farm $eFarm, \Collection $cSale): string {

		$h = '<h4>'.s("Générer les étiquettes des ventes aux professionnels en cours").'</h4>';

		if($cSale->empty()) {
			$h .= '<div class="util-info">'.s("Il n'y a aucune vente en cours de préparation ou déjà préparée.").'</div>';
		} else {

			$form = new \util\FormUi();

			$h .= $form->open(NULL, ['action' => '/selling/sale:downloadLabels']);

				$h .= $form->hidden('id', $eFarm['id']);
				$h .= $form->hidden('checkSales', TRUE);

				$h .= '<div class="selling-label">';

				foreach($cSale as $eSale) {

					$h .= '<label class="selling-label-item util-block">';
						$h .= '<div class="selling-label-title">';
							$h .= SaleUi::link($eSale);
							$h .= '<h4>'.CustomerUi::getColorCircle($eSale['customer']).' '.CustomerUi::link($eSale['customer']).'</h4>';
							$h .= $form->inputCheckbox('sales[]', $eSale['id']);
						$h .= '</div>';
						$h .= '<ul class="util-summarize">';
							$h .= '<li>';
								$h .= '<h5>'.s("Articles").'</h5>';
								$h .= $eSale['items'];
							$h .= '</li>';
							$h .= '<li>';
								$h .= '<h5>'.s("Montant").'</h5>';
								$h .= \util\TextUi::money($eSale['priceExcludingVat']).' '.$eSale->getTaxes();
							$h .= '</li>';
						$h .= '</ul>';
					$h .= '</label>';

				}

			$h .= '</div>';

			$h .= $form->submit(s("Générer les étiquettes"));

			$h .= $form->close();

			$h .= '<br/>';

		}

		$h .= '<h4>'.s("Modèle des étiquettes").'</h4>';

		$h .= '<div class="selling-label-example">';
			$h .= (new PdfUi())->getLabel($eFarm, new Customer(), quality: $eFarm['quality']);
		$h .= '</div>';

		$form = new \util\FormUi();

		$h .= $form->open(NULL, ['action' => '/selling/sale:downloadLabels']);

			$h .= $form->hidden('id', $eFarm['id']);
			$h .= $form->hidden('checkSales', FALSE);

			$h .= $form->submit(s("Générer des étiquettes vides"));

		$h .= $form->close();

		return $h;

	}

	public function getHeader(Sale $eSale): string {

		$h = '<div class="util-action">';

			$h .= '<h1>'.SaleUi::getName($eSale).'</h1>';

			$h .= '<div>';
				$h .= $this->getUpdate($eSale, 'btn-primary');
			$h .= '</div>';

		$h .= '</div>';

		$h .= '<div class="util-action-subtitle">';
			$h .= $this->getPreparationStatusForUpdate($eSale, shortText: FALSE);
		$h .= '</div>';

		return $h;

	}

	public function getRelativeSales(Sale $e, ?array $relativeSales): string {

		if($relativeSales === NULL) {
			return '';
		}

		[
			'count' => $count,
			'position' => $position,
			'before' => $eSaleBefore,
			'after' => $eSaleAfter
		] = $relativeSales;

		$h = '<div class="sale-relative-wrapper stick-xs">';

			if($eSaleBefore->notEmpty()) {
				$h .= '<a href="'.SaleUi::url($eSaleBefore).'" class="sale-relative-before">';
					$h .= '<div class="sale-relative-customer">'.encode($eSaleBefore['customer']['name']).'</div>';
					$h .= '<div class="sale-relative-arrow">';
						$h .= \Asset::icon('chevron-left');
					$h .= '</div>';
				$h .= '</a>';
			} else {
				$h .= '<div class="sale-relative-before"></div>';
			}

			$h .= '<div class="sale-relative-title">';
				$h .= '<h4>'.encode($e['shop']['name']).'</h4>';
				$h .= '<a href="'.\shop\ShopUi::adminDateUrl($e['farm'], $e['shop'], $e['shopDate']).'" class="sale-relative-date">'.s("Vente du {value}", \util\DateUi::numeric($e['shopDate']['deliveryDate'])).'</a>';
				$h .= '<div class="sale-relative-current">';
					$h .= s("Commande {position} / {count}", ['position' => $position, 'count' => $count]);
				$h .= '</div>';
			$h .= '</div>';

			if($eSaleAfter->notEmpty()) {
				$h .= '<a href="'.SaleUi::url($eSaleAfter).'" class="sale-relative-after">';
					$h .= '<div class="sale-relative-arrow">';
						$h .= \Asset::icon('chevron-right');
					$h .= '</div>';
					$h .= '<div class="sale-relative-customer">'.encode($eSaleAfter['customer']['name']).'</div>';
				$h .= '</a>';
			} else {
				$h .= '<div class="sale-relative-after"></div>';
			}

		$h .= '</div>';

		return $h;

	}

	public function getContent(Sale $eSale, \Collection $cPdf): string {

		$h = '<div class="util-block stick-xs">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Client").'</dt>';
				$h .= '<dd>'.CustomerUi::link($eSale['customer']).'</dd>';
				if($eSale['market'] === FALSE) {
					$h .= '<dt>'.s("Moyen de paiement").'</dt>';
					$h .= '<dd>';
						if(in_array($eSale['paymentMethod'], [Sale::TRANSFER, Sale::ONLINE_CARD])) {
							$h .= \selling\SaleUi::p('paymentMethod')->values[$eSale['paymentMethod']];
							$h .= ' '.\selling\SaleUi::getPaymentStatus($eSale);
						} else if(in_array($eSale['paymentMethod'], [Sale::TRANSFER, Sale::ONLINE_CARD])) {
							$h .= \selling\SaleUi::p('paymentMethod')->values[$eSale['paymentMethod']];
							$h .= ' '.\selling\SaleUi::getPaymentStatus($eSale);
						}
					$h .= '</dd>';
				}

				if($eSale['from'] === Sale::SHOP) {

					$h .= '<dt>'.s("Origine").'</dt>';
					$h .= '<dd>';
						if($eSale['shop']->notEmpty()) {
							$h .= \shop\ShopUi::link($eSale['shop']);
						}
					$h .= '</dd>';

					$h .= '<dt>'.s("Livraison").'</dt>';
					$h .= '<dd>';
						if($eSale['shopPoint']->notEmpty()) {
							$h .= \shop\PointUi::p('type')->values[$eSale['shopPoint']['type']];
							$h .= '<div class="sale-display-address">';
								$h .= match($eSale['shopPoint']['type']) {
									\shop\Point::HOME => '<a href="'.$eSale->getDeliveryAddressLink().'" target="_blank" class="color-muted">'.nl2br(encode($eSale->getDeliveryAddress())).'</a>',
									\shop\Point::PLACE => encode($eSale['shopPoint']['name'])
								};
							$h .= '</div>';
						}
					$h .= '</dd>';

				}

				$h .= '<dt>'.s("Date de vente").'</dt>';
				$h .= '<dd>';

				$update = fn($content) => $eSale->canWriteDeliveredAt() ? $eSale->quick('deliveredAt', $content) : $content;

				$h .= $eSale['preparationStatus'] === Sale::DELIVERED ?
					$update(\util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE)) :
					$update($eSale['deliveredAt'] ? s("Planifié le {value}", \util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE)) : s("Non planifié"));
				$h .= '</dd>';

				if($eSale->hasDiscount()) {
					$h .= '<dt>'.s("Remise commerciale").'</dt>';
					$h .= '<dd>'.($eSale['discount'] > 0 ? s("{value} %", $eSale['discount']) : '').'</dd>';
				}

				if($eSale->canAnyDocument()) {

					$h .= '<dt>'.s("Documents").'</dt>';
					$h .= '<dd>';
						$h .= $this->getDocuments($eSale, $cPdf, 'element');
					$h .= '</dd>';

				}

				if($eSale->hasShipping()) {

					if($eSale['hasVat'] and $eSale['items'] > 0) {
						$vatRate = 'title="'.s("Taux de TVA appliqué de {value} %", $eSale['shippingVatRate']).'"';
					} else {
						$vatRate = '';
					}

					$h .= '<dt>'.SaleUi::getShippingName().'</dt>';
					$h .= '<dd '.$vatRate.'>';

						if($eSale['shipping'] !== NULL) {

							$shipping = \util\TextUi::money($eSale['shipping']).' '.$eSale->getTaxes();

							if($eSale->isClosed() === FALSE) {
								$h .= $eSale->quick('shipping', $shipping);
							} else {
								$h .= $shipping;
							}

						}

					$h .= '</dd>';

				}

			$h .= '</dl>';
		$h .= '</div>';

		if(
			$eSale->isMarket() and
			$eSale->isMarketPreparing() === FALSE and
			$eSale->canWrite()
		) {
			$h .= '<div class="mb-1">';
				$h .= '<a href="'.SaleUi::urlMarket($eSale).'" class="btn btn-xl btn-selling" style="width: 100%">'.\Asset::icon('shop-window').'  '.s("Ouvrir la console de vente").'</a>';
			$h .= '</div>';
		}

		if(
			($eSale['market'] === TRUE and $eSale->isMarketPreparing() === FALSE) or
			($eSale['market'] === FALSE and $eSale['items'] > 0)
		) {
			$h .= self::getSummary($eSale);
		}


		return $h;

	}

	public static function getSummary(Sale $eSale, bool $onlyIncludingVat = FALSE): string {

			$h = '<ul class="util-summarize">';
				$h .= '<li>';
					$h .= '<h5>'.s("Articles").'</h5>';
					$h .= $eSale['items'];
				$h .= '</li>';
				if($eSale['market']) {
					$h .= '<li>';
						$h .= '<h5>'.s("Ventes").'</h5>';
						$h .= $eSale['marketSales'];
					$h .= '</li>';
				}

				if($eSale['hasVat']) {

					if($onlyIncludingVat === FALSE) {
						$h .= '<li>';
							$h .= '<h5>'.s("Montant HT").'</h5>';
							$h .= \util\TextUi::money($eSale['priceExcludingVat'] ?? 0);
						$h .= '</li>';
						$h .= '<li>';
							$h .= '<h5>'.s("TVA").'</h5>';
							$h .= \util\TextUi::money($eSale['vat'] ?? 0);
						$h .= '</li>';
					}

					$h .= '<li>';
						$h .= '<h5>'.s("Montant TTC").'</h5>';
						$h .= \util\TextUi::money($eSale['priceIncludingVat'] ?? 0);
					$h .= '</li>';

				} else {
					$h .= '<li>';
						$h .= '<h5>'.s("Montant").'</h5>';
						$h .= \util\TextUi::money($eSale['priceIncludingVat'] ?? 0);
					$h .= '</li>';
				}

			$h .= '</ul>';

			return $h;

	}

	protected function getUpdate(Sale $eSale, string $btn): string {

		$primaryList = '';

		if(
			$eSale->isMarket() and
			$eSale->canWrite()
		) {

			if($eSale->isMarketPreparing() === FALSE) {
				$primaryList .= ' <a href="'.SaleUi::urlMarket($eSale).'" class="dropdown-item">'.s("Ouvrir la console de vente").'</a>';
			}

			if($eSale->isMarketClosed()) {
				$primaryList = '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::SELLING.'" class="dropdown-item">'.s("Réouvrir le marché").'</a>';
			}

		}

		if($eSale->canUpdate()) {
			$primaryList .= '<a href="/selling/sale:update?id='.$eSale['id'].'" class="dropdown-item">'.s("Modifier la vente").'</a>';
		}

		if($eSale->canAssociateShop()) {
			$primaryList .= '<a href="/selling/sale:updateShop?id='.$eSale['id'].'" class="dropdown-item">'.s("Associer la vente à une boutique").'</a>';
		}

		if($eSale->canDissociateShop()) {
			$primaryList .= '<a data-ajax="/selling/sale:doUpdateShop" post-id="'.$eSale['id'].'" post-from="'.Sale::USER.'" class="dropdown-item">'.s("Dissocier la vente de la boutique").'</a>';
		}

		if(
			$eSale->canWritePreparationStatus() and
			$eSale['marketParent']->empty()
		) {

			$draft = '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::DRAFT.'" class="dropdown-item">'.s("Repasser en brouillon").'</a>';

			$statusList = match($eSale['preparationStatus']) {

				Sale::CONFIRMED => $draft,
				Sale::PREPARED, Sale::SELLING => '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::CONFIRMED.'" class="dropdown-item">'.s("Remettre à préparer").'</a>'.$draft,
				Sale::DELIVERED => $eSale->canCancelDelivered() ? '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::CONFIRMED.'" class="dropdown-item">'.s("Annuler la livraison").'</a>' : '',
				Sale::CANCELED => '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::CONFIRMED.'" class="dropdown-item">'.s("Revalider la vente").'</a>',
				default => ''
			};

			if($eSale->canStatusCancel()) {
				$statusList .= '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::CANCELED.'" class="dropdown-item">'.s("Annuler la vente").'</a>';
			}

		} else {
			$statusList = '';
		}

		$secondaryList = '';

		if($eSale->canDeleteSale()) {
			$secondaryList .= '<a data-ajax="/selling/sale:doDelete" post-id="'.$eSale['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression de la vente ?").'">'.s("Supprimer la vente").'</a>';
		}

		if($eSale->canDuplicate()) {
			$primaryList .= '<a href="/selling/sale:duplicate?id='.$eSale['id'].'" class="dropdown-item">'.s("Copier la vente").'</a>';
		}


		if($primaryList === '' and $secondaryList === '') {
			return '';
		}

		$h = '<a data-dropdown="bottom-end" class="dropdown-toggle btn '.$btn.'">'.\Asset::icon('gear-fill').'</a>';
		$h .= '<div class="dropdown-list">';
			$h .= '<div class="dropdown-title">'.self::getName($eSale).'</div>';

			$h .= $primaryList;

			if($statusList) {

				$h .= '<div class="dropdown-divider"></div>';
				$h .= $statusList;

			}

			if($secondaryList) {

				$h .= '<div class="dropdown-divider"></div>';
				$h .= $secondaryList;

			}

		$h .= '</div>';

		return $h;

	}

	public function getMarket(\farm\Farm $eFarm, \Collection $ccSale) {

		if($ccSale->empty()) {
			return '';
		}

		$h = '';

		foreach($ccSale as $cSale) {

			if($cSale->empty()) {
				continue;
			}

			$h .= '<h3>'.match($cSale->first()['preparationStatus']) {
				\selling\Sale::DELIVERED => s("Ventes terminées"),
				\selling\Sale::DRAFT => s("Ventes en cours"),
				\selling\Sale::CANCELED => s("Ventes annulés")
			}.'</h3>';

			$h .= $this->getList($eFarm, $cSale, hide: ['deliveredAt', 'actions', 'documents'], show: ['createdAt']);

		}

		$h .= '<br/>';

		return $h;

	}

	public function getHistory(\Collection $cHistory) {

		if($cHistory->empty()) {
			return '';
		}

		$h = '<h3>'.s("Historique").'</h3>';

		$h .= '<div class="util-overflow-md">';

			$h .= '<table class="tr-bordered">';

				$h .= '<tr>';
					$h .= '<th>'.s("Date").'</th>';
					$h .= '<th>'.s("Événement").'</th>';
					$h .= '<th>'.s("Par").'</th>';
				$h .= '</tr>';

				foreach($cHistory as $eHistory) {

					$h .= '<tr>';

						$h .= '<td>';
							$h .= \util\DateUi::numeric($eHistory['date']);
						$h .= '</td>';

						$h .= '<td>';
							$h .= $eHistory['event']['name'];
							if($eHistory['comment']) {
								$h .= '<div class="util-annotation color-muted">';
									$h .= encode($eHistory['comment']);
								$h .= '</div>';
							}
						$h .= '</td>';

						$h .= '<td>';
							$h .= $eHistory['user']->empty() ? '-' : \user\UserUi::name($eHistory['user']);
						$h .= '</td>';

					$h .= '</tr>';

				}

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function create(Sale $eSale): \Panel {

		$eSale->expects(['farm', 'cShop', 'market']);

		$form = new \util\FormUi();

		$eSale['from'] = Sale::USER;

		$h = '';

		$h .= $form->openAjax('/selling/sale:doCreate', ['id' => 'sale-create']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eSale['farm']['id']);
			$h .= $form->hidden('market', $eSale['market']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eSale['farm'], TRUE)
			);

			$h .= $form->dynamicGroup($eSale, 'customer*', function($d) {
					$d->autocompleteDispatch = '#sale-create';
				});

			if($eSale['customer']->notEmpty()) {

				if($eSale['customer']['destination'] === Customer::COLLECTIVE) {
					$h .= $form->dynamicGroup($eSale, 'market');
				}

				$h .= $form->dynamicGroups($eSale, ['deliveredAt', 'comment']);

				$h .= $form->group(
					content: $form->submit(s("Créer la vente"))
				);

			}

		$h .= $form->close();

		return new \Panel(
			id: 'panel-sale-create',
			title: s("Ajouter une vente"),
			body: $h
		);

	}

	public function duplicate(Sale $eSale): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/sale:doDuplicate');

			$h .= $form->hidden('id', $eSale['id']);

			$h .= $form->group(
				s("Vente d'origine"),
				SaleUi::link($eSale, newTab: TRUE)
			);

			$h .= $form->group(
				s("Client"),
				CustomerUi::link($eSale['customer'])
			);

			$h .= $form->group(
				s("Date de la nouvelle vente"),
				$form->dynamicField($eSale, 'deliveredAt')
			);

			$h .= $form->group(
				content: '<div class="util-info">'.s("La vente sera dupliquée avec l'ensemble des articles de la vente initiale, et placée en état <i>Brouillon</i>.").'</div>'
			);

			$h .= $form->group(
				content: $form->submit(s("Dupliquer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-sale-duplicate',
			title: s("Copier une vente"),
			body: $h,
		);

	}

	public function update(Sale $eSale): \Panel {

		$eSale->expects(['cShop']);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/sale:doUpdate');

			$h .= $form->hidden('id', $eSale['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eSale['farm'], TRUE)
			);

			if($eSale->isClosed() === FALSE) {

				$h .= $form->dynamicGroup($eSale, 'deliveredAt');

				if($eSale->hasDiscount()) {
					$h .= $form->dynamicGroup($eSale, 'discount');
				}

				if($eSale->hasShipping()) {

					$h .= $form->dynamicGroup($eSale, 'shipping');

					if($eSale['hasVat']) {
						$h .= $form->dynamicGroup($eSale, 'shippingVatRate');
					}

				}
			}

			$h .= $form->dynamicGroup($eSale, 'comment');

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier une vente"),
			body: $h
		);

	}

	public function updateShop(Sale $eSale): \Panel {

		$eSale->expects(['cShop']);

		$eSale['from'] = Sale::SHOP;

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/sale:doUpdateShop');

			$h .= $form->hidden('id', $eSale['id']);
			$h .= $form->hidden('from', Sale::SHOP);

			$h .= $form->group(
				s("Vente"),
				SaleUi::link($eSale, newTab: TRUE)
			);

			$h .= $form->dynamicGroup($eSale, 'shopDate');

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Associer une vente à une boutique"),
			body: $h
		);

	}

	public function updateCustomer(Sale $eSale): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/sale:doUpdateCustomer');

			$h .= $form->hidden('id', $eSale['id']);

			$h .= $form->dynamicGroup($eSale, 'customer');

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Transférer la vente à un autre client"),
			body: $h
		);

	}

	public static function getVat(\farm\Farm $eFarm, bool $short = FALSE): array {

		/* La ferme permettra ultérieurement de personnaliser la TVA en fonction du pays */

		return [
			1 => $short ? s("2.1 %") : s("2.1 % - Taux particulier"),
			2 => $short ? s("5.5 %") : s("5.5 % - Taux réduit"),
			3 => $short ? s("10 %") : s("10 % - Taux intermédiaire"),
			4 => $short ? s("20 %") : s("20 % - Taux normal")
		];

	}

	public static function getVatRates(\farm\Farm $eFarm, bool $short = FALSE): array {

		$vat = self::getVat($eFarm, $short);
		$vatRates = [];

		foreach($vat as $key => $text) {
			$vatRates[(string)\Setting::get('selling\vatRates')[$key]] = $text;
		}

		return $vatRates;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Sale::model()->describer($property, [
			'customer' => s("Client"),
			'deliveredAt' => s("Date de vente"),
			'from' => s("Origine de la vente"),
			'market' => s("Activer le mode <i>Marché</i>"),
			'preparationStatus' => s("Statut de préparation"),
			'paymentStatus' => s("État du paiement"),
			'orderFormValidUntil' => s("Date d'échéance du devis"),
			'orderFormPaymentCondition' => s("Conditions de paiement"),
			'discount' => s("Remise commerciale"),
			'shipping' => self::getShippingName(),
			'shippingVatRate' => s("Taux de TVA sur les frais de livraison"),
			'shop' => s("Boutique"),
			'shopDate' => s("Associer à"),
			'comment' => s("Observations internes"),
		]);

		switch($property) {

			case 'customer' :
				$d->after = function(\util\FormUi $form, Sale $e) {
					return '<small>'.\Asset::icon('chevron-right').' <a href="/selling/customer:create?farm='.$e['farm']['id'].'">'.s("Créer un nouveau client").'</a></small>';
				};

				$d->autocompleteBody = function(\util\FormUi $form, Sale $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id']
					];
				};
				(new CustomerUi())->query($d);
				break;

			case 'shopDate' :
				$d->field = function(\util\FormUi $form, Sale $e) {

					$e->expects(['cShop']);

					$h = '<div class="util-block-gradient">';

					foreach($e['cShop'] as $eShop) {

						$h .= '<h5>'.encode($eShop['name']).'</h5>';

						$h .= $form->radios('shopDate', $eShop['cDate'], $e['shopDate'] ?? new \shop\Date(), attributes: [
							'callbackRadioContent' => fn($eDate) => s("Vente du {value}", \util\DateUi::numeric($eDate['deliveryDate'])),
							'mandatory' => TRUE,
						]);

						$h .= '<br/>';

					}

					$h .= '</div>';

					return $h;

				};
				$d->group = function(Sale $e) {

					$e->expects(['from']);


					$hide = ($e['from'] === Sale::SHOP) ? '' : 'hide';

					return [
						'id' => 'sale-write-date',
						'class' => $hide
					];

				};
				break;

			case 'discount' :
				$d->append = s("%");
				$d->after = fn(\util\FormUi $form, Sale $e) => match($e['type']) {
					Sale::PRIVATE => \util\FormUi::info(s("Cette remise commerciale s'applique automatiquement à tous les produits ajoutés <u>ultérieurement</u> à cette vente.")),
					Sale::PRO => \util\FormUi::info(s("Cette remise commerciale s'applique automatiquement à tous les produits ajoutés <u>ultérieurement</u> à cette vente."))
				};
				break;

			case 'deliveredAt' :
				$d->default = currentDate();
				$d->group = function(Sale $e) {

					$e->expects(['from']);

					$hide = ($e['from'] === Sale::USER) ? '' : 'hide';

					return [
						'id' => 'sale-write-delivered-at',
						'class' => $hide
					];

				};
				break;

			case 'market' :
				$d->field = 'yesNo';
				break;

			case 'taxes' :
				$d->values = [
					Sale::INCLUDING => s("TTC"),
					Sale::EXCLUDING => s("HT"),
				];
				break;

			case 'shipping' :
				$d->append = function(\util\FormUi $form, Sale $e) {
					return $form->addon(s("€ {taxes}", ['taxes' => $e->getTaxes()]));
				};
				break;

			case 'shippingVatRate' :
				$d->field = function(\util\FormUi $form, Sale $e) {

					$e->expects([
						'farm' => [
							'selling' => ['defaultVatShipping']
						]
					]);

					$values = [];

					foreach(SaleUi::getVat($e['farm']) as $position => $text) {
						$rate = \Setting::get('selling\vatRates')[$position];
						$values[(string)$rate] = s("Personnalisé - {value}", $text);
					}

					$defaultVatRate = $e['farm']['selling']['defaultVatShipping'] ? \Setting::get('selling\vatRates')[$e['farm']['selling']['defaultVatShipping']] : NULL;
					$calculatedVatRate = ($e['shippingVatFixed'] ? NULL : $e['shippingVatRate']) ?? $defaultVatRate;

					return $form->select('shippingVatRate', $values, $e['shippingVatFixed'] ? $e['shippingVatRate'] : NULL, [
						'placeholder' => $calculatedVatRate ? s("Par défaut - {value} %", $calculatedVatRate) : s("Par défaut")
					]);

				};
				break;

			case 'preparationStatus' :
				$d->values = [
					Sale::DRAFT => s("Brouillon"),
					Sale::BASKET => s("Panier"),
					Sale::CONFIRMED => s("Confirmé"),
					Sale::SELLING => s("En vente"),
					Sale::PREPARED => s("Préparé"),
					Sale::DELIVERED => s("Livré"),
					Sale::CANCELED => s("Annulé"),
				];
				$d->shortValues = [
					Sale::DRAFT => s("B"),
					Sale::BASKET => s("P"),
					Sale::CONFIRMED => s("C"),
					Sale::SELLING => s("V"),
					Sale::PREPARED => s("P"),
					Sale::DELIVERED => s("L"),
					Sale::CANCELED => s("A"),
				];
				break;

			case 'paymentStatus' :
				$d->values = [
					Sale::FAILED => s("En échec"),
					Sale::PAID => s("Payé"),
					Sale::UNDEFINED => s("Non payé"),
					Sale::PROCESSING => s("En cours de paiement"),
					Sale::WAITING => s("En cours de validation"),
				];
				$d->field = function(\util\FormUi $form, Sale $eSale) {
					return $form->radios('paymentStatus', [
						Sale::PAID => s("Payé"),
						Sale::UNDEFINED => s("Non payé"),
					], $eSale['paymentStatus'], ['mandatory' => TRUE]);
				};
				break;

			case 'paymentMethod' :
				$d->values = [
					Sale::OFFLINE => s("Direct avec le producteur"),
					Sale::TRANSFER => s("Virement bancaire"),
					Sale::CHECK => s("Chèque"),
					Sale::CASH => s("Espèces"),
					Sale::CARD => s("Carte bancaire"),
					Sale::ONLINE_CARD => \Asset::icon('stripe', ['title' => 'Stripe']).' '.s("Carte bancaire")
				];
				break;

			case 'orderFormPaymentCondition' :
				$d->placeholder = s("Exemple : Acompte de 20 % à la signature du devis, et solde à la livraison.");
				$d->after = \util\FormUi::info(s("Facultatif, indiquez ici les conditions de paiement après acceptation du devis."));
				break;

		}

		return $d;

	}

}
?>
