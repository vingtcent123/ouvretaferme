<?php
namespace preaccounting;

Class PreaccountingUi {

	public function __construct() {

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

	public function explainExport(\farm\Farm $eFarm): string {

		$h = '<div class="util-block-help">';

			$h .= '<p>'.s("L'export qui vous est proposé est un export au format FEC.<br />Cependant, certaines informations obligatoires peuvent être manquantes et ne seront donc donc pas indiquées.").'</p>';
			$h .= '<p>'.s("Vous trouverez plus de détails <link>dans l'aide {icon}</link>.", ['link' => '<a href="/doc/accounting" target="_blank">', 'icon' => \Asset::icon('person-raised-hand')]).'</p>';

		$h .= '</div>';

		return $h;
	}

	public function salesPayment(string $type, \Collection $cSale, \Collection $cPaymentMethod, int $nToCheck, int $nVerified): string {

		\Asset::css('selling', 'sale.css');

		$h = '<table class="tr-even" data-batch="#batch-accounting-sale-'.$type.'">';

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

		$url = \company\CompanyUi::urlFarm($eFarm).'/precomptabilite/'.$type.'?from='.$search->get('from').'&to='.$search->get('to');

		if(count($nToCheck) > 1) {

			$h .= '<div class="tabs-item">';
				foreach($nToCheck as $tab => $count) {

					$h .= '<a data-ajax="'.$url.'&tab='.$tab.'"  data-ajax-method="get" class="tab-item '.(($search->get('tab') === $tab) ? 'selected' : '').'" data-step="'.$type.'" data-tab="'.$tab.'">';
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
								$h .= '<input type="checkbox" name="batch[]" batch-type="invoie-'.$type.'" value="'.$eInvoice['id'].'" oninput="Preaccounting.changeSelection(this)"/>';
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

			$menu .= '<a data-dropdown="top-start" class="batch-menu-payment-method batch-menu-item">';
				$menu .= \Asset::icon('cash-coin');
				$menu .= '<span style="letter-spacing: -0.2px">'.s("Moyen de<br />paiement").'</span>';
			$menu .= '</a>';

			$menu .= '<div class="dropdown-list bg-secondary">';

				$menu .= '<div class="dropdown-title">'.s("Moyen de paiement").'</div>';
				foreach($cPaymentMethod as $ePaymentMethod) {
					if($ePaymentMethod['online'] === FALSE) {
						$menu .= '<a data-ajax-submit="/selling/sale:doUpdatePaymentMethodCollection" data-ajax-target="#batch-accounting-sale-'.$type.'-form" post-for="preaccounting" post-payment-method="'.$ePaymentMethod['id'].'" class="dropdown-item">'.\payment\MethodUi::getName($ePaymentMethod).'</a>';
					}
				}

			$menu .= '</div>';

		}

		return \util\BatchUi::group('batch-accounting-sale-'.$type, $menu, title: s("Pour les ventes sélectionnées"));

	}

	public function getBatchInvoices(string $type, \Collection $cPaymentMethod): string {

		$menu = '';

		if($type === 'payment') {

			$menu .= '<a data-dropdown="top-start" class="batch-menu-payment-method batch-menu-item">';
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

		return \util\BatchUi::group('batch-accounting-sale-'.$type, $menu, title: s("Pour les ventes sélectionnées"));

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

			$url = \company\CompanyUi::urlFarm($eFarm).'/precomptabilite/product?from='.$search->get('from').'&to='.$search->get('to');

			$list = function(string $class) use ($eFarm, $cCategory, $eCategorySelected, $isItemSelected, $products, $toVerifyItems, $url) {

				$h = '';

				foreach($cCategory as $eCategory) {

					if(($products[$eCategory['id']] ?? 0) > 0) {
						$h .= '<a data-ajax="'.$url.'&tab='.$eCategory['id'].'" class="'.$class.' '.(($eCategorySelected->notEmpty() and $eCategorySelected['id'] === $eCategory['id']) ? 'selected' : '').'" data-ajax-method="get" data-step="product" data-tab="'.$eCategory['id'].'">'.encode($eCategory['name']).' <small class="'.$class.'-count">'.($products[$eCategory['id']] ?? 0).'</small></a>';
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

	public function products(\farm\Farm $eFarm, \Collection $cProduct, int $nToCheck, int $nVerified, \Collection $cCategory, array $products, \Search $search, array $itemData): string {


		if(empty($products) and empty($itemData['cItem'])) {

			return '<div class="util-success">'.s("Tous vos produits ont un compte associé !").'</div>';

		}

		$h = $this->getCategories($eFarm, $cCategory, $products, $itemData['nToCheck'], $search);

		if($search->get('tab') === 'items') {

			$h .= $this->items($itemData['cItem']);

		} else {

			$h .= '<table class="tr-even" data-batch="#batch-accounting-product">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th rowspan="2" class="td-checkbox">';
							$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="product" onclick="Preaccounting.toggleGroupSelection(this)"/>';
						$h .= '</th>';
						$h .= '<th rowspan="2"></th>';
						$h .= '<th rowspan="2">'.s("Nom").'</th>';
						$h .= '<th colspan="2" class="text-center">'.s("Compte").'</th>';
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.s("Particulier").'</th>';
						$h .= '<th class="text-center">'.s("Professionnel").'</th>';
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

									$value = '<i>'.s("Non défini").'</i>';

								}

								$h .= $eProduct->quick('privateAccount', $value);

							$h .= '</td>';

							$h .= '<td class="text-center">';

								if($eProduct['proAccount']->notEmpty()) {

									$value = '<span data-dropdown="bottom" data-dropdown-hover="true">';
									$value .= $eProduct['proAccount']['class'];
									$value .= '</span>';
									$value .= new \account\AccountUi()->getDropdownTitle($eProduct['proAccount']);


								} else {

									$value = '<i>'.s("Non défini").'</i>';

								}

								$h .= $eProduct->quick('proAccount', $value);

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

		$menu = '<a data-ajax-submit="'.$url.'" data-ajax-method="get" class="batch-menu-item">'.\Asset::icon('journal-text').'<span>'.s("Compte").'</span></a>';

		return \util\BatchUi::group('batch-accounting-'.$type, $menu, title: $title);

	}

}
?>

