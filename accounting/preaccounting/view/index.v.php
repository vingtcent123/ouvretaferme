<?php
new AdaptativeView('/precomptabilite', function($data, FarmTemplate $t) {

	Asset::js('preaccounting', 'preaccounting.js');

	$t->title = s("Précomptabilité des factures de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite';

	$t->nav = 'preaccounting';

	$toCheck = $data->nProductToCheck + $data->nItemToCheck + $data->nPaymentToCheck;

	$title = '<div class="util-action">';

		$title .= '<h1>';

			$title .= s("Préparer les données des factures");
			if($toCheck > 0) {
				$title .= ' <small>('.$toCheck.')</small>';
			}

		$title .= '</h1>';

		$title .= '<div>';
			$title .= '<a href="/doc/accounting" class="btn btn-xs btn-outline-primary">'.\Asset::icon('person-raised-hand').' '.s("Aide").'</a>';
		$title .= '</div>';

	$title .= '</div>';

	$t->mainTitle = $title;

	echo new \preaccounting\PreaccountingUi()->getSearch($data->eFarm, $data->search, 'invoices');

	if(($toCheck + $data->nProductVerified + $data->nPaymentVerified) === 0) {

		echo '<div class="util-block-important">';
			echo s("Il n'a aucune facture à afficher. Avez-vous choisi la bonne période ?");
		echo '</div>';
		return;
	}

	Asset::css('preaccounting', 'step.css');

	$steps = [
		[
			'position' => 1,
			'number' => $data->nProductToCheck + $data->nItemToCheck,
			'numberVerified' => $data->nProductVerified,
			'type' => 'product',
			'title' => s("Produits"),
			'description' => s("Associez un numéro de compte à vos produits et articles"),
		],
		[
			'position' => 2,
			'number' => $data->nPaymentToCheck,
			'numberVerified' => $data->nPaymentVerified,
			'type' => 'payment',
			'title' => s("Moyens de paiement"),
			'description' => s("Renseignez le moyen de paiement des factures"),
		],
	];

	echo '<div class="step-process">';

		foreach($steps as $step) {

	    echo '<a class="step '.($step['number'] > 0 ? 'active' : 'success').' '.($data->type === $step['type'] ? 'selected' : '').'"  href="'.$t->canonical.'?type='.$step['type'].'">';

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

		echo '<a class="step '.($data->type === 'export' ? 'selected' : '').'" href="'.$t->canonical.'?type=export">';
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
					itemData: ['nToCheck' => $data->nItemToCheck, 'cItem' => $data->cItem],
				);
				break;

			case 'payment':
				echo new \preaccounting\PreaccountingUi()->invoices($data->eFarm, $data->cInvoice, $data->cPaymentMethod, $data->search);
				break;

			case 'export':
				echo new \preaccounting\PreaccountingUi()->export($data->eFarm, $data->nProductToCheck + $data->nItemToCheck,  $data->nPaymentToCheck, $data->isSearchValid, $data->search);
				break;
		}

	echo '</div>';


});
new AdaptativeView('/precomptabilite/ventes', function($data, FarmTemplate $t) {

	Asset::js('preaccounting', 'preaccounting.js');

	$t->title = s("Précomptabilité des ventes de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite/ventes';

	$t->nav = 'preaccounting';

	$title = '<div class="util-action">';

		$title .= '<h1>';

			$title .= '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$title .= s("Explorer les données comptables des ventes");

		$title .= '</h1>';

		$title .= '<div>';
			$title .= '<a href="/doc/accounting" class="btn btn-xs btn-outline-primary">'.\Asset::icon('person-raised-hand').' '.s("Aide").'</a>';
		$title .= '</div>';

	$title .= '</div>';

	$t->mainTitle = $title;

	echo new \preaccounting\PreaccountingUi()->getSearch($data->eFarm, $data->search, 'sales', count($data->operations) > 0);

	if(count($data->operations) > 0) {

		echo new \preaccounting\SaleUi()->list($data->eFarm, $data->operations, $data->nSale);

	} else {

		if($data->search->empty(['id'])) {

			echo '<div class="util-info">';
				echo s("Il n'y a aucune donnée comptable à afficher.");
			echo '</div>';

		} else {

			echo '<div class="util-info">';
				echo s("Aucune vente ne correspond à vos critères de recherche.");
			echo '</div>';

		}

	}

});

new AdaptativeView('/precomptabilite:importer', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'operations';

	$t->title = s("Importer les factures de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:importer';

	$navigation = '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
	$maintitle = new \farm\FarmUi()->getAccountingYears($data->eFarm);
	$maintitle .= '<h1>'.$navigation.s("Importer les factures").($data->nInvoice > 0 ? '<span class="util-counter ml-1">'.$data->nInvoice.'</span>' : '').'</h1>';
	$t->mainTitle = $maintitle;

		echo new \preaccounting\ImportUi()->list($data->eFarm, $data->eFarm['eFinancialYear'], $data->cInvoice, $data->nInvoice, $data->search);

});

new AdaptativeView('/precomptabilite:rapprocher', function($data, FarmTemplate $t) {

	$t->nav = 'bank';

	$t->title = s("Rapprocher factures et opérations bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:rapprocher';
	$navigation = '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations" class="h-back">'.\Asset::icon('arrow-left').'</a>';
	$t->mainTitle = '<h1>'.$navigation.s("Rapprocher factures et opérations bancaires").($data->countsByInvoice > 0 ? '<span class="util-counter ml-1">'.$data->countsByInvoice.'</span>' : '').'</h1>';

	if($data->ccSuggestion->empty()) {

		echo '<div class="util-empty">'.s("Il n'y a aucune facture à rapprocher pour le moment !").'</div>';

		echo '<div class="util-block-important">';
			echo \Asset::icon('fire', ['class' => 'util-block-icon']);

			echo '<p>';

			if($data->eImportLast->empty()) {

				echo s("Vous n'avez pas encore réalisé d'import bancaire.");

			} else {

				echo s("Votre dernier import bancaire a permis de rapprocher vos factures jusqu'au {date}.", ['date' => \util\DateUi::numeric($data->eImportLast['endDate'], \util\DateUi::DATE)]);

			}

			echo '</p>';

			if($data->eImportLast->empty()) {
				echo '<a class="btn btn-transparent" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/imports:import">'.s("Importer un premier relevé bancaire").'</a>';
			} else {
				echo '<a class="btn btn-transparent" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/imports:import">'.s("Importer un relevé bancaire").'</a>';
			}

		echo '</div>';

	} else {

		echo new \preaccounting\ReconciliateUi()->list($data->eFarm, $data->ccSuggestion, $data->cMethod);

	}

});

