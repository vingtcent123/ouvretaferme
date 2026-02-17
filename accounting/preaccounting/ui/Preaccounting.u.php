<?php
namespace preaccounting;

Class PreaccountingUi {

	public function __construct() {

		\Asset::css('preaccounting', 'preaccounting.css');
		\Asset::js('preaccounting', 'preaccounting.js');

	}

	public function check(\farm\Farm $eFarm, array $dates, \Collection $cInvoice, \Collection $cInvoiceImported, \Collection $cRegister, \Collection $cCash, \Collection $cCashImported): string {

		if($eFarm['eFinancialYear']['status'] === \account\FinancialYear::CLOSE) {

			$h = '<div class="util-warning">';
				$h .= s("L'exercice comptable {value} est clos, aucune écriture ne peut y être ajoutée.", \account\FinancialYearUi::getYear($eFarm['eFinancialYear']));
			$h .= '</div>';

			return $h;

		}

		$h = '<div class="preaccounting-import-container">';

			foreach($dates as $date) {

				$month = ucfirst(\util\DateUi::getMonthName(mb_substr($date, 5, 2)));
				$year = mb_substr($date, 0, 4);

				$future = $date > date('Y-m-d');
				$current = mb_substr($date, 0, 7) === date('Y-m');

				$h .= '<div class="preaccounting-import-month '.($future ? 'preaccounting-import-month-future' : '').'">';

					$h .= '<div class="preaccounting-import-title">';
						$h .= '<h3>'.s("{month} {year}", ['month' => $month, 'year' => $year]).'</h3>';
						if($future === FALSE) {
							$h .= '<a href="'.\farm\FarmUi::urlConnected($eFarm).'/precomptabilite/verifier:import?from='.$date.'-01" class="btn '.($current ? 'btn-outline-primary' : 'btn-primary').'">'.($current ? s("Consulter") : s("Importer")).'</a>';
						}
					$h .= '</div>';

					$h .= '<div class="preaccounting-import-item">';
						$h .= '<span>'.s("Factures avec rapprochement bancaire").'</span>';
						$h .= '<b>';
							if($future === FALSE) {
								if(($cInvoiceImported[$date]['count'] ?? 0) > 0) {
									$h .= '<span title="'.s("Factures importées").'" class="mr-1">';
										$h .= $cInvoiceImported[$date]['count'] ?? 0;
										$h .= ' '.\Asset::icon('check-lg', ['class' => 'color-success']);
									$h .= '</span>';
								}
								$h .= '<span title="'.s("Factures en attente d'import").'">';
									$h .= $cInvoice[$date]['count'] ?? 0;
									if($current === FALSE) {
										$h .= ' '.\Asset::icon('hourglass');
									}
								$h .= '</span>';
							}
						$h .= '</b>';
					$h .= '</div>';

					foreach($cRegister as $eRegister) {

						if($eRegister['account']->empty()) {

							$count = '<a class="btn btn-sm btn-outline-danger" title="'.s("Ce journal de caisse n'a pas de numéro de compte configuré").'" href="'.\farm\FarmUi::urlConnected($eFarm).'/cash/register:update?id='.$eRegister['id'].'">'.\Asset::icon('exclamation-triangle').'</a>';

						} else if($eRegister['hasAccounts'] === FALSE) {

							$count = '<a class="btn btn-sm btn-danger" title="'.s("Les numéros de compte des opérations de caisse ne sont pas configurés").'" href="'.\farm\FarmUi::urlConnected($eFarm).'/cash/register:update?id='.$eRegister['id'].'">'.\Asset::icon('exclamation-triangle').'</a>';

						} else if($eRegister['closedAt'] < first($dates).'-01') {

							$count = '<a class="btn btn-sm btn-danger" title="'.s("Le journal de caisse n'est pas clôturé").'" href="'.\farm\FarmUi::urlConnected($eFarm).'/journal-de-caisse?id='.$eRegister['id'].'">'.\Asset::icon('exclamation-triangle').'</a>';

						} else {

							$count = '';
							if(($cCashImported[$eRegister['id']][$date]['count'] ?? 0) > 0) {
								$count .= '<span title="'.s("Opérations importées").'" class="mr-1">';
									$count .= $cInvoiceImported[$date]['count'] ?? 0;
									$count .= ' '.\Asset::icon('check-lg', ['class' => 'color-success']);
								$count .= '</span>';
							}
							$count .= '<span title="'.s("Opérations en attente d'import").'">';
								$count .= $cCash[$eRegister['id']][$date]['count'] ?? 0;
								if($current === FALSE) {
									$count .= ' '.\Asset::icon('hourglass');
								}
							$count .= '</span>';

						}

						$h .= '<div class="preaccounting-import-item">';
							$h .= '<span>'.\cash\RegisterUi::getName($eRegister).'</span>';
							$h .= '<b>';
								if($future === FALSE) {
										$h .= $count;
								}
							$h .= '</b>';
						$h .= '</div>';

					}

				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}
	
	public function getCheckSteps(int $nProductToCheck, int $nItemToCheck, int $nInvoiceForPaymentToCheck, \Collection $cRegisterMissing, string $type, string $checkType, string $url, \Search $search): string {
		
		\Asset::css('preaccounting', 'step.css');

		$steps = [
			[
				'position' => 1,
				'number' => $nProductToCheck + $nItemToCheck,
				'type' => 'product',
				'title' => s("Produits"),
				'description' => s("Associez un numéro de compte à vos produits et articles"),
			],
			[
				'position' => 2,
				'number' => ($checkType === 'fec' ? $nInvoiceForPaymentToCheck : $cRegisterMissing->count()),
				'type' => 'payment',
				'title' => ($checkType === 'fec' ? s("Moyens de paiement") : s("Numéros de compte")),
				'description' => ($checkType === 'fec' ? s("Renseignez les moyens de paiement des factures") : s("Paramétrez les numéros de compte de vos journaux")),
			],
		];

		$h = '<div class="step-process mb-2">';

			foreach($steps as $step) {

		    $h .= '<a class="step '.($step['number'] > 0 ? '' : 'step-success').' '.($type === $step['type'] ? 'selected' : '').'"  href="'.$url.'?type='.$step['type'].'&from='.$search->get('from').'&to='.$search->get('to').'">';

				$h .= '<div class="step-header">';

					$h .= '<span class="step-number">'.($step['position']).'</span>';

					$h .= '<div class="step-main">';

					$h .= '<div class="step-title">';
						$h .= $step['title'];

						if($step['number'] > 0) {
							$h .= '<span class="bg-warning tab-item-count ml-1" title="'.s("À contrôler").'">'.\Asset::icon('exclamation-circle').'  '.$step['number'].'</span>';
						}

					$h .= '</div>';

					$h .= '<div class="step-value">';

					$h .= '</div>';

				$h .= '</div>';

		      $h .= '</div>';

			    $h .= '<p class="step-desc hide-sm-down">';
			      $h .= $step['description'];
			    $h .= '</p>';

			  $h .= '</a>';

			}

			$h .= '<a class="step '.($type === 'export' ? 'selected' : '').'" href="'.$url.'?type=export&from='.$search->get('from').'&to='.$search->get('to').'">';
				$h .= '<div class="step-header">';
					$h .= '<span class="step-number">'.(count($steps) + 1).'</span>';
					$h .= '<div class="step-main">';
						$h .= '<div class="step-title">'.($checkType === 'import' ? s("Importer les opérations") : s("Export", ['fec' => '<span class="util-badge bg-primary">FEC</span>'])).'</div>';
						$h .= '<div class="step-value"></div>';
					$h .= '</div>';
				$h .= '</div>';
				$h .= '<p class="step-desc">';
					if($checkType === 'import') {
						$h .= s("Générez les écritures dans votre livre journal");
					} else {
						$h .= s("Téléchargez votre fichier FEC");
					}
				$h .= '</p>';
			$h .= '</a>';

		$h .= '</div>';

		return $h;
	}

	public function getSearchFec(\farm\Farm $eFarm, \Search $search): string {

		$h = '<div class="util-block-search">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH.'?type='.GET('type').'&from='.GET('from').'&to='.GET('to');

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search util-search-3']);

				if($search->has('cRegister') and $search->get('cRegister')->count() > 0) {
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
							'classPrefixes[2]' => \account\AccountSetting::FINANCIAL_GENERAL_CLASS,
						];
						$index = count($query);
						foreach(\account\AccountSetting::WAITING_ACCOUNT_CLASSES as $waitingClass) {
							$query['classPrefixes['.$index.']'] = $waitingClass;
							$index++;
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
						$h .= ' <a href="'.$url.'" class="btn btn-outline-primary">'.\Asset::icon('x-lg').'</a>';
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
							$h .= '<a href="/selling/invoice:updatePayment?id='.$eInvoice['id'].'" class="btn btn-sm btn-outline-primary">'.s("Choisir").'</a>';
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

	public function registers(\farm\Farm $eFarm, \Collection $cRegister, \Search $search): string {

		$form = new \util\FormUi();

		$h = '<table class="tr-even">';

			$h .= '<thead>';

				$h .= '<tr>';

					$h .= '<th>'.s("Journal de caisse").'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';

			$h .= '</thead>';

			$h .= '<tbody>';

				foreach($cRegister as $eRegister) {

					$h .= '<tr>';

						$h .= '<td>';
							$h .= \cash\RegisterUi::getName($eRegister);
						$h .= '</td>';

						$h .= '<td>';
							$h .= '<a class="btn btn-outline-primary" href="'.\farm\FarmUi::urlConnected($eFarm).'/cash/register:update?id='.$eRegister['id'].'">'.s("Paramétrer le journal").'</a>';
						$h .= '</td>';

					$h .= '</tr>';

				}

			$h .= '</tbody>';

		$h .= '</table>';

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

		return \util\BatchUi::group('batch-accounting-invoice', $menu, title: s("Pour les paiements sélectionnés"));

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

			$url = \farm\FarmUi::urlFinancialYear(NULL, $eFarm).'/precomptabilite?type=product&from='.$search->get('from').'&to='.$search->get('to');

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
							$h .= '<a href="'.$url.'" class="btn btn-outline-primary">'.\Asset::icon('x-lg').'</a>';
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
			$h .= '<a class="color-accounting" href="'.\farm\FarmUi::urlFinancialYear(NULL, $eFarm).'/precomptabilite:rapprocher">';
				$h .= s("Aucun paiement à rapprocher pour le moment");
			$h .= '</a>';
			
		} else {

			$h = '<a class="btn btn-success bg-accounting border-accounting" href="'.\farm\FarmUi::urlFinancialYear(NULL, $eFarm).'/precomptabilite:rapprocher">';
				$h .= \Asset::icon('fire').' ';
				$h .= '<span class="hide-md-up">';
					$h .= $nSuggestion;
				$h .= '</span>';
				$h .= '<span class="hide-sm-down">';
					$h .= p("{value} paiement à rapprocher", "{value} paiements à rapprocher", $nSuggestion);
				$h .= '</span>';
			$h .= '</a>';

		}

		return $h;
	}

	public function getSearchPeriod(\Search $search): string {

		$getArgs = $_GET;
		unset($getArgs['origin']);
		unset($getArgs['farm']);

		$h = '<div class="util-block-search">';

			$form = new \util\FormUi();

			$h .= $form->openAjax(LIME_REQUEST_PATH.'?'.http_build_query($getArgs).'&type='.GET('type').'&importType='.GET('importType'), ['method' => 'get', 'class' => 'util-search']);

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

	public function export(\farm\Farm $eFarm, array $operations, int $nSale, int $nInvoice, int $nCash, \Collection $cInvoice, \Search $search): string {

		$h = new \preaccounting\PreaccountingUi()->getSearchFec($eFarm, $search);

		if(count($operations) > 0) {

			// Attention, le calcul est credit - debit car on va compter les contreparties pour les avoir toutes (et non pas la banque ni la caisse).
			$filteredOperations = array_filter(
				$operations,
				fn($operation) => $operation[\preaccounting\AccountingLib::EXTRA_FEC_COLUMN_IS_SUMMED] === 1
			);

			$totalDebit = array_sum(array_column($filteredOperations, \preaccounting\AccountingLib::FEC_COLUMN_DEBIT));
			$totalCredit = array_sum(array_column($filteredOperations, \preaccounting\AccountingLib::FEC_COLUMN_CREDIT));

			$h .= '<ul class="util-summarize">';

				$h .= '<li>';
					$h .= '<div>';
						$h .= '<h5>'.p("Écriture", "Écritures", count($operations)).'</h5>';
						$h .= '<div>'.count($operations).'</div>';
					$h .= '</div>';
				$h .= '</li>';

				$h .= '<li>';
					$h .= '<div>';
						$h .= '<h5>';

							$h .= match($search->get('filter')) {
								NULL => s("Ventes et factures"),
								'hasInvoice' => p("Facture", "Factures", $nInvoice),
								'noInvoice' => p("Vente", "Ventes", $nSale),
								default => p("Opération de caisse", "Opérations de caisse", $nCash)
							};

						$h .= '</h5>';
							$h .= '<div>';

							$salesAndInvoicesOperations = array_filter(
								$operations,
								fn($operation) => in_array($operation[\preaccounting\AccountingLib::EXTRA_FEC_COLUMN_ORIGIN], ['invoice', 'sale'])
							);
							$cashOperations = array_filter(
								$operations,
								fn($operation) => $operation[\preaccounting\AccountingLib::EXTRA_FEC_COLUMN_ORIGIN] === 'register'
							);

							$nSalesAndInvoices = count(array_unique(array_column($salesAndInvoicesOperations, \preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT)));

							$h .= match($search->get('filter')) {
								NULL => $nSalesAndInvoices + $nCash,
								'hasInvoice' => $nSalesAndInvoices,
								'noInvoice' => $nSalesAndInvoices,
								default => $nCash,
							};

						$h .= '</div>';
					$h .= '</div>';
				$h .= '</li>';

				$h .= '<li>';
					$h .= '<div>';
						$h .= '<h5>'.s("Montant").'</h5>';
						$h .= '<div>'.\util\TextUi::money(round($totalCredit - $totalDebit, 2)).'</div>';
					$h .= '</div>';
				$h .= '</li>';

			$h .= '</ul>';

			parse_str(mb_substr(LIME_REQUEST_ARGS, 1), $args);
			$url = \farm\FarmUi::urlFinancialYear(NULL, $eFarm).'/precomptabilite/fec:telecharger?'.http_build_query($args);

			$h .= '<div class="mt-2 mb-2 text-center">';
				$h .= '<a class="dropdown-toggle btn btn-lg btn-secondary" data-dropdown="bottom-down" >'.\Asset::icon('download').' '.s("Télécharger le fichier {fec}", ['fec' => '<span class="util-badge bg-primary">FEC</span>']).'</a>';
				$h .= '<div class="dropdown-list">';
					$h .= '<a href="'.$url.'&format=csv" class="dropdown-item" data-ajax-navigation="never">';
						$h .= s("Au format CSV");
					$h .= '</a>';
					$h .= '<a href="'.$url.'&format=txt" class="dropdown-item" data-ajax-navigation="never">';
						$h .= s("Au format TXT");
					$h .= '</a>';
				$h .= '</div>';
			$h .= '</div>';

			$h .= new \preaccounting\SaleUi()->list($eFarm, $operations, $search->get('hasInvoice'), $cInvoice);

		} else {

			if($search->empty(['id'])) {

				$h .= '<div class="util-info">';
					$h .= s("Il n'y a aucune donnée comptable à afficher.");
				$h .= '</div>';

			} else {

				$h .= '<div class="util-empty">';
					$h .= s("Aucune vente ne correspond à vos critères de recherche.");
				$h .= '</div>';

			}

		}

		return $h;
	}

}
?>

