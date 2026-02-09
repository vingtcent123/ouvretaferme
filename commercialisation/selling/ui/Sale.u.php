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

		$eSale->expects(['id', 'profile', 'priceExcludingVat', 'compositionEndAt']);

		if($eSale->isComposition()) {
			return s("Composition du {value}", \util\DateUi::numeric($eSale['deliveredAt']));
		} else if($eSale['priceExcludingVat'] < 0) {
			return s("Avoir #{value}", $eSale['document']);
		} else {
			return s("Vente #{value}", $eSale['document']);
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
			Sale::EXCLUDING => $eSale['priceExcludingVat'] === NULL ? '' : \util\TextUi::money($eSale['priceExcludingVat']).$taxes,
			Sale::INCLUDING => $eSale['priceIncludingVat'] === NULL ? '' : \util\TextUi::money($eSale['priceIncludingVat']).$taxes
		};

	}

	public static function getAverage(Sale $eSale, bool $displayIncludingTaxes = TRUE): string {

		if($eSale['marketSales'] > 0) {

			if($eSale['taxes'] === Sale::INCLUDING) {
				$taxes = $displayIncludingTaxes ? ' <span class="util-annotation">'.$eSale->getTaxes().'</span>' : '';
			} else {
				$taxes = ' <span class="util-annotation">'.$eSale->getTaxes().'</span>';
			}

			return match($eSale['taxes']) {
				Sale::EXCLUDING => $eSale['priceExcludingVat'] ? \util\TextUi::money($eSale['priceExcludingVat'] / $eSale['marketSales'], precision: 2).$taxes : '',
				Sale::INCLUDING => $eSale['priceIncludingVat'] ? \util\TextUi::money($eSale['priceIncludingVat'] / $eSale['marketSales'], precision: 2).$taxes : ''
			};

		} else {
			return '';
		}

	}

	public static function getIncludingTaxesTotal(Sale|Invoice $eSale): string {

		return $eSale['priceIncludingVat'] ? \util\TextUi::money($eSale['priceIncludingVat']).' <span class="util-annotation">'.SaleUi::p('taxes')->values[Sale::INCLUDING].'</span>' : '-';

	}

	public static function getPanelHeader(Sale $eSale): string {

		return '<div class="panel-header-subtitle">'.self::getName($eSale).'</div>';

	}

	public function getSearch(\Search $search, \Collection $cPaymentMethod): string {

		$h = '<div id="sale-search" class="util-block-search '.($search->empty(['ids']) ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$statuses = SaleUi::p('preparationStatus')->values;
			unset($statuses[Sale::BASKET], $statuses[Sale::SELLING]);

			$paymentMethods = $cPaymentMethod->toArray(fn($ePaymentMethod) => ['label' => $ePaymentMethod['name'], 'value' => $ePaymentMethod['id']]);
			$paymentMethods[] = ['label' => s("Sans moyen de paiement"), 'value' => -1];

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Numéro").'</legend>';
					$h .= $form->text('document', $search->get('document'), ['placeholder' => s("Numéro")]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("État").'</legend>';
					$h .= $form->select('preparationStatus', $statuses, $search->get('preparationStatus'));
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Moyen de paiement").'</legend>';
					$h .= $form->select('paymentMethod', $paymentMethods, $search->get('paymentMethod'));
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Règlement").'</legend>';
					$h .= $form->select('paymentStatus', self::p('paymentStatus')->values, $search->get('paymentStatus'));
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Client").'</legend>';
					$h .= $form->text('customerName', $search->get('customerName'), ['placeholder' => s("Client")]);
				$h .= '</fieldset>';
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Date").'</legend>';
					$h .= $form->month('deliveredAt', $search->get('deliveredAt'));
				$h .= '</fieldset>';

				$h .= '<div class="util-search-submit">';
					$h .= $form->submit(s("Chercher"));
					$h .= '<a href="'.$url.'" class="btn">'.\Asset::icon('x-lg').'</a>';
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
			return '';
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
		$hasAverage = (in_array('average', $show) and $cSale->contains(fn($eSale) => $eSale->isMarket()));
		$hasDocuments = $cSale->contains(fn($eSale) => $eSale->isMarket() === FALSE);

		$previousSubtitle = NULL;
		$previousSegment = NULL;

		$h .= '<table class="tr-even" data-batch="#batch-sale">';

			$h .= '<thead>';

				$h .= '<tr>';

					$h .= '<th class="td-min-content">';
						if($hasSubtitles === FALSE) {
							$h .= '<label title="'.s("Tout cocher / Tout décocher").'">';
								$h .= '<input type="checkbox" class="batch-all" onclick="Sale.toggleSelection(this)"/>';
							$h .= '</label>';
						}
					$h .= '</th>';

					$label = s("Numéro");
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
					if($hasAverage) {
						$h .= '<th class="text-end">'.s("Panier moyen").'</th>';
					}
					if($hasDocuments and in_array('documents', $hide) === FALSE) {
						$h .= '<th class="text-center"  colspan="3">'.s("Documents").'</th>';
						$columns++;
					}
					if(in_array('point', $show)) {
						$h .= '<th>'.s("Mode de livraison").'</th>';
						$columns++;
					}
					if($hasDocuments and in_array('paymentMethod', $hide) === FALSE) {
						$h .= '<th class="'.($dynamicHide['paymentMethod'] ?? 'hide-md-down').'">'.s("Règlement").'</th>';
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
												$h .= '<input type="checkbox" class="batch-all batch-all-group" onclick="Sale.toggleGroupSelection(this)"/>';
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

								if($previousSegment !== NULL) {
									$h .= '</tbody>';
									$h .= '<tbody>';
								}

									$h .= '<tr class="tr-title">';
										$h .= '<th class="td-checkbox">';
											$h .= '<label title="'.s("Cocher ces ventes / Décocher ces ventes").'">';
												$h .= '<input type="checkbox" class="batch-all batch-all-group" onclick="Sale.toggleGroupSelection(this)"/>';
											$h .= '</label>';
										$h .= '</th>';
										$h .= '<td colspan="'.$columns.'">';
											if($eSale['shopPoint']->empty()) {
												$h .= s("Aucun mode de livraison");
											} else if($eSale['shopPoint']['type'] === \shop\Point::HOME) {
												$h .= s("Livraison à domicile");
											} else if($eSale['shopPoint']['type'] === \shop\Point::PLACE) {
												$h .= encode($eSale['shopPoint']['name']);
											}
										$h .= '</td>';
									$h .= '</tr>';
								$h .= '</tbody>';
								$h .= '<tbody>';

							}

							$previousSegment = $currentSegment;

							break;

					}

					$batch = [];

					if(
						$eSale->canWrite() === FALSE or
						$eSale['preparationStatus'] !== Sale::CONFIRMED
					) {
						$batch[] = 'not-prepare';
					}

					if($eSale->canWrite()) {

						if($eSale->acceptStatusConfirmed()) {
							$batch[] = 'accept-confirmed';
						}

						if($eSale->acceptStatusCanceled()) {
							$batch[] = 'accept-canceled';
						}

						if($eSale->acceptStatusDelivered()) {
							$batch[] = 'accept-delivered';
						}

						if($eSale->acceptStatusPrepared()) {
							$batch[] = 'accept-prepared';
						}

						if(
							$eSale->acceptStatusConfirmed() or
							$eSale->acceptStatusCanceled() or
							$eSale->acceptStatusDelivered() or
							$eSale->acceptStatusPrepared()
						) {
							$batch[] = 'accept-status';
						}

						if($eSale->acceptDelete()) {
							$batch[] = 'accept-delete';
						}

						if($eSale->acceptReplacePayment()) {
							$batch[] = 'accept-replace-payment';
						}

						if($eSale->acceptPayPayment()) {
							$batch[] = 'accept-pay-payment';
						}

					}

					$h .= '<tr';
						if(in_array($eSale['preparationStatus'], [Sale::CANCELED, Sale::EXPIRED])) {
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
								$eSale->isMarketSale()
							) {
								$h .= '<span class="btn btn-sm disabled">'.$eSale['document'].'</span>';
							} else {
								$h .= '<a href="'.$link($eSale).'" class="btn btn-sm '.($eSale['deliveredAt'] === currentDate() ? 'btn-primary' : 'btn-outline-primary').'">'.$eSale['document'].'</a>';
							}
						$h .= '</td>';

						if(in_array('customer', $hide) === FALSE) {
							$h .= '<td class="sale-item-name">';
								if($eSale->canRead()) {
									$h .= CustomerUi::link($eSale['customer']);
								} else {
									$h .= encode($eSale['customer']->getName());
								}
								if($eSale->isMarket()) {
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
								$h .= $this->getPreparationStatusForUpdate($eSale, 'btn-xs');
							$h .= '</td>';

						}

						if(in_array('deliveredAt', $hide) === FALSE) {

							$h .= '<td class="sale-item-delivery">';
								$h .= '<div>';
									if($eSale->acceptUpdateDeliveredAt() === FALSE) {
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
									} else if($eSale->isMarketSale()) {
										$h .= '<a href="'.SaleUi::url($eSale['marketParent']).'">'.encode($eSale['marketParent']['customer']->getName()).'</a>';;
									} else if($eSale->isMarket()) {
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

						if($hasAverage) {
							$h .= '<td class="sale-item-price text-end">';
								$h .= SaleUi::getAverage($eSale);
							$h .= '</td>';
						}

						if($hasDocuments and in_array('documents', $hide) === FALSE) {

							if($eSale['preparationStatus'] === Sale::BASKET) {

								$h .= '<td class="sale-item-basket" colspan="3">';
									if($eSale['shopDate']->acceptOrder()) {
										$h .= s("Commande à l'état de panier et non confirmée par le client.");
									} else {
										$h .= s("Commande restée à l'état de panier et non confirmée par le client.");
									}
								$h .= '</td>';

							} else if($eSale['preparationStatus'] === Sale::EXPIRED) {

								$h .= '<td class="sale-item-basket" colspan="3">';
									$h .= s("Commande non confirmée par le client dont le panier a expiré.");
								$h .= '</td>';

							} else {
								$h .= $this->getDocuments($eSale, $eSale['ccPdf'] ?? new \Collection(), 'list');
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
											\shop\Point::HOME => '<a href="'.$eSale->getDeliveryAddressLink().'" target="_blank" class="color-muted">'.$eSale->getDeliveryAddress('html', $eFarm).'</a>',
											\shop\Point::PLACE => encode($eSale['shopPoint']['name'])
										};
									$h .= '</div>';

								}
							$h .= '</td>';

						}

						if($hasDocuments and in_array('paymentMethod', $hide) === FALSE) {

							$h .= '<td class="sale-item-payment-type '.($dynamicHide['paymentMethod'] ?? 'hide-md-down').'">';
								$h .= $this->getPaymentBox($eSale);
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

		$h .= $this->getBatch($eFarm, $cPaymentMethod);

		return $h;

	}

	public function getBatch(\farm\Farm $eFarm, \Collection $cPaymentMethod): string {

		$menu = '<a data-url="/selling/item:summary?farm='.$eFarm['id'].'" class="batch-amount batch-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-item-number"></span>';
				$menu .= ' <span class="batch-item-taxes" data-excluding="'.s("HT").'" data-including="'.s("TTC").'"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Synthèse").'</span>';
		$menu .= '</a>';

		$actions = [Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED, Sale::CANCELED];

		$menu .= '<a data-dropdown="top-start" class="batch-preparation-status batch-item" data-batch-test="accept-status" data-batch-not-contains="hide">';
			$menu .= '<div class="batch-preparation-status-list">';
				foreach($actions as $action) {
					$menu .= '<span class="batch-'.$action.' sale-preparation-status-'.$action.'-button" data-batch-test="accept-'.$action.'" data-batch-not-contains="not-visible"></span>';
				}
			$menu .= '</div>';
			$menu .= '<span>'.s("État").'</span>';
		$menu .= '</a>';

		$menu .= '<div class="dropdown-list bg-secondary">';
			$menu .= '<div class="dropdown-title">'.s("Changer d'état").'</div>';

			foreach($actions as $action) {

				$menu .= '<div data-batch-test="accept-'.$action.'" data-batch-not-contains="hide">';

					if($action === Sale::CANCELED) {
						$menu .= '<div class="dropdown-divider"></div>';
					}

					$confirm = match($action) {
						Sale::CONFIRMED => s("Marquer ces ventes comme confirmées ?"),
						Sale::PREPARED => s("Marquer ces ventes comme préparées ?"),
						Sale::DELIVERED => s("Marquer ces ventes comme livrées ?"),
						Sale::CANCELED => s("Marquer ces ventes comme annulées ?"),
					};

					$menu .= '<a data-ajax="/selling/sale:doUpdate'.ucfirst($action).'Collection" data-batch-test="accept-'.$action.'" data-batch-contains="post" data-confirm="'.$confirm.'" class="dropdown-item batch-'.$action.'">';
						$menu .= '<span class="btn btn-xs sale-preparation-status-'.$action.'-button">'.self::p('preparationStatus')->values[$action].'</span>';
						$menu .= '  <span class="util-badge bg-primary" data-batch-test="accept-'.$action.'" data-batch-contains="count" data-batch-not-contains="hide" data-batch-only="hide"></span></span>';
					$menu .= '</a>';

				$menu .= '</div>';

			}

		$menu .= '</div>';

		$menu .= '<a data-dropdown="top-start" data-batch-test="accept-replace-payment" data-batch-not-contains="hide" class="batch-item">';
			$menu .= \Asset::icon('cash-coin');
			$menu .= '<span>'.s("Règlement").'</span>';
		$menu .= '</a>';

		$menu .= '<div class="dropdown-list dropdown-list-2 bg-secondary">';
			$menu .= '<div class="dropdown-title">'.s("Changer de moyen de paiement").' <span class="batch-item-count util-badge bg-primary" data-batch-test="accept-replace-payment" data-batch-contains="count" data-batch-only="hide"></span></div>';
			foreach($cPaymentMethod as $ePaymentMethod) {
				if($ePaymentMethod->acceptManualUpdate()) {
					$menu .= '<a data-ajax="/selling/sale:doUpdatePaymentNotPaidCollection" data-batch-test="accept-replace-payment" data-batch-contains="post" post-payment-method="'.$ePaymentMethod['id'].'" class="dropdown-item">'.\payment\MethodUi::getName($ePaymentMethod).'</a>';
				}
			}
			$menu .= '<a data-ajax="/selling/sale:doUpdatePaymentNotPaidCollection" data-batch-test="accept-replace-payment" data-batch-contains="post" post-payment-method="" class="dropdown-item" style="grid-column: span 2"><i>'.s("Pas de moyen de paiement").'</i></a>';
			$menu .= '<div class="dropdown-subtitle">'.s("Changer l'état du paiement").' <span class="batch-item-count util-badge bg-primary" data-batch-test="accept-pay-payment" data-batch-always="count" data-batch-only="hide"></span></div>';
			$menu .= '<a data-ajax="/selling/sale:doUpdatePaymentStatusCollection" data-confirm="'.s("Les ventes seront marqués payées au {value}. Voulez-vous continuer ?", currentDate()).'" data-batch-test="accept-pay-payment" data-batch-contains="post" data-batch-not-contains="hide" post-payment-status="'.Sale::PAID.'" class="dropdown-item" data-confirm="'.s("Êtes-vous sûre de vouloir passer ces ventes à l'état payé ? Vous ne pourrez pas facilement revenir en arrière.").'">'.self::getPaymentStatusBadge(Sale::PAID).'</a>';
		$menu .= '</div>';

		$menu .= '<a data-ajax-submit="/selling/sale:doExportCollection" data-ajax-navigation="never" class="batch-item">';
			$menu .= \Asset::icon('file-pdf');
			$menu .= '<span>'.s("Exporter").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-url="/vente/" data-confirm="'.s("Vous allez entrer dans le mode de préparation de commandes. Voulez-vous continuer ?").'" class="batch-prepare batch-item">';
			$menu .= \Asset::icon('person-workspace');
			$menu .= '<span style="letter-spacing: -0.2px">'.s("Préparer<br/>les commandes").'</span>';
		$menu .= '</a>';

		$danger = '<a data-ajax-submit="/selling/sale:doDeleteCollection" data-confirm="'.s("Confirmer la suppression de ces ventes ?").'" data-batch-test="accept-delete" data-batch-not-only="hide" class="batch-item batch-item-danger">';
			$danger .= \Asset::icon('trash');
			$danger .= '<span>'.s("Supprimer").'</span>';
		$danger .= '</a>';

		return \util\BatchUi::group('batch-sale', $menu, $danger, title: s("Pour les ventes sélectionnées"));

	}

	protected function getDocuments(Sale $eSale, \Collection $ccPdf, string $origin): string {

		if($eSale['items'] > 0) {

			$list = [
				$this->getOrderFormDocuments($eSale, $ccPdf[Pdf::ORDER_FORM] ?? new \Collection(), $origin),
				$this->getDeliveryNoteDocument($eSale, $ccPdf->offsetExists(Pdf::DELIVERY_NOTE) ? $ccPdf[Pdf::DELIVERY_NOTE]->first() : new Pdf(), $origin),
				$this->getInvoiceDocument($eSale, $origin)
			];

		} else {
			$list = [NULL, NULL, NULL];
		}

		if($origin === 'list') {

			$h = '';

			foreach($list as $position => $document) {
				$h .= '<td class="text-center td-min-content sale-document-cell sale-document-cell-'.$position.'">'.($document ?? '').'</td>';
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

	protected function getInvoiceDocument(Sale $eSale, string $origin): ?string {

		\Asset::css('selling', 'invoice.css');

		if($eSale->acceptInvoice() === FALSE) {
			return NULL;
		}

		$type = Pdf::INVOICE;

		if($eSale['invoice']->empty()) {

			if(
				$eSale->acceptGenerateInvoice() and
				$eSale->canDocument(Pdf::INVOICE)
			) {

				$document = '<a href="/selling/invoice:create?customer='.$eSale['customer']['id'].'&sales[]='.$eSale['id'].'&origin=sales" class="btn btn-sm sale-document sale-document-new" title="'.s("Créer une facture").'">';
					$document .= '<div class="sale-document-name">'.\selling\SellingSetting::INVOICE.'</div>';
					$document .= '<div class="sale-document-status">';
						$document .= \Asset::icon('plus');
					$document .= '</div>';
				$document .= '</a> ';

				return $document;

			} else {
				return NULL;
			}

		} else {

			$eInvoice = $eSale['invoice'];
			$sales = count($eInvoice['sales']);

			$label = PdfUi::getName($type, $eInvoice);
			if($eInvoice['number'] !== NULL) {
				$label .= ' '.encode($eInvoice['number']);
			}

			$dropdown = match($origin) {
				'list' => 'bottom-end',
				'element' => 'bottom-start',
			};

			$document = '<a class="btn sale-document" title="'.$label.'" data-dropdown="'.$dropdown.'">';
				$document .= '<div class="sale-document-name">';
					$document .= \selling\SellingSetting::INVOICE;
				$document .= '</div>';
				$document .= '<div class="sale-document-status">';

					if($eSale['invoice']['emailedAt']) {
						$document .= \Asset::icon('check-all');
					} else {
						$document .= \Asset::icon('check');
					}

				$document .= '</div>';
				$document .= '<div class="sale-document-count">';
					$document .= $sales;
				$document .= '</div>';
			$document .= '</a> ';

			$document .= '<div class="dropdown-list bg-primary">';
				$document .= '<div class="dropdown-title">';
					$document .= $label;
					$document .= '  <span class="btn btn-sm btn-readonly invoice-status-'.$eInvoice['status'].'-button">'.InvoiceUi::p('status')->values[$eInvoice['status']].'</span>';
					$document .= '<div class="font-sm">';
						$document .= \util\DateUi::numeric($eInvoice['date']).'  ';
					$document .= '</div>';
				$document .= '</div>';

				$document .= '<a href="'.\farm\FarmUi::urlSellingInvoices($eSale['farm']).'?invoice='.$eSale['invoice']['id'].'" data-ajax-navigation="never" class="dropdown-item">'.s("Consulter la facture").'</a>';

				if($eInvoice->acceptDownload()) {
					$document .= '<a href="'.InvoiceUi::url($eSale['invoice']).'" data-ajax-navigation="never" class="dropdown-item">'.s("Télécharger le PDF").'</a>';
				}

				if($eSale['invoice']->acceptSend()) {

					$document .= '<div class="dropdown-divider"></div>';

					if($eSale['invoice']->acceptSend()) {
						$text = s("Envoyer au client par e-mail").'</a>';
					} else {
						$text = '<span class="sale-document-forbidden">'.s("Envoyer au client par e-mail").'</span>';
					}

					$document .= '<a data-ajax="/selling/invoice:doSendCollection" post-ids="'.$eSale['invoice']['id'].'" data-confirm="'.s("Confirmer l'envoi de la facture au client par e-mail ? Une facture envoyée par e-mail n'est plus annulable.").'" class="dropdown-item">'.$text.'</a>';

				}

				if(
					$eInvoice->acceptDelete() and
					$eSale->canWrite()
				) {

					$document .= '<div class="dropdown-divider"></div>';
					$document .= '<a data-ajax="/selling/invoice:doDelete" post-id="'.$eInvoice['id'].'" class="dropdown-item" data-confirm="'.s("La suppression d'une facture est définitive. Voulez-vous continuer ?").'">'.s("Supprimer la facture").'</a>';

				}

			$document .= '</div>';

			return $document;

		}

	}

	protected function getOrderFormDocuments(Sale $eSale, \Collection $cPdf, string $origin): ?string {

		if($eSale->acceptOrderForm() === FALSE) {
			return NULL;
		}

		if($cPdf->empty()) {

			if(
				$eSale->acceptGenerateOrderForm() and
				$eSale->canDocument(Pdf::ORDER_FORM)
			) {

				$h = '<a href="/selling/sale:generateOrderForm?id='.$eSale['id'].'" class="btn btn-sm sale-document sale-document-new" title="'.s("Générer un devis").'">';
					$h .= '<div class="sale-document-name">'.SellingSetting::ORDER_FORM.'</div>';
					$h .= '<div class="sale-document-status">';
						$h .= \Asset::icon('plus');
					$h .= '</div>';
				$h .= '</a> ';

			} else {
				$h = '';
			}

		} else {

			$ePdfLast = $cPdf->first();

			$consistency = (
				$eSale['preparationStatus'] !== Sale::DRAFT or
				$eSale['crc32'] === NULL or  // Historique
				$ePdfLast['crc32'] === NULL or // Historique
				$eSale['crc32'] === $ePdfLast['crc32']
			);

			$dropdown = match($origin) {
				'list' => 'bottom-end',
				'element' => 'bottom-start',
			};

			$h = '<a class="btn sale-document '.($consistency ? '' : 'sale-document-inconsistency').'" title="'.s("Devis").'" data-dropdown="'.$dropdown.'">';
				$h .= '<div class="sale-document-name">'.SellingSetting::ORDER_FORM.'</div>';
				$h .= '<div class="sale-document-status">';

					if($ePdfLast['emailedAt']) {
						$h .= \Asset::icon('check-all');
					} else {
						$h .= \Asset::icon('check');
					}

				$h .= '</div>';
			$h .= '</a> ';

			$h .= '<div class="dropdown-list bg-primary">';
				$h .= '<div class="dropdown-title">';
					$h .= s("Devis");
					if($ePdfLast['emailedAt']) {
						$h .= ' <span class="btn btn-sm btn-readonly btn-success">'.s("Envoyé").'</span>';
					}
					$h .= '<div class="font-sm">';
						$h .= \util\DateUi::numeric($eSale['createdAt'], \util\DateUi::DATE);
						if($ePdfLast['version'] > 1) {
							$h .= ' '.s("| Version {value}", $ePdfLast['version']);
						}
					$h .= '</div>';
					if($consistency === FALSE) {
						$h .= '<div class="util-block-danger mt-1" style="max-width: 20rem">'.s("La vente a été modifiée et le devis n'est plus à jour. Vous devriez en générer une nouvelle version.").'</div>';
					}
				$h .= '</div>';

				$h .= $this->getDownloadDocument($eSale, $ePdfLast);

				if($eSale->acceptGenerateOrderForm()) {
					$h .= '<a href="/selling/sale:generateOrderForm?id='.$eSale['id'].'" class="dropdown-item">'.s("Générer une nouvelle version").'</a>';
				}

				$cPdfOther = $cPdf->slice(1);

				if($cPdfOther->notEmpty()) {
					$h .= '<div class="dropdown-divider"></div>';
					$h .= '<div class="dropdown-subtitle">'.s("Anciennes versions").'</div>';
				}

				foreach($cPdfOther as $ePdf) {

					$label = s("Version {value}", $ePdf['version']);

					if($ePdf['content']->notEmpty()) {
						$h .= '<a href="'.PdfUi::url($ePdf).'" data-ajax-navigation="never" class="dropdown-item">'.$label.'</a>';
					} else {
						$h .= '<span class="dropdown-item">';
							$h .= '<span class="sale-document-forbidden">'.$label.'</span>';
							$h .= ' <span class="sale-document-expired">'.s("Document expiré").'</span>';
						$h .= '</span>';
					}

				}

			$h .= '</div>';

		}

		return $h;

	}

	protected function getDeliveryNoteDocument(Sale $eSale, Pdf $ePdf, string $origin): ?string {

		if($eSale->acceptDeliveryNote() === FALSE) {
			return NULL;
		}

		if($ePdf->empty()) {

			if(
				$eSale->acceptGenerateDeliveryNote() and
				$eSale->canDocument(Pdf::DELIVERY_NOTE)
			) {

				$h = '<a href="/selling/sale:generateDeliveryNote?id='.$eSale['id'].'" class="btn btn-sm sale-document sale-document-new" title="'.s("Générer le bon de livraison").'">';
					$h .= '<div class="sale-document-name">'.s("BL").'</div>';
					$h .= '<div class="sale-document-status">';
						$h .= \Asset::icon('plus');
					$h .= '</div>';
				$h .= '</a> ';

			} else {
				$h = '';
			}

		} else {

			$dropdown = match($origin) {
				'list' => 'bottom-end',
				'element' => 'bottom-start',
			};

			$consistency = (
				$eSale['crc32'] === NULL or  // Historique
				$ePdf['crc32'] === NULL or // Historique
				$eSale['crc32'] === $ePdf['crc32']
			);

			$h = '<a class="btn sale-document '.($consistency ? '' : 'sale-document-inconsistency').'" title="'.s("Bon de livraison").'" data-dropdown="'.$dropdown.'">';
				$h .= '<div class="sale-document-name">'.SellingSetting::DELIVERY_NOTE.'</div>';
				$h .= '<div class="sale-document-status">';

					if($ePdf['emailedAt']) {
						$h .= \Asset::icon('check-all');
					} else {
						$h .= \Asset::icon('check');
					}

				$h .= '</div>';
			$h .= '</a> ';

			$h .= '<div class="dropdown-list bg-primary">';
				$h .= '<div class="dropdown-title">';
					$h .= s("Bon de livraison");
					if($ePdf['emailedAt']) {
						$h .= ' <span class="btn btn-sm btn-readonly btn-success">'.s("Envoyé").'</span>';
					}
					if($eSale['deliveryNoteDate'] !== NULL) {
						$h .= '<div class="font-sm">'.\util\DateUi::numeric($eSale['deliveryNoteDate']).'</div>';
					}
					if($consistency === FALSE) {
						$h .= '<div class="util-block-danger mt-1" style="max-width: 20rem">'.s("La vente a été modifiée et le bon de livraison n'est plus à jour. Vous devriez en générer une nouvelle version.").'</div>';
					}
				$h .= '</div>';

				$h .= $this->getDownloadDocument($eSale, $ePdf);

				if($eSale->canDocument(Pdf::DELIVERY_NOTE)) {

					if($eSale->acceptGenerateDeliveryNote()) {
						$h .= '<a href="/selling/sale:generateDeliveryNote?id='.$eSale['id'].'" class="dropdown-item">'.s("Générer une nouvelle version").'</a>';
					}

					if($eSale->canManage()) {

						$h .= '<div class="dropdown-divider"></div>';
						$h .= ' <a data-ajax="/selling/sale:doDeleteDocument" post-id="'.$ePdf['id'].'" data-confirm="'.s("Voulez-vous vraiment supprimer ce bon de livraison ?").'" class="dropdown-item">'.s("Supprimer").'</a>';

					}

				$h .= '</div>';

			}

		}

		return $h;

	}

	public function getDownloadDocument(Sale $eSale, Pdf $ePdf): string {

		$h = '';

		if($ePdf->acceptSend()) {
			$h .= '<a data-ajax="/selling/sale:doSendDocument" post-id="'.$eSale['id'].'" post-type="'.$ePdf['type'].'" data-confirm="'.s("Confirmer l'envoi du bon de livraison au client par e-mail ?").'" class="dropdown-item">'.s("Envoyer au client par e-mail").'</a>';
		}

		if($ePdf['content']->notEmpty()) {
			$h .= '<a href="'.PdfUi::url($ePdf).'" data-ajax-navigation="never" class="dropdown-item">'.s("Télécharger").'</a>';
		} else {
			$h .= '<span class="dropdown-item">';
				$h .= '<span class="sale-document-forbidden">'.s("Télécharger").'</span>';
				$h .= ' <span class="sale-document-expired">'.s("Document expiré").'</span>';
			$h .= '</span>';
		}

		return $h;

	}

	public function getPreparationStatusForUpdate(Sale $eSale, string $btn = ''): string {

		if(
			$eSale->canWrite() === FALSE or
			$eSale->isMarketSale()
		) {
			return '<span class="btn btn-readonly '.$btn.' sale-preparation-status-'.$eSale['preparationStatus'].'-button">'.self::p('preparationStatus')->values[$eSale['preparationStatus']].'</span>';
		}

		if($eSale['closed']) {
			return '<span class="btn btn-readonly '.$btn.' sale-preparation-status-closed-button" title="'.s("Il n'est pas possible de modifier une vente clôturée.").'">'.s("Clôturé").'  '.\Asset::icon('lock-fill').'</span>';
		}

		if($eSale->acceptUpdatePreparationStatus() === FALSE) {

			if($eSale['invoice']->notEmpty()) {
				$label = s("Il n'est pas possible de modifier une vente facturée.");
			} else if($eSale->isReadonly()) {
				$label = s("Il n'est pas possible de modifier une vente livrée il y a plus d'un an.");
			} else {
				$label = s("Il sera possible de modifier le statut lorsque la période de prise des commandes sera close.");
			}

			return '<span class="btn btn-readonly '.$btn.' sale-preparation-status-'.$eSale['preparationStatus'].'-button" title="'.$label.'">'.self::p('preparationStatus')->values[$eSale['preparationStatus']].'  '.\Asset::icon('lock-fill').'</span>';
		}

		$button = function(string $preparationStatus, ?string $confirm = NULL) use ($eSale) {

			$h = '<a data-ajax="/selling/sale:doUpdate'.ucfirst($preparationStatus).'Collection" post-ids="'.$eSale['id'].'" class="dropdown-item" '.($confirm ? attr('data-confirm', $confirm) : '').'>';
				$h .= \Asset::icon('arrow-right').'  <span class="btn btn-sm sale-preparation-status-'.$preparationStatus.'-button">'.self::p('preparationStatus')->values[$preparationStatus].'</span>';
			$h .= '</a>';

			return $h;

		};

		$link = function(string $to) use ($eSale, $btn) {

			if($to) {

				$h = '<a data-dropdown="bottom-start" data-dropdown-id="sale-dropdown-'.$eSale['id'].'" data-dropdown-hover="true" class="btn '.$btn.' sale-preparation-status-'.$eSale['preparationStatus'].'-button dropdown-toggle">'.self::p('preparationStatus')->values[$eSale['preparationStatus']].'</a>';
				$h .= '<div data-dropdown-id="sale-dropdown-'.$eSale['id'].'-list" class="dropdown-list bg-primary">';
					$h .= $to;
				$h .= '</div>';

			} else {

				$h = '<span class="btn btn-readonly '.$btn.' sale-preparation-status-'.$eSale['preparationStatus'].'-button">'.self::p('preparationStatus')->values[$eSale['preparationStatus']].'</a>';

			}

			return $h;

		};

		$h = '';

		if($eSale->isMarket()) {

			switch($eSale['preparationStatus']) {

				case Sale::CANCELED :
					$h = $link(
						$button(Sale::CONFIRMED)
					);
					break;

				case Sale::DRAFT :
					$h = $link(
						$button(Sale::CONFIRMED)
					);
					break;

				case Sale::CONFIRMED :
					$to = $button(Sale::SELLING, s("Vous allez commencer votre vente avec le logiciel de caisse ! Les quantités des produits que vous avez saisies pour préparer cette vente seront remises à zéro et vous pourrez commencer à enregistrer les commandes des clients. C'est parti ?"));
					if($eSale->acceptStatusCanceled()) {
						$to .= '<div class="dropdown-divider"></div>';
						$to .= $button(Sale::CANCELED);
					}
					$h = $link($to);
					break;

				case Sale::SELLING :
					$h = '<span class="btn btn-readonly '.$btn.' sale-preparation-status-'.$eSale['preparationStatus'].'-button">'.self::p('preparationStatus')->values[$eSale['preparationStatus']].'</span>';
					$h .= ' '.\Asset::icon('arrow-right').' <a href="'.SaleUi::urlMarket($eSale).'" class="btn '.$btn.' sale-preparation-status-'.$eSale['preparationStatus'].'">'.\Asset::icon('cart4').' '.s("Caisse").'</a>';
					break;

			};

		} else {

			$to = '';

			if($eSale->acceptStatusDelivered()) {
				$to .= $button(Sale::DELIVERED);
			}

			if($eSale->acceptStatusConfirmed()) {
				$to .= $button(Sale::CONFIRMED);
			}

			if($eSale->acceptStatusPrepared()) {
				$to .= $button(Sale::PREPARED);
			}

			if($eSale->acceptStatusDraft()) {
				$to .= $button(Sale::DRAFT);
			}

			if($eSale->acceptStatusCanceled()) {
				$to .= '<div class="dropdown-divider"></div>';
				$to .= $button(Sale::CANCELED);
			}

			if($eSale->acceptDelete()) {
				$to .= '<div class="dropdown-divider"></div>';
				$to .= '<a data-ajax="/selling/sale:doDelete" post-id="'.$eSale['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression de la vente ?").'">'.s("Supprimer la vente").'</a>';
			}

			$h .= $link($to);

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

		}

	}

	public static function getPaymentStatus(Sale $eSale): string {

		if($eSale['paymentStatus'] !== NULL) {
			return self::getPaymentStatusBadge($eSale['paymentStatus'], $eSale['paidAt']);
		} else {
			return '';
		}

	}

	public static function getPaymentStatusBadge(string $status, ?string $paidAt = NULL): string {

		$label = self::p('paymentStatus')->values[$status];

		$h = '<span class="util-badge sale-payment-status sale-payment-status-'.$status.'">';

			if($paidAt !== NULL) {
				$h .= s("{status} le {date}", ['status' => $label, 'date' => \util\DateUi::numeric($paidAt)]);
			} else {
				$h .= $label;
			}

		$h .= '</span>';

		return $h;

	}

	public static function getPaymentStatusForCustomer(Sale $eSale, bool $withColors = FALSE): string {

		switch($eSale['paymentStatus']) {

			case Sale::NOT_PAID :
				$color = '--text';
				$text = s("Non payé");
				break;

			case Sale::PAID :
				$color = '--success';
				$text = \Asset::icon('check').' '.s("Payé");
				break;

		};

		if($withColors) {
			return '<span style="color: var('.$color.')">'.$text.'</span>';
		} else {
			return $text;
		}

	}

	public function getLabels(\farm\Farm $eFarm, \Collection $cSale): string {

		$h = '<h3>'.s("Générer les étiquettes des ventes aux professionnels en cours").'</h3>';

		if($cSale->empty()) {
			$h .= '<div class="util-empty">'.s("Il n'y a aucune vente confirmée ou préparée.").'</div>';
		} else {

			$form = new \util\FormUi();

			$h .= $form->open(NULL, ['action' => '/selling/sale:downloadLabels']);

				$h .= $form->hidden('id', $eFarm['id']);
				$h .= $form->hidden('checkSales', TRUE);

				$h .= '<div class="util-overflow-xs">';
					$h .= '<table class="tr-even">';
						$h .= '<thead>';
							$h .= '<tr>';
								$h .= '<th></th>';
								$h .= '<th class="text-center">'.s("Vente").'</th>';
								$h .= '<th>'.s("Client").'</th>';
								$h .= '<th class="text-center">'.s("Date").'</th>';
								$h .= '<th class="text-center">'.s("Montant").'</th>';
								$h .= '<th class="text-center hide-sm-down">'.s("Articles").'</th>';
							$h .= '</tr>';
						$h .= '</thead>';

						$h .= '<tbody>';

							foreach($cSale as $eSale) {

								$h .= '<tr>';
									$h .= '<td class="td-min-content selling-label-checkbox">'.$form->inputCheckbox('sales[]', $eSale['id']).'</td>';
									$h .= '<td class="td-min-content">'.SaleUi::link($eSale).'</td>';
									$h .= '<td>';
										$h .= CustomerUi::getCircle($eSale['customer']).' '.CustomerUi::link($eSale['customer']);
									$h .= '</td>';
									$h .= '<td class="text-center">'.\util\DateUi::numeric($eSale['deliveredAt']).'</td>';
									$h .= '<td class="text-center">';
										if($eSale['priceExcludingVat'] !== NULL) {
											$h .= \util\TextUi::money($eSale['priceExcludingVat']).' '.$eSale->getTaxes();
										}
									$h .= '</td>';
									$h .= '<td class="text-center hide-sm-down">'.$eSale['items'].'</td>';
								$h .= '</tr>';

							}

					$h .= '</tbody>';

				$h .= '</table>';
			$h .= '</div>';

			$h .= $form->submit(s("Générer les étiquettes"));

			$h .= $form->close();

			$h .= '<br/>';

		}

		$h .= '<h3>'.s("Modèle des étiquettes").'</h3>';

		$h .= '<div class="selling-label-example">';
			$h .= new PdfUi()->getLabel($eFarm, new Customer(), $eFarm['quality']);
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

		$h = '<div class="util-title">';
			$h .= '<div>';
				if($eSale->isComposition() === FALSE) {
					$h .= '<h1 style="margin-bottom: 0.5rem">'.SaleUi::getName($eSale).'  '.$this->getPreparationStatusForUpdate($eSale).'</h1>';
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

	public static function getPaymentMethodName(Sale $eSale): ?string {

		$eSale->expects(['cPayment']);

		$cPayment = $eSale['cPayment'];

		$paymentList = [];

		foreach($cPayment as $ePayment) {

			$payment = '';
			if($ePayment['accountingHash'] !== NULL) {
				$payment .= ' <a href="'.\farm\FarmUi::urlConnected($eSale['farm']).'/journal/livre-journal?hash='.$ePayment['accountingHash'].'" class="util-badge bg-accounting" title="'.("Intégré dans le livre-journal").'">';
					$payment .= \Asset::icon('journal-bookmark');
				$payment .= '</a> ';
			}
			$payment .= \payment\MethodUi::getName($ePayment['method']);

			if(
				$cPayment->count() > 1 or
				($ePayment['status'] === Payment::PAID and $eSale['paymentStatus'] === Sale::PARTIAL_PAID)
			) {
				$payment .= ' <span class="color-muted font-sm">'.\util\TextUi::money($ePayment['amountIncludingVat']).'</span>';
			}

			$paymentList[] = $payment;
		}

		return implode('<br />', $paymentList);

	}

	public function getContent(Sale $eSale, \Collection $cPdf): string {

		if($eSale->isComposition()) {
			return '';
		}

		$h = $this->getPresentation($eSale, $cPdf);

		if(
			(
				($eSale->isMarket() and $eSale->isMarketPreparing() === FALSE) or
				($eSale->isMarket() === FALSE and $eSale['items'] > 0)
			)
		) {
			$h .= $this->getSummary($eSale);
		}

		return $h;

	}

	public function getPresentation(Sale $eSale, \Collection $cPdf): string {

		$h = '<div class="sale-presentation util-block stick-xs">';
			$h .= '<dl class="util-presentation util-presentation-2">';
				$h .= '<dt>'.s("Client").'</dt>';
				$h .= '<dd>'.CustomerUi::link($eSale['customer']).'</dd>';


				$h .= '<dt>'.s("Date de vente").'</dt>';
				$h .= '<dd>';

				$update = fn($content) => $eSale->acceptUpdateDeliveredAt() ? $eSale->quick('deliveredAt', $content) : $content;

				$h .= $update($eSale['deliveredAt'] ? \util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE) : s("Non planifié"));
				$h .= '</dd>';

				if($eSale->isMarket() === FALSE) {
					$h .= '<dt>'.s("Moyen de paiement").'</dt>';
					$h .= '<dd>';
						$h .= self::getPaymentBox($eSale, optimize: TRUE);
					$h .= '</dd>';
				}

				if($eSale['shop']->notEmpty()) {

					$h .= '<dt>'.s("Boutique").'</dt>';
					$h .= '<dd>';
						if($eSale['shopDate']->notEmpty()) {
							$h .= '<a href="'.\shop\ShopUi::adminDateUrl($eSale['farm'], $eSale['shopDate']).'">'.encode($eSale['shop']['name']).'</a>';
						} else {
							$h .= '<a href="'.\shop\ShopUi::adminUrl($eSale['farm'], $eSale['shop']).'">'.encode($eSale['shop']['name']).'</a>';
						}
					$h .= '</dd>';

					$h .= '<dt>'.s("Mode de livraison").'</dt>';
					$h .= '<dd>';
						if($eSale['shopPoint']->notEmpty()) {
							$h .= \shop\PointUi::p('type')->values[$eSale['shopPoint']['type']];
							$h .= '<div class="sale-display-address">';
								$h .= match($eSale['shopPoint']['type']) {
									\shop\Point::HOME => '<a href="'.$eSale->getDeliveryAddressLink().'" target="_blank" class="color-muted">'.$eSale->getDeliveryAddress('html', $eSale['farm']).'</a>',
									\shop\Point::PLACE => encode($eSale['shopPoint']['name'])
								};
							$h .= '</div>';
						}
					$h .= '</dd>';

				}

				if($eSale->acceptAnyDocument()) {

					$h .= '<dt>'.s("Documents").'</dt>';
					$h .= '<dd>';
						$h .= $this->getDocuments($eSale, $cPdf, 'element');
					$h .= '</dd>';

				}

				if($eSale->isMarket()) {

					$h .= '<dt>'.s("Logiciel de caisse").'</dt>';
					$h .= '<dd>';
						$h .=  \util\TextUi::switch([
							'disabled' => TRUE,
						], TRUE, s("Activé"));
					$h .= '</dd>';
			}

			$h .= '</dl>';

		$h .= '</div>';

		return $h;

	}

	public function getStats(Sale $eSale): string {

		$h = '';

		if($eSale['closed']) {
			$h .= '<div class="sale-closed">';
				$h .= \Asset::icon('lock-fill').' ';
				if($eSale->isMarket()) {
					$h .= s("Cette vente est clôturée et n'est plus modifiable, mais vous pouvez toujours <link>consulter le logiciel de caisse</link> en lecture seule.", ['link' => '<a href="'.SaleUi::urlMarket($eSale).'">']);
				} else {
					$h .= s("Cette vente est clôturée, elle n'est plus modifiable.");
				}
			$h .= '</div>';
		}

		if(
			$eSale->isMarket() and
			$eSale->canWrite()
		) {
			if($eSale['preparationStatus'] === Sale::CONFIRMED) {

				$h .= '<div class="util-block color-white bg-selling mb-2">';
					$h .= '<h4>'.s("Votre vente va démarrer ?").'</h4>';
					$h .= '<p>'.s("Vous pouvez commencer à prendre les commandes avec la caisse virtuelle !").'<br/>'.s("Les quantités des produits que vous avez saisies pour préparer cette vente seront remises à zéro.").'</p>';
					$h .= '<a data-ajax="/selling/sale:doUpdateSellingCollection" post-ids="'.$eSale['id'].'" class="btn btn-transparent" data-confirm="'.s("C'est parti ?").'">'.\Asset::icon('cart4').'  '.s("Ouvrir le logiciel de caisse").'</a>';
				$h .= '</div>';

			} else if($eSale['preparationStatus'] === Sale::SELLING) {
				$h .= '<a href="'.SaleUi::urlMarket($eSale).'" class="btn btn-xl btn-selling mb-2" style="width: 100%">'.\Asset::icon('cart4').'  '.s("Ouvrir le logiciel de caisse").'</a>';
			}
		}

		return $h;

	}

	public function getSummary(Sale $eSale): string {

		$h = '<table class="tr-bordered sale-summary">';

			if($eSale['discount'] > 0) {

				$discountAmount = -1 * ($eSale['priceGross'] - $eSale['price']);
				$taxes = $eSale->getTaxes();

				$h .= '<tr>';
					$h .= '<td>'.s("Montant total avant remise {taxes}", ['taxes' => $taxes]).'</td>';
					$h .= '<td class="sale-summary-value sale-summary-value-highlight">'.\util\TextUi::money($eSale['priceGross'] ?? 0).'</td>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<td>'.s("Remise <i>- {value} %</i>", $eSale['discount']).'</td>';
					$h .= '<td class="sale-summary-value">'.\util\TextUi::money($discountAmount).'</td>';
				$h .= '</tr>';

			}

			if($eSale['hasVat']) {

				switch($eSale['taxes']) {

					case Sale::INCLUDING :

						$h .= '<tr>';
							$h .= '<td>'.s("Montant total TTC").'</td>';
							$h .= '<td class="sale-summary-value sale-summary-value-highlight">'.\util\TextUi::money($eSale['priceIncludingVat'] ?? 0).'</td>';
						$h .= '</tr>';

						$h .= '<tr class="color-muted"">';
							$h .= '<td style="padding-left: 2rem">'.s("Dont TVA").'</td>';
							$h .= '<td class="sale-summary-value">'.\util\TextUi::money($eSale['vat'] ?? 0).'</td>';
						$h .= '</tr>';

						$h .= '<tr class="color-muted">';
							$h .= '<td style="padding-left: 2rem">'.s("Dont montant HT").'</td>';
							$h .= '<td class="sale-summary-value">'.\util\TextUi::money($eSale['priceExcludingVat'] ?? 0).'</td>';
						$h .= '</tr>';

						break;

					case Sale::EXCLUDING :

						$h .= '<tr>';
							$h .= '<td>'.s("Montant total HT").'</td>';
							$h .= '<td class="sale-summary-value sale-summary-value-highlight">'.\util\TextUi::money($eSale['priceExcludingVat'] ?? 0).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td>'.s("TVA").'</td>';
							$h .= '<td class="sale-summary-value">'.\util\TextUi::money($eSale['vat'] ?? 0).'</td>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<td>'.s("Montant total TTC").'</td>';
							$h .= '<td class="sale-summary-value">'.\util\TextUi::money($eSale['priceIncludingVat'] ?? 0).'</td>';
						$h .= '</tr>';

						break;

				}

			} else {

				$h .= '<tr>';
					$h .= '<td>'.s("Montant total").'</td>';
					$h .= '<td class="sale-summary-value sale-summary-value-highlight">'.\util\TextUi::money($eSale['priceIncludingVat'] ?? 0).'</td>';
				$h .= '</tr>';

			}

		$h .= '</table>';

		return $h;

	}

	public function getUpdate(Sale $eSale, string $btn): string {

		$primaryList = '';

		if($eSale->canUpdate()) {
			$primaryList .= '<a href="/selling/sale:update?id='.$eSale['id'].'" class="dropdown-item">';
				$primaryList .= match($eSale->isComposition()) {
					TRUE => s("Modifier la composition"),
					FALSE => $eSale['closed'] ? s("Commenter la vente") : s("Modifier la vente"),
				};
			$primaryList .= '</a>';
		}

		if($eSale->acceptUpdatePayment()) {
			$primaryList .= '<a href="/selling/sale:updatePayment?id='.$eSale['id'].'" class="dropdown-item">';
				$primaryList .= $eSale['cPayment']->empty() ? s("Choisir le règlement") : s("Changer le règlement");
			$primaryList .= '</a>';
		}

		$secondaryList = '';

		if($eSale->acceptAssociateShop()) {
			$secondaryList .= '<a href="/selling/sale:updateShop?id='.$eSale['id'].'" class="dropdown-item">'.s("Associer la vente à une boutique").'</a>';
		}

		if($eSale->acceptDissociateShop()) {
			$secondaryList .= '<a data-ajax="/selling/sale:doDissociateShop" post-id="'.$eSale['id'].'" class="dropdown-item">'.s("Dissocier la vente de la boutique").'</a>';
		}

		if(
			$eSale->acceptDelete() and
			$eSale->canDelete()
		) {

			$confirm = $eSale->isComposition() ?
				s("Confirmer la suppression de la composition ? Les éventuelles ventes qui utilisent cette composition ne seront pas modifiées.") :
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

			if($secondaryList) {

				$h .= '<div class="dropdown-divider"></div>';
				$h .= $secondaryList;

			}

		$h .= '</div>';

		return $h;

	}

	public function getMarket(Sale $eSaleMarket, \farm\Farm $eFarm, \Collection $ccSale, \Collection $cPaymentMethod) {

		if($ccSale->empty()) {
			return '';
		}

		$h = '';

		foreach($ccSale as $cSale) {

			if($cSale->empty()) {
				continue;
			}

			$preparationStatus = $cSale->first()['preparationStatus'];

			$h .= '<h3>';
				$h .= match($preparationStatus) {
					\selling\Sale::DELIVERED => s("Ventes terminées"),
					\selling\Sale::DRAFT => s("Ventes en cours"),
					\selling\Sale::CANCELED => s("Ventes annulées")
				};
				$h .= '  <span class="util-badge bg-primary">'.$cSale->count().'</span>';
			$h .= '</h3>';

			if($preparationStatus === Sale::DELIVERED) {
				$h .= $this->getPaymentMethods($eSaleMarket, $cSale, $cPaymentMethod);
			}

			$h .= $this->getList($eFarm, $cSale, hide: ['deliveredAt', 'actions', 'documents'], show: ['createdAt'], cPaymentMethod: $cPaymentMethod);

		}

		$h .= '<br/>';

		return $h;

	}

	protected function getPaymentMethods(Sale $eSaleMarket, \Collection $cSale, \Collection $cPaymentMethod): string {

		$methods = [];

		$cSale->map(function($eSale) use(&$methods) {

			foreach($eSale['cPayment'] as $ePayment) {

				$methods[$ePayment['method']['id']] ??= [
					'sales' => 0,
					'amountIncludingVat' => 0.0
				];
				$methods[$ePayment['method']['id']]['sales']++;
				$methods[$ePayment['method']['id']]['amountIncludingVat'] += $ePayment['amountIncludingVat'];

			}

		});

		$h = '<ul class="util-summarize">';

			foreach($methods as $method => ['sales' => $sales, 'amountIncludingVat' => $amountIncludingVat]) {

				$h .= '<li>';

					$h .= '<h5>'.encode($cPaymentMethod[$method]['name']).'</h5>';
					$h .= '<div>'.\util\TextUi::money($amountIncludingVat).'</div>';
					$h .= '<div class="util-summarize-muted">'.p("{value} vente", "{value} ventes", $sales).'</div>';

				$h .= '</li>';

			}

		$h .= '</ul>';

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

		$h .= '<div class="util-overflow-sm stick-xs">';

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

		if($eSale['profile'] === Sale::COMPOSITION) {
			return $this->createComposition($eSale);
		} else {
			return $this->createCustomer($eSale);
		}

	}

	public function createComposition(Sale $eSale): \Panel {

		$eSale->expects(['farm']);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->asteriskInfo();

		$h .= $form->hidden('farm', $eSale['farm']['id']);
		$h .= $form->hidden('compositionOf', $eSale['compositionOf']['id']);

		$h .= $form->dynamicGroup($eSale, 'deliveredAt');

		$arguments = ['product' => '<u>'.encode($eSale['compositionOf']['name']).'</u>', 'price' => '<b>'.\util\TextUi::money($eSale['compositionOf'][$eSale['type'].'Price']).'</b>'];

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
			dialogOpen: $form->openAjax('/selling/sale:doCreate', ['id' => 'sale-create', 'class' => 'panel-dialog']),
			dialogClose: $form->close(),
			body: $h,
			footer: $footer
		);

	}

	public function createCustomer(Sale $eSale): \Panel {

		$eSale->expects([
			'farm' => ['hasSales'],
			'shopDate', 'nGrid'
		]);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->asteriskInfo();

		$h .= $form->hidden('farm', $eSale['farm']['id']);
		if($eSale['shopDate']->notEmpty()) {
			$h .= $form->hidden('shopDate', $eSale['shopDate']['id']);
		}
		$h .= $form->hidden('market', $eSale->isMarket());

		$h .= $form->dynamicGroup($eSale, 'customer*', function($d) use($form, $eSale) {

			$d->autocompleteDispatch = '#sale-create';

			if($eSale['shopDate']->notEmpty()) {

				$d->autocompleteBody = [
					'type' => $eSale['shopDate']['type']
				] + ($d->autocompleteBody)($form, $eSale);

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
					$form->fake(\util\DateUi::numeric($eSale['shopDate']['deliveryDate'] ?? currentDate()))
				);
			}

			$h .= $this->getPreparationStatusField($form, $eSale);

			if($eSale['cProduct']->notEmpty()) {

				$h .= '<h3 class="mt-2">'.s("Ajouter des produits à la vente").'</h3>';

				if($eSale['nGrid'] > 0) {
					$h .= '<div class="util-info">';
						$h .= \Asset::icon('info-circle').' '.s("Les prix indiqués tiennent compte des prix personnalisés qui ont été trouvés pour ce client");
					$h .= '</div>';
				}

				if(
					$eSale['shopProducts'] and
					$eSale['cProduct']->contains(fn($eProduct) => $eProduct['shopProduct']['available'] !== NULL)
				) {
					$h .= '<div class="util-info">';
						$h .= \Asset::icon('exclamation-circle').' '.s("La création de cette vente n'imputera pas les quantités disponibles à la vente des produits proposés en quantités limitées.");
					$h .= '</div>';
				}

				$h .= $form->dynamicField($eSale, 'productsList');

				$footer = ItemUi::getCreateSubmit($eSale, $form, s("Créer la vente"));

			} else {

				$footer = $form->submit(s("Créer la vente"), ['class' => 'btn btn-primary btn-lg']);
			}

		} else {
			$footer = NULL;
		}

		if($eSale['farm']['hasSales'] === FALSE) {
			$h .= $this->getWarning();
		}

		return new \Panel(
			id: 'panel-sale-create',
			title: s("Ajouter une vente"),
			dialogOpen: $form->openAjax('/selling/sale:doCreate', ['id' => 'sale-create', 'class' => 'panel-dialog']),
			dialogClose: $form->close(),
			body: $h,
			footer: $footer
		);

	}

	public function getWarning(bool $temporary = FALSE): string {

		$h = '<div class="util-block-info mt-2">';
			$h .= '<h3>'.s("Vous souhaitez créer des ventes pour tester {siteName} ?").'</h3>';
			$h .= '<p>'.s("N'utilisez pas votre compte principal, car vous ne pouvez pas librement supprimer les ventes que vous renseignez dans le logiciel pour des contraintes réglementaires. Si vous souhaitez tester les fonctionnalités de commercialisation de Ouvretaferme, nous vous suggérons de :").'</p>';
			$h .= '<ul>';
				$h .= '<li>'.s("Créer une ferme fictive sur votre compte").'</li>';
				$h .= '<li>'.s("Utiliser la ferme de démonstration").'</li>';
			$h .= '</ul>';
			$h .= '<p>';
				$h .= '<a href="/farm/farm:create" target="_blank" class="btn btn-transparent">'.s("Créer une autre ferme").'</a> ';
				$h .= '<a href="'.OTF_DEMO_URL.'/ferme/1/ventes" target="_blank" class="btn btn-transparent">'.s("Utiliser la démo").'</a>';
			$h .= '</p>';
			if($temporary) {
				$h .= '<p>'.s("Cet écran d'alerte disparaitra lorsque vous aurez réalisé votre 5<sup>ème</sup> vente sur {siteName} !").'</p>';
			}
		$h .= '</div>';

		return $h;

	}

	public function createCollection(Sale $eSale, \Collection $cCustomerGroup): \Panel {

		$eSale->expects(['farm']);

		$form = new \util\FormUi();

		if($eSale['customer']->empty()) {
			$eSale['customers'] = new \Collection();
		} else {
			$eSale['customers'] = new \Collection([$eSale['customer']]);
		}

		$h = '';

		$h .= $form->asteriskInfo();

		$h .= $form->hidden('farm', $eSale['farm']['id']);

		if($eSale['shopDate']->notEmpty()) {
			$h .= $form->hidden('shopDate', $eSale['shopDate']['id']);
		}

		if($eSale['cCustomer']->notEmpty()) {

			$formId = 'sale-create-collection';

			$h .= $form->dynamicGroup($eSale, 'customers*', function($d) {

				$d->autocompleteDispatch = '#sale-create-collection';

			});

			$h .= $form->dynamicGroup($eSale, 'deliveredAt');
			$h .= $this->getPreparationStatusField($form, $eSale);

			if($eSale['cProduct']->notEmpty()) {

				$h .= '<h3 class="mt-2">'.s("Ajouter des produits à la vente").'</h3>';

				if($eSale['nGrid'] > 0) {

					$h .= '<div class="util-info">';
						$h .= match($eSale['gridSource']) {
							'customer' => s("Les prix indiqués tiennent compte des prix personnalisés qui ont été trouvés pour {value}.", '<b>'.encode($eSale['gridValue']->getName()).'</b>'),
							'group' => s("Les prix indiqués tiennent compte des prix personnalisés qui ont été trouvés pour {value}.", CustomerGroupUi::link($eSale['gridValue']))
						};
					$h .= '</div>';

				}

				$h .= $form->dynamicField($eSale, 'productsList');

				$footer = ItemUi::getCreateSubmit($eSale, $form, s("Créer la vente"));

			} else {
				$footer = $form->submit(s("Créer la vente"), ['data-waiter' => s("Création en cours..."), 'class' => 'btn btn-primary btn-lg']);
			}

		} else {

			$formId = 'sale-create';

			$h .= $form->dynamicGroup($eSale, 'customer*', function($d) use($form, $eSale) {

				$d->autocompleteDispatch = '#sale-create';

			});

			if($cCustomerGroup->notEmpty()) {

				$h .= '<div class="text-end">';
					$h .= '<a class="dropdown-toggle" data-dropdown="bottom-end">'.s("Créer une vente pour un groupe de clients").'</a>';
					$h .= '<div class="dropdown-list bg-primary">';
					foreach($cCustomerGroup as $eCustomerGroup) {
						$h .= '<a href="'.\util\HttpUi::setArgument(LIME_REQUEST, 'group', $eCustomerGroup['id']).'" class="dropdown-item">';
							$h .= '<span class="util-badge" style="background-color: '.$eCustomerGroup['color'].'">'.encode($eCustomerGroup['name']).'</span>';
						$h .= '</a>';
					}
					$h .= '</div>';
				$h .= '</div>';

			}

			$footer = NULL;
		}

		return new \Panel(
			id: 'panel-sale-create-collection',
			title: s("Ajouter une vente"),
			dialogOpen: $form->openAjax('/selling/sale:doCreateCollection', ['id' => $formId, 'class' => 'panel-dialog']),
			dialogClose: $form->close(),
			body: $h,
			footer: $footer
		);

	}

	protected function getPreparationStatusField(\util\FormUi $form, Sale $eSale): string {

		if($eSale['customer']['destination'] === Customer::COLLECTIVE) {
			return '';
		}

		$values = [];

		foreach([Sale::DRAFT, Sale::CONFIRMED, Sale::PREPARED] as $status) {
			$values[] = [
				'value' => $status,
				'label' => '⬤  '.self::p('preparationStatus')->values[$status],
				'attributes' => ['class' => 'sale-preparation-status-'.$status]
			];
		}

		$h = $form->group(
			s("État"),
			$form->select('preparationStatus', $values, attributes: ['class' => 'sale-field-preparation-status', 'mandatory' => TRUE])
		);

		return $h;

	}

	public function duplicate(Sale $eSale, bool $acceptCredit): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/sale:doDuplicate');

			$h .= '<div class="util-block-info">';
				$h .= '<h3>'.s("Dupliquer une vente").'</h3>';
				$h .= '<ul>';
					$h .= '<li>'.s("La vente sera dupliquée avec l'ensemble des articles de la vente initiale").'</li>';
					if($eSale['cPayment']->notEmpty()) {
						$h .= '<li>'.s("Les moyens de paiement ne sont pas copiés mais si le client a un moyen de paiement par défaut, celui-ci est utilisé").'</li>';
					}
					if($eSale['shop']->notEmpty()) {
						$h .= '<li>'.s("La vente dupliquée sera dissociée à la boutique {value}", '<u>'.encode($eSale['shop']['name']).'</u>').'</li>';
					}
				$h .= '</ul>';
			$h .= '</div>';

			$h .= $form->hidden('id', $eSale['id']);

			$h .= $form->group(
				s("Vente d'origine"),
				SaleUi::link($eSale, newTab: TRUE).'  '.CustomerUi::link($eSale['customer'])
			);

			$h .= $form->group(
				s("Date de la nouvelle vente"),
				$form->dynamicField($eSale, 'deliveredAt')
			);

			$h .= $this->getPreparationStatusField($form, $eSale);

			if($acceptCredit) {

				$h .= '<div class="util-block bg-background">';
					$h .= $form->group(
						s("Faire un avoir"),
						$form->switch('credit').\util\FormUi::info(s("Lorsque vous faites un avoir, les prix de la vente dupliquée sont passés en négatif."))
					);
				$h .= '</div>';

			}

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

		$h .= $form->openAjax('/selling/sale:doUpdate', ['class' => 'sale-update-form']);

			$h .= $form->hidden('id', $eSale['id']);

			if($eSale->isLocked() === FALSE) {

				$h .= $form->dynamicGroup($eSale, 'deliveredAt');

				if($eSale->acceptDiscount()) {
					$h .= $form->dynamicGroup($eSale, 'discount');
				}

			}

			$h .= $form->dynamicGroup($eSale, 'comment');

			if(
				$eSale->acceptUpdateShipping() or
				$eSale->acceptUpdateShopPoint()
			) {

				$h .= '<div class="util-block bg-background-light">';

					$h .= $form->group(content: '<h4>'.s("Livraison").'</h4>');

					if($eSale->acceptUpdateShopPoint()) {
						$h .= $form->dynamicGroup($eSale, 'shopPointPermissive');
					}

					if($eSale->acceptShipping()) {

						$h .= $form->dynamicGroup($eSale, 'shipping');

						if($eSale['hasVat']) {
							$h .= $form->dynamicGroup($eSale, 'shippingVatRate');
						}

					}



				$h .= '</div>';

			}

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-sale-update',
			title: $eSale->isComposition() ? s("Modifier une composition") : s("Modifier une vente"),
			body: $h
		);

	}

	public function updatePayment(Sale $eSale): \Panel {

		if($eSale['invoice']->notEmpty()) {
			$h = $this->getInvoicePayment($eSale);
		} else {
			if($eSale->isPaymentOnline(Payment::FAILED)) {
				$h = $this->getOnlinePayment($eSale);
			} else {
				$h = $this->getPaymentForm($eSale);
			}
		}

		return new \Panel(
			id: 'panel-sale-update',
			title: $eSale['cPayment']->empty() ?
				s("Choisir le règlement") :
				s("Changer le règlement"),
			body: $h
		);

	}

	protected function getOnlinePayment(Sale $eSale): string {

		$content = '<div class="flex-justify-space-between flex-align-center">';
			$content .= '<div>'.SaleUi::getPaymentMethodName($eSale).' '.SaleUi::getPaymentStatus($eSale).'</div>';
			$content .= '<a data-ajax="/selling/sale:doDeletePayment" post-id="'.$eSale['id'].'" class="btn btn-xs btn-danger" data-confirm="'.s("Voulez-vous vraiment supprimer ce mode de règlement pour la vente ?").'">'.s("Supprimer").'</a>';
		$content .= '</div>';

		$h = '<div class="util-block bg-background-light">';
			$h .= $content;
		$h .= '</div>';

		return $h;

	}

	protected function getPaymentBox(Sale $eSale, bool $optimize = FALSE): string {

		$h = '';

		if($eSale['paymentStatus'] === Sale::NEVER_PAID) {
			$h .= '<span class="util-badge sale-payment-status-never-paid">'.self::p('paymentStatus')->values[Sale::NEVER_PAID].'</span>';
		} else if($eSale['cPayment']->empty()) {

			if($eSale->acceptUpdatePayment()) {
				$h .= '<a href="/selling/sale:updatePayment?id='.$eSale['id'].'" class="btn btn-sm btn-outline-primary">'.s("Choisir").'</a>';
			}

		} else {

			if($eSale->acceptUpdatePayment() and $eSale['paymentStatus'] !== Sale::PAID) {
				$h .= '<a href="/selling/sale:updatePayment?id='.$eSale['id'].'" class="btn btn-sm btn-outline-primary sale-button">';
			}

				$h .= self::getPaymentMethodName($eSale);

				$paymentStatus = self::getPaymentStatus($eSale);

				if($paymentStatus) {

					if(
						$optimize and
						$eSale['cPayment']->count() === 1 and
						$eSale['paymentStatus'] !== Sale::PARTIAL_PAID
					) {
						$h .= '  '.$paymentStatus;
					} else {
						$h .= '<div style="margin-top: 0.25rem">'.$paymentStatus.'</div>';
					}

				}

			if($eSale->acceptUpdatePayment() and $eSale['paymentStatus'] !== Sale::PAID) {
				$h .= '</a>';
			}

		}

		return $h;

	}

	protected function getPaymentForm(Sale $eSale): string {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/sale:doUpdatePayment');

			$h .= $form->hidden('id', $eSale['id']);

			if($eSale['paymentStatus'] === Sale::NEVER_PAID) {
				$h .= $form->group(
					content: '<div class="util-block-info">'.s("Cette vente est actuellement enregistrée comme une vente qui ne sera pas payée, mais vous pouvez revenir sur votre choix.").'</div>'
				);
			}

			$never = $eSale->acceptNeverPaid() ? '<a data-ajax="/selling/sale:doUpdateNeverPaid" post-id="'.$eSale['id'].'" class="btn btn-outline-primary" data-confirm="'.s("Vous allez indiquer que cette vente ne sera jamais payée. Voulez-vous continuer ?").'">'.s("Ne sera pas payée").'</a>' : '';

			$h .= $form->group(content: '<div class="util-title">'.
				'<h4>'.s("Vente de {value}", \util\TextUi::money($eSale['priceIncludingVat'])).'</h4>'.
				$never.
			'</div>');
			$h .= new PaymentTransactionUi()->update($eSale, $eSale['cPayment'], $eSale['cPaymentMethod']);

			$h .= $form->group(
				content: '<div class="flex-justify-space-between">'.
					$form->submit(s("Enregistrer")).
					'<a class="btn btn-outline-primary" onclick="Payment.add()">'.\Asset::icon('plus-circle').' '.s("Ajouter un autre paiement").'</a>'.
				'</div>'
		);

		$h .= $form->close();

		return $h;

	}

	protected function getInvoicePayment(Sale $eSale): string {

		$h = '<div class="util-block-info">';
			$h .= '<p>';
				$h .= s("Cette vente est incluse dans la facture <b>{invoiceNumber}</b>.<br/>Vous pouvez modifier le moyen de paiement et l'état du paiement directement dans la facture.", [
				'invoiceNumber' => encode($eSale['invoice']['number']),
			]);
			$h .= '</p>';
			$h .= '<a href="'.\farm\FarmUi::urlSellingInvoices($eSale['farm']).'?invoice='.$eSale['invoice']['id'].'" class="btn btn-transparent">';
				$h .= s("Consulter la facture");
			$h .= '</a>';
		$h .= '</div>';

		return $h;
	}


	public function updateShop(Sale $eSale): \Panel {

		$eSale->expects(['cShop']);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/sale:doAssociateShop');

			$h .= $form->hidden('id', $eSale['id']);

			$h .= $form->group(
				s("Vente"),
				SaleUi::link($eSale, newTab: TRUE)
			);

			$h .= $form->dynamicGroup($eSale, 'shopDate');

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
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
				content: $form->submit(s("Enregistrer"))
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

		$eCountry = $eFarm['legalCountry'];

		if($eCountry['id'] === \user\UserSetting::FR) {

			return [
				// FR
				0 => $short ? s("0 %") : s("0 % - Pas de TVA"),
				1 => $short ? s("2.1 %") : s("2.1 % - Taux particulier"),
				2 => $short ? s("5.5 %") : s("5.5 % - Taux réduit"),
				3 => $short ? s("10 %") : s("10 % - Taux intermédiaire"),
				4 => $short ? s("20 %") : s("20 % - Taux normal"),
			];

		} else if($eCountry['id'] === \user\UserSetting::BE) {

			return [
				// BE
				100 => $short ? s("0 %") : s("0 % - Pas de TVA"),
				101 => $short ? s("6 %") : s("6 % - Taux réduit"),
				102 => $short ? s("12 %") : s("12 % - Taux intermédiaire"),
				103 => $short ? s("21 %") : s("21 % - Taux standard"),
			];

		} else {

			return [

				9999 => $short ? s("0 %") : s("0 % - Pas de TVA"),

			];

		}

	}

	public static function getVatRates(\farm\Farm $eFarm, bool $short = FALSE): array {

		$vat = self::getVat($eFarm, $short);
		$vatRates = [];

		foreach($vat as $key => $text) {
			$vatRates[(string)SellingSetting::getVatRate($eFarm, $key)] = $text;
		}

		return $vatRates;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Sale::model()->describer($property, [
			'customers' => s("Clients"),
			'customer' => s("Client"),
			'deliveredAt' => fn($e) => $e->isComposition() ? s("Pour les livraisons à partir du") : s("Date de vente"),
			'paidAt' => s("Date de paiement"),
			'market' => s("Utiliser le logiciel de caisse<br/>pour cette vente"),
			'preparationStatus' => s("Statut de préparation"),
			'paymentStatus' => s("État du paiement"),
			'orderFormValidUntil' => s("Date d'échéance du devis"),
			'orderFormPaymentCondition' => s("Conditions de paiement"),
			'orderFormHeader' => s("Texte personnalisé affiché en haut du devis"),
			'orderFormFooter' => s("Texte personnalisé affiché en bas du devis"),
			'deliveryNoteDate' => s("Date de livraison"),
			'deliveryNoteHeader' => s("Texte personnalisé affiché en haut du bon de livraison"),
			'deliveryNoteFooter' => s("Texte personnalisé affiché en bas du bon de livraison"),
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

					$body = [
						'farm' => $e['farm']['id'],
						'new' => TRUE
					];

					if($e['type'] !== NULL) {
						$body['type'] = $e['type'];
					}

					return $body;
				};
				new CustomerUi()->query($d);
				break;

			case 'customers' :
				$d->autocompleteDefault = fn(Sale $e) => $e['cCustomer'] ?? new \Collection();
				$d->autocompleteBody = function(\util\FormUi $form, Sale $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id'],
						'type' => $e['type'],
						'new' => TRUE
					];
				};
				new CustomerUi()->query($d, multiple: TRUE);
				break;

			case 'shopDate' :
				$d->field = function(\util\FormUi $form, Sale $e) {

					$e->expects(['cShop']);

					$cShop = $e['cShop']->find(fn($eShop) => $eShop['cDate']->notEmpty());

					$list = '';

					foreach($cShop as $eShop) {

						switch($eShop['opening']) {

							case \shop\Shop::FREQUENCY :

								$cDate = $eShop['cDate']->find(fn($eDate) => $eDate['deliveryDate'] !== NULL);

								if($cDate->notEmpty()) {

									$list .= '<h5>'.encode($eShop['name']).'</h5>';

									$list .= $form->radios('shopDate', $cDate, $e['shopDate'] ?? new \shop\Date(), attributes: [
										'callbackRadioContent' => fn($eDate) => s("Livraison du {value}", \util\DateUi::numeric($eDate['deliveryDate'])),
										'mandatory' => TRUE,
									]);
									
									$list .= '<br/>';

								}
								break;

							case \shop\Shop::ALWAYS :

								$eDate = $eShop['cDate']->find(fn($eDate) => $eDate['deliveryDate'] === NULL, limit: 1);

								$list .= '<h5>'.$form->radio('shopDate', $eDate['id'], encode($eShop['name'])).'</h5>';
								$list .= '<br/>';

								break;

						}

					}

					if($list) {
						return '<div class="util-block-gradient">'.$list.'</div>';
					} else {

						$message = match($e['type']) {
							Sale::PRIVATE => s("Nous n'avons pas trouvé de boutique pour clients particuliers à associer à cette vente."),
							Sale::PRO => s("Nous n'avons pas trouvé de boutique pour clients professionnels à associer à cette vente."),
						};

						return '<div class="util-info">'.$message.'</div>';

					}

					return $h;

				};
				$d->group = function(Sale $e) {

					$e->expects(['shop']);

					$hide = $e['shop']->empty() ? '' : 'hide';

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
				$d->after = \util\FormUi::info(s("La remise commerciale s'applique à l'intégralité de la vente à l'exception d'éventuels frais de livraison."));
				break;

			case 'deliveredAt' :
				$d->default = currentDate();
				$d->labelAfter = function(Sale $e) {

					if($e->isComposition()) {
						return \util\FormUi::info(
							$e->exists() ?
								s("Les ventes déjà créées avec un {product} ne seront pas modifiées.",  ['product' => '<u>'.encode($e['compositionOf']['name']).'</u>']) :
								s("Les ventes déjà créées avec un {product} garderont leur composition actuelle.",  ['product' => '<u>'.encode($e['compositionOf']['name']).'</u>'])
						);
					}

				};
				$d->group = function(Sale $e) {

					$e->expects(['shop']);

					$hide = ($e['shop']->empty() or $e['shopDate']['deliveryDate'] === NULL) ? '' : 'hide';

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
						$rate = SellingSetting::getVatRate($e['farm'], $position);
						$values[(string)$rate] = s("Personnalisé - {value}", $text);
					}

					$defaultVatRate = $e['farm']->getConf('defaultVatShipping') ? SellingSetting::getVatRate($e['farm'], $e['farm']->getConf('defaultVatShipping')) : NULL;
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
					Sale::EXPIRED => s("Expiré"),
					Sale::CONFIRMED => s("Confirmé"),
					Sale::SELLING => s("En vente"),
					Sale::PREPARED => s("Préparé"),
					Sale::DELIVERED => s("Livré"),
					Sale::CANCELED => s("Annulé"),
				];
				break;

			case 'paymentStatus' :
				$d->values = [
					Sale::PAID => s("Payé"),
					Sale::NOT_PAID => s("Non payé"),
					Sale::PARTIAL_PAID => s("Payé partiellement"),
					Sale::FAILED => s("Paiement en échec"),
					Sale::NEVER_PAID => s("Ne sera pas payé"),
				];
				break;

			case 'orderFormPaymentCondition' :
				$d->placeholder = s("Ex. : Acompte de 20 % à la signature du devis, et solde à la livraison.");
				$d->after = \util\FormUi::info(s("Facultatif, indiquez ici les conditions de paiement après acceptation du devis."));
				break;

			case 'productsList' :
				$d->field = function(\util\FormUi $form, Sale $e) {

					if($e['cProduct']->empty()) {
						return '<div class="util-empty">'.s("Aucun produit n'est disponible à la vente.").'</div>';
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
