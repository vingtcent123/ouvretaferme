<?php
new AdaptativeView('/precomptabilite', function($data, FarmTemplate $t) {

	Asset::js('preaccounting', 'preaccounting.js');

	$t->title = s("Précomptabilité de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite';

	$t->nav = 'preaccounting';

	$t->mainTitle = '<h1>'.s("Préparer les données de vente").'</h1>';

	$errors = $data->nProduct + $data->nSalePayment + $data->nSaleClosed;

	echo '<div class="util-block">';
		echo '<h3>'.s("Choix de la période").'</h3>';
		echo new \preaccounting\PreaccountingUi()->getSearch($data->eFarm, $data->search);
	echo '</div>';

	if($errors === 0 and $data->nProductVerified === 0 and $data->nSalePaymentVerified === 0 and $data->nSaleClosedVerified === 0) {

		echo '<div class="util-block-important">';
			echo s("Il n'a aucune vente à afficher. Avez-vous choisi la bonne période ?");
		echo '</div>';
		return;
	}

	Asset::css('preaccounting', 'step.css');

	$steps = [
		[
			'position' => 1,
			'number' => $data->nProduct,
			'numberVerified' => $data->nProductVerified,
			'type' => 'product',
			'title' => s("Produits"),
			'description' => s("Associez un compte à vos produits"),
		],
		[
			'position' => 2,
			'number' => $data->nSalePayment,
			'numberVerified' => $data->nSalePaymentVerified,
			'type' => 'payment',
			'title' => s("Moyens de paiement"),
			'description' => s("Renseignez le moyen de paiement des ventes"),
		],
		[
			'position' => 3,
			'number' => $data->nSaleClosed,
			'numberVerified' => $data->nSaleClosedVerified,
			'type' => 'closed',
			'title' => s("Clôture"),
			'description' => s("Clôturez vos ventes"),
		],
	];

	$onrender = '';
	if($errors === 0 and $data->isSearchValid) {
		$onrender = "Preaccounting.toggle('export', '');";
	} else if($errors > 0 and in_array(GET('type'), ['product', 'payment', 'closed'])) {
		$onrender = "Preaccounting.toggle('".GET('type')."', '".GET('tab')."');";
	}
	echo '<div class="step-process" onrender="'.$onrender.'">';

		foreach($steps as $step) {

	    echo '<a class="step '.($step['number'] > 0 ? 'active' : 'success').'" data-url="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite/'.$step['type'].'" data-step="'.$step['type'].'" onclick="Preaccounting.toggle(\''.$step['type'].'\'); return true;"">';

	      echo '<div class="step-header">';

					echo '<span class="step-number">'.($step['position']).'</span>';

					echo '<div class="step-main">';

		        echo '<div class="step-title">'.$step['title'].'</div>';

						echo '<div class="step-value">';

							if($step['number'] > 0) {
								echo $step['number'] > 0 ? '<span class="bg-warning tab-item-count ml-0" title="'.s("À contrôler").'">'.Asset::icon('exclamation-triangle').' '.$step['number'].'</span> ' : '';
								echo $step['numberVerified'] > 0 ? '<span class="bg-success tab-item-count ml-0" title="'.s("Vérifiés").'">'.Asset::icon('check').' '.$step['numberVerified'].'</span>' : '';
							}

	          echo '</div>';

	        echo '</div>';

	      echo '</div>';

		    echo '<p class="step-desc hide-sm-down">';
		      echo $step['description'];
		    echo '</p>';

		  echo '</a>';

		}

		echo '<a class="step '.($step['number'] > 0 ? 'active' : 'success').'" data-step="export" onclick="Preaccounting.toggle(\'export\'); return true;">';
			echo '<div class="step-header">';
				echo '<span class="step-number">'.(count($steps) + 1).'</span>';
				echo '<div class="step-main">';
					echo '<div class="step-title">'.s("Export").' <span class="util-badge bg-primary">FEC</span></div>';
					echo '<div class="step-value"></div>';
				echo '</div>';
			echo '</div>';
			echo '<p class="step-desc">';
				echo s("Intégrez vos ventes en comptabilité");
			echo '</p>';
		echo '</a>';

	echo '</div>';

	foreach($steps as $step) {
		echo '<div data-step="'.$step['type'].'" class="hide"></div>';
	}

	$form = new \util\FormUi();

	echo '<div data-step="export" class="hide">';

		if($errors > 0) {
			if($data->nProduct > 0) {
				if($data->nSalePayment > 0) {
					if($data->nSaleClosed > 0) {
						$check = s("Vérifiez <link>{icon} vos produits</link>, <link2>{icon2} les moyens de paiement</link2> et <link3>{icon3} la clôture de vos ventes</link3>.", [
							'icon' => Asset::icon('1-circle'), 'link' => '<a onclick="Preaccounting.toggle(\'product\');">',
							'icon2' => Asset::icon('2-circle'), 'link2' => '<a onclick="Preaccounting.toggle(\'payment\');">',
							'icon3' => Asset::icon('3-circle'), 'link3' => '<a onclick="Preaccounting.toggle(\'closed\');">',
						]);
					} else {
						$check = s("Vérifiez <link>{icon} vos produits</link> et les <link2>{icon2} moyens de paiement</link2>.", [
							'icon' => Asset::icon('1-circle'), 'link' => '<a onclick="Preaccounting.toggle(\'product\');">',
							'icon2' => Asset::icon('2-circle'), 'link2' => '<a onclick="Preaccounting.toggle(\'payment\');">',
						]);
					}
				} else if($data->nSaleClosed > 0) {
					$check = s("Vérifiez <link>{icon} vos produits</link> et <link3>{icon3} la clôture de vos ventes</link3>.", [
						'icon' => Asset::icon('1-circle'), 'link' => '<a onclick="Preaccounting.toggle(\'product\');">',
						'icon3' => Asset::icon('3-circle'), 'link3' => '<a onclick="Preaccounting.toggle(\'closed\');">',
					]);
				} else {
					$check = s("Vérifiez <link>{icon} vos produits</link>.", [
						'icon' => Asset::icon('1-circle'), 'link' => '<a onclick="Preaccounting.toggle(\'product\');">',
					]);
				}
			} else if($data->nSalePayment > 0) {
				if($data->nSaleClosed > 0) {
					$check = s("Vérifiez <link2>{icon2} les moyens de paiement</link2> et <link3>{icon3} la clôture de vos ventes</link3>.", [
						'icon2' => Asset::icon('2-circle'), 'link2' => '<a onclick="Preaccounting.toggle(\'payment\');">',
						'icon3' => Asset::icon('3-circle'), 'link3' => '<a onclick="Preaccounting.toggle(\'closed\');">',
					]);
				} else {
					$check = s("Vérifiez <link2>{icon2} les moyens de paiement</link2>.", [
						'icon2' => Asset::icon('2-circle'), 'link2' => '<a onclick="Preaccounting.toggle(\'payment\');">',
					]);
				}
			} else {
					$check = s("Vérifiez <link3>{icon3} la clôture de vos ventes</link3>.", [
						'icon3' => Asset::icon('3-circle'), 'link3' => '<a onclick="Preaccounting.toggle(\'closed\');">',
					]);
			}
			echo '<div class="util-outline-block-important">'.s("Certaines données sont manquantes ({check}).", ['check' => $check]).'</div>';

		}

		echo '<div class="step-bloc-export">';
			echo '<div class="util-block-optional">';

				if($data->isSearchValid) {

					$attributes = [
						'href' => \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:fec?from='.$data->search->get('from').'&to='.$data->search->get('to'),
						'data-ajax-navigation' => 'never',
					];
					$class = ($errors > 0 ? 'btn-warning' : 'btn-secondary');

				} else {
					$attributes = [
						'href' => 'javascript: void(0);',
					];
					$class = 'btn-secondary disabled';
				}
				echo '<h3>'.s("Exportez votre fichier des écritures comptables").'</h3>';

				if($errors > 0) {
					echo '<p class="util-info">'.s("Vous pouvez faire un export du FEC mais il sera incomplet et un travail de configuration sera nécessaire lors de l'import").'</p>';
				} else {
					echo '<p>'.s("Vous pouvez importer ce fichier dans votre logiciel de comptabilité habituel pour y retrouver toutes vos ventes ventilées par numéro de compte.").'</p>';
				}

				echo '<a '.attrs($attributes).'>'.$form->button(s("Télécharger le fichier"), ['class' => 'btn '.$class]).'</a>';

			echo '</div>';

			echo '<div class="util-block-optional">';

				echo '<h3>'.s("Intégrez vos ventes dans votre comptabilité").'</h3>';

				if($errors > 0) {
					echo '<p class="util-info">'.s("Des données étant manquantes, l'import n'est pas possible.").'</p>';
				} else {
					echo '<p>'.s("Rendez-vous dans votre journal pour y importer vos ventes !").'</p>';
				}
				$class = 'btn btn-primary';
				if($errors > 0) {
					$class .= ' disabled';
					$url = 'javascript: void(0);';
				} else {
					$url = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:importer';
				}
				echo '<a href="'.$url.'" class="'.$class.'">'.s("Importer dans ma comptabilité").'</a>';

			echo '</div>';
		echo '</div>';

	echo '</div>';

});

new JsonView('/precomptabilite/{type}', function($data, AjaxTemplate $t) {

	$t->js()->replaceHistory(LIME_REQUEST);

	switch($data->type) {

		case 'product':
			$t->qs('div[data-step="product"]')->innerHtml(new \preaccounting\PreaccountingUi()->products(
				$data->eFarm,
				$data->cProduct,
				$data->nToCheck,
				$data->nVerified,
				$data->cCategories,
				$data->products,
				$data->search,
				itemData: ['nToCheck' => $data->nToCheckItem, 'nVerified' => $data->nVerifiedItem, 'cItem' => $data->cItem],
			));
			break;

		case 'payment':
			$t->qs('div[data-step="'.$data->type.'"]')->innerHtml(
				new \preaccounting\PreaccountingUi()->salesPayment($data->type, $data->cSale, $data->cPaymentMethod, $data->nToCheck, $data->nVerified)
			);
			break;

		case 'closed':
			$t->qs('div[data-step="'.$data->type.'"]')->innerHtml(
				new \preaccounting\PreaccountingUi()->sales($data->eFarm, $data->type, $data->cSale, $data->cInvoice, $data->cPaymentMethod, $data->nToCheck, $data->nVerified, $data->search)
			);
			break;
	}

});


new AdaptativeView('/precomptabilite:importer', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'operations';

	$t->title = s("Les ventes de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:importer';

	$t->mainTitle = '<h1>'.s("Importer les ventes").(array_sum($data->counts) > 0 ? '<span class="util-counter ml-1">'.array_sum($data->counts).'</span>' : '').'</h1>';

	if($data->counts['sales'] > 0) {
		if($data->counts['market'] > 0) {
			if($data->counts['invoice'] > 0) {
				$check = s("Vous pouvez importer {sales}, {market} et {invoices}.", [
					'sales' => '<b>'.p("{value} vente", "{value} ventes", $data->counts['sales']).'</b>',
					'market' => '<b>'.p("{value} marché", "{value} marchés", $data->counts['market']).'</b>',
					'invoices' => '<b>'.p("{value} facture", "{value} factures", $data->counts['invoice']).'</b>',
				]);
			} else {
				$check = s("Vous pouvez importer {sales} et {market}.", [
					'sales' => '<b>'.p("{value} vente", "{value} ventes", $data->counts['sales']).'</b>',
					'market' => '<b>'.p("{value} marché", "{value} marchés", $data->counts['market']).'</b>',
				]);
			}
		} else if($data->counts['invoice'] > 0) {
			$check = s("Vous pouvez importer {sales} et {invoices}.", [
				'sales' => '<b>'.p("{value} vente", "{value} ventes", $data->counts['sales']).'</b>',
				'invoices' => '<b>'.p("{value} facture", "{value} factures", $data->counts['invoice']).'</b>',
			]);
		} else {
			$check = s("Vous pouvez importer {sales}.", [
				'sales' => '<b>'.p("{value} vente", "{value} ventes", $data->counts['sales']).'</b>',
			]);
		}
	} else if($data->counts['market'] > 0) {
		if($data->counts['invoice'] > 0) {
			$check = s("Vous pouvez importer {market} et {invoices}.", [
				'market' => '<b>'.p("{value} marché", "{value} marchés", $data->counts['market']).'</b>',
				'invoices' => '<b>'.p("{value} facture", "{value} factures", $data->counts['invoice']).'</b>',
			]);
		} else {
			$check = s("Vous pouvez importer {market}.", [
				'market' => '<b>'.p("{value} marché", "{value} marchés", $data->counts['market']).'</b>',
			]);
		}
	} else if($data->counts['invoice'] > 0) {
		$check = s("Vous pouvez importer {invoices}.", [
			'invoices' => '<b>'.p("{value} facture", "{value} factures", $data->counts['invoice']),
		]);
	} else {
		$check = NULL;
	}

	if($check === NULL) {

		echo '<div class="util-info">'.s("Vous êtes à jour de vos imports ! ... ou alors vous n'avez pas terminé de <link>préparer vos données de ventes</link>", ['link' => '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite">']).'</div>';

	} else {

		echo '<div class="util-block-help">';
			echo $check;
		echo '</div>';
		$showTabs = count(array_filter($data->counts, fn($val) => $val > 0)) > 1;


		if($showTabs) {

			echo '<div class="tabs-item">';

			foreach(['market', 'invoice', 'sales'] as $tab) {

				echo '<a class="tab-item '.($data->selectedTab === $tab ? ' selected' : '').'" data-tab="'.$tab.'" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:importer?tab='.$tab.'">';
				echo match($tab) {
					'market' => s("Marchés"),
					'invoice' => s("Factures"),
					'sales' => s("Autres ventes"),
				};
				echo ' <small class="tab-item-count">'.$data->counts[$tab].'</small>';
				echo '</a>';

			}

			echo '</div>';

		}
		echo match($data->selectedTab) {
			'market' => new \preaccounting\ImportUi()->displayMarket($data->eFarm, $data->eFinancialYear, $data->c),
			'invoice' => new \preaccounting\ImportUi()->displayInvoice($data->eFarm, $data->eFinancialYear, $data->c, $data->search),
			'sales' => new \preaccounting\ImportUi()->displaySales($data->eFarm, $data->eFinancialYear, $data->c, $data->search),
		};

	}
});

new AdaptativeView('/precomptabilite:rapprocher-ventes', function($data, FarmTemplate $t) {

	$t->nav = 'preaccounting';

	$t->title = s("Les ventes de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:rapprocher-ventes';

	$total = $data->countsByInvoice + $data->countsBySale;

	$t->mainTitle = '<h1>'.s("Rapprocher les ventes").($total > 0 ? '<span class="util-counter ml-1">'.$total.'</span>' : '').'</h1>';

	if($total > 0) {

		echo '<div class="util-block-help">';
			if($data->countsByInvoice and $data->countsBySale > 0) {
				echo s("{nSales} et {nInvoices} semblent être rapprochables avec des opérations bancaires.", [
					'nSales' => '<b>'.p("{value} vente", "{value} ventes", $data->countsBySale).'</b>',
					'nInvoices' => '<b>'.p("{value} facture", "{value} factures", $data->countsByInvoice).'</b>',
				]);
			} else if($data->countsByInvoice > 0) {
				echo p("<b>{value} facture</b> semble être rapprochable.", "<b>{value} factures</b> semblent être rapprochables.", $data->countsByInvoice);
			} else if($data->countsBySale > 0) {
				echo p("<b>{value} vente</b> semble être rapprochable.", "<b>{value} ventes</b> semblent être rapprochables.", $data->countsBySale);
			}
		echo '</div>';

	}


	if($data->ccSuggestion->empty()) {

		echo '<div class="util-info">';
		echo '<p>'.s("Il n'y a aucune vente à rapprocher.").'</p>';
		echo '</div>';

		if($data->eImportLast->empty()) {

			echo '<p>'.s("Vous n'avez pas encore réalisé d'import bancaire.").'</p>';
			echo '<a class="btn btn-primary" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations">'.s("Faire mon premier import bancaire !").'</a>';

		} else {

			echo '<p>'.s("Votre dernier import bancaire a couvert vos transactions jusqu'au {date}.", ['date' => \util\DateUi::numeric($data->eImportLast['endDate'], \util\DateUi::DATE)]).'</p>';
			echo '<a class="btn btn-primary" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations">'.s("Importer mes données bancaires").'</a>';

		}

	} else {

		echo new \preaccounting\ReconciliateUi()->tableByCashflow($data->eFarm, $data->ccSuggestion, $data->cMethod);

	}

});

new AdaptativeView('/precomptabilite:rapprocher-ecritures', function($data, FarmTemplate $t) {

	$t->nav = 'preaccounting';

	$t->title = s("Rapprocher les écritures comptables de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:rapprocher-ecritures';

		$t->mainTitle = '<h1>'.s("Rapprocher les écritures").($data->counts > 0 ? '<span class="util-counter ml-1">'.$data->counts.'</span>' : '').'</h1>';

	echo '<div class="util-block-help">';
	echo s("Cette page vous permet de rapprocher vos écritures comptables avec les opérations bancaires que vous avez importées.");
	echo '</div>';

	if($data->ccSuggestion->empty()) {

		echo '<div class="util-info">';
		echo '<p>'.s("Il n'y a aucune écriture à rapprocher.").'</p>';
		echo '<p>'.s("Souhaitez-vous <linkOperation>ajouter une écriture dans le journal</linkOperation> ou <linkCashflow>réaliser un import bancaire</linkCashflow> ?", [
				'linkOperation' => '<a href="'.\company\CompanyUi::urlJournal($data->eFarm).'">',
				'linkCashflow' => '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations">',
			]).'</p>';
		echo '</div>';

	} else {

		echo new \preaccounting\ReconciliateUi()->tableByOperations($data->eFarm, $data->ccSuggestion);

	}

});

