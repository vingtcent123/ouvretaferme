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

		$eSale->expects(['id', 'priceExcludingVat', 'market', 'compositionOf', 'compositionEndAt']);

		if($eSale->isComposition()) {
			return s("Composition du {value}", \util\DateUi::numeric($eSale['deliveredAt']));
		} else if($eSale['market']) {
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
			$taxes = $displayIncludingTaxes ? ' <span class="util-annotation">'.$eSale->getTaxes().'</span>' : '';
		} else {
			$taxes = ' <span class="util-annotation">'.$eSale->getTaxes().'</span>';
		}

		return match($eSale['taxes']) {
			Sale::EXCLUDING => $eSale['priceExcludingVat'] ? \util\TextUi::money($eSale['priceExcludingVat']).$taxes : '-',
			Sale::INCLUDING => $eSale['priceIncludingVat'] ? \util\TextUi::money($eSale['priceIncludingVat']).$taxes : '-'
		};

	}

	public static function getPanelHeader(Sale $eSale): string {

		return '<div class="panel-header-subtitle">'.self::getName($eSale).'</div>';

	}

	public function getSearch(\Search $search, \Collection $cPaymentMethod): string {

		$h = '<div id="sale-search" class="util-block-search stick-xs '.($search->empty(['ids']) ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$statuses = SaleUi::p('preparationStatus')->values;
			unset($statuses[Sale::BASKET], $statuses[Sale::SELLING]);

			$paymentMethods = $cPaymentMethod->toArray(fn($ePaymentMethod) => ['label' => $ePaymentMethod['name'], 'value' => $ePaymentMethod['id']]);

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
					$h .= '<a href="/selling/item:summary?farm='.$eFarm['id'].'&date='.$date.''.($type ? '&type='.$type : '').'" style="'.($date < currentDate() ? 'opacity: 0.5' : '').'">';
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

	public function getList(\farm\Farm $eFarm, \Collection $cSale, ?int $nSale = NULL, ?\Search $search = NULL, array $hide = [], array $dynamicHide = [], array $show = [], ?int $page = NULL, ?\Closure $link = NULL, ?bool $hasSubtitles = NULL, ?string $segment = NULL, ?\Collection $cPaymentMethod = NULL): string {

		if($cSale->empty()) {

			$h = '<div class="util-empty">';
				$h .= s("Il n'y a aucune vente à afficher.");
			$h .= '</div>';

			return $h;

		}

		$link ??= fn($eSale) => '/vente/'.$eSale['id'];

		$h = '<div class="util-overflow-md stick-xs">';

		$columns = 5;

		if($hasSubtitles === NULL) {
			$hasSubtitles = (
				$cSale->count() > 10 and
				($search !== NULL and str_starts_with($search->getSort(), 'preparationStatus'))
			);
		}

		$hasFarm = count(array_count_values($cSale->getColumnCollection('farm')->getIds())) > 1;

		$previousSubtitle = NULL;

		$h .= '<table class="tr-even">';

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
						$h .= '<th>';
							$label = s("Prénom");
							$h .= ($search ? $search->linkSort('firstName', $label) : $label).' / ';
							$label = s("Nom");
							$h .= ($search ? $search->linkSort('lastName', $label) : $label);
						$h .= '</th>';
						$columns++;
					}
					if($hasFarm) {
						$h .= '<th>'.s("Producteur").'</th>';
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
						$h .= '<th>'.s("Mode de livraison").'</th>';
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

				if($segment === 'point') {
					$previousSegment = new \shop\Point();
				}

				foreach($cSale as $eSale) {

					if($hasSubtitles) {

						$currentSubtitle = ($eSale['preparationStatus'] === Sale::DRAFT) ? Sale::DRAFT : $eSale['deliveredAt'];

						if($currentSubtitle !== $previousSubtitle) {

							if($previousSubtitle !== NULL) {
								$h .= '</tbody>';
								$h .= '<tbody>';
							}

							$h .= '<tr class="tr-title">';
								$h .= '<th class="td-checkbox">';
									$h .= '<label title="'.s("Cocher ces ventes / Décocher ces ventes").'">';
										$h .= '<input type="checkbox" class="batch-all" onclick="Sale.toggleDaySelection(this)"/>';
									$h .= '</label>';
								$h .= '</th>';
								$h .= '<td colspan="'.$columns.'">';
									$h .= match($currentSubtitle) {
										Sale::DRAFT => s("Brouillon"),
										currentDate() => s("Aujourd'hui"),
										default => \util\DateUi::textual($currentSubtitle)
									};
								$h .= '</td>';
							$h .= '</tr>';
								$h .= '</tbody>';
								$h .= '<tbody>';

							$previousSubtitle = $currentSubtitle;


						}

					}

					switch($segment) {

						case 'point' :

							$currentSegment = $eSale['shopPoint'];

							if($currentSegment->is($previousSegment) === FALSE) {

								$h .= '<tr class="tr-title">';
									$h .= '<td colspan="'.($columns + 1).'">';
										if($eSale['shopPoint']->empty()) {
											$h .= s("Aucun mode de livraison");
										} else if($eSale['shopPoint']['type'] === \shop\Point::HOME) {
											$h .= s("Livraison à domicile");
										} else if($eSale['shopPoint']['type'] === \shop\Point::PLACE) {
											$h .= encode($eSale['shopPoint']['name']);
										}
									$h .= '</td>';
								$h .= '</tr>';

							}

							$previousSegment = $currentSegment;

							break;

					}

					$batch = [];

					if(
						$eSale->canWrite() === FALSE or
						$eSale->acceptStatusPrepared() === FALSE
					) {
						$batch[] = 'not-prepared';
					}

					if(
						$eSale->canWrite() === FALSE or
						$eSale->acceptStatusConfirmed() === FALSE
					) {
						$batch[] = 'not-confirmed';
					}

					if(
						$eSale->canWrite() === FALSE or
						$eSale->acceptStatusCanceled() === FALSE
					) {
						$batch[] = 'not-canceled';
					}

					if(
						$eSale->canWrite() === FALSE or
						$eSale->acceptStatusDelivered() === FALSE
					) {
						$batch[] = 'not-delivered';
					}

					if(
						$eSale->canWrite() === FALSE or
						$eSale->acceptDelete() === FALSE
					) {
						$batch[] = 'not-delete';
					}

					$h .= '<tr';
						if($eSale['preparationStatus'] === Sale::CANCELED) {
							$h .= ' style="opacity: 0.5"';
						}
					$h .= '>';

						$h .= '<td class="td-checkbox">';
							if($eSale->canRead()) {
								$h .= '<label>';
									$h .= '<input type="checkbox" name="batch[]" value="'.$eSale['id'].'" oninput="Sale.changeSelection()" data-batch-amount-excluding="'.($eSale['priceExcludingVat'] ?? 0.0).'" data-batch-amount-including="'.($eSale['priceIncludingVat'] ?? 0.0).'" data-batch-taxes="'.($eSale['hasVat'] ? $eSale['taxes'] : '').'" data-batch="'.implode(' ', $batch).'"/>';
								$h .= '</label>';
							}
						$h .= '</td>';

						$h .= '<td class="td-min-content text-center">';
							if(
								$eSale->canRead() === FALSE or
								$eSale['marketParent']->notEmpty()
							) {
								$h .= '<span class="btn btn-sm disabled">'.$eSale->getNumber().'</span>';
							} else {
								$h .= '<a href="'.$link($eSale).'" class="btn btn-sm '.($eSale['deliveredAt'] === currentDate() ? 'btn-primary' : 'btn-outline-primary').'">'.$eSale->getNumber().'</a>';
							}
						$h .= '</td>';

						if(in_array('customer', $hide) === FALSE) {
							$h .= '<td class="sale-item-name">';
								if($eSale->canRead()) {
									$h .= CustomerUi::link($eSale['customer']);
								} else {
									$h .= encode($eSale['customer']->getName());
								}
								if($eSale['market']) {
									$h .= ' <span class="util-badge bg-secondary" title="'.s("Le logiciel de caisse est activé pour cette vente").'">'.\Asset::icon('cart4').'</span>';
								}
								if($eSale['customer']->notEmpty()) {
									$h .= '<div class="util-annotation">';
										$h .= CustomerUi::getCategory($eSale['customer']);
									$h .= '</div>';
								}
							$h .= '</td>';
						}

						if($hasFarm) {
							$h .= '<td class="font-sm color-muted">';
								$h .= encode($eSale['farm']['name']);
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
									if($eSale->acceptWriteDeliveredAt() === FALSE) {
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
										$h .= '<a href="'.\shop\ShopUi::adminDateUrl($eSale['farm'], $eSale['shopDate']).'">'.encode($eSale['shop']['name']).'</a>';
										if($eSale['shopShared']) {
											$h .= '  <span class="util-badge bg-secondary">'.\Asset::icon('people-fill').'</span>';
										}
									} else if($eSale['marketParent']->notEmpty()) {
										$h .= '<a href="'.SaleUi::url($eSale['marketParent']).'">'.encode($eSale['marketParent']['customer']->getName()).'</a>';;
									} else if($eSale['market']) {
										if($eSale['marketSales'] > 0) {
											$h .= \Asset::icon('cart4').' '.p("{value} vente", "{value} ventes", $eSale['marketSales']);
										}
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
									if($eSale['shopDate']->acceptOrder()) {
										$h .= s("Commande à l'état de panier et non confirmée par le client.");
									} else {
										$h .= s("Commande restée à l'état de panier et non confirmée par le client.");
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
								$h .= PaymentUi::getListDisplay($eSale, $eSale['cPayment'], $cPaymentMethod);
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

		$h .= $this->getBatch($eFarm);

		return $h;

	}

	public function getBatch(\farm\Farm $eFarm): string {

		$menu = '<a data-url="/selling/item:summary?farm='.$eFarm['id'].'" class="batch-menu-amount batch-menu-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-menu-item-number"></span>';
				$menu .= ' <span class="batch-menu-item-taxes" data-excluding="'.s("HT").'" data-including="'.s("TTC").'"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Synthèse").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/sale:doUpdateConfirmedCollection" data-confirm="'.s("Marquer ces ventes comme confirmées ?").'" class="batch-menu-confirmed batch-menu-item">';
			$menu .= '<span class="sale-preparation-status-label sale-preparation-status-batch sale-preparation-status-confirmed">'.self::p('preparationStatus')->shortValues[Sale::CONFIRMED].'</span>';
			$menu .= '<span>'.s("Confirmé").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/sale:doUpdatePreparedCollection" data-confirm="'.s("Marquer ces ventes comme confirmées ?").'" class="batch-menu-prepared batch-menu-item">';
			$menu .= '<span class="sale-preparation-status-label sale-preparation-status-batch sale-preparation-status-prepared">'.self::p('preparationStatus')->shortValues[Sale::PREPARED].'</span>';
			$menu .= '<span>'.s("Préparé").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/sale:doUpdateDeliveredCollection" data-confirm="'.s("Marquer ces ventes comme livrées ?").'" class="batch-menu-delivered batch-menu-item">';
			$menu .= '<span class="sale-preparation-status-label sale-preparation-status-batch sale-preparation-status-delivered">'.self::p('preparationStatus')->shortValues[Sale::DELIVERED].'</span>';
			$menu .= '<span>'.s("Livré").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/sale:doUpdateCanceledCollection" data-confirm="'.s("Annuler ces ventes ?").'" class="batch-menu-cancel batch-menu-item">';
			$menu .= '<span class="sale-preparation-status-label sale-preparation-status-batch sale-preparation-status-draft">'.self::p('preparationStatus')->shortValues[Sale::CANCELED].'</span>';
			$menu .= '<span>'.s("Annuler").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/sale:doExportCollection" data-ajax-navigation="never" class="batch-menu-item">';
			$menu .= \Asset::icon('filetype-pdf');
			$menu .= '<span>'.s("Exporter").'</span>';
		$menu .= '</a>';

		$danger = '<a data-ajax-submit="/selling/sale:doDeleteCollection" data-confirm="'.s("Confirmer la suppression de ces ventes ?").'" class="batch-menu-delete batch-menu-item batch-menu-item-danger">';
			$danger .= \Asset::icon('trash');
			$danger .= '<span>'.s("Supprimer").'</span>';
		$danger .= '</a>';

		return \util\BatchUi::group($menu, $danger, title: s("Pour les ventes sélectionnées"));

	}

	protected function getDocuments(Sale $eSale, \Collection $cPdf, string $origin): string {

		if($eSale['items'] > 0) {

			$list = [];

			foreach([Pdf::ORDER_FORM, Pdf::DELIVERY_NOTE, Pdf::INVOICE] as $type) {

				if($eSale->acceptDocument($type) === FALSE) {
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

				$acceptGenerate = $eSale->acceptGenerateDocument($type);
				$acceptRegenerate = $eSale->acceptRegenerateDocument($type);

				$urlGenerate = match($type) {
					Pdf::DELIVERY_NOTE => 'data-ajax="/selling/sale:doGenerateDocument" post-id="'.$eSale['id'].'" post-type="'.$type.'"',
					Pdf::ORDER_FORM => 'href="/selling/sale:generateOrderForm?id='.$eSale['id'].'"',
					Pdf::INVOICE => 'href="/selling/invoice:create?customer='.$eSale['customer']['id'].'&sales[]='.$eSale['id'].'&origin=sales"',
				};

				if($acceptRegenerate) {

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
						$acceptGenerate and
						$eSale->canDocument($type)
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

						if($eSale->canDocument($type)) {

							if($texts['generateNew'] !== NULL) {

								if($acceptRegenerate) {
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

							if($eSale->canManage()) {
								$document .= ' <a '.$urlDelete.' data-confirm="'.$texts['deleteConfirm'].'" class="dropdown-item">'.s("Supprimer le document").'</a>';
							}

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
			$eSale['marketParent']->notEmpty()
		) {
			return $h;
		}

		$wrapper = function($content) {
			if($content) {
				return ' '.\Asset::icon('caret-right-fill').$content;
			} else {
				return '';
			}
		};

		if(
			$eSale['shopDate']->notEmpty() and
			$eSale['shopDate']->acceptOrder()
		) {

			$h .= '<span title="'.s("Il sera possible de modifier le statut lorsque la période de prise des commandes sera close").'">'.$wrapper(
					' '.\Asset::icon('lock-fill')
			).'</span>';

			return $h;

		}

		$buttonsStyle = self::getPreparationStatusButtons();

		$button = fn(string $status, ?string $confirm = NULL) => ' <a data-ajax="/selling/sale:doUpdate'.ucfirst($status).'Collection" post-ids="'.$eSale['id'].'" class="sale-preparation-status-action '.$buttonsStyle[$status].'" title="'.self::p('preparationStatus')->values[$status].'" '.($confirm ? attr('data-confirm', $confirm) : '').'>'.($shortText ? self::p('preparationStatus')->shortValues[$status] : self::p('preparationStatus')->values[$status]).'</a>';

		if($eSale['market']) {

			switch($eSale['preparationStatus']) {

				case Sale::DRAFT :
					$h .= $wrapper(
						$button(Sale::CONFIRMED)
					);
					break;

				case Sale::CONFIRMED :
					$h .= $wrapper(
						$button(Sale::SELLING, s("Vous allez commencer votre vente avec la caisse virtuelle ! Les quantités des produits que vous avez saisies pour préparer cette vente seront remises à zéro et vous pourrez commencer à enregistrer les commandes des clients. C'est parti ?"))
					);
					break;

				case Sale::SELLING :
					$h .= $wrapper(
						' <a href="'.SaleUi::urlMarket($eSale).'" class="sale-preparation-status-action btn-outline-selling">'.\Asset::icon('cart4').'  '.s("Caisse virtuelle").'</a>'
					);
					break;

			};

		} else {

			switch($eSale['preparationStatus']) {

				case Sale::BASKET :
					$h .= $wrapper(
						($eSale->acceptStatusConfirmed() ? $button(Sale::CONFIRMED) : '').
						($eSale->acceptStatusCanceled() ? $button(Sale::CANCELED) : '')
					);
					break;

				case Sale::DRAFT :
					$h .= $wrapper(
						$eSale->acceptStatusConfirmed() ? $button(Sale::CONFIRMED) : ''
					);
					break;

				case Sale::CONFIRMED :

					$h .= $wrapper(
						($eSale->acceptStatusPrepared() ? $button(Sale::PREPARED) : '').
						($eSale->acceptStatusDelivered() ? $button(Sale::DELIVERED) : '')
					);
					break;

				case Sale::PREPARED :
					$h .= $wrapper(
						($eSale->acceptStatusDelivered() ? $button(Sale::DELIVERED) : '')
					);
					break;

			};

		}

		return $h;

	}

	public static function getPreparationStatusButtons(): array {

		return [
			Sale::DRAFT => 'btn-outline-todo',
			Sale::CANCELED => 'btn-outline-muted',
			Sale::CONFIRMED => 'btn-outline-order',
			Sale::PREPARED => 'btn-outline-done',
			Sale::SELLING => 'btn-outline-selling',
			Sale::DELIVERED => 'btn-outline-success',
		];

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

	public static function getPaymentStatus(Sale $eSale, Payment $ePayment): string {

		if($ePayment['method']['fqn'] === \payment\MethodLib::TRANSFER and $eSale['invoice']->empty()) {
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
			$h .= '<div class="util-empty">'.s("Il n'y a aucune vente en cours de préparation ou déjà préparée.").'</div>';
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
			$h .= new PdfUi()->getLabel($eFarm, new Customer(), quality: $eFarm['quality']);
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
			$h .= '<div>';
				if($eSale->isComposition() === FALSE) {
					$h .= '<h1 style="margin-bottom: 0.5rem">'.SaleUi::getName($eSale).'</h1>';
					$h .= $this->getPreparationStatusForUpdate($eSale, shortText: FALSE);
				} else {
					$h .= '<h1 class="mb-0">'.encode($eSale['compositionOf']['name']).'</h1>';
				}
			$h .= '</div>';
			if($eSale->isComposition() === FALSE) {
				$h .= '<div>';
					$h .= $this->getUpdate($eSale, 'btn-primary');
				$h .= '</div>';
			}

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
					$h .= '<div class="sale-relative-customer">'.encode($eSaleBefore['customer']->getName()).'</div>';
					$h .= '<div class="sale-relative-arrow">';
						$h .= \Asset::icon('chevron-left');
					$h .= '</div>';
				$h .= '</a>';
			} else {
				$h .= '<div class="sale-relative-before"></div>';
			}

			$h .= '<div class="sale-relative-title">';
				$h .= '<h4>'.encode($e['shop']['name']).'</h4>';
				$h .= '<a href="'.\shop\ShopUi::adminDateUrl($e['farm'], $e['shopDate']).'" class="sale-relative-date">'.s("Livraison du {value}", \util\DateUi::numeric($e['shopDate']['deliveryDate'])).'</a>';
				$h .= '<div class="sale-relative-current">';
					$h .= s("Commande {position} / {count}", ['position' => $position, 'count' => $count]);
				$h .= '</div>';
			$h .= '</div>';

			if($eSaleAfter->notEmpty()) {
				$h .= '<a href="'.SaleUi::url($eSaleAfter).'" class="sale-relative-after">';
					$h .= '<div class="sale-relative-arrow">';
						$h .= \Asset::icon('chevron-right');
					$h .= '</div>';
					$h .= '<div class="sale-relative-customer">'.encode($eSaleAfter['customer']->getName()).'</div>';
				$h .= '</a>';
			} else {
				$h .= '<div class="sale-relative-after"></div>';
			}

		$h .= '</div>';

		return $h;

	}

	public function getContent(Sale $eSale, \Collection $cPdf, \Collection $cPaymentMethod): string {

		if($eSale->isComposition()) {
			return '';
		}

		$h = '<div class="sale-content-wrapper util-block stick-xs">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Client").'</dt>';
				$h .= '<dd>'.CustomerUi::link($eSale['customer']).'</dd>';
				if($eSale['market'] === FALSE) {
					$h .= '<dt>'.s("Moyen(s) de paiement").'</dt>';
					$h .= '<dd>';
						$h .= PaymentUi::getListDisplay($eSale, $eSale['cPayment'], $cPaymentMethod);
					$h .= '</dd>';
				}

				if($eSale['from'] === Sale::SHOP) {

					$h .= '<dt>'.s("Origine").'</dt>';
					$h .= '<dd>';
						if($eSale['shop']->notEmpty()) {
							$h .= \shop\ShopUi::link($eSale['shop']);
						}
					$h .= '</dd>';

					$h .= '<dt>'.s("Mode de livraison").'</dt>';
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

				$update = fn($content) => $eSale->acceptWriteDeliveredAt() ? $eSale->quick('deliveredAt', $content) : $content;

				$h .= $eSale['preparationStatus'] === Sale::DELIVERED ?
					$update(\util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE)) :
					$update($eSale['deliveredAt'] ? s("Planifié le {value}", \util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE)) : s("Non planifié"));
				$h .= '</dd>';

				if($eSale->hasDiscount()) {
					$h .= '<dt>'.s("Remise commerciale").'</dt>';
					$h .= '<dd>'.($eSale['discount'] > 0 ? s("{value} %", $eSale['discount']) : '').'</dd>';
				}

				if($eSale->acceptAnyDocument()) {

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

		return $h;

	}

	public function getStats(Sale $eSale): string {

		$h = '';

		if(
			$eSale->isMarket() and
			$eSale->canWrite()
		) {
			if($eSale['preparationStatus'] === Sale::CONFIRMED) {

				$h .= '<div class="util-block color-white bg-selling">';
					$h .= '<h4>'.s("Votre vente est prête à démarrer ?").'</h4>';
					$h .= '<p>'.s("Vous pouvez commencer à prendre les commandes avec caisse virtuelle !").'<br/>'.s("Les quantités des produits que vous avez saisies pour préparer cette vente seront remises à zéro.").'</p>';
					$h .= '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::SELLING.'" class="btn btn-transparent" data-confirm="'.s("C'est parti ?").'">'.\Asset::icon('cart4').'  '.s("Ouvrir la caisse virtuelle").'</a>';
				$h .= '</div>';

			} else if($eSale['preparationStatus'] !== Sale::DRAFT) {
				$h .= '<a href="'.SaleUi::urlMarket($eSale).'" class="btn btn-xl btn-selling" style="width: 100%">'.\Asset::icon('cart4').'  '.s("Ouvrir la caisse virtuelle").'</a>';
			}
		}

		if(
			($eSale['market'] === TRUE and $eSale->isMarketPreparing() === FALSE) or
			($eSale['market'] === FALSE and $eSale['items'] > 0)
		) {
			$h .= $this->getSummary($eSale);
		}


		return $h;

	}

	public function getSummary(Sale $eSale, bool $onlyIncludingVat = FALSE, bool $includeMoney = FALSE): string {

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

						if(
							$eSale->isComposition() === FALSE or
							$eSale['taxes'] === Sale::EXCLUDING
						) {

							$h .= '<li>';
								$h .= '<h5>'.s("Montant HT").'</h5>';
								$h .= \util\TextUi::money($eSale['priceExcludingVat'] ?? 0);
							$h .= '</li>';

						}

						if($eSale->isComposition() === FALSE) {

							$h .= '<li>';
								$h .= '<h5>'.s("TVA").'</h5>';
								$h .= \util\TextUi::money($eSale['vat'] ?? 0);
							$h .= '</li>';

						}

					}

					if(
						$eSale->isComposition() === FALSE or
						$eSale['taxes'] === Sale::INCLUDING
					) {

						$h .= '<li>';
							$h .= '<h5>'.s("Montant TTC").'</h5>';
							$h .= \util\TextUi::money($eSale['priceIncludingVat'] ?? 0);
						$h .= '</li>';

					}

				} else {
					$h .= '<li>';
						$h .= '<h5>'.s("Montant").'</h5>';
						$h .= \util\TextUi::money($eSale['priceIncludingVat'] ?? 0);
					$h .= '</li>';
				}

				if($includeMoney) {

					$h .= '<li style="align-self: end">';
						$h .= '<a '.attr('onclick', 'Sale.toggleMoney('.$eSale['id'].')').' class="btn btn-outline-primary">'.s("Rendu de monnaie").'</a>';
					$h .= '</li>';

				}

			$h .= '</ul>';

			if($includeMoney) {

				$values = [];

				foreach([5, 10, 20, 50] as $value) {
					if($eSale['priceIncludingVat'] < $value) {
						$values[] = $value;
					}
				}

				$values[] = floor($eSale['priceIncludingVat'] / 5) * 5 + 5;
				$values[] = floor($eSale['priceIncludingVat'] / 10) * 10 + 10;
				$values[] = floor($eSale['priceIncludingVat'] / 20) * 20 + 20;

				$values = array_unique($values);
				sort($values);

				$form = new \util\FormUi();

				$h .= '<div id="sale-money-'.$eSale['id'].'" class="hide util-overflow-sm">';
					$h .= '<table>';
						$h .= '<tr>';
							$h .= '<td><h3 class="mb-0">'.s("Donné").'</h3></td>';
							foreach($values as $value) {
								$h .= '<td style="font-size: 1.2rem">'.\util\TextUi::money($value, precision: 0).'<td>';
							}
							$h .= '<td>';
								$h .= $form->inputGroup(
									$form->number(attributes: ['placeholder' => s("Autre"), 'id' => 'sale-money-'.$eSale['id'].'-field', 'oninput' => 'Sale.updateCustomerMoney('.$eSale['id'].', '.$eSale['priceIncludingVat'].', this)']).
									$form->addon('€')
								);
							$h .= '<td>';
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td><h3 class="mb-0">'.s("À rendre").'</h3></td>';
							foreach($values as $value) {
								$h .= '<td style="font-size: 1.2rem">'.\util\TextUi::money($value - $eSale['priceIncludingVat']).'<td>';
							}
							$h .= '<td style="font-size: 1.2rem" id="sale-money-'.$eSale['id'].'-custom"><td>';
						$h .= '</tr>';
					$h .= '</table>';
				$h .= '</div>';

			}

			return $h;

	}

	public function getUpdate(Sale $eSale, string $btn): string {

		$primaryList = '';

		if(
			$eSale->isMarket() and
			$eSale->canWrite()
		) {

			if($eSale->isMarketPreparing() === FALSE) {
				$primaryList .= ' <a href="'.SaleUi::urlMarket($eSale).'" class="dropdown-item">'.s("Ouvrir le logiciel de caisse").'</a>';
			}

			if($eSale->isMarketClosed()) {
				$primaryList = '<a data-ajax="/selling/sale:doUpdatePreparationStatus" post-id="'.$eSale['id'].'" post-preparation-status="'.Sale::SELLING.'" class="dropdown-item">'.s("Réouvrir la vente").'</a>';
			}

		}

		if($eSale->canUpdate()) {
			$primaryList .= '<a href="/selling/sale:update?id='.$eSale['id'].'" class="dropdown-item">';
				$primaryList .= match($eSale->isComposition()) {
					TRUE => s("Modifier la composition"),
					FALSE => s("Modifier la vente"),
				};
			$primaryList .= '</a>';
		}

		if($eSale->acceptAssociateShop()) {
			$primaryList .= '<a href="/selling/sale:updateShop?id='.$eSale['id'].'" class="dropdown-item">'.s("Associer la vente à une boutique").'</a>';
		}

		if($eSale->acceptDissociateShop()) {
			$primaryList .= '<a data-ajax="/selling/sale:doUpdateShop" post-id="'.$eSale['id'].'" post-from="'.Sale::USER.'" class="dropdown-item">'.s("Dissocier la vente de la boutique").'</a>';
		}

		if(
			$eSale->canWrite() and
			$eSale['marketParent']->empty()
		) {

			$statusList = '';

			if(in_array($eSale['preparationStatus'], [Sale::PREPARED, Sale::SELLING]) and $eSale->acceptStatusConfirmed()) {
				$statusList .= '<a data-ajax="/selling/sale:doUpdateConfirmedCollection" post-ids="'.$eSale['id'].'" class="dropdown-item">'.s("Remettre à préparer").'</a>';
			}

			if($eSale->acceptStatusDraft()) {
				$statusList .= '<a data-ajax="/selling/sale:doUpdateDraftCollection" post-ids="'.$eSale['id'].'" class="dropdown-item">'.s("Repasser en brouillon").'</a>';
			}

			if($eSale->acceptCancelDelivered()) {
				$statusList .= '<a data-ajax="/selling/sale:doUpdateConfirmedCollection" post-ids="'.$eSale['id'].'" class="dropdown-item">'.s("Annuler la livraison").'</a>';
			}

			if($eSale['preparationStatus'] === Sale::CANCELED and $eSale->acceptStatusConfirmed()) {
				$statusList .= '<a data-ajax="/selling/sale:doUpdateConfirmedCollection" post-ids="'.$eSale['id'].'" class="dropdown-item">'.s("Revalider la vente").'</a>';
			}

			if($eSale->acceptStatusCanceled()) {
				$statusList .= '<a data-ajax="/selling/sale:doUpdateCanceledCollection" post-ids="'.$eSale['id'].'" class="dropdown-item">'.s("Annuler la vente").'</a>';
			}

		} else {
			$statusList = '';
		}

		$secondaryList = '';

		if(
			$eSale->acceptDelete() and
			$eSale->canDelete()
		) {

			$confirm = $eSale->isComposition() ?
				s("Confirmer la suppression de la composition ?") :
				s("Confirmer la suppression de la vente ?");

			$secondaryList .= '<a data-ajax="/selling/sale:doDelete" post-id="'.$eSale['id'].'" class="dropdown-item" data-confirm="'.$confirm.'">';
				$secondaryList .= match($eSale->isComposition()) {
					TRUE => s("Supprimer la composition"),
					FALSE => s("Supprimer la vente"),
				};
			$secondaryList .= '</a>';
		}

		if($eSale->acceptDuplicate()) {
			$primaryList .= '<a href="/selling/sale:duplicate?id='.$eSale['id'].'" class="dropdown-item">'.s("Dupliquer la vente").'</a>';
		}

		if($eSale->acceptUpdateCustomer()) {
			$primaryList .= '<a href="/selling/sale:updateCustomer?id='.$eSale['id'].'" class="dropdown-item">'.s("Transférer à un autre client ").'</a>';
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

	public function getMarket(\farm\Farm $eFarm, \Collection $ccSale, \Collection $cPaymentMethod) {

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

			$h .= $this->getList($eFarm, $cSale, hide: ['deliveredAt', 'actions', 'documents'], show: ['createdAt'], cPaymentMethod: $cPaymentMethod);

		}

		$h .= '<br/>';

		return $h;

	}

	public function getHistory(Sale $eSale, \Collection $cHistory) {

		if(
			$eSale->isComposition() or
			$cHistory->empty()
		) {
			return '';
		}

		$h = '<h3>'.s("Historique").'</h3>';

		$h .= '<div class="util-overflow-md stick-xs">';

			$h .= '<table>';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th>'.s("Événement").'</th>';
						$h .= '<th>'.s("Par").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

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
								$h .= $eHistory['user']->empty() ? '-' : $eHistory['user']->getName();
							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function create(Sale $eSale): \Panel {

		if($eSale['compositionOf']->empty()) {
			return $this->createCustomer($eSale);
		} else {
			return $this->createComposition($eSale);
		}

	}

	public function createComposition(Sale $eSale): \Panel {

		$eSale->expects(['farm', 'market']);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->asteriskInfo();

		$h .= $form->hidden('farm', $eSale['farm']['id']);
		$h .= $form->hidden('from', Sale::USER);
		$h .= $form->hidden('compositionOf', $eSale['compositionOf']['id']);

		$h .= $form->dynamicGroup($eSale, 'deliveredAt');

		$arguments = ['product' => '<b>'.encode($eSale['compositionOf']['name']).'</b>', 'price' => '<b>'.\util\TextUi::money($eSale['compositionOf'][$eSale['type'].'Price']).'</b>'];

		if($eSale['cProduct']->notEmpty()) {

			$h .= '<h3 class="mt-2">'.s("Composition du produit").'</h3>';

			$h .= '<div class="util-info">';
				$h .= s("Les prix unitaires et les montants des produits sont indiqués à titre informatif pour vous aider à composer votre {product} qui sera toujours vendu à {price}.", $arguments);
				$h .= ' '.match($eSale['compositionOf']['compositionVisibility']) {
					Product::PUBLIC => s("Seules les quantités sont communiquées à vos clients."),
					Product::PRIVATE => s("Aucune information sur la composition n'est communiquée à vos clients.")
				};
			$h .= '</div>';

			$h .= $form->dynamicField($eSale, 'productsList');

			$footer = ItemUi::getCreateSubmit($eSale, $form, s("Valider la composition"));

		} else {

			$h .= '<div class="util-block-help mt-1">';
				$h .= '<p>';
					$h .= match($eSale['type']) {
						Sale::PRIVATE => s("Aucun produit vendu aux particuliers n'est disponible pour composer votre {product} d'une valeur de {price}.", $arguments),
						Sale::PRO => s("Aucun produit vendu aux professionnels n'est disponible pour composer votre {product} d'une valeur de {price}.", $arguments)
					};
				$h .= '</p>';
				$h .= '<a href="/selling/product:create?farm='.$eSale['farm']['id'].'" class="btn btn-secondary">'.s("Ajouter un produit").'</a>';
			$h .= '</div>';

			$footer = NULL;
		}

		return new \Panel(
			id: 'panel-sale-create',
			title: encode($eSale['compositionOf']['name']),
			dialogOpen: $form->openAjax('/selling/sale:doCreate', ['id' => 'sale-create', 'class' => 'panel-dialog container']),
			dialogClose: $form->close(),
			body: $h,
			footer: $footer
		);

	}

	public function createCustomer(Sale $eSale): \Panel {

		$eSale->expects(['farm', 'market', 'shopDate']);

		$form = new \util\FormUi();

		$eSale['from'] = Sale::USER;

		$h = '';

		$h .= $form->asteriskInfo();

		$h .= $form->hidden('farm', $eSale['farm']['id']);
		$h .= $form->hidden('from', $eSale['shopDate']->empty() ? Sale::USER : Sale::SHOP);
		if($eSale['shopDate']->notEmpty()) {
			$h .= $form->hidden('shopDate', $eSale['shopDate']['id']);
		}
		$h .= $form->hidden('market', $eSale['market']);

		$h .= $form->dynamicGroup($eSale, 'customer*', function($d) use($form, $eSale) {

				$d->autocompleteDispatch = '#sale-create';

				if($eSale['shopDate']->notEmpty()) {
					$d->autocompleteBody = ($d->autocompleteBody)($form, $eSale) + [
						'type' => $eSale['shopDate']['type']
					];
				}

			});

		if($eSale['customer']->notEmpty()) {

			if($eSale['customer']['destination'] === Customer::COLLECTIVE) {
				$h .= '<div class="util-block util-block-dark sale-create-market bg-selling">';
					$h .= $form->dynamicGroup($eSale, 'market');
				$h .= '</div>';
			}

			if($eSale['shopDate']->empty()) {
				$h .= $form->dynamicGroup($eSale, 'deliveredAt');
			} else {
				$h .= $form->group(
					(self::p('deliveredAt')->label)($eSale),
					$form->fake(\util\DateUi::numeric($eSale['shopDate']['deliveryDate']))
				);
			}

			if($eSale['cProduct']->notEmpty()) {

				$h .= '<h3 class="mt-2">'.s("Ajouter des produits à la vente").'</h3>';

				if($eSale['discount'] > 0) {
					$h .= '<div class="util-info">'.s("Les prix indiqués tiennent compte de la réduction de {value} % dont bénéficie ce client.", $eSale['discount']).'</div>';
				}

				$h .= $form->dynamicField($eSale, 'productsList');

				$footer = ItemUi::getCreateSubmit($eSale, $form, s("Créer la vente"));

			} else {
				$footer = $form->submit(s("Créer la vente"), ['class' => 'btn btn-primary btn-lg']);
			}

		} else {
			$footer = NULL;
		}

		return new \Panel(
			id: 'panel-sale-create',
			title: s("Ajouter une vente"),
			dialogOpen: $form->openAjax('/selling/sale:doCreate', ['id' => 'sale-create', 'class' => 'panel-dialog container']),
			dialogClose: $form->close(),
			body: $h,
			footer: $footer
		);

	}

	public function duplicate(Sale $eSale): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/sale:doDuplicate');

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
				s("Date de la nouvelle livraison"),
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
			title: s("Dupliquer une vente"),
			body: $h,
		);

	}

	public function update(Sale $eSale): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/sale:doUpdate');

			$h .= $form->hidden('id', $eSale['id']);

			if($eSale->isClosed() === FALSE) {

				$h .= $form->dynamicGroup($eSale, 'deliveredAt');

				if(
					$eSale->isComposition() === FALSE and
					$eSale['shopShared'] === FALSE
				) {

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

			}

			if($eSale['shop']->notEmpty()) {
				$h .= $form->dynamicGroup($eSale, 'shopPointPermissive');
			}

			$h .= $form->dynamicGroup($eSale, 'comment');

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-sale-update',
			title: $eSale->isComposition() ? s("Modifier une composition") : s("Modifier une vente"),
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
			id: 'panel-sale-associate',
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
			id: 'panel-sale-customer',
			title: s("Transférer la vente à un autre client"),
			body: $h
		);

	}

	public static function getCompositionSwitch(\farm\Farm $eFarm, string $btn): string {

		\Asset::css('selling', 'sale.css');

		$eFarmer = $eFarm->getFarmer();

		$action = fn($to) => attrs([
			'data-ajax' => \util\FormUi::getQuickUrl($eFarmer),
			'post-id' => $eFarmer['id'],
			'post-property' => 'viewAnalyzeComposition',
			'post-view-analyze-composition' => $to,
		]);

		$composition = \Asset::icon('puzzle-fill');
		$ingredient = \Asset::icon('puzzle-fill').' '.\Asset::icon('arrow-right').' '.\Asset::icon('boxes');

		$h = '<a data-dropdown="bottom-start" class="btn '.$btn.' dropdown-toggle">'.($eFarmer['viewAnalyzeComposition'] === \farm\Farmer::COMPOSITION ? $composition : $ingredient).'</a>';
		$h .= '<div class="dropdown-list">';

			if($eFarmer['viewAnalyzeComposition'] === \farm\Farmer::COMPOSITION) {
				$h .= '<a '.$action(\farm\Farmer::INGREDIENT).' data-confirm="'.s("Remplacer les produits composés par leur composition dans l'affichage ?").'" class="dropdown-item">'.$ingredient.'</a>';
			} else {
				$h .= '<a '.$action(\farm\Farmer::COMPOSITION).' data-confirm="'.s("Ne plus remplacer les produits composés par leur composition ?").'" class="dropdown-item">'.$composition.'</a>';
			}

		$h .= '</div>';

		return $h;

	}

	public static function getVat(\farm\Farm $eFarm, bool $short = FALSE): array {

		/* La ferme permettra ultérieurement de personnaliser la TVA en fonction du pays */

		return [
			0 => $short ? s("0 %") : s("0 % - Pas de TVA"),
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
			'deliveredAt' => fn($e) => $e->isComposition() ? s("Pour les livraisons à partir du") : s("Date de vente"),
			'from' => s("Origine de la vente"),
			'market' => s("Utiliser le logiciel de caisse<br/>pour cette vente"),
			'preparationStatus' => s("Statut de préparation"),
			'paymentStatus' => s("État du paiement"),
			'orderFormValidUntil' => s("Date d'échéance du devis"),
			'orderFormPaymentCondition' => s("Conditions de paiement"),
			'discount' => s("Remise commerciale"),
			'shipping' => self::getShippingName(),
			'shippingVatRate' => s("Taux de TVA sur les frais de livraison"),
			'shop' => s("Boutique"),
			'shopDate' => s("Associer à"),
			'shopPointPermissive' => s("Mode de livraison"),
			'comment' => s("Commentaire interne"),
			'productsList' => s("Choisir les produits proposés à la vente"),
		]);

		switch($property) {

			case 'customer' :
				$d->autocompleteBody = function(\util\FormUi $form, Sale $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id'],
						'new' => TRUE
					];
				};
				new CustomerUi()->query($d);
				break;

			case 'shopDate' :
				$d->field = function(\util\FormUi $form, Sale $e) {

					$e->expects(['cShop']);

					$h = '<div class="util-block-gradient">';

					foreach($e['cShop'] as $eShop) {

						if($eShop['cDate']->empty()) {
							continue;
						}

						$h .= '<h5>'.encode($eShop['name']).'</h5>';

						$h .= $form->radios('shopDate', $eShop['cDate'], $e['shopDate'] ?? new \shop\Date(), attributes: [
							'callbackRadioContent' => fn($eDate) => s("Livraison du {value}", \util\DateUi::numeric($eDate['deliveryDate'])),
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

			case 'shopPointPermissive' :
				$d->field = 'select';
				$d->values = fn(Sale $e) => $e['cPoint']->toArray(function($ePoint) {
					return [
						'value' => $ePoint['id'],
						'label' => $ePoint['name'].' / '.\shop\PointUi::p('type')->values[$ePoint['type']]
					];
				}) ?? $e->expects(['cPoint']);
				$d->default = fn(Sale $e) => $e['shopPoint'];
				$d->placeholder = s("Non défini");
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

			case 'shopComment' :
				$d->field = 'textarea';
				$d->attributes = ['data-limit' => Sale::model()->getPropertyRange('shopComment')[1]];
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

					$values = [];

					foreach(SaleUi::getVat($e['farm']) as $position => $text) {
						$rate = \Setting::get('selling\vatRates')[$position];
						$values[(string)$rate] = s("Personnalisé - {value}", $text);
					}

					$defaultVatRate = $e['farm']->getSelling('defaultVatShipping') ? \Setting::get('selling\vatRates')[$e['farm']->getSelling('defaultVatShipping')] : NULL;
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

			case 'orderFormPaymentCondition' :
				$d->placeholder = s("Exemple : Acompte de 20 % à la signature du devis, et solde à la livraison.");
				$d->after = \util\FormUi::info(s("Facultatif, indiquez ici les conditions de paiement après acceptation du devis."));
				break;

			case 'productsList' :
				$d->field = function(\util\FormUi $form, Sale $e) {

					if($e['cProduct']->empty()) {
						return '<div class="util-info">'.s("Aucun produit n'est disponible à la vente.").'</div>';
					}

					return new ItemUi()->getCreateList(
						$e['cProduct'], $e['cCategory'],
						fn($cProduct) => ItemUi::getCreateByCategory($form, $e, $cProduct)
					);
				};
				$d->group = [
					'wrapper' => 'productsList',
					'for' => FALSE
				];
				break;

		}

		return $d;

	}

}
?>
