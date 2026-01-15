<?php

new AdaptativeView(
	'index', function ($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'balance';

	$t->title = s("La balance de {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/balance';

	$t->mainTitle = new \journal\BalanceUi()->getTitle($data->eFarm, $data->eFinancialYearDocument, $data->document);

	echo '<div class="tabs-h" id="balances">';

	if($data->eFarm['eFinancialYear']->isAccrualAccounting()) {
		echo new \journal\BalanceUi()->getTabs($data->eFarm, $data->tab);
	}

	switch($data->tab) {

		case 'customer':
		case 'supplier':
			echo '<div class="tab-panel selected" data-tab="'.$data->tab.'">';
				echo new \journal\BalanceUi()->displayThirdParty($data->eFarm, $data->cOperation, $data->tab);
			echo '</div>';
			break;

		default:
			echo '<div class="tab-panel selected" data-tab="">';

				echo new \journal\BalanceUi()->getSearch($data->search, $data->eFarm['eFinancialYear']);

				if(count($data->trialBalanceData) === 0 and $data->search->empty()) {
					echo '<div class="util-empty">'.s("La balance n'est pas disponible car il n'y a aucune écriture pour cet exercice comptable.").'</div>';
					echo '<a class="btn btn-primary" href="'.\company\CompanyUi::urlJournal($data->eFarm).'/livre-journal">'.s("Créer ma première écriture comptable dans le journal").'</a>';
				} else {

					echo new \journal\BalanceUi()->list($data->eFarm['eFinancialYear'], $data->eFinancialYearPrevious, $data->trialBalanceData, $data->trialBalancePreviousData, $data->search, $data->searches);
				}
			echo '</div>';

	}

	echo '</div>';


});
