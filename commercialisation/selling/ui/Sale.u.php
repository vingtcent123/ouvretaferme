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
			Sale::EXCLUDING => $eSale['priceExcludingVat'] ? \util\TextUi::money($eSale['priceExcludingVat']).$taxes : '',
			Sale::INCLUDING => $eSale['priceIncludingVat'] ? \util\TextUi::money($eSale['priceIncludingVat']).$taxes : ''
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
				Sale::EXCLUDING => $eSale['priceExcludingVat'] ? \util\TextUi::money($eSale['priceExcludingVat'] / $eSale['marketSales']).$taxes : '',
				Sale::INCLUDING => $eSale['priceIncludingVat'] ? \util\TextUi::money($eSale['priceIncludingVat'] / $eSale['marketSales']).$taxes : ''
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
						$eSale->acceptStatusPrepared() === FALSE
					) {
						$batch[] = 'not-prepared';
					}

					if(
						$eSale->canWrite() === FALSE or
						$eSale['preparationStatus'] !== Sale::CONFIRMED
					) {
						$batch[] = 'not-prepare';
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

					if(
						$eSale->canWrite() === FALSE or
						$eSale->acceptUpdatePayment() === FALSE
					) {
						$batch[] = 'not-update-payment';
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
											\shop\Point::HOME => '<a href="'.$eSale->getDeliveryAddressLink().'" target="_blank" class="color-muted">'.$eSale->getDeliveryAddress('html', $eFarm).'</a>',
											\shop\Point::PLACE => encode($eSale['shopPoint']['name'])
										};
									$h .= '</div>';

								}
							$h .= '</td>';

						}

						if($hasDocuments and in_array('paymentMethod', $hide) === FALSE) {

							$h .= '<td class="sale-item-payment-type '.($dynamicHide['paymentMethod'] ?? 'hide-md-down').'">';

							$h .= self::getPaymentMethodName($eSale);

							$paymentStatus = self::getPaymentStatus($eSale);
							if($paymentStatus) {
								$h .= '<div style="margin-top: 0.25rem">'.$paymentStatus.'</div>';
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

		$h .= $this->getBatch($eFarm, $cPaymentMethod);

		return $h;

	}

	public function getBatch(\farm\Farm $eFarm, \Collection $cPaymentMethod): string {

		$menu = '<a data-url="/selling/item:summary?farm='.$eFarm['id'].'" class="batch-menu-amount batch-menu-item">';
			$menu .= '<span>';
				$menu .= '<span class="batch-menu-item-number"></span>';
				$menu .= ' <span class="batch-menu-item-taxes" data-excluding="'.s("HT").'" data-including="'.s("TTC").'"></span>';
			$menu .= '</span>';
			$menu .= '<span>'.s("Synthèse").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/sale:doUpdateConfirmedCollection" data-confirm="'.s("Marquer ces ventes comme confirmées ?").'" class="batch-menu-confirmed batch-menu-item">';
			$menu .= '<span class="btn btn-xs sale-preparation-status-batch sale-preparation-status-confirmed-button">'.self::p('preparationStatus')->shortValues[Sale::CONFIRMED].'</span>';
			$menu .= '<span>'.s("Confirmé").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/sale:doUpdatePreparedCollection" data-confirm="'.s("Marquer ces ventes comme confirmées ?").'" class="batch-menu-prepared batch-menu-item">';
			$menu .= '<span class="btn btn-xs sale-preparation-status-batch sale-preparation-status-prepared-button">'.self::p('preparationStatus')->shortValues[Sale::PREPARED].'</span>';
			$menu .= '<span>'.s("Préparé").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/sale:doUpdateDeliveredCollection" data-confirm="'.s("Marquer ces ventes comme livrées ?").'" class="batch-menu-delivered batch-menu-item">';
			$menu .= '<span class="btn btn-xs sale-preparation-status-batch sale-preparation-status-delivered-button">'.self::p('preparationStatus')->shortValues[Sale::DELIVERED].'</span>';
			$menu .= '<span>'.s("Livré").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/sale:doUpdateCanceledCollection" data-confirm="'.s("Annuler ces ventes ?").'" class="batch-menu-cancel batch-menu-item">';
			$menu .= '<span class="btn btn-xs sale-preparation-status-batch sale-preparation-status-draft-button">'.self::p('preparationStatus')->shortValues[Sale::CANCELED].'</span>';
			$menu .= '<span>'.s("Annuler").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/sale:doExportCollection" data-ajax-navigation="never" class="batch-menu-item">';
			$menu .= \Asset::icon('filetype-pdf');
			$menu .= '<span>'.s("Exporter").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-dropdown="top-start" class="batch-menu-payment-method batch-menu-item">';
			$menu .= \Asset::icon('cash-coin');
			$menu .= '<span style="letter-spacing: -0.2px">'.s("Changer de moyen<br/>de paiement").'</span>';
		$menu .= '</a>';

		$menu .= '<div class="dropdown-list bg-secondary">';
			$menu .= '<div class="dropdown-title">'.s("Changer de moyen de paiement").'</div>';
			foreach($cPaymentMethod as $ePaymentMethod) {
				if($ePaymentMethod['online'] === FALSE) {
					$menu .= '<a data-ajax-submit="/selling/sale:doUpdatePaymentMethodCollection" data-ajax-target="#batch-sale-form" post-payment-method="'.$ePaymentMethod['id'].'" class="dropdown-item">'.\payment\MethodUi::getName($ePaymentMethod).'</a>';
				}
			}
			$menu .= '<a data-ajax-submit="/selling/sale:doUpdatePaymentMethodCollection" data-ajax-target="#batch-sale-form" post-payment-method="" class="dropdown-item"><i>'.s("Pas de moyen de paiement").'</i></a>';
		$menu .= '</div>';

		$menu .= '<a data-url="/vente/" data-confirm="'.s("Vous allez entrer dans le mode de préparation de commandes. Voulez-vous continuer ?").'" class="batch-menu-prepare batch-menu-item">';
			$menu .= \Asset::icon('person-workspace');
			$menu .= '<span style="letter-spacing: -0.2px">'.s("Préparer<br/>les commandes").'</span>';
		$menu .= '</a>';

		$danger = '<a data-ajax-submit="/selling/sale:doDeleteCollection" data-confirm="'.s("Confirmer la suppression de ces ventes ?").'" class="batch-menu-delete batch-menu-item batch-menu-item-danger">';
			$danger .= \Asset::icon('trash');
			$danger .= '<span>'.s("Supprimer").'</span>';
		$danger .= '</a>';

		return \util\BatchUi::group('batch-sale', $menu, $danger, title: s("Pour les ventes sélectionnées"));

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

						$document = '<a '.$urlGenerate.' class="btn btn-sm sale-document sale-document-new" title="'.$texts['generate'].'" '.attr('data-confirm', $texts['generateConfirm']).'>';
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

						if($type === Pdf::INVOICE) {
							$document .= '<a href="'.\farm\FarmUi::urlSellingInvoices($eSale['farm']).'?invoice='.$eSale['invoice']['id'].'" data-ajax-navigation="never" class="dropdown-item">'.s("Consulter la facture").'</a>';
						}

						if($ePdf['content']->notEmpty()) {

							if($type === Pdf::INVOICE) {
								$document .= '<a href="'.InvoiceUi::url($eSale['invoice']).'" data-ajax-navigation="never" class="dropdown-item">'.s("Télécharger le PDF").'</a>';
							} else {
								$document .= '<a href="'.PdfUi::url($ePdf).'" data-ajax-navigation="never" class="dropdown-item">'.s("Télécharger le PDF").'</a>';
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
			return '<span class="btn btn-readonly '.$btn.' sale-preparation-status-'.$eSale['preparationStatus'].'-button" title="'.s("Il sera possible de modifier le statut lorsque la période de prise des commandes sera close.").'">'.self::p('preparationStatus')->values[$eSale['preparationStatus']].'  '.\Asset::icon('lock-fill').'</span>';
		}

		$button = function(string $preparationStatus, ?string $confirm = NULL) use ($eSale) {

			$h = '<a data-ajax="/selling/sale:doUpdate'.ucfirst($preparationStatus).'Collection" post-ids="'.$eSale['id'].'" class="dropdown-item" '.($confirm ? attr('data-confirm', $confirm) : '').'>';
				$h .= \Asset::icon('arrow-right').'  <span class="btn btn-sm sale-preparation-status-'.$preparationStatus.'-button">'.self::p('preparationStatus')->values[$preparationStatus].'</span>';
			$h .= '</a>';

			return $h;

		};

		$link = function(string $to) use ($eSale, $btn) {

			$h = '<a data-dropdown="bottom-start" data-dropdown-id="sale-dropdown-'.$eSale['id'].'" data-dropdown-hover="true" class="btn '.$btn.' sale-preparation-status-'.$eSale['preparationStatus'].'-button dropdown-toggle">'.self::p('preparationStatus')->values[$eSale['preparationStatus']].'</a>';
			$h .= '<div data-dropdown-id="sale-dropdown-'.$eSale['id'].'-list" class="dropdown-list bg-primary">';
				$h .= $to;
			$h .= '</div>';

			return $h;

		};

		$h = '';

		if($eSale->isMarket()) {

			switch($eSale['preparationStatus']) {

				case Sale::DRAFT :
					$h = $link(
						$button(Sale::CONFIRMED)
					);
					break;

				case Sale::CONFIRMED :
					$h = $link(
						$button(Sale::SELLING, s("Vous allez commencer votre vente avec le logiciel de caisse ! Les quantités des produits que vous avez saisies pour préparer cette vente seront remises à zéro et vous pourrez commencer à enregistrer les commandes des clients. C'est parti ?"))
					);
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

		if($eSale['onlinePaymentStatus'] !== NULL) {
			return '<span class="util-badge sale-payment-status sale-payment-status-'.$eSale['onlinePaymentStatus'].'">'.self::p('onlinePaymentStatus')->values[$eSale['onlinePaymentStatus']].'</span>';
		} else if($eSale['paymentStatus'] !== NULL) {
			return '<span class="util-badge sale-payment-status sale-payment-status-'.$eSale['paymentStatus'].'">'.self::p('paymentStatus')->values[$eSale['paymentStatus']].'</span>';
		} else {
			return '';
		}


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
			$h .= '<div class="util-empty">'.s("Il n'y a aucune vente en cours de préparation ou déjà préparée.").'</div>';
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
										$h .= CustomerUi::getColorCircle($eSale['customer']).' '.CustomerUi::link($eSale['customer']);
									$h .= '</td>';
									$h .= '<td class="text-center">'.\util\DateUi::numeric($eSale['deliveredAt']).'</td>';
									$h .= '<td class="text-center">'.\util\TextUi::money($eSale['priceExcludingVat']).' '.$eSale->getTaxes().'</td>';
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
		$hasAtLeastOneSuccessfulPayment = $eSale->hasSuccessfulPayment();

		$paymentList = [];
		foreach($eSale['cPayment'] as $ePayment) {

			// On n'affiche pas les paiements en échec s'il y a au moins 1 paiement en succès
			if(
				($hasAtLeastOneSuccessfulPayment and $ePayment->isNotPaid()) or
				// Le paiement par CB n'est pas le dernier => On ne le prend pas en compte
				($eSale['onlinePaymentStatus'] === NULL and $ePayment['method']->isOnline())
			) {
				continue;
			}

			$payment = \payment\MethodUi::getName($ePayment['method']);
			if($eSale['cPayment']->find(fn($e) => $e->isPaid())->count() > 1 and $ePayment['amountIncludingVat'] !== NULL) {
				$payment .= ' ('.\util\TextUi::money($ePayment['amountIncludingVat']).')';
			}

			$paymentList[] = $payment;
		}

		return implode('<br />', $paymentList);

	}
	public static function getPayment(Sale $eSale): string {

		$paymentList = [];

		if($eSale['invoice']->notEmpty()) {

			if($eSale['invoice']->isCreditNote()) {
				$paymentList[] = s("Avoir");
			} else {
				$paymentList[] = s("Facture").' '.InvoiceUi::getPaymentStatus($eSale['invoice']);
			}
		} else {

			if($eSale['cPayment']->empty()) {
				return '';
			}

			$payment = self::getPaymentMethodName($eSale);

			if($eSale['cPayment']->count() > 1) {
				$payment .= '<br />';
			} else {
				$payment .= ' ';
			}

			$payment .= self::getPaymentStatus($eSale);
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
						$h .= self::getPayment($eSale);
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

		if($eSale->acceptAssociateShop()) {
			$primaryList .= '<a href="/selling/sale:updateShop?id='.$eSale['id'].'" class="dropdown-item">'.s("Associer la vente à une boutique").'</a>';
		}

		if($eSale->acceptDissociateShop()) {
			$primaryList .= '<a data-ajax="/selling/sale:doDissociateShop" post-id="'.$eSale['id'].'" class="dropdown-item">'.s("Dissocier la vente de la boutique").'</a>';
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

			if(
				$eSale->isMarket() and
				$eSale['preparationStatus'] === Sale::SELLING
			) {

				$h .= '<div class="dropdown-divider"></div>';
				$h .= '<a data-ajax="/selling/sale:doUpdateConfirmedCollection" post-ids="'.$eSale['id'].'" class="dropdown-item">';
					$h .= s("Remettre à préparer");
				$h .= '</a>';
			}

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

			$h .= '<div class="util-block-important mt-2">';
				$h .= '<h3>'.s("Si vous souhaitez créer des ventes pour tester {siteName}").'</h3>';
				$h .= '<p>'.s("Nous vous suggérons d'utiliser la ferme de démonstration ou de créer une ferme de test si vous souhaitez tester les fonctionnalités de commercialisation de Ouvretaferme. En raison de contraintes réglementaires, vous ne pouvez pas supprimer des ventes clôturées sur le logiciel.").'</p>';
				$h .= '<a href="'.OTF_DEMO_URL.'/ferme/1/ventes" target="_blank" class="btn btn-transparent">'.s("Utiliser la démo").'</a>';
			$h .= '</div>';

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
				$footer = $form->submit(s("Créer la vente"), ['data-submit-waiter' => s("Création en cours..."), 'class' => 'btn btn-primary btn-lg']);
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

		foreach([Sale::DRAFT, Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED] as $status) {
			$values[] = [
				'value' => $status,
				'label' => '⬤  '.self::p('preparationStatus')->values[$status],
				'attributes' => ['class' => 'sale-preparation-status-'.$status]
			];
		}

		$h = $form->group(
			s("État"),
			$form->select('preparationStatus', $values, attributes: ['class' => 'sale-field-preparation-status', 'mandatory' => TRUE]),
		);

		return $h;

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

		$h .= $form->openAjax('/selling/sale:doUpdate', ['class' => 'sale-update-form']);

			$h .= $form->hidden('id', $eSale['id']);

			if($eSale->isLocked() === FALSE) {

				$h .= $form->dynamicGroup($eSale, 'deliveredAt');

				if($eSale->acceptDiscount()) {
					$h .= $form->dynamicGroup($eSale, 'discount');
				}

			}

			if($eSale['invoice']->notEmpty()) {

				$paymentInfo = '<div class="util-info">';
					$paymentInfo .= '<p>';
						$paymentInfo .= s("Cette vente est incluse dans la facture <b>{invoiceNumber}</b>. Vous pouvez modifier le moyen de paiement et l'état du paiement directement dans la facture.", [
						'invoiceNumber' => encode($eSale['invoice']['name']),
					]);
					$paymentInfo .= '</p>';
					$paymentInfo .= '<a href="'.\farm\FarmUi::urlSellingInvoices($eSale['farm']).'?invoice='.$eSale['invoice']['id'].'" class="btn btn-secondary">';
						$paymentInfo .= s("Consulter la facture");
					$paymentInfo .= '</a>';
				$paymentInfo .= '</div>';

				$h .= '<div class="util-block bg-background-light">';
					$h .= $form->group(content: '<h4>'.s("Règlement").'</h4>');
					$h .= $form->group(SaleUi::p('paymentMethod')->label, SaleUi::getPaymentMethodName($eSale));
					$h .= $form->group(SaleUi::p('paymentStatus')->label, SaleUi::getPaymentStatus($eSale));
					$h .= $form->group(content: $paymentInfo);
				$h .= '</div>';


			} else if($eSale->acceptUpdatePayment()) {

				$h .= '<div class="util-block bg-background-light">';
					$h .= $form->group(content: '<h4>'.s("Règlement").'</h4>');

					$h .= $form->group(
						s("Moyen de paiement"),
						$form->select(
							'method', $eSale['cPaymentMethod'], $eSale['cPayment']->first()['method'] ?? new \payment\Method(), [
								'onrender' => 'Sale.changePaymentMethod(this)',
								'onchange' => 'Sale.changePaymentMethod(this)',
								'placeholder' => s("Non défini"),
							]
						)
					);

					$h .= $form->dynamicGroup($eSale, 'paymentStatus', function($d) {
						$d->default = fn(Sale $eSale) => $eSale['paymentStatus'] ?? Sale::NOT_PAID;
					});
				$h .= '</div>';

			} else if($eSale->isPaymentOnline()) {

				$content = '<div class="flex-justify-space-between flex-align-center">';
					$content .= '<div>'.SaleUi::getPaymentMethodName($eSale).' '.SaleUi::getPaymentStatus($eSale).'</div>';
					$content .= '<a data-ajax="/selling/sale:doDeleteOnlinePaymentMethod" post-id="'.$eSale['id'].'" class="btn btn-xs btn-danger" data-confirm="'.s("Voulez-vous vraiment supprimer ce mode de règlement pour la vente ?").'">'.s("Supprimer").'</a>';
				$content .= '</div>';

				$h .= '<div class="util-block bg-background-light">';
					$h .= $form->group(content: '<h4>'.s("Règlement").'</h4>');
					$h .= $form->group(
						self::p('paymentMethod')->label,
						$content
					);
				$h .= '</div>';

			}

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

			$h .= $form->dynamicGroup($eSale, 'comment');

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

		$eCountry = $eFarm->getSelling('taxCountry');

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

					$defaultVatRate = $e['farm']->getSelling('defaultVatShipping') ? SellingSetting::getVatRate($e['farm'], $e['farm']->getSelling('defaultVatShipping')) : NULL;
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
				$d->shortValues = [
					Sale::DRAFT => s("B"),
					Sale::BASKET => s("P"),
					Sale::EXPIRED => s("E"),
					Sale::CONFIRMED => s("C"),
					Sale::SELLING => s("V"),
					Sale::PREPARED => s("P"),
					Sale::DELIVERED => s("L"),
					Sale::CANCELED => s("A"),
				];
				break;

			case 'paymentStatus' :
				$d->values = [
					Sale::PAID => s("Payé"),
					Sale::NOT_PAID => s("Non payé"),
				];
				$d->field = 'switch';
				$d->attributes = [
					'labelOn' => $d->values[Sale::PAID],
					'labelOff' => $d->values[Sale::NOT_PAID],
					'valueOn' => Sale::PAID,
					'valueOff' => Sale::NOT_PAID,
				];
				break;

			case 'onlinePaymentStatus' :
				$d->values = [
					Sale::INITIALIZED => s("Non payé"),
					Sale::SUCCESS => s("Payé"),
					Sale::FAILURE => s("Échec"),
				];
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
