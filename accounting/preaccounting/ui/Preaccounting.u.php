<?php
namespace preaccounting;

Class PreaccountingUi {

	public function __construct() {

		\Asset::css('preaccounting', 'preaccounting.css');
		\Asset::js('preaccounting', 'preaccounting.js');

	}

	public function getSearch(\farm\Farm $eFarm, \Search $search, string $type, bool $hasOperations = FALSE): string {

		$h = '<div class="util-block-search">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Période").'</legend>';
					$h .= $form->inputGroup($form->addon(s("Du")).
						$form->date('from', $search->get('from')).
						$form->addon(s("au")).
						$form->date('to', $search->get('to'))
					);
				$h .= '</fieldset>';

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Client").'</legend>';
					$h .= $form->dynamicField(new \selling\Invoice(['farm' => $eFarm, 'customer' => $search->get('customer')]), 'customer');
				$h .= '</fieldset>';

				if($search->get('cGroup') and $search->get('cGroup')->notEmpty()) {
					$h .= '<fieldset>';
						$h .= '<legend>'.s("Groupe").'</legend>';
						$h .= $form->select('group', $search->get('cGroup'), $search->get('group'));
					$h .= '</fieldset>';
				}

				if($search->get('cMethod') and $search->get('cMethod')->notEmpty()) {
					$h .= '<fieldset>';
						$h .= '<legend>'.s("Moyen de paiement").'</legend>';
						$h .= $form->select('method', $search->get('cMethod'), $search->get('method'));
					$h .= '</fieldset>';
				}

				$h .= '<div class="util-search-submit">';
					$h .= $form->submit(s("Valider"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-outline-secondary">'.\Asset::icon('x-lg').'</a>';
					if($hasOperations) {
						$h .= '<a class="btn btn-outline-secondary" href="'.\company\CompanyUi::urlFarm($eFarm).'/precomptabilite/ventes:telecharger?from='.encode($search->get('from')).'&to='.encode($search->get('to')).'&customer='.($search->get('customer')['id'] ?? '').'&group='.($search->get('group')['id'] ?? '').'" data-ajax-navigation="never">'.\Asset::icon('download').' '.s("Télécharger l'export").'</a>';
					}

				$h .= '</div>';

			$h .= $form->close();

			if($type === 'invoices') {
				$h .= '<a href="'.\company\CompanyUi::urlFarm($eFarm).'/precomptabilite/ventes?from='.encode($search->get('from')).'&to='.encode($search->get('to')).'">'.s("Explorer les données comptables des ventes non facturées").'</a>';
			}
		$h .= '</div>';

		return $h;

	}

	public function invoices(\farm\Farm $eFarm, \Collection $cInvoice, \Collection $cPaymentMethod, \Search $search): string {

		\Asset::css('selling', 'sale.css');

		$form = new \util\FormUi();
		parse_str(mb_substr(LIME_REQUEST_ARGS, 1), $args);

		$h = '<div class="mb-2">';
			$h .= $form->openUrl(LIME_REQUEST_PATH.'?'.http_build_query($args), ['id' => 'preaccounting-payment-customer']);
				$h .= $form->dynamicField(new \selling\Invoice(['farm' => $eFarm, 'customer' => $search->get('customer')]), 'customer');
			$h .= $form->close();
		$h .= '</div>';

		if($cInvoice->empty()) {
			return $h.'<div class="util-info">'.s("Toutes les factures ont un moyen de paiement renseigné.").'</div>';
		}

		$h .= '<table class="tr-even" data-batch="#batch-accounting-invoice">';

			$h .= '<thead>';

				$h .= '<tr>';

					$h .= '<th class="text-center">';
						$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="invoice" onclick="Preaccounting.toggleGroupSelection(this)"/>';
					$h .= '</th>';
					$h .= '<th class="td-min-content">#</th>';
					$h .= '<th>'.s("Date").'</th>';
					$h .= '<th>'.s("Client").'</th>';
					$h .= '<th class="highlight-stick-right text-end">'.s("Montant").'</th>';
					$h .= '<th>'.s("Moyen de paiement").'</th>';
					$h .= '<th>'.s("État").'</th>';
				$h .= '</tr>';

			$h .= '</thead>';

			$h .= '<tbody>';

				foreach($cInvoice as $eInvoice) {

					$h .= '<tr>';

						$h .= '<td class="td-checkbox">';
							$h .= '<input type="checkbox" name="batch[]" batch-type="invoice" value="'.$eInvoice['id'].'" oninput="Preaccounting.changeSelection(this)"/>';
						$h .= '</td>';

						$h .= '<td class="td-min-content">';
							$h .= '<a href="/ferme/'.$eFarm['id'].'/factures?document='.encode($eInvoice['document']).'&customer='.encode($eInvoice['customer']['name']).'">'.encode($eInvoice['name']).'</a></td>';
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

						$h .= '<td>';
							$h .= '<div>'.\payment\MethodUi::getName($eInvoice['paymentMethod']).'</div>';
							if($eInvoice['paymentMethod']->empty()) {
								$h .= $form->dynamicField($eInvoice, 'paymentMethod', function($d) use($form, $cPaymentMethod, $eInvoice) {
									$d->values = $cPaymentMethod;
									$d->default = fn() => $eInvoice['paymentMethod'];
									$d->attributes['onchange'] = 'Preaccounting.updatePaymentMethod(this);';
									$d->attributes['onrender'] = '';
									$d->attributes['data-invoice'] = $eInvoice['id'];
									$d->attributes['data-payment-status'] = $eInvoice['paymentStatus'];
									if($eInvoice['paymentMethod']->notEmpty()) {
										$d->attributes['mandatory'] = TRUE;
									}
								});
							}
						$h .= '</td>';

						$h .= '<td class="sale-item-status">';
						$h .= new \selling\InvoiceUi()->getStatusForUpdate($eInvoice, 'btn-xs');
						$h .= '</td>';

					$h .= '</tr>';

				}

			$h .= '</tbody>';

		$h .= '</table>';

		$h .= $this->getBatchInvoices($cPaymentMethod);

		return $h;

	}

	public function getBatchInvoices(\Collection $cPaymentMethod): string {

		$menu = '';

		$menu .= '<a data-dropdown="top-start" class="batch-payment-method batch-item">';
			$menu .= \Asset::icon('cash-coin');
			$menu .= '<span style="letter-spacing: -0.2px">'.s("Choisir un moyen de paiement").'</span>';
		$menu .= '</a>';

		$menu .= '<div class="dropdown-list bg-secondary">';

			$menu .= '<div class="dropdown-title">'.s("Moyen de paiement").'</div>';
			foreach($cPaymentMethod as $ePaymentMethod) {
				if($ePaymentMethod['online'] === FALSE) {
					$menu .= '<a data-ajax-submit="/selling/invoice:doUpdatePaymentMethodCollection" data-ajax-target="#batch-accounting-invoice-form" post-for="preaccounting" post-payment-method="'.$ePaymentMethod['id'].'" class="dropdown-item">'.\payment\MethodUi::getName($ePaymentMethod).'</a>';
				}
			}

		$menu .= '</div>';

		$menu .= '<a data-ajax-submit="/selling/invoice:doUpdateRefuseReadyForAccountingCollection" data-confirm="'.s("En ignorant ces factures, elles ne seront jamais incluses dans les exports comptables. Continuer ?").'"  class="batch-ignore batch-item">'.\Asset::icon('hand-thumbs-down').'<span>'.s("Ignorer les factures").'</span></a>';


		return \util\BatchUi::group('batch-accounting-invoice', $menu, title: s("Pour les factures sélectionnées"));

	}

	public function items(\Collection $cItem): string {

			$h = '<table class="tr-even" data-batch="#batch-accounting-item">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="td-checkbox">';
							$h .= '<input type="checkbox" class="batch-all batch-all-group" batch-type="product" onclick="Preaccounting.toggleGroupSelection(this)"/>';
						$h .= '</th>';
						$h .= '<th>'.s("Nom").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

					foreach($cItem as $eItem) {

						$h .= '<tr>';
							$h .= '<td class="td-checkbox">';
								$h .= '<label>';
									$h .= '<input type="checkbox" name="batch[]" value="'.$eItem['name'].'" batch-type="item" oninput="Preaccounting.changeSelection(this)"/>';
								$h .= '</label>';
							$h .= '</td>';

							$h .= '<td class="product-item-name">';
								$h .= encode($eItem['name']);
							$h .= '</td>';

						$h .= '</tr>';
					}

				$h .= '</tbody>';

			$h .= '</table>';

			$h .= $this->getBatch('item');


		return $h;

	}

	public function getCategories(\farm\Farm $eFarm, \Collection $cCategory, array $products, int $toVerifyItems, \Search $search): string {

		$tab = \session\SessionLib::get('preAccountingProductTab');
		$h = '';

		if($cCategory->notEmpty()) {

			$isItemSelected = ($tab === 'items');
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

					$h .= '<a href="'.$url.'&tab=0" data-ajax-method="get" class="'.$class.' '.(($eCategorySelected === NULL or $eCategorySelected->empty() and $isItemSelected === FALSE) ? 'selected' : '').'" data-step="product" data-tab="0">'.s("Non catégorisé").' <small class="'.$class.'-count">'.$uncategorized.'</small></a>';

				}

				if($toVerifyItems > 0) {

					$h .= '<a href="'.$url.'&tab=items" data-ajax-method="get" class="'.$class.' '.($isItemSelected ? 'selected' : '').'" data-step="product" data-tab="items">'.s("Articles").' <small class="'.$class.'-count">'.$toVerifyItems.'</small></a>';
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

		if(empty($products) and $itemData['nToCheck'] === 0) {

			return '<div class="util-empty">'.s("Tous vos produits ont un numéro de compte associé !").'</div>';

		}

		$h = $this->getCategories($eFarm, $cCategory, $products, $itemData['nToCheck'], $search);

		$tab = \session\SessionLib::get('preAccountingProductTab');

		if($tab === 'items') {

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
						$h .= '<a href="'.LIME_REQUEST_PATH.'?'.http_build_query($args).'" class="btn btn-outline-secondary">'.\Asset::icon('x-lg').'</a>';
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

			$h = \Asset::icon('fire', ['class' => 'color-accounting']).' ';
			$h .= '<a class="color-accounting" href="'.\company\CompanyUi::urlFarm($eFarm).'/precomptabilite:rapprocher">';
				$h .= s("Aucune facture à rapprocher pour le moment");
			$h .= '</a>';
			
		} else {

			$h = '<a class="btn btn-success bg-accounting border-accounting" href="'.\company\CompanyUi::urlFarm($eFarm).'/precomptabilite:rapprocher">';
				$h .= \Asset::icon('fire').' ';
				$h .= '<span class="hide-md-up">';
					$h .= $nSuggestion;
				$h .= '</span>';
				$h .= '<span class="hide-sm-down">';
					$h .= p("{value} facture à rapprocher", "{value} factures à rapprocher", $nSuggestion);
				$h .= '</span>';
			$h .= '</a>';

		}

		return $h;
	}

	public function export(\farm\Farm $eFarm, int $nProduct, int $nPaymentToCheck, bool $isSearchValid, \Search $search): string {
		
		$form = new \util\FormUi();
		
		$urlProduct = \company\CompanyUi::urlFarm($eFarm).'/precomptabilite?type=product';
		$urlPayment = \company\CompanyUi::urlFarm($eFarm).'/precomptabilite?type=payment';

		$h = '';

		$errors = $nProduct + $nPaymentToCheck;
		if($errors > 0) {

			$h .= '<div class="util-block-important">';
				$h .= '<h3>'.s("Certaines données sont manquantes").'</h3>';

				if($nProduct > 0) {

					if($nPaymentToCheck > 0) {
						$h .= s("Vérifiez <link>{icon} vos produits</link> et les <link2>{icon2} moyens de paiement</link2>", [
							'icon' => \Asset::icon('1-circle'), 'link' => '<a href="'.$urlProduct.'">',
							'icon2' => \Asset::icon('2-circle'), 'link2' => '<a href="'.$urlPayment.'">',
						]);
					} else {
						$h .= s("Vérifiez <link>{icon} vos produits</link>", [
							'icon' => \Asset::icon('1-circle'), 'link' => '<a href="'.$urlProduct.'">',
						]);
					}

				} else if($nPaymentToCheck > 0) {
					$h .= s("Vérifiez <link2>{icon2} les moyens de paiement</link2>", [
						'icon2' => \Asset::icon('2-circle'), 'link2' => '<a href="'.$urlPayment.'">',
					]);
				}
			$h .= '</div>';

		}

		$h .= '<div class="step-bloc-export">';
			$h .= '<div class="util-block">';

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
				$h .= '<h3>'.s("Exportez un fichier {value}", '<span class="util-badge bg-primary">FEC</span>').'</h3>';

				if($errors > 0) {
					$h .= '<p class="util-info">'.s("Vous pouvez faire un export du fichier des écritures comptables mais il sera incomplet et un travail de configuration sera nécessaire lors de l'import").'</p>';
				} else {
					$h .= '<p>'.s("Vous pouvez importer ce fichier des écritures comptables dans votre logiciel de comptabilité habituel pour y retrouver toutes vos ventes ventilées par numéro de compte.").'</p>';
				}

				$h .= '<a '.attrs($attributes).'>'.$form->button(s("Télécharger le fichier"), ['class' => 'btn '.$class]).'</a>';

			$h .= '</div>';

			$h .= '<div class="util-block">';

				$h .= '<h3>'.s("Intégrez vos factures dans votre comptabilité").'</h3>';

				if($nProduct > 0) {
					$h .= '<p class="util-info">'.s("Des données étant manquantes, l'import n'est pas possible.").'</p>';
				} else {
					$h .= '<p>'.s("Rendez-vous dans votre journal pour y importer vos factures !").'</p>';
				}
				$class = 'btn btn-primary';
				if($nProduct > 0) {
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

