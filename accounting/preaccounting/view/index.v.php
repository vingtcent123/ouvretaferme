<?php
new AdaptativeView('/precomptabilite', function($data, FarmTemplate $t) {

	Asset::js('preaccounting', 'preaccounting.js');

	$t->title = s("Précomptabilité de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite';

	$t->nav = 'preaccounting';

	$t->mainTitle = new \farm\FarmUi()->getPreAccountingInvoiceTitle($data->eFarm, $data->eFinancialYear, 'prepare', ['import' => array_sum($data->counts['import']), 'reconciliate-sales' => $data->counts['reconciliate']['sales'], 'reconciliate-operations' => $data->counts['reconciliate']['operations']]);

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
			'type' => 'product',
			'title' => s("Produits"),
			'description' => s("Associez un compte à vos produits"),
		],
		[
			'position' => 2,
			'number' => $data->nSalePayment,
			'type' => 'payment',
			'title' => s("Moyens de paiement"),
			'description' => s("Renseignez le moyen de paiement des ventes"),
		],
		[
			'position' => 3,
			'number' => $data->nSaleClosed,
			'type' => 'closed',
			'title' => s("Clôture"),
			'description' => s("Clôturez vos ventes"),
		],
	];
	echo '<div class="step-process" onrender="'.(in_array(GET('type'), ['product', 'payment', 'closed']) ? 'Preaccounting.toggle(\''.GET('type').'\', \''.GET('tab').'\');' : '') .'">';

		foreach($steps as $step) {

	    echo '<a class="step '.($step['number'] > 0 ? 'active' : 'success').'" data-url="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite/'.$step['type'].'" data-step="'.$step['type'].'" onclick="Preaccounting.toggle(\''.$step['type'].'\'); return true;"">';
	      echo '<div class="step-header">';
					echo '<span class="step-number">'.($step['position']).'</span>';
					echo '<div class="step-main">';
		        echo '<div class="step-title">'.$step['title'].'</div>';
		        echo '<div class="step-value">'.($step['number'] > 0 ? '<span class="bg-warning tab-item-count ml-0">'.$step['number'].'</span>' : '').'</div>';
	        echo '</div>';
	      echo '</div>';
		    echo '<p class="step-desc">';
		      echo $step['description'];
		    echo '</p>';
		  echo '</a>';

		}

		echo '<a class="step last '.($step['number'] > 0 ? 'active' : 'success').'" data-step="export" onclick="Preaccounting.toggle(\'export\'); return true;">';
			echo '<div class="step-header">';
				echo '<span class="step-number">'.(count($steps) + 1).'</span>';
				echo '<div class="step-main">';
					echo '<div class="step-title">'.s("Export").'</div>';
					echo '<div class="step-value"></div>';
				echo '</div>';
			echo '</div>';
			echo '<p class="step-desc">';
				echo s("Téléchargez un Fichier des Écritures Comptables (FEC)");
			echo '</p>';
		echo '</a>';

	echo '</div>';

	foreach($steps as $step) {
		echo '<div data-step="'.$step['type'].'" class="hide"></div>';
	}

	$form = new \util\FormUi();
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
			echo '<div class="util-block-important">'.s("Certaines données sont manquantes ({check}).<br />Vous pouvez faire un export du FEC mais il sera incomplet et un travail de configuration sera nécessaire lors de l'import.<br />Si vous souhaitez importer les données de vente dans votre comptabilité sur {siteName}, vous ne pourrez pas importer les ventes dont des données manquent.", ['check' => $check]).'</div>';
		}
		echo '<a '.attrs($attributes).' style="height: 100%;">'.$form->button(s("Exporter"), ['class' => 'btn '.$class]).'</a>';
	echo '</div>';

});

