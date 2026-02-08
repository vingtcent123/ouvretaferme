<?php
namespace preaccounting;

Class PreaccountingUi {

	public function __construct() {

		\Asset::css('preaccounting', 'preaccounting.css');
		\Asset::js('preaccounting', 'preaccounting.js');

	}

	public function summarize(int $nInvoice, int $nSale, int $nCash): string {

		$h = '<ul class="util-summarize">';

			$h .= '<li>';
				$h .= '<div>';
					$h .= '<h5>'.p("Vente", "Ventes", $nSale).'</h5>';
					$h .= '<div>'.$nSale.'</div>';
				$h .= '</div>';
			$h .= '</li>';

			$h .= '<li>';
				$h .= '<div>';
					$h .= '<h5>'.p("Facture", "Factures", $nInvoice).'</h5>';
					$h .= '<div>'.$nInvoice.'</div>';
				$h .= '</div>';
			$h .= '</li>';

			if(CashLib::isActive()) {

				$h .= '<li>';
					$h .= '<div>';
						$h .= '<h5>'.p("Opération de caisse", "Opérations de caisse", $nCash).'</h5>';
						$h .= '<div>'.$nCash.'</div>';
					$h .= '</div>';
				$h .= '</li>';

			}
		$h .= '</ul>';

		return $h;

	}

	public function getSearchPeriod(\Search $search): string {

		$h = '<div class="util-block-search">';

			$form = new \util\FormUi();

			$h .= $form->openAjax(LIME_REQUEST_PATH, ['method' => 'get', 'class' => 'util-search']);

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Période").'</legend>';
					$h .= $form->inputGroup($form->addon(s("Du")).
						$form->date('from', $search->get('from')).
						$form->addon(s("au")).
						$form->date('to', $search->get('to'))
					);
				$h .= '</fieldset>';

				$h .= '<div>';
					$h .= $form->submit(s("Valider"));
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getSearchSales(\farm\Farm $eFarm, \Search $search): string {

		$h = '<div class="util-block-search">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search util-search-3']);

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Période").'</legend>';
					$h .= $form->inputGroup($form->addon(s("Du")).
						$form->date('from', $search->get('from')).
						$form->addon(s("au")).
						$form->date('to', $search->get('to'))
					);
				$h .= '</fieldset>';


				if(\preaccounting\CashLib::isActive() and $search->has('cRegister') and $search->get('cRegister')->count() > 0) {
					$values = [[
						'label' => s("Factures"),
						'values' => [
							'hasInvoice' => s("Ventes facturées"),
							'noInvoice' => s("Ventes livrées non facturées")
						]
					]];
					$values[] = [
						'label' => s("Journaux de caisse"),
						'values' => $search->get('cRegister')->makeArray(function($e, &$key) {
							$key = $e['id'];
							return $e['account']->notEmpty() ?
							s("{name}, numéro de compte {class}", ['name' => $e['paymentMethod']['name'], 'class' => $e['account']['class']]) :
							s("{name}", ['name' => $e['paymentMethod']['name']]);
						})
					];
					$h .= '<fieldset>';
						$h .= '<legend>'.s("Origine des ventes").'</legend>';
						$h .= $form->select('filter', $values, $search->get('filter'), ['placeholder' => s("Toutes les ventes livrées"), 'group' => TRUE]);
					$h .= '</fieldset>';

				} else {

					$h .= '<fieldset>';
						$h .= '<legend>'.s("Ventes").'</legend>';
						$h .= $form->select('filter', ['hasInvoice' => s("Ventes facturées"), 'noInvoice' => s("Ventes livrées non facturées")], $search->get('filter'), ['placeholder' => s("Toutes les ventes livrées")]);
					$h .= '</fieldset>';

				}

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Numéro de compte").'</legend>';
					$h .= $form->dynamicField(new \journal\Operation(['account' => $search->get('account')]), 'account', function($d) use($form, $eFarm) {
						$query = [
							'classPrefixes[0]' => \account\AccountSetting::PRODUCT_SOLD_ACCOUNT_CLASS,
							'classPrefixes[1]' => \account\AccountSetting::VAT_CLASS,
						];
						$index = 1;
						foreach(\account\AccountSetting::WAITING_ACCOUNT_CLASSES as $waitingClass) {
							$index++;
							$query['classPrefixes['.$index.']'] = $waitingClass;
						}
						$d->autocompleteUrl = function(\util\FormUi $form, $e) use ($eFarm, $query) {
							if($eFarm->empty()) {
								$eFarm = $e['farm'];
							}
							return \company\CompanyUi::urlAccount($eFarm).'/account:query?'.http_build_query($query);
						};
					});
				$h .= '</fieldset>';

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Client").'</legend>';
					$h .= $form->dynamicField(new \selling\Invoice(['farm' => $eFarm, 'customer' => $search->get('customer')]), 'customer');
				$h .= '</fieldset>';

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Moyen de paiement").'</legend>';
					$h .= $form->select('method', $search->get('cMethod'), $search->get('method'));
				$h .= '</fieldset>';

				$h .= '<div>';
					$h .= $form->submit(s("Valider"));
					if($search->notEmpty(['from', 'to', 'cMethod'])) {
						$h .= ' <a href="'.$url.'" class="btn">'.s("Réinitialiser").'</a>';
					}
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function invoices(\farm\Farm $eFarm, \Collection $cInvoice, \Collection $cPaymentMethod, \Search $search): string {

		\Asset::css('selling', 'sale.css');

		$form = new \util\FormUi();

		$h = '<div class="mb-2">';
			$h .= $form->openUrl(LIME_REQUEST, ['id' => 'preaccounting-payment-customer']);
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
							$h .= '<a href="/ferme/'.$eFarm['id'].'/factures?invoice='.encode($eInvoice['id']).'&customer='.encode($eInvoice['customer']['name']).'">'.encode($eInvoice['number']).'</a></td>';
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
								$h .= $form->openAjax('/selling/invoice:doUpdatePayment', ['id' => 'preaccounting-payment-customer', 'class' => 'flex-justify-space-between']);
								$h .= $form->hidden('paymentStatus', $eInvoice['paymentStatus'] ?? \selling\Invoice::NOT_PAID);
								$h .= $form->hidden('id', $eInvoice['id']);
								$h .= $form->dynamicField($eInvoice, 'paymentMethod', function($d) use($form, $cPaymentMethod, $eInvoice) {
									$d->values = $cPaymentMethod;
									$d->default = fn() => $eInvoice['paymentMethod'];
									$d->attributes['onrender'] = '';
									$d->attributes['onchange'] = '';
									$d->attributes['data-invoice'] = $eInvoice['id'];
									$d->attributes['data-payment-status'] = $eInvoice['paymentStatus'];
									if($eInvoice['paymentMethod']->notEmpty()) {
										$d->attributes['mandatory'] = TRUE;
									}
								});
								$h .= $form->submit(s("Valider"), ['class' => 'btn btn-xs btn-outline-secondary']);
								$h .= $form->close();
							}
						$h .= '</td>';

						$h .= '<td class="sale-item-status">';
							$h .= '<span class="btn btn-readonly invoice-status-'.$eInvoice['status'].'-button btn-xs">'.\selling\InvoiceUi::p('status')->values[$eInvoice['status']].'</span>';
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
					$menu .= '<a data-ajax-submit="/selling/invoice:doUpdatePaymentNotPaidCollection" data-ajax-target="#batch-accounting-invoice-form" post-for="preaccounting" post-payment-method="'.$ePaymentMethod['id'].'" class="dropdown-item">'.\payment\MethodUi::getName($ePaymentMethod).'</a>';
				}
			}

		$menu .= '</div>';

		$menu .= '<a data-ajax-submit="/selling/invoice:doUpdateRefuseReadyForAccountingCollection" data-confirm="'.s("En ignorant ces factures, elles ne seront jamais incluses dans les exports comptables. Continuer ?").'"  class="batch-ignore batch-item">'.\Asset::icon('hand-thumbs-down').'<span>'.s("Ignorer les factures").'</span></a>';


		return \util\BatchUi::group('batch-accounting-invoice', $menu, title: s("Pour les factures sélectionnées"));

	}

	public function items(\farm\Farm $eFarm, \Collection $cItem): string {

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

			$h .= $this->getBatch($eFarm, 'item');


		return $h;

	}

	public function getCategories(\farm\Farm $eFarm, \Collection $cCategory, array $products, int $toVerifyItems, \Search $search): string {

		$tab = \session\SessionLib::get('preAccountingProductTab');
		if($tab instanceof \selling\Category) {
			$tab = ($tab['id'] ?? NULL);
		}
		$h = '';

		$cCategory->filter(fn($e) => ($products[$e['id']] ?? 0) > 0);

		if($cCategory->notEmpty()) {

			$url = \farm\FarmUi::urlConnected($eFarm).'/precomptabilite?type=product&from='.$search->get('from').'&to='.$search->get('to');

			$list = function(string $class) use ($eFarm, $cCategory, $products, $toVerifyItems, $url, $tab) {

				$h = '';

				foreach($cCategory as $eCategory) {

					if(($products[$eCategory['id']] ?? 0) > 0) {
						$h .= '<a href="'.$url.'&tab='.$eCategory['id'].'" class="'.$class.' '.($eCategory['id'] === $tab ? 'selected' : '').'" >'.encode($eCategory['name']).' <small class="'.$class.'-count">'.($products[$eCategory['id']] ?? 0).'</small></a>';
					}

				}

				$uncategorized = ($products[0] ?? 0);

				if($uncategorized > 0) {

					$h .= '<a href="'.$url.'&tab=0" data-ajax-method="get" class="'.$class.' '.(NULL === $tab ? 'selected' : '').'" data-step="product" data-tab="0">'.s("Non catégorisé").' <small class="'.$class.'-count">'.$uncategorized.'</small></a>';

				}

				if($toVerifyItems > 0) {

					$h .= '<a href="'.$url.'&tab=items" data-ajax-method="get" class="'.$class.' '.($tab === 'items' ? 'selected' : '').'" data-step="product" data-tab="items">'.s("Articles").' <small class="'.$class.'-count">'.$toVerifyItems.'</small></a>';
				}

				return $h;

			};

			if($cCategory->count() > 4) {

				if($tab === 'items') {
					$current = s("Articles");
					$count = $toVerifyItems;
				} else if($tab) {
					$current = $cCategory[$tab]['name'];
					$count = $products[$tab];
				} else {
					$current = s("Non catégorisé");
					$count = $products[0];
				}

				$h .= '<div class="btn-group mb-1">';
					$h .= '<div class="btn btn-group-addon btn-outline-primary">'.s("Catégorie").'</div>';
						$h .= '<a class="dropdown-toggle btn btn-primary" data-dropdown="bottom-start" data-dropdown-hover="true" data-dropdown-id="product-dropdown-categories">';
							$h .= encode($current);
							$h .= '<small class="dropdown-item-count">'.$count.'</small>';
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

	public function products(\farm\Farm $eFarm, \Collection $cProduct, \Collection $cCategory, array $products, \Search $search, int $nProductToCheck, array $itemData): string {

		if($itemData['nToCheck'] === 0 and $nProductToCheck === 0) {
			return '<div class="util-empty">'.s("Tous vos produits ont un numéro de compte associé !").'</div>';
		}

		$h = $this->getCategories($eFarm, $cCategory, $products, $itemData['nToCheck'], $search);

		$tab = \session\SessionLib::get('preAccountingProductTab');

		if($tab === 'items') {

			$h .= $this->items($eFarm, $itemData['cItem']);

		} else {

			$form = new \util\FormUi();

			$url = LIME_REQUEST;
			$url = \util\HttpUi::removeArgument($url, 'profile');
			$url = \util\HttpUi::removeArgument($url, 'name');
			$url = \util\HttpUi::removeArgument($url, 'plant');

			$h .= '<div class="mb-2">';
				$h .= $form->openUrl($url, ['id' => 'preaccounting-payment-product', 'method' => 'get']);
					$h .= '<div style="display: flex; column-gap: 0.5rem;">';
						$h .= $form->select('profile', \selling\ProductUi::p('profile')->values, $search->get('profile'), ['placeholder' => s("Type")]);
						$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom du produit")]);
						$h .= $form->text('plant', $search->get('plant'), ['placeholder' => s("Espèce")]);
						$h .= $form->submit(s("Chercher"));

						if($search->notEmpty(['from', 'to'])) {
							$h .= '<a href="'.$url.'" class="btn">'.s("Réinitialiser").'</a>';
						}

					$h .= '</div>';
				$h .= $form->close();
			$h .= '</div>';

			if($cProduct->empty()) {
				if($search->get('profile') or $search->get('name') or $search->get('plant')) {
					$h .= '<div class="util-empty">'.s("Il n'y a pas de produit sans numéro de compte à afficher avec vos critères de recherche.").'</div>';
				} else {
					$h .= '<div class="util-empty">'.s("Il n'y a plus de produit sans numéro de compte à afficher.").'</div>';
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

			$h .= $this->getBatch($eFarm, 'product');

		}

		return $h;

	}

	public function getBatch(\farm\Farm $eFarm, string $type): string {

		$url = match($type) {
			'product' => '/selling/product:updateAccount?farm='.$eFarm['id'],
			'item' => '/selling/item:updateAccount?farm='.$eFarm['id'],
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
			$h .= '<a class="color-accounting" href="'.\farm\FarmUi::urlConnected($eFarm).'/precomptabilite:rapprocher">';
				$h .= s("Aucune facture à rapprocher pour le moment");
			$h .= '</a>';
			
		} else {

			$h = '<a class="btn btn-success bg-accounting border-accounting" href="'.\farm\FarmUi::urlConnected($eFarm).'/precomptabilite:rapprocher">';
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

		$urlProduct = \farm\FarmUi::urlConnected($eFarm).'/precomptabilite?type=product';
		$urlPayment = \farm\FarmUi::urlConnected($eFarm).'/precomptabilite?type=payment';

		$h = '';

		$errors = $nProduct + $nPaymentToCheck;

		if($errors > 0) {

			$h .= '<div class="util-block-info bg-warning">';
				$h .= '<h3>'.s("Certaines données sont manquantes").'</h3>';
				$h .= '<ul>';

				if($nProduct > 0) {
					$h .= '<li>'.s("Vérifiez que vous avez <link>associé des classes de comptes à vos produits et articles</link>", ['link' => '<a href="'.$urlProduct.'">']).'</li>';
				}

				if($nPaymentToCheck > 0) {
					$h .= '<li>'.s("Vérifiez que vous avez <link>renseigné le moyen de paiement des factures</link>", ['link' => '<a href="'.$urlPayment.'">']).'</li>';
				}
				$h .= '</ul>';

			$h .= '</div>';

		}

		$h .= '<div class="step-block-export">';

			$h .= '<div class="util-block step-block-item">';

				$h .= '<h3>'.s("Intégrez vos factures dans le logiciel comptable de Ouvretaferme").'</h3>';

				if($eFarm->usesAccounting() === FALSE) {
					$h .= '<p class="util-info">'.s("L'import sera possible dès lors que vous utiliserez le logiciel comptable de {siteName} pour tenir votre comptabilité !").'</p>';
				} else if($nProduct > 0) {
					$h .= '<p class="util-info">'.s("Des données étant manquantes, l'import n'est pas possible.").'</p>';
				} else {
					$h .= '<p>'.s("Rendez-vous dans votre journal pour y importer les factures avec lesquelles vous avez fait un rapprochement bancaire !").'</p>';
				}
				$class = 'btn btn-primary';
				if($nProduct > 0 or $eFarm->usesAccounting() === FALSE) {
					$class .= ' disabled';
					$url = 'javascript: void(0);';
				} else {
					$url = \farm\FarmUi::urlConnected($eFarm).'/precomptabilite:importer';
				}
				$h .= '<a href="'.$url.'" class="'.$class.'">'.s("Importer les factures").'</a>';

			$h .= '</div>';

			$h .= '<div class="util-block step-block-item">';

				if($isSearchValid) {

					$attributes = [
						'href' => \farm\FarmUi::urlConnected($eFarm).'/precomptabilite/ventes?from='.$search->get('from').'&to='.$search->get('to'),
					];
					$class = ($errors > 0 ? 'btn-warning' : 'btn-secondary');

				} else {
					$attributes = [
						'href' => \farm\FarmUi::urlConnected($eFarm).'/precomptabilite:ventes?from='.$search->get('from').'&to='.$search->get('to'),
					];
					$class = 'btn-secondary disabled';
				}
				$h .= '<h3>'.s("Exportez un fichier {value}", '<span class="util-badge bg-primary">FEC</span>').'</h3>';

				if($errors > 0) {
					$h .= '<p class="util-info">'.s("Vous pouvez faire un export du fichier des écritures comptables mais il sera incomplet et un travail de configuration sera nécessaire lors de l'import").'</p>';
				} else {
					$h .= '<p>'.s("Vous pourrez importer ce fichier des écritures comptables dans votre logiciel de comptabilité habituel pour y retrouver toutes vos ventes ventilées par numéro de compte.").'</p>';
				}

				$h .= '<a '.attrs($attributes).' class="btn '.$class.'">'.s("Exporter les données des ventes").'</a>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

}
?>

