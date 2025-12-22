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

	echo '<div class="step-process">';

		foreach($steps as $step) {

	    echo '<a class="step '.($step['number'] > 0 ? 'active' : 'success').' '.($data->type === $step['type'] ? 'selected' : '').'"  href="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite?type='.$step['type'].'">';

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

		echo '<a class="step '.($step['number'] > 0 ? 'active' : 'success').'" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite?type=export">';
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


	echo '<div data-step="'.$data->type.'" class="stick-md util-overflow-md">';

		switch($data->type) {

			case 'product':
				echo new \preaccounting\PreaccountingUi()->products(
					$data->eFarm,
					$data->cProduct,
					$data->cCategories,
					$data->products,
					$data->search,
					itemData: ['nToCheck' => $data->nToCheckItem, 'nVerified' => $data->nVerifiedItem, 'cItem' => $data->cItem],
				);
				break;

			case 'payment':
				echo new \preaccounting\PreaccountingUi()->salesPayment($data->eFarm, $data->type, $data->cSale, $data->cPaymentMethod, $data->search);
				break;

			case 'closed':
				echo new \preaccounting\PreaccountingUi()->sales($data->eFarm, $data->type, $data->cSale, $data->cInvoice, $data->cPaymentMethod, $data->nToCheck, $data->nVerified, $data->search);
				break;

			case 'export':
				echo new \preaccounting\PreaccountingUi()->export($data->eFarm, $errors, $data->nProduct,  $data->nSalePayment,  $data->nSaleClosed,  $data->isSearchValid, $data->search);
				break;
		}

	echo '</div>';


});

new AdaptativeView('/precomptabilite:importer', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'operations';

	$t->title = s("Importer les ventes de {farm}", ['farm' => encode($data->eFarm['name'])]);
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

new AdaptativeView('/precomptabilite:rapprocher-factures', function($data, FarmTemplate $t) {

	$t->nav = 'bank';

	$t->title = s("Rapprocher factures et opérations bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:rapprocher-factures';

	$t->mainTitle = '<h1>'.s("Rapprocher factures et opérations bancaires").($data->countsByInvoice > 0 ? '<span class="util-counter ml-1">'.$data->countsByInvoice.'</span>' : '').'</h1>';

	if($data->ccSuggestion->empty()) {

		echo '<div class="util-empty">';

			echo '<p>';
				echo s("Il n'y a aucune facture à rapprocher pour le moment !").'<br/>';

			if($data->eImportLast->empty()) {

				echo s("Vous n'avez pas encore réalisé d'import bancaire.");

			} else {

				echo s("Votre dernier import bancaire a permis de rapprocher vos factures jusqu'au {date}.", ['date' => \util\DateUi::numeric($data->eImportLast['endDate'], \util\DateUi::DATE)]);

			}

			echo '</p>';

			if($data->eImportLast->empty()) {
				echo '<a class="btn btn-primary" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations">'.s("Faire mon premier import bancaire !").'</a>';
			} else {
				echo '<a class="btn btn-primary" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations">'.s("Importer mes données bancaires").'</a>';
			}

		echo '</div>';

	} else {

		echo new \preaccounting\ReconciliateUi()->tableByCashflow($data->eFarm, $data->ccSuggestion, $data->cMethod);

	}

});

