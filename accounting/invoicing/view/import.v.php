<?php
new AdaptativeView('/ventes/importer', function($data, FarmTemplate $t) {

	$t->nav = 'invoicing';

	$t->title = s("Les ventes de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/ventes/importer';

	$t->mainTitle = new \farm\FarmUi()->getAccountingInvoiceTitle($data->eFarm, $data->eFinancialYear, 'import', ['import' => array_sum($data->numberImport)]);

	echo '<div class="util-block-help">';
		echo s("Cette page vous permet de vérifier et importer vos ventes depuis le module de commercialisation directement en comptabilité.<br />Si vous voyez le symbole {symbol}, l'import en comptabilité ne pourra pas être réalisé car des informations sont manquantes. <link>Cliquez ici</link> pour préparer les données de vos ventes à votre comptabilité.", ['symbol' => new \invoicing\ImportUi()->emptyData(), 'link' => '<a href="'.\farm\FarmUi::urlSellingSalesAccounting($data->eFarm).'">']);
	echo '</div>';

	echo '<div class="tabs-item">';

		foreach(['market', 'invoice', 'sales'] as $tab) {

			echo '<a class="tab-item '.($data->selectedTab === $tab ? ' selected' : '').'" data-tab="'.$tab.'" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/ventes/importer?tab='.$tab.'">';
				echo match($tab) {
					'market' => s("Marchés"),
					'invoice' => s("Factures"),
					'sales' => s("Autres ventes"),
				};
				echo ' <small class="tab-item-count">'.$data->numberImport[$tab].'</small>';
			echo '</a>';

		}

	echo '</div>';

	echo match($data->selectedTab) {
		'market' => new \invoicing\ImportUi()->displayMarket($data->eFarm, $data->eFinancialYear, $data->c),
		'invoice' => new \invoicing\ImportUi()->displayInvoice($data->eFarm, $data->eFinancialYear, $data->c),
		'sales' => new \invoicing\ImportUi()->displaySales($data->eFarm, $data->eFinancialYear, $data->c),
	};

});