new JsonView('/precomptabilite/{type}', function($data, AjaxTemplate $t) {

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

	$t->nav = 'preaccounting';

	$t->title = s("Les ventes de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:importer';

	$t->mainTitle = new \farm\FarmUi()->getPreAccountingInvoiceTitle($data->eFarm, $data->eFinancialYear, 'import', ['import' => array_sum($data->counts['import']), 'reconciliate-sales' => $data->counts['reconciliate']['sales'], 'reconciliate-operations' => $data->counts['reconciliate']['operations']]);

	echo '<div class="util-block-help">';
	echo '<p>'.s("Cette page vous permet de vérifier et importer vos ventes depuis le module de commercialisation directement en comptabilité.").'</p>';
	echo '<p>'.s("Si des ventes n'apparaissent pas, vérifiez si les données de vos ventes sont bien préparées pour la comptabilité sur <link>cette page</link>.", ['link' => '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite&from='.$data->eFinancialYear['startDate'].'&to='.$data->eFinancialYear['endDate'].'">']).'</p>';
	echo '</div>';

	echo '<div class="tabs-item">';

	foreach(['market', 'invoice', 'sales'] as $tab) {

		echo '<a class="tab-item '.($data->selectedTab === $tab ? ' selected' : '').'" data-tab="'.$tab.'" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:importer?tab='.$tab.'">';
		echo match($tab) {
			'market' => s("Marchés"),
			'invoice' => s("Factures"),
			'sales' => s("Autres ventes"),
		};
		echo ' <small class="tab-item-count">'.$data->counts['import'][$tab].'</small>';
		echo '</a>';

	}

	echo '</div>';

	echo match($data->selectedTab) {
		'market' => new \preaccounting\ImportUi()->displayMarket($data->eFarm, $data->eFinancialYear, $data->c),
		'invoice' => new \preaccounting\ImportUi()->displayInvoice($data->eFarm, $data->eFinancialYear, $data->c, $data->search),
		'sales' => new \preaccounting\ImportUi()->displaySales($data->eFarm, $data->eFinancialYear, $data->c, $data->search),
	};

});

new AdaptativeView('/precomptabilite:rapprocher-ventes', function($data, FarmTemplate $t) {

	$t->nav = 'preaccounting';

	$t->title = s("Les ventes de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:rapprocher-ventes';

	$t->mainTitle = new \farm\FarmUi()->getPreAccountingInvoiceTitle($data->eFarm, $data->eFinancialYear, 'reconciliate-sales', ['import' => array_sum($data->counts['import']), 'reconciliate-sales' => $data->counts['reconciliate']['sales'], 'reconciliate-operations' => $data->counts['reconciliate']['operations']]);

	echo '<div class="util-block-help">';
	echo s("Cette page vous permet de rapprocher vos ventes et factures avec les opérations bancaires que vous avez importées.");
	echo '</div>';

	if($data->ccSuggestion->empty()) {

		$linkPreAccounting = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite?';
		if($data->eFinancialYear->notEmpty()) {
			$linkPreAccounting .= 'from='.$data->eFinancialYear['startDate'].'&to='.$data->eFinancialYear['endDate'];
		}

		echo '<div class="util-info">';
		echo '<p>'.s("Il n'y a aucune vente à rapprocher.").'</p>';
		echo '<p>'.s("Souhaitez-vous <linkPreAccounting>préparer vos données de vente</linkPreAccounting> ou <linkCashflow>réaliser un import bancaire</linkCashflow> ?", [
				'linkPreAccounting' => '<a href="'.$linkPreAccounting.'">',
				'linkCashflow' => '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations">',
			]).'</p>';
		echo '</div>';

	} else {

		echo new \preaccounting\ReconciliateUi()->tableByCashflow($data->eFarm, $data->ccSuggestion);

	}

});

new AdaptativeView('/precomptabilite:rapprocher-ecritures', function($data, FarmTemplate $t) {

	$t->nav = 'preaccounting';

	$t->title = s("Rapprocher les écritures comptables de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:rapprocher-ecritures';

	$t->mainTitle = new \farm\FarmUi()->getPreAccountingInvoiceTitle($data->eFarm, $data->eFinancialYear, 'reconciliate-operations', ['import' => array_sum($data->counts['import']), 'reconciliate-sales' => $data->counts['reconciliate']['sales'], 'reconciliate-operations' => $data->counts['reconciliate']['operations']]);

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

