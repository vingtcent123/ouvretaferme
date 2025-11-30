<?php
namespace selling;

Class AccountingUi {

	public function __construct() {

		\Asset::css('selling', 'sale.css');
		\Asset::js('selling', 'accounting.js');

	}

	public function getSearch(\Search $search): string {

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
				$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
			$h .= '</div>';

		$h .= $form->close();

		$h .= '<div>'.$form->button(s("Exporter"), ['class' => 'btn btn-warning', 'data-url' => '/selling/csv:exportAccounting?id=7', 'onclick' => 'SellingAccounting.export();', 'data-accounting-action' => 'export']).'</div>';

		$h .= '</div>';

		return $h;

	}

	public function salesTab(\farm\Farm $eFarm, \Search $search, array $nSale): string {

		$url = \farm\FarmUi::urlSellingSalesAccounting($eFarm).'/sale/?from='.$search->get('from').'&to='.$search->get('to').'&type=';

		$h = '<div class="tabs-item">';

		foreach([
			'preparationStatus' => s("Non livrées"),
			'missingPayment' => s("Sans moyen de paiement"),
			'notClosed' => s("Non clôturées"),
		] as $type => $title) {

			if($nSale[$type] === 0) {
				continue;
			}
			$h .= '<a data-ajax="'.$url.$type.'" data-ajax-method="get" class="tab-item" data-sale-type="'.$type.'">'.$title.'  <small class="tab-item-count">'.($nSale[$type]).'</small></a>';

		}

		$h .= '</div>';

		$h .= '<div data-step="sale-content">';
		$h .= '</div>';

		return $h;
	}

	public function sales(\Collection $cSale, \Collection $cPaymentMethod, string $type): string {

		$h = '<table class="tr-even" data-batch="#batch-accounting-sale">';

			$h .= '<thead>';

				$h .= '<tr>';

					$h .= '<th class="text-center">';
						$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="sale" onclick="SellingAccounting.toggleGroupSelection(this)"/>';
					$h .= '</th>';
					$h .= '<th>#</th>';

					$h .= '<th>'.s("Prénom / Nom - Article").'</th>';
					$h .= '<th class="text-center">'.s("État").'</th>';
					$h .= '<th class="text-center">'.s("Clôturée").'</th>';
					$h .= '<th class="text-center">'.s("Vente").'</th>';
					$h .= '<th class="highlight-stick-right text-end">'.s("Montant").'</th>';
					$h .= '<th>'.s("Moyen de paiement").'</th>';
				$h .= '</tr>';

			$h .= '</thead>';

			$h .= '<tbody>';

				foreach($cSale as $eSale) {

					$h .= '<tr>';

						$h .= '<td class="td-checkbox">';
							$h .= '<input type="checkbox" name="batch[]" batch-type="sale" value="'.$eSale['id'].'" oninput="SellingAccounting.changeSelection(this)"/>';
						$h .= '</td>';

						$h .= '<td>';
							$h .= '<a href="/vente/'.$eSale['id'].'" class="btn btn-sm '.($eSale['deliveredAt'] === currentDate() ? 'btn-primary' : 'btn-outline-primary').'">'.$eSale->getNumber().'</a>';
						$h .= '</td>';

						$h .= '<td class="sale-item-name">';
							if($eSale['profile'] === Sale::SALE_MARKET) {
								$h .= encode($eSale['marketParent']['customer']->getName());
							} else {

								$h .= encode($eSale['customer']->getName());
								if($eSale['customer']->notEmpty()) {
									$h .= '<div class="util-annotation">';
										$h .= CustomerUi::getCategory($eSale['customer']);
									$h .= '</div>';
								}

							}
						$h .= '</td>';

						$h .= '<td class="sale-item-status text-center">';
							$h .= '<span class="btn btn-md sale-preparation-status-'.$eSale['preparationStatus'].'-button">'.SaleUi::p('preparationStatus')->values[$eSale['preparationStatus']].'</span>';
						$h .= '</td>';

						$h .= '<td class="sale-item-status text-center">';
							if($eSale['closed']) {
								$h .= '<span class="color-success">'.s("Oui").'</span>';
							} else {
								$h .= '<span class="util-danger">'.s("Non").'</span>';
							}
						$h .= '</td>';

						$h .= '<td class="sale-item-created-at text-center">';
							$h .= \util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE);
						$h .= '</td>';

						$h .= '<td class="highlight-stick-right sale-item-price text-end">';
							$h .= SaleUi::getTotal($eSale);
						$h .= '</td>';

						$h .= '<td class="sale-item-payment-type">';

							if($eSale['cPayment']->empty()) {

								$h .= '<span class="util-danger">'.s("Aucun").'</span>';
								$problems[] = 'paymentMethod';

							} else {

								$h .= SaleUi::getPaymentMethodName($eSale);

								$paymentStatus = SaleUi::getPaymentStatus($eSale);
								if($paymentStatus) {
									$h .= '<div style="margin-top: 0.25rem">'.$paymentStatus.'</div>';
								}

							}

						$h .= '</td>';

					$h .= '</tr>';

				}

			$h .= '</tbody>';

		$h .= '</table>';

		$h .= $this->getBatchSales($cPaymentMethod);

		return $h;

	}

	public function getBatchSales(\Collection $cPaymentMethod): string {

		$menu = '<a data-ajax-submit="/selling/sale:doUpdateDeliveredCollection" data-confirm="'.s("Marquer ces ventes comme livrées ?").'" class="batch-menu-delivered batch-menu-item">';
		$menu .= '<span class="btn btn-xs sale-preparation-status-batch sale-preparation-status-delivered-button">'.SaleUi::p('preparationStatus')->shortValues[Sale::DELIVERED].'</span>';
		$menu .= '<span>'.s("Livré").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/selling/sale:doUpdateCanceledCollection" data-confirm="'.s("Annuler ces ventes ?").'" class="batch-menu-cancel batch-menu-item">';
		$menu .= '<span class="btn btn-xs sale-preparation-status-batch sale-preparation-status-draft-button">'.SaleUi::p('preparationStatus')->shortValues[Sale::CANCELED].'</span>';
		$menu .= '<span>'.s("Annuler").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-dropdown="top-start" class="batch-menu-payment-method batch-menu-item">';
		$menu .= \Asset::icon('cash-coin');
		$menu .= '<span style="letter-spacing: -0.2px">'.s("Changer de moyen<br/>de paiement").'</span>';
		$menu .= '</a>';

		$menu .= '<div class="dropdown-list bg-secondary">';
		$menu .= '<div class="dropdown-title">'.s("Changer de moyen de paiement").'</div>';
		foreach($cPaymentMethod as $ePaymentMethod) {
			if($ePaymentMethod['online'] === FALSE) {
				$menu .= '<a data-ajax-submit="/selling/sale:doUpdatePaymentMethodCollection" data-ajax-target="#batch-accounting-sale-form" post-payment-method="'.$ePaymentMethod['id'].'" class="dropdown-item">'.\payment\MethodUi::getName($ePaymentMethod).'</a>';
			}
		}

		$danger = '<a data-ajax-submit="/selling/sale:doDeleteCollection" data-confirm="'.s("Confirmer la suppression de ces ventes ?").'" class="batch-menu-delete batch-menu-item batch-menu-item-danger">';
		$danger .= \Asset::icon('trash');
		$danger .= '<span>'.s("Supprimer").'</span>';
		$danger .= '</a>';

		return \util\BatchUi::group('batch-accounting-sale', $menu, $danger, title: s("Pour les ventes sélectionnées"));

	}

	public function items(\Collection $ccItem): string {

		$h = '<table class="tr-even" data-batch="#batch-accounting-item">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th></th>';
					$h .= '<th>#</th>';
					$h .= '<th>'.s("Prénom / Nom - Article").'</th>';
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
							$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="item" onclick="SellingAccounting.toggleGroupSelection(this)"/>';
						$h .= '</td>';

						$h .= '<td>';
							$h .= '<a href="/vente/'.$eSale['id'].'" class="btn btn-sm '.($eSale['deliveredAt'] === currentDate() ? 'btn-primary' : 'btn-outline-primary').'">'.$eSale->getNumber().'</a>';
						$h .= '</td>';

						$h .= '<td class="sale-item-name">';
							$h .= encode($eCustomer->getName());
							if($eCustomer->notEmpty()) {
								$h .= '<div class="util-annotation">';
									$h .= CustomerUi::getCategory($eCustomer);
								$h .= '</div>';
							}
						$h .= '</td>';

						$h .= '<td class="sale-item-status text-center">';
							$h .= '<span class="btn btn-xs sale-preparation-status-'.$eSale['preparationStatus'].'-button">'.SaleUi::p('preparationStatus')->values[$eSale['preparationStatus']].'</span>';
						$h .= '</td>';

						$h .= '<td class="sale-item-created-at text-center">';
							$h .= \util\DateUi::numeric($eSale['deliveredAt'], \util\DateUi::DATE);
						$h .= '</td>';

						$h .= '<td class="highlight-stick-right  sale-item-price text-end">';
							$h .= SaleUi::getTotal($eSale);
						$h .= '</td>';

					$h .= '</tr>';

				$h .= '</tbody>';

				$h .= '<tbody>';

				foreach($cItem as $eItem) {

					$h .= '<tr>';

						$h .= '<td class="td-checkbox">';
						$h .= '<input type="checkbox" name="batch[]" batch-type="item" value="'.$eItem['id'].'" oninput="SellingAccounting.changeSelection(this)"/>';
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
	public function products(\farm\Farm $eFarm, \Collection $ccProduct): string {

		$h = '<table class="tr-even" data-batch="#batch-accounting-product">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th rowspan="2">';
					$h .= '</th>';
					$h .= '<th rowspan="2"></th>';
					$h .= '<th rowspan="2">'.s("Nom").'</th>';
					$h .= '<th colspan="2" class="text-center">'.s("Classe de compte").'</th>';
				$h .= '</tr>';
				$h .= '<tr>';
					$h .= '<th class="text-center">'.s("Particulier").'</th>';
					$h .= '<th class="text-center">'.s("Professionnel").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';

			foreach($ccProduct as $cProduct) {

				$eCategory = $cProduct->first()['category'];

				$h .= '<tbody>';

					$h .= '<tr class="tr-title">';
						$h .= '<td class="td-checkbox">';
							$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="product" onclick="SellingAccounting.toggleGroupSelection(this)"/>';
						$h .= '</td>';
						$h .= '<td colspan="5">';
							if($eCategory->empty()) {
								$h .= s("Sans catégorie");
							} else {
								$h .= encode($eCategory['name']);
							}
						$h .= '</td>';
					$h .= '</tr>';
				$h .= '</tbody>';


				$h .= '<tbody>';

					foreach($cProduct as $eProduct) {

						$h .= '<tr>';
							$h .= '<td class="td-checkbox">';
								$h .= '<label>';
									$h .= '<input type="checkbox" name="batch[]" value="'.$eProduct['id'].'" batch-type="product" oninput="SellingAccounting.changeSelection(this)"/>';
								$h .= '</label>';
							$h .= '</td>';

							$h .= '<td class="product-item-vignette">';
								$h .= new \media\ProductVignetteUi()->getCamera($eProduct, size: '4rem');
							$h .= '</td>';

							$h .= '<td class="product-item-name">';
								$h .= ProductUi::getInfos($eProduct);
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

			}

		$h .= '</table>';

		$h .= $this->getBatch('product');

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

		$menu = '<a data-ajax-submit="'.$url.'" data-ajax-method="get" class="batch-menu-item">'.\Asset::icon('journal-text').'<span>'.s("Classe de compte").'</span></a>';

		return \util\BatchUi::group('batch-accounting-'.$type, $menu, title: $title);

	}

}
?>

