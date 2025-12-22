<?php
namespace preaccounting;

Class PreaccountingUi {

	public function __construct() {

		\Asset::css('preaccounting', 'preaccounting.css');
		\Asset::js('preaccounting', 'preaccounting.js');

	}

	public function getSearch(\farm\Farm $eFarm, \Search $search): string {

		$h = '<div id="sale-search" class="util-block-search flex-justify-space-between">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';

					$h .= $form->inputGroup($form->addon(s("Du")).
						$form->date('from', $search->get('from')).
						$form->addon(s("au")).
						$form->date('to', $search->get('to'))
					);
					$h .= $form->submit(s("Valider"), ['class' => 'btn btn-secondary']);

				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function salesPayment(\farm\Farm $eFarm, string $type, \Collection $cSale, \Collection $cPaymentMethod, \Search $search): string {

		\Asset::css('selling', 'sale.css');


		$form = new \util\FormUi();
		parse_str(mb_substr(LIME_REQUEST_ARGS, 1), $args);

		$h = '<div class="mb-2">';
			$h .= $form->openUrl(LIME_REQUEST_PATH.'?'.http_build_query($args), ['id' => 'preaccounting-payment-customer']);
				$h .= $form->dynamicField(new \selling\Invoice(['farm' => $eFarm, 'customer' => $search->get('customer')]), 'customer');
			$h .= $form->close();
		$h .= '</div>';

		if($cSale->empty()) {
			return $h.'<div class="util-info">'.s("Il n'y a rien à afficher ici !").'</div>';
		}

		$h .= '<table class="tr-even" data-batch="#batch-accounting-sale-'.$type.'">';

			$h .= '<thead>';

				$h .= '<tr>';

					$h .= '<th class="text-center">';
						$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="sale-'.$type.'" onclick="Preaccounting.toggleGroupSelection(this)"/>';
					$h .= '</th>';
					$h .= '<th>#</th>';
					$h .= '<th>'.s("Date").'</th>';
					$h .= '<th>'.s("Client").'</th>';
					$h .= '<th class="highlight-stick-right text-end">'.s("Montant").'</th>';
					$h .= '<th class="text-center">'.s("Statut").'</th>';
				$h .= '</tr>';

			$h .= '</thead>';

			$h .= '<tbody>';

				foreach($cSale as $eSale) {

					$h .= '<tr>';

						$h .= '<td class="td-checkbox">';
							$h .= '<input type="checkbox" name="batch[]" batch-type="sale-'.$type.'" value="'.$eSale['id'].'" oninput="Preaccounting.changeSelection(this)"/>';
						$h .= '</td>';

						$h .= '<td>';
							$h .= '<a href="/vente/'.$eSale['id'].'" class="btn btn-sm '.($eSale['deliveredAt'] === currentDate() ? 'btn-primary' : 'btn-outline-primary').'">'.$eSale->getNumber().'</a>';
						$h .= '</td>';

						$h .= '<td>';
							$h .= \util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE);
						$h .= '</td>';

						$h .= '<td class="sale-item-name">';
							if($eSale['profile'] === \selling\Sale::SALE_MARKET) {
								$h .= encode($eSale['marketParent']['customer']->getName());
							} else {

								$h .= encode($eSale['customer']->getName());
								if($eSale['customer']->notEmpty()) {
									$h .= '<div class="util-annotation">';
										$h .= \selling\CustomerUi::getCategory($eSale['customer']);
									$h .= '</div>';
								}

							}
						$h .= '</td>';

						$h .= '<td class="highlight-stick-right sale-item-price text-end">';
							$h .= \selling\SaleUi::getTotal($eSale);
						$h .= '</td>';

						$h .= '<td class="sale-item-status text-center">';
						if($eSale['closed']) {
							$h .= '<span class="color-success">'.\Asset::icon('check-lg').' '.s("Clôturée").'</span>';
						} else if($eSale['preparationStatus'] === \selling\Sale::DELIVERED) {
							$h .= '<span class="color-success">'.\Asset::icon('check-lg').' '.\selling\SaleUi::p('preparationStatus')->values[$eSale['preparationStatus']].'</span>';
						} else {
							$h .= '<span class="btn btn-md sale-preparation-status-'.$eSale['preparationStatus'].'-button">'.\selling\SaleUi::p('preparationStatus')->values[$eSale['preparationStatus']].'</span>';
						}
						$h .= '</td>';

					$h .= '</tr>';

				}

			$h .= '</tbody>';

		$h .= '</table>';

		$h .= $this->getBatchSales($type, $cPaymentMethod);

		return $h;

	}

	public function sales(\farm\Farm $eFarm, string $type, \Collection $cSale, \Collection $cInvoice, \Collection $cPaymentMethod, array $nToCheck, int $nVerified, \Search $search): string {

		\Asset::css('selling', 'sale.css');

		$h = '';

		$url = \company\CompanyUi::urlFarm($eFarm).'/precomptabilite?type='.$type.'&from='.$search->get('from').'&to='.$search->get('to');

		if(count($nToCheck) > 1) {

			$h .= '<div class="tabs-item">';
				foreach($nToCheck as $tab => $count) {

					$h .= '<a href="'.$url.'&tab='.$tab.'"  class="tab-item '.(($search->get('tab') === $tab) ? 'selected' : '').'">';
						$h .= match($tab) {
							\selling\Sale::SALE => s("Ventes"),
							'invoice' => s("Factures"),
						} ;
					$h .= ' <small class="tab-item-count">'.$count.'</small></a>';

				}
			$h .= '</div>';

		}

		if($search->get('tab') === 'invoice') {

			$h .= '<table class="tr-even" data-batch="#batch-accounting-invoice-'.$type.'">';

				$h .= '<thead>';

					$h .= '<tr>';

						$h .= '<th class="text-center">';
							$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="invoice-'.$type.'" onclick="Preaccounting.toggleGroupSelection(this)"/>';
						$h .= '</th>';
						$h .= '<th>#</th>';
						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th>'.s("Client").'</th>';
						$h .= '<th class="highlight-stick-right text-end">'.s("Montant").'</th>';
						$h .= '<th>'.s("Moyen de paiement").'</th>';
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cInvoice as $eInvoice) {

						$h .= '<tr>';

							$h .= '<td class="td-checkbox">';
								$h .= '<input type="checkbox" name="batch[]" batch-type="invoice-'.$type.'" value="'.$eInvoice['id'].'" oninput="Preaccounting.changeSelection(this)"/>';
							$h .= '</td>';

							$h .= '<td>';
								$h .= '<a href="/facture/'.$eInvoice['id'].'" target="_blank" class="btn btn-sm btn-outline-primary">'.encode($eInvoice['name']).'</a>';
							$h .= '</td>';

							$h .= '<td>';
								$h .= \util\DateUi::numeric($eInvoice['date'], \util\DateUi::DATE);
							$h .= '</td>';

							$h .= '<td class="sale-item-name">';
								$h .= encode($eInvoice['customer']->getName());
								if($eInvoice['customer']->notEmpty()) {
									$h .= '<div class="util-annotation">';
										$h .= \selling\CustomerUi::getCategory($eInvoice['customer']);
									$h .= '</div>';
								}
							$h .= '</td>';

							$h .= '<td class="highlight-stick-right sale-item-price text-end">';
								$h .= \selling\SaleUi::getTotal($eInvoice);
							$h .= '</td>';

							$h .= '<td class="sale-item-payment-type">';

								if($eInvoice['paymentMethod']->empty()) {

									$h .= '<span>'.\Asset::icon('x-lg').'</span>';

								} else {

									$h .= encode($eInvoice['paymentMethod']['name']);

									$paymentStatus = \selling\InvoiceUi::getPaymentStatus($eInvoice);
									if($paymentStatus) {
										$h .= '<div style="margin-top: 0.25rem">'.$paymentStatus.'</div>';
									}

								}

							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

			$h .= $this->getBatchInvoices($type, $cPaymentMethod);

		} else {

			$h .= '<table class="tr-even" data-batch="#batch-accounting-sale-'.$type.'">';

				$h .= '<thead>';

					$h .= '<tr>';

						$h .= '<th class="text-center">';
							$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="sale-'.$type.'" onclick="Preaccounting.toggleGroupSelection(this)"/>';
						$h .= '</th>';
						$h .= '<th>#</th>';
						$h .= '<th>'.s("Date").'</th>';
						$h .= '<th>'.s("Client").'</th>';
						$h .= '<th class="highlight-stick-right text-end">'.s("Montant").'</th>';
						if($type !== 'payment') {
							$h .= '<th>'.s("Moyen de paiement").'</th>';
						}
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cSale as $eSale) {

						$h .= '<tr>';

							$h .= '<td class="td-checkbox">';
								$h .= '<input type="checkbox" name="batch[]" batch-type="sale-'.$type.'" value="'.$eSale['id'].'" oninput="Preaccounting.changeSelection(this)"/>';
							$h .= '</td>';

							$h .= '<td>';
								$h .= '<a href="/vente/'.$eSale['id'].'" class="btn btn-sm '.($eSale['deliveredAt'] === currentDate() ? 'btn-primary' : 'btn-outline-primary').'">'.$eSale->getNumber().'</a>';
							$h .= '</td>';

							$h .= '<td>';
								$h .= \util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE);
							$h .= '</td>';

							$h .= '<td class="sale-item-name">';
								$h .= encode($eSale['customer']->getName());
								if($eSale['customer']->notEmpty()) {
									$h .= '<div class="util-annotation">';
										$h .= \selling\CustomerUi::getCategory($eSale['customer']);
									$h .= '</div>';
								}
							$h .= '</td>';

							$h .= '<td class="highlight-stick-right sale-item-price text-end">';
								$h .= \selling\SaleUi::getTotal($eSale);
							$h .= '</td>';

							$h .= '<td class="sale-item-payment-type">';

								if($eSale['cPayment']->empty()) {

									$h .= '<span>'.\Asset::icon('x-lg').'</span>';

								} else {

									$h .= \selling\SaleUi::getPaymentMethodName($eSale);

									$paymentStatus = \selling\SaleUi::getPaymentStatus($eSale);
									if($paymentStatus) {
										$h .= '<div style="margin-top: 0.25rem">'.$paymentStatus.'</div>';
									}

								}

							$h .= '</td>';

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

			$h .= $this->getBatchSales($type, $cPaymentMethod);

		}


		return $h;

	}

	public function getBatchSales(string $type, \Collection $cPaymentMethod): string {

		$menu = '';

		if($type === 'payment') {

			$menu .= '<a data-dropdown="top-start" class="batch-payment-method batch-item">';
				$menu .= \Asset::icon('cash-coin');
				$menu .= '<span style="letter-spacing: -0.2px">'.s("Moyen de<br />paiement").'</span>';
			$menu .= '</a>';

			$menu .= '<div class="dropdown-list bg-secondary">';

				$menu .= '<div class="dropdown-title">'.s("Moyen de paiement").'</div>';
				foreach($cPaymentMethod as $ePaymentMethod) {
					if($ePaymentMethod['online'] === FALSE) {
						$menu .= '<a data-ajax-submit="/selling/sale:doUpdatePaymentMethodCollection" data-ajax-target="#batch-accounting-sale-'.$type.'-form" post-payment-method="'.$ePaymentMethod['id'].'" class="dropdown-item">'.\payment\MethodUi::getName($ePaymentMethod).'</a>';
					}
				}

			$menu .= '</div>';

		}

		$menu .= '<a data-ajax-submit="/selling/sale:doUpdateRefuseReadyForAccountingCollection" data-confirm="'.s("Confirmez-vous ignorer ces ventes ? Elles ne seront jamais incluses dans les exports comptables.").'"  class="batch-ignore batch-item">'.\Asset::icon('hand-thumbs-down').'<span>'.s("Ignorer").'</span></a>';

		return \util\BatchUi::group('batch-accounting-sale-'.$type, $menu, title: s("Pour les ventes sélectionnées"));

	}

	public function getBatchInvoices(string $type, \Collection $cPaymentMethod): string {

		$menu = '';

		if($type === 'payment') {

			$menu .= '<a data-dropdown="top-start" class="batch-payment-method batch-item">';
				$menu .= \Asset::icon('cash-coin');
				$menu .= '<span style="letter-spacing: -0.2px">'.s("Moyen de<br />paiement").'</span>';
			$menu .= '</a>';

			$menu .= '<div class="dropdown-list bg-secondary">';

				$menu .= '<div class="dropdown-title">'.s("Moyen de paiement").'</div>';
				foreach($cPaymentMethod as $ePaymentMethod) {
					if($ePaymentMethod['online'] === FALSE) {
						$menu .= '<a data-ajax-submit="/selling/invoice:doUpdatePaymentMethodCollection" data-ajax-target="#batch-accounting-invoice-'.$type.'-form" post-for="preaccounting" post-payment-method="'.$ePaymentMethod['id'].'" class="dropdown-item">'.\payment\MethodUi::getName($ePaymentMethod).'</a>';
					}
				}

			$menu .= '</div>';

		}

		$menu .= '<a data-ajax-submit="/selling/invoice:doUpdateRefuseReadyForAccountingCollection" data-confirm="'.s("Confirmez-vous ignorer ces factures ? Elles ne seront jamais incluses dans les exports comptables.").'"  class="batch-ignore batch-item">'.\Asset::icon('hand-thumbs-down').'<span>'.s("Ignorer").'</span></a>';


		return \util\BatchUi::group('batch-accounting-invoice-'.$type, $menu, title: s("Pour les factures sélectionnées"));

	}

	public function items(\Collection $ccItem): string {

		$h = '<table class="tr-even" data-batch="#batch-accounting-item">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th></th>';
					$h .= '<th>#</th>';
					$h .= '<th>'.s("Client").'</th>';
					$h .= '<th class="text-center">'.s("État").'</th>';
					$h .= '<th class="text-center">'.s("Vente").'</th>';
					$h .= '<th class="highlight-stick-right text-end">'.s("Montant").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';

			foreach($ccItem as $cItem) {

				$eSale = $cItem->first()['sale'];
				$eCustomer = $cItem->first()['customer'];

				$h .= '<tbody>';

					$h .= '<tr class="tr-title">';

						$h .= '<td class="td-checkbox">';
							$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="item" onclick="Preaccounting.toggleGroupSelection(this)"/>';
						$h .= '</td>';

						$h .= '<td>';
							$h .= '<a href="/vente/'.$eSale['id'].'" class="btn btn-sm '.($eSale['deliveredAt'] === currentDate() ? 'btn-primary' : 'btn-outline-primary').'">'.$eSale->getNumber().'</a>';
						$h .= '</td>';

						$h .= '<td class="sale-item-name">';
							$h .= encode($eCustomer->getName());
							if($eCustomer->notEmpty()) {
								$h .= '<div class="util-annotation">';
									$h .= \selling\CustomerUi::getCategory($eCustomer);
								$h .= '</div>';
							}
						$h .= '</td>';

						$h .= '<td class="sale-item-status text-center">';
							$h .= '<span class="btn btn-xs sale-preparation-status-'.$eSale['preparationStatus'].'-button">'.\selling\SaleUi::p('preparationStatus')->values[$eSale['preparationStatus']].'</span>';
						$h .= '</td>';

						$h .= '<td class="sale-item-created-at text-center">';
							$h .= \util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE);
						$h .= '</td>';

						$h .= '<td class="highlight-stick-right  sale-item-price text-end">';
							$h .= \selling\SaleUi::getTotal($eSale);
						$h .= '</td>';

					$h .= '</tr>';

				$h .= '</tbody>';

				$h .= '<tbody>';

				foreach($cItem as $eItem) {

					$h .= '<tr>';

						$h .= '<td class="td-checkbox">';
						$h .= '<input type="checkbox" name="batch[]" batch-type="item" value="'.$eItem['id'].'" oninput="Preaccounting.changeSelection(this)"/>';
						$h .= '</td>';

						$h .= '<td></td>';

						$h .= '<td class="sale-item-name">';
							$h .= encode($eItem['name']);
						$h .= '</td>';

						$h .= '<td class="sale-item-status">';
						$h .= '</td>';

						$h .= '<td class="sale-item-created-at">';
						$h .= '</td>';

						$h .= '<td class="sale-item-price text-end">';
						$h .= '</td>';

					$h .= '</tr>';

				}

				$h .= '</tbody>';

			}

		$h .= '</table>';

		$h .= $this->getBatch('item');

		return $h;

	}

	public function getCategories(\farm\Farm $eFarm, \Collection $cCategory, array $products, int $toVerifyItems, \Search $search): string {

		$h = '';

		if($cCategory->notEmpty()) {

			$isItemSelected = ($search->get('tab') === 'items');
			if($isItemSelected) {
				$eCategorySelected = new \selling\Category();
			} else {
				$eCategorySelected = $search->get('tab');
			}

			$url = \company\CompanyUi::urlFarm($eFarm).'/precomptabilite?type=product&from='.$search->get('from').'&to='.$search->get('to');

			$list = function(string $class) use ($eFarm, $cCategory, $eCategorySelected, $isItemSelected, $products, $toVerifyItems, $url) {

				$h = '';

				foreach($cCategory as $eCategory) {

					if(($products[$eCategory['id']] ?? 0) > 0) {
						$h .= '<a href="'.$url.'&tab='.$eCategory['id'].'" class="'.$class.' '.(($eCategorySelected->notEmpty() and $eCategorySelected['id'] === $eCategory['id']) ? 'selected' : '').'" >'.encode($eCategory['name']).' <small class="'.$class.'-count">'.($products[$eCategory['id']] ?? 0).'</small></a>';
					}

				}

				$uncategorized = ($products[0] ?? 0);

				if($uncategorized > 0) {

					$h .= '<a data-ajax="'.$url.'&tab=0" data-ajax-method="get" class="'.$class.' '.(($eCategorySelected->empty() and $isItemSelected === FALSE) ? 'selected' : '').'" data-step="product" data-tab="0">'.s("Non catégorisé").' <small class="'.$class.'-count">'.$uncategorized.'</small></a>';

				}

				if($toVerifyItems > 0) {

					$h .= '<a data-ajax="'.$url.'&tab=items" data-ajax-method="get" class="'.$class.' '.($isItemSelected ? 'selected' : '').'" data-step="product" data-tab="items">'.s("Articles").' <small class="'.$class.'-count">'.$toVerifyItems.'</small></a>';
				}

				return $h;

			};

			if($cCategory->count() > 4) {

				$h .= '<div class="btn-group mb-1">';
					$h .= '<div class="btn btn-group-addon btn-outline-primary">'.s("Catégorie").'</div>';
						$h .= '<a class="dropdown-toggle btn btn-primary" data-dropdown="bottom-start" data-dropdown-hover="true" data-dropdown-id="product-dropdown-categories">';
							$h .= ($eCategorySelected->notEmpty() ? encode($cCategory[$eCategorySelected['id']]['name']) : s("Non catégorisé"));
							$h .= '<small class="dropdown-item-count">'.($products[$eCategorySelected->notEmpty() ? $eCategorySelected['id'] : NULL] ?? 0).'</small>';
						$h .= '</a>';
					$h .= '</div>';
					$h .= '<div class="dropdown-list '.($cCategory->count() > 10 ? 'dropdown-list-2' : '').'" data-dropdown-id="product-dropdown-categories-list">';
						$h .= '<div class="dropdown-title">'.s("Catégories").'</div>';
						$h .= $list('dropdown-item');
					$h .= '</div>';
				$h .= '</div>';

			} else {

				$h .= '<div class="tabs-item">';
					$h .= $list('tab-item');
				$h .= '</div>';

			}

		}

		return $h;

	}

	public function products(\farm\Farm $eFarm, \Collection $cProduct, \Collection $cCategory, array $products, \Search $search, array $itemData): string {


		if(empty($products) and empty($itemData['cItem'])) {

			return '<div class="util-success">'.s("Tous vos produits ont un compte associé !").'</div>';

		}

		$h = $this->getCategories($eFarm, $cCategory, $products, $itemData['nToCheck'], $search);

		if($search->get('tab') === 'items') {

			$h .= $this->items($itemData['cItem']);

		} else {

			$form = new \util\FormUi();
			parse_str(mb_substr(LIME_REQUEST_ARGS, 1), $args);
			unset($args['profile']);
			unset($args['name']);
			unset($args['plant']);

			$h .= '<div class="mb-2">';
				$h .= $form->openUrl(LIME_REQUEST_PATH.'?'.http_build_query($args), ['id' => 'preaccounting-payment-product', 'method' => 'get']);
					$h .= '<div style="display: flex; column-gap: 0.5rem;">';
						$h .= $form->select('profile', \selling\ProductUi::p('profile')->values, $search->get('profile'), ['placeholder' => s("Type")]);
						$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom du produit")]);
						$h .= $form->text('plant', $search->get('plant'), ['placeholder' => s("Espèce")]);
						$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
						$h .= '<a href="'.LIME_REQUEST_PATH.'?'.http_build_query($args).'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
					$h .= '</div>';
				$h .= $form->close();
			$h .= '</div>';

			if($cProduct->empty()) {
				if($search->get('profile') or $search->get('name') or $search->get('plant')) {
					$h .= '<div class="util-info">'.s("Il n'y a pas de produit sans numéro de compte à afficher avec vos critères de recherche.").'</div>';
				} else {
					$h .= '<div class="util-info">'.s("Il n'y a plus de produit sans numéro de compte à afficher.").'</div>';
				}
				return $h;
			}


			$h .= '<table class="tr-even" data-batch="#batch-accounting-product">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="td-checkbox">';
							$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="product" onclick="Preaccounting.toggleGroupSelection(this)"/>';
						$h .= '</th>';
						$h .= '<th></th>';
						$h .= '<th>'.s("Nom").'</th>';
						$h .= '<th class="text-center">'.s("Numéro de compte").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cProduct as $eProduct) {

						$h .= '<tr>';
							$h .= '<td class="td-checkbox">';
								$h .= '<label>';
									$h .= '<input type="checkbox" name="batch[]" value="'.$eProduct['id'].'" batch-type="product" oninput="Preaccounting.changeSelection(this)"/>';
								$h .= '</label>';
							$h .= '</td>';

							$h .= '<td class="product-item-vignette">';
								$h .= new \media\ProductVignetteUi()->getCamera($eProduct, size: '4rem');
							$h .= '</td>';

							$h .= '<td class="product-item-name">';
								$h .= \selling\ProductUi::getInfos($eProduct);
							$h .= '</td>';

							$h .= '<td class="text-center">';

								if($eProduct['privateAccount']->notEmpty()) {

									$value = '<span data-dropdown="bottom" data-dropdown-hover="true">';
										$value .= $eProduct['privateAccount']['class'];
									$value .= '</span>';
									$value .= new \account\AccountUi()->getDropdownTitle($eProduct['privateAccount']);

								} else {

									$value = '<span class="btn btn-outline-secondary">'.s("Définir").'</span>';

								}

								$h .= $eProduct->quick('privateAccount', $value);

							$h .= '</td>';
						$h .= '</tr>';
					}

				$h .= '</tbody>';

			$h .= '</table>';

			$h .= $this->getBatch('product');

		}

		return $h;

	}

	public function getBatch(string $type): string {

		$url = match($type) {
			'product' => '/selling/product:updateAccount',
			'item' => '/selling/item:updateAccount',
		};
		$title = match($type) {
			'product' => s("Pour les produits sélectionnés"),
			'item' => s("Pour les articles sélectionnés"),
		};

		$menu = '<a data-ajax-submit="'.$url.'" data-ajax-method="get" class="batch-item">'.\Asset::icon('journal-text').'<span>'.s("Numéro de compte").'</span></a>';

		return \util\BatchUi::group('batch-accounting-'.$type, $menu, title: $title);

	}

	public function getLinkToReconciliate(\farm\Farm $eFarm, int $nSuggestion): string {

		if($nSuggestion === 0) {
			return '';
		}

		$h = '<a class="btn btn-success bg-accounting border-accounting" href="'.\company\CompanyUi::urlFarm($eFarm).'/precomptabilite:rapprocher-factures">';
			$h .= \Asset::icon('fire').' ';
			$h .= '<span class="hide-md-up">';
				$h .= $nSuggestion;
			$h .= '</span>';
			$h .= '<span class="hide-sm-down">';
				$h .= p("{value} facture à rapprocher", "{value} factures à rapprocher", $nSuggestion);
			$h .= '</span>';
		$h .= '</a>';

		return $h;
	}

	public function export(\farm\Farm $eFarm, int $errors, int $nProduct, int $nSalePayment, int $nSaleClosed, bool $isSearchValid, \Search $search): string {
		
		$form = new \util\FormUi();
		
		$urlProduct = \company\CompanyUi::urlFarm($eFarm).'/precomptabilite?type=product';
		$urlPayment = \company\CompanyUi::urlFarm($eFarm).'/precomptabilite?type=payment';
		$urlClosed = \company\CompanyUi::urlFarm($eFarm).'/precomptabilite?type=closed';

		$h = '';

		if($errors > 0) {
			if($nProduct > 0) {
				if($nSalePayment > 0) {
					if($nSaleClosed > 0) {
						$check = s("Vérifiez <link>{icon} vos produits</link>, <link2>{icon2} les moyens de paiement</link2> et <link3>{icon3} la clôture de vos ventes</link3>.", [
							'icon' => \Asset::icon('1-circle'), 'link' => '<a href="'.$urlProduct.'">',
							'icon2' => \Asset::icon('2-circle'), 'link2' => '<a href="'.$urlPayment.'">',
							'icon3' => \Asset::icon('3-circle'), 'link3' => '<a href="'.$urlClosed.'">',
						]);
					} else {
						$check = s("Vérifiez <link>{icon} vos produits</link> et les <link2>{icon2} moyens de paiement</link2>.", [
							'icon' => \Asset::icon('1-circle'), 'link' => '<a href="'.$urlProduct.'">',
							'icon2' => \Asset::icon('2-circle'), 'link2' => '<a href="'.$urlPayment.'">',
						]);
					}
				} else if($nSaleClosed > 0) {
					$check = s("Vérifiez <link>{icon} vos produits</link> et <link3>{icon3} la clôture de vos ventes</link3>.", [
						'icon' => \Asset::icon('1-circle'), 'link' => '<a href="'.$urlProduct.'">',
						'icon3' => \Asset::icon('3-circle'), 'link3' => '<a href="'.$urlClosed.'">',
					]);
				} else {
					$check = s("Vérifiez <link>{icon} vos produits</link>.", [
						'icon' => \Asset::icon('1-circle'), 'link' => '<a href="'.$urlProduct.'">',
					]);
				}
			} else if($nSalePayment > 0) {
				if($nSaleClosed > 0) {
					$check = s("Vérifiez <link2>{icon2} les moyens de paiement</link2> et <link3>{icon3} la clôture de vos ventes</link3>.", [
						'icon2' => \Asset::icon('2-circle'), 'link2' => '<a href="'.$urlPayment.'">',
						'icon3' => \Asset::icon('3-circle'), 'link3' => '<a href="'.$urlClosed.'">',
					]);
				} else {
					$check = s("Vérifiez <link2>{icon2} les moyens de paiement</link2>.", [
						'icon2' => \Asset::icon('2-circle'), 'link2' => '<a href="'.$urlPayment.'">',
					]);
				}
			} else {
					$check = s("Vérifiez <link3>{icon3} la clôture de vos ventes</link3>.", [
						'icon3' => \Asset::icon('3-circle'), 'link3' => '<a href="'.$urlClosed.'">',
					]);
			}
			$h .= '<div class="util-outline-block-important">'.s("Certaines données sont manquantes ({check}).", ['check' => $check]).'</div>';

		}

		$h .= '<div class="step-bloc-export">';
			$h .= '<div class="util-block-optional">';

				if($isSearchValid) {

					$attributes = [
						'href' => \company\CompanyUi::urlFarm($eFarm).'/precomptabilite:fec?from='.$search->get('from').'&to='.$search->get('to'),
						'data-ajax-navigation' => 'never',
					];
					$class = ($errors > 0 ? 'btn-warning' : 'btn-secondary');

				} else {
					$attributes = [
						'href' => 'javascript: void(0);',
					];
					$class = 'btn-secondary disabled';
				}
				$h .= '<h3>'.s("Exportez votre fichier des écritures comptables").'</h3>';

				if($errors > 0) {
					$h .= '<p class="util-info">'.s("Vous pouvez faire un export du FEC mais il sera incomplet et un travail de configuration sera nécessaire lors de l'import").'</p>';
				} else {
					$h .= '<p>'.s("Vous pouvez importer ce fichier dans votre logiciel de comptabilité habituel pour y retrouver toutes vos ventes ventilées par numéro de compte.").'</p>';
				}

				$h .= '<a '.attrs($attributes).'>'.$form->button(s("Télécharger le fichier"), ['class' => 'btn '.$class]).'</a>';

			$h .= '</div>';

			$h .= '<div class="util-block-optional">';

				$h .= '<h3>'.s("Intégrez vos ventes dans votre comptabilité").'</h3>';

				if($errors > 0) {
					$h .= '<p class="util-info">'.s("Des données étant manquantes, l'import n'est pas possible.").'</p>';
				} else {
					$h .= '<p>'.s("Rendez-vous dans votre journal pour y importer vos ventes !").'</p>';
				}
				$class = 'btn btn-primary';
				if($errors > 0) {
					$class .= ' disabled';
					$url = 'javascript: void(0);';
				} else {
					$url = \company\CompanyUi::urlFarm($eFarm).'/precomptabilite:importer';
				}
				$h .= '<a href="'.$url.'" class="'.$class.'">'.s("Importer dans ma comptabilité").'</a>';

			$h .= '</div>';
		$h .= '</div>';

		return $h;
	}
}
?>

