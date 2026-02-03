<?php
new AdaptativeView('/precomptabilite', function($data, FarmTemplate $t) {

	Asset::js('preaccounting', 'preaccounting.js');

	$t->title = s("Précomptabilité des factures de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/precomptabilite';

	$t->nav = 'preaccounting';

	$mainTitle = '<div class="util-action">';
		$mainTitle .= '<h1>';
			$mainTitle .= s("Précomptabilité");
		$mainTitle .= '</h1>';

		$mainTitle .= '<div>';
			$mainTitle .= '<a href="/doc/accounting" class="btn btn-xs btn-outline-primary">'.\Asset::icon('person-raised-hand').' '.s("Aide").'</a>';
		$mainTitle .= '</div>';

	$mainTitle .= '</div>';
	$t->mainTitle = $mainTitle;

	if($data->eFarm['hasSales']) {

		echo new \preaccounting\PreaccountingUi()->getSearchPeriod($data->search);
		echo new \preaccounting\PreaccountingUi()->summarize($data->nInvoice, $data->nSale);

		if($data->nInvoice === 0 and $data->nSale === 0) {

			echo '<div class="util-block-info">';
				echo '<h3>'.s("Aucune donnée disponible").'</h3>';
				echo '<p>'.s("Aucune vente ni facture éligible à la comptabilité n'a été trouvée sur {siteName} pour cette période.<br/>Modifiez la période de recherche, ou <linkSales>consultez vos ventes</linkSales> ou <linkInvoice>vos factures</linkInvoice>.", [
					'linkSales' => '<a href="'.\farm\FarmUi::urlSellingSales($data->eFarm).'">',
					'linkInvoice' => '<a href="'.\farm\FarmUi::urlSellingInvoices($data->eFarm).'">',
					]).'</p>';
			echo '</div>';
			return;

		}

	} else {

		echo '<div class="util-block-help">';
			echo '<h3>'.s("La précomptabilité").'</h3>';
			echo '<p>'.s("La précomptabilité est l'opération préparatoire de vos ventes avant l'intégration dans votre comptabilité. Après avoir associé des numéros de compte à vos produits, vous pourrez exporter un {fec} ou importer vos factures en un clic dans le logiciel comptable de {siteName}.", ['fec' => '<span class="util-badge bg-primary">FEC</span>']).'</p>';
			echo '<p>'.s("La précomptabilité fonctionne avec les ventes et les factures que vous avez enregistrées sur {siteName}.<br/>À ce jour, vous n'avez pas encore utilisé le module de vente, la précomptabilité n'est donc pas disponible.").'</p>';
			echo '<a href="'.\farm\FarmUi::urlSellingSales($data->eFarm).'" class="btn btn-secondary">'.s("Créer une première vente").'</a>';
		echo '</div>';

		return;

	}

	Asset::css('preaccounting', 'step.css');

	$steps = [
		[
			'position' => 1,
			'number' => $data->nProductToCheck + $data->nItemToCheck,
			'type' => 'product',
			'title' => s("Produits"),
			'description' => s("Associez un numéro de compte à vos produits et articles"),
		],
		[
			'position' => 2,
			'number' => $data->nPaymentToCheck,
			'type' => 'payment',
			'title' => s("Moyens de paiement"),
			'description' => s("Renseignez le moyen de paiement des factures"),
		],
	];

	echo '<div class="step-process">';

		foreach($steps as $step) {

	    echo '<a class="step '.($step['number'] > 0 ? '' : 'step-success').' '.($data->type === $step['type'] ? 'selected' : '').'"  href="'.$t->canonical.'?type='.$step['type'].'&from='.$data->search->get('from').'&to='.$data->search->get('to').'">';

			echo '<div class="step-header">';

				echo '<span class="step-number">'.($step['position']).'</span>';

				echo '<div class="step-main">';

				echo '<div class="step-title">';
					echo $step['title'];

					if($step['number'] > 0) {
						echo '<span class="bg-warning tab-item-count ml-1" title="'.s("À contrôler").'">'.Asset::icon('exclamation-circle').'  '.$step['number'].'</span>';
					}

				echo '</div>';

				echo '<div class="step-value">';

				echo '</div>';

			echo '</div>';

	      echo '</div>';

		    echo '<p class="step-desc hide-sm-down">';
		      echo $step['description'];
		    echo '</p>';

		  echo '</a>';

		}

		echo '<a class="step '.($data->type === 'export' ? 'selected' : '').'" href="'.$t->canonical.'?type=export&from='.$data->search->get('from').'&to='.$data->search->get('to').'">';
			echo '<div class="step-header">';
				echo '<span class="step-number">'.(count($steps) + 1).'</span>';
				echo '<div class="step-main">';
					echo '<div class="step-title">'.s("Intégration en comptabilité").'</div>';
					echo '<div class="step-value"></div>';
				echo '</div>';
			echo '</div>';
			echo '<p class="step-desc">';
				echo s("Exportez un fichier {value} ou créez les écritures comptables de vos factures sur le logiciel comptable de {siteName}", '<span class="util-badge bg-primary">FEC</span>');
			echo '</p>';
		echo '</a>';

	echo '</div>';

	switch($data->type) {

		case 'product':
			echo '<div data-step="'.$data->type.'" class="stick-md util-overflow-md">';
				echo new \preaccounting\PreaccountingUi()->products(
					$data->eFarm,
					$data->cProduct,
					$data->cCategories,
					$data->products,
					$data->search,
					$data->nProductToCheck,
					itemData: ['nToCheck' => $data->nItemToCheck, 'cItem' => $data->cItem],
				);
			echo '</div>';
			break;

		case 'payment':
			echo '<div data-step="'.$data->type.'" class="stick-md util-overflow-md">';
				echo new \preaccounting\PreaccountingUi()->invoices($data->eFarm, $data->cInvoice, $data->cPaymentMethod, $data->search);
			echo '</div>';
			break;

		case 'export':
			echo new \preaccounting\PreaccountingUi()->export($data->eFarm, $data->nProductToCheck + $data->nItemToCheck,  $data->nPaymentToCheck, $data->isSearchValid, $data->search);
			break;
	}

});
new AdaptativeView('/precomptabilite/ventes', function($data, FarmTemplate $t) {

	Asset::js('preaccounting', 'preaccounting.js');

	$t->title = s("Précomptabilité des ventes de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/precomptabilite/ventes';

	$t->nav = 'preaccounting';

	$mainTitle = '<div class="util-action">';
		$mainTitle .= '<h1>';
		$mainTitle .= '<a href="'.\farm\FarmUi::urlConnected($data->eFarm).'/precomptabilite"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$mainTitle .= s("Exporter les données des ventes");
		$mainTitle .= '</h1>';

		$mainTitle .= '<div>';
			$mainTitle .= '<a href="/doc/accounting" class="btn btn-xs btn-outline-primary">'.\Asset::icon('person-raised-hand').' '.s("Aide").'</a>';
		$mainTitle .= '</div>';

	$mainTitle .= '</div>';
	$t->mainTitle = $mainTitle;

	echo new \preaccounting\PreaccountingUi()->getSearchSales($data->eFarm, $data->search);

	if(count($data->operations) > 0) {

		// On exclut les lignes de banque du total
		$filteredOperations = array_filter(
			$data->operations,
			fn($operation) => \account\AccountLabelLib::isFromClass(
				$operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL],
				\account\AccountSetting::BANK_ACCOUNT_CLASS
			) === FALSE
		);

		$totalDebit = array_sum(array_column($filteredOperations, \preaccounting\AccountingLib::FEC_COLUMN_DEBIT));
		$totalCredit = array_sum(array_column($filteredOperations, \preaccounting\AccountingLib::FEC_COLUMN_CREDIT));

		echo '<ul class="util-summarize">';

			echo '<li>';
				echo '<div>';
					echo '<h5>'.p("Écriture", "Écritures", count($data->operations)).'</h5>';
					echo '<div>'.count($data->operations).'</div>';
				echo '</div>';
			echo '</li>';

			echo '<li>';
				echo '<div>';
					echo '<h5>';
						echo match($data->search->get('hasInvoice')) {
							NULL => s("Ventes et factures"),
							0 => p("Vente", "Ventes", $data->nSale),
							1 => p("Facture", "Factures", $data->nInvoice),
						};
					echo '</h5>';
						echo '<div>';

							echo match($data->search->get('hasInvoice')) {
								NULL => $data->nSale + $data->nInvoice,
								0 => $data->nSale,
								1 => $data->nInvoice,
							};
					echo '</div>';
				echo '</div>';
			echo '</li>';

			echo '<li>';
				echo '<div>';
					echo '<h5>'.s("Montant").'</h5>';
					echo '<div>'.\util\TextUi::money(round($totalCredit - $totalDebit, 2)).'</div>';
				echo '</div>';
			echo '</li>';

		echo '</ul>';

		parse_str(mb_substr(LIME_REQUEST_ARGS, 1), $args);
		$url = \farm\FarmUi::urlConnected($data->eFarm).'/precomptabilite/ventes:telecharger?'.http_build_query($args);

		echo '<div class="mt-2 mb-2 text-center">';
			echo '<a class="dropdown-toggle btn btn-lg btn-secondary" data-dropdown="bottom-down" >'.\Asset::icon('download').' '.s("Télécharger le fichier {fec}", ['fec' => '<span class="util-badge bg-primary">FEC</span>']).'</a>';
			echo '<div class="dropdown-list">';
				echo '<a href="'.$url.'&format=csv" class="dropdown-item" data-ajax-navigation="never">';
					echo s("Au format CSV");
				echo '</a>';
				echo '<a href="'.$url.'&format=txt" class="dropdown-item" data-ajax-navigation="never">';
					echo s("Au format TXT");
				echo '</a>';
			echo '</div>';
		echo '</div>';

		echo new \preaccounting\SaleUi()->list($data->eFarm, $data->operations, $data->search->get('hasInvoice'), $data->cInvoice);

	} else {

		if($data->search->empty(['id'])) {

			echo '<div class="util-info">';
				echo s("Il n'y a aucune donnée comptable à afficher.");
			echo '</div>';

		} else {

			echo '<div class="util-empty">';
				echo s("Aucune vente ne correspond à vos critères de recherche.");
			echo '</div>';

		}

	}

});

new AdaptativeView('/precomptabilite:importer', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'operations';

	$t->title = s("Importer les factures de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/precomptabilite:importer';

	$navigation = '<a href="'.\farm\FarmUi::urlConnected($data->eFarm).'/precomptabilite"  class="h-back">'.\Asset::icon('arrow-left').'</a>';

	$t->mainTitle = '<h1>'.$navigation.s("Importer les factures dans le logiciel comptable").($data->nInvoice > 0 ? '<span class="util-counter ml-1">'.$data->nInvoice.'</span>' : '').'</h1>';

	echo new \preaccounting\ImportUi()->list($data->eFarm, $data->eFarm['eFinancialYear'], $data->cInvoice, $data->nInvoice, $data->search);

});

new AdaptativeView('/precomptabilite:rapprocher', function($data, FarmTemplate $t) {

	$t->nav = 'bank';

	$t->title = s("Rapprocher factures et opérations bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/precomptabilite:rapprocher';
	$navigation = '<a href="'.\farm\FarmUi::urlConnected($data->eFarm).'/banque/operations" class="h-back">'.\Asset::icon('arrow-left').'</a>';
	$t->mainTitle = '<h1>'.$navigation.s("Rapprocher factures et opérations bancaires").($data->countsByInvoice > 0 ? '<span class="util-counter ml-1">'.$data->countsByInvoice.'</span>' : '').'</h1>';

	if($data->ccSuggestion->empty()) {

		echo '<div class="util-empty">'.s("Il n'y a aucune facture à rapprocher pour le moment !").'</div>';

		echo '<div class="util-block-info">';
			echo \Asset::icon('fire', ['class' => 'util-block-icon']);

			echo '<p>';

			if($data->eImportLast->empty()) {

				echo s("Vous n'avez pas encore réalisé d'import bancaire.");

			} else {

				echo s("Votre dernier import bancaire a permis de rapprocher vos factures jusqu'au {date}.", ['date' => \util\DateUi::numeric($data->eImportLast['endDate'], \util\DateUi::DATE)]);

			}

			echo '</p>';

			if($data->eImportLast->empty()) {
				echo '<a class="btn btn-transparent" href="'.\farm\FarmUi::urlConnected($data->eFarm).'/banque/imports:import">'.s("Importer un premier relevé bancaire").'</a>';
			} else {
				echo '<a class="btn btn-transparent" href="'.\farm\FarmUi::urlConnected($data->eFarm).'/banque/imports:import">'.s("Importer un relevé bancaire").'</a>';
			}

		echo '</div>';

	} else {

		echo new \preaccounting\ReconciliateUi()->list($data->eFarm, $data->ccSuggestion, $data->cMethod);

	}

});

