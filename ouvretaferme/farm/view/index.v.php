<?php
new AdaptativeView('planning', function($data, FarmTemplate $t) {

	$t->title = s("Planning de {value}", $data->eFarm['name']);
	$t->tab = 'home';
	$t->subNav = (new \farm\FarmUi())->getPlanningSubNav($data->eFarm, $data->week);

	$uiTask = new \series\TaskUi();

	switch(Setting::get('main\viewPlanning')) {

		case \farm\Farmer::DAILY :

			$t->template = 'farm farm-planning-daily';
			$t->mainContainer = FALSE;
			$t->footer = '';
			$t->canonical = \farm\FarmUi::urlPlanningDaily($data->eFarm, $data->week);

			echo '<div class="container">';
				echo $uiTask->getWeekCalendar(
					$data->eFarm,
					$data->week,
					fn($week) => \farm\FarmUi::urlPlanningDaily($data->eFarm, $week)
				);
				echo $uiTask->getCalendarSearch($data->eFarm, $data->search, $data->cAction, $data->cZone);
			echo '</div>';

			echo $uiTask->getDayPlanning($data->eFarm, $data->week, $data->cccTask, $data->cccTaskAssign, $data->cUserFarm, $data->eUserSelected, $data->seasonsWithSeries, $data->cCategory);

			break;

		case \farm\Farmer::WEEKLY :

			$t->template = 'farm farm-planning-weekly';
			$t->canonical = \farm\FarmUi::urlPlanningWeekly($data->eFarm, $data->week);

			echo $uiTask->getWeekCalendar(
				$data->eFarm,
				$data->week,
				fn($week) => \farm\FarmUi::urlPlanningWeekly($data->eFarm, $week),
				fn() => $uiTask->getCalendarFilter()
			);

			echo (new \main\HomeUi())->getTraining(TRUE);

			echo $uiTask->getCalendarSearch($data->eFarm, $data->search, $data->cAction, $data->cZone);

			echo $uiTask->getWeekPlanning($data->eFarm, $data->week, $data->cccTask, $data->cUserFarm, $data->eUserTime, $data->seasonsWithSeries, $data->cActionMain, $data->cCategory);

			break;

		case \farm\Farmer::YEARLY :

			$t->template = 'farm farm-planning-yearly';

			$t->canonical = \farm\FarmUi::urlPlanningYear($data->eFarm, $data->year, $data->month);

			$t->main = $uiTask->getYearCalendar(
				$data->eFarm,
				$data->year,
				fn() => $uiTask->getCalendarFilter(),
				fn() => '<div class="container">'.$uiTask->getCalendarSearch($data->eFarm, $data->search, $data->cAction, $data->cZone).'</div>'
			);

			$t->main .= $uiTask->getYearPlanning($data->year, $data->month, $data->ccTask);

			$t->footer = '';

			break;

	}

	$t->package('main')->updateNavPlanning($t->canonical);
	$t->js()->replaceHistory($t->canonical);

});

new AdaptativeView('/ferme/{id}/taches/{week}/{action}', function($data, FarmTemplate $t) {

	$t->title = $data->eAction['name'];
	$t->tab = 'home';
	$t->subNav = (new \farm\FarmUi())->getPlanningSubNav($data->eFarm);

	echo (new \series\TaskUi())->displayByAction($data->eFarm, $data->week, $data->eAction, $data->cTask);

});

new AdaptativeView('sequence', function($data, FarmTemplate $t) {

	$t->canonical = \farm\FarmUi::urlCultivationSequences($data->eFarm);
	$t->title = s("Itinéraires techniques de {value}", $data->eFarm['name']);
	$t->tab = 'cultivation';
	$t->subNav = (new \farm\FarmUi())->getCultivationSubNav($data->eFarm);

	$t->package('main')->updateNavCultivation($t->canonical);

	echo '<div class="util-action">';
		echo '<h1>';
			echo s("Itinéraires techniques");
		echo '</h1>';
		echo  '<div>';
			echo '<a '.attr('onclick', 'Lime.Search.toggle("#sequence-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a>';
			if($data->eFarm->canManage()) {
				echo ' <a href="/production/sequence:create?farm='.$data->eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Nouvel itinéraire").'</span></a>';
			}
		echo  '</div>';
	echo  '</div>';

	if($data->ccCrop->notEmpty() or $data->emptySearch === FALSE) {

		echo (new \production\SequenceUi())->getSearch($data->eFarm, $data->search, $data->emptySearch);
		echo (new \production\SequenceUi())->getTabs($data->eFarm, $data->search, $data->sequences);
		echo (new \production\SequenceUi())->getListByPlants($data->eFarm, $data->ccCrop, $data->cActionMain, $data->search);

	} else {
		echo '<div class="util-block-help">';
			echo '<p>'.s("Il n'y a pas encore d'itinéraire technique sur votre ferme !").'</p>';
			echo '<p>'.s("Un itinéraire technique contient la liste des interventions à réaliser pour une culture donnée. L'utilisation de cette fonctionnalité est facultative, mais elle permet de créer plus facilement vos séries d'une saison sur l'autre sans avoir à resaisir l'ensemble des interventions !").'</p>';
		echo '</div>';
		if($data->eFarm->canManage()) {
			echo '<div>';
				echo '<a href="/production/sequence:create?farm='.$data->eFarm['id'].'" class="btn btn-secondary">'.s("Créer un premier itinéraire technique").'</a>';
			echo '</div>';
		}
	}

});

new AdaptativeView('series', function($data, FarmTemplate $t) {

	$t->canonical = \farm\FarmUi::urlCultivationSeries($data->eFarm, season: $data->season);
	$t->title = s("Plan de culture de {value}", $data->eFarm['name']);
	$t->tab = 'cultivation';
	$t->subNav = (new \farm\FarmUi())->getCultivationSubNav($data->eFarm);

	$t->package('main')->updateNavCultivation($t->canonical);

	$t->js()->replaceHistory($t->canonical);

	$view = Setting::get('main\viewSeries');

	$uiSeries = new \series\SeriesUi();
	$uiFarm = new \farm\FarmUi();

	if(OTF_DEMO and $view === \farm\Farmer::AREA) {
		echo '<div class="util-block bg-demo color-white">';
			echo '<h4>'.s("Bienvenue sur la démo !").'</h4>';
			echo '<p>'.s("Cette ferme est en libre service partagé entre tous les utilisateurs de {siteName} pour vous aider à découvrir les fonctionnalités de notre site. N'hésitez pas à parcourir toutes les pages et faire toutes sortes d'actions ! Les données de cette ferme sont automatiquement remises à zéro toutes les nuits.").'</p>';
			echo '<p>'.s("Vous pouvez quitter à tout moment la démo en utilisant le lien <i>Quitter la démo</i> en haut de la page.").'</p>';
		echo '</div>';
	}

	echo $uiSeries->displayImport($data->cSeriesImport);

	echo $uiFarm->getCultivationSeriesTitle($data->eFarm, $data->season, $view, $data->nSeries);

	if($data->nSeries === 0 and $view !== \farm\Farmer::FORECAST) {

		if($data->firstSeries) {

			echo '<p class="util-block-help">';
				echo s("Vous êtes sur la page qui permet de créer des séries pour votre plan de culture. Vous êtes sur le point de créer une première série pour la saison {value}. Si vous souhaitez créer une série pour une autre année, utilisez le menu déroulant ci-dessus pour changer de saison.", $data->season);
			echo '</p>';

		} else {

			echo '<p class="util-block-help">';
				echo s("Votre plan de culture pour la saison {value} est encore vide. À vous de jouer et de créer une première série pour cette saison !", $data->season);
			echo '</p>';

		}

		echo '<div class="util-block-gradient">';

			echo '<h4>'.s("Ajouter une première série dans le plan de culture").'</h4>';

			$season = $data->season;

			$currentYear = (int)date('Y');
			$currentMonth = (int)date('m');

			$nextYear = $currentYear + 1;

			if($currentYear >= $data->eFarm['seasonFirst'] and $currentYear <= $data->eFarm['seasonLast']) {
				$season = $currentYear;
			}

			if($currentMonth >= Setting::get('farm\newSeason') and $nextYear >= $data->eFarm['seasonFirst'] and $nextYear <= $data->eFarm['seasonLast']) {
				$season = $nextYear;
			}

			echo $uiSeries->createFrom($data->eFarm, $season)->body;

		echo '</div>';

		echo $uiSeries->deleteSeason($data->eFarm, $data->season);

	} else {

		if($view !== \farm\Farmer::FORECAST) {
			echo $uiFarm->getCultivationSeriesSearch($view, $data->eFarm, $data->season, $data->search, $data->cSupplier);
		}

		echo match($view)  {
			\farm\Farmer::AREA => (new \series\CultivationUi())->displayByArea($data->season, $data->eFarm, $data->ccCultivation, $data->ccForecast),
			\farm\Farmer::FORECAST => (new \series\CultivationUi())->displayByForecast($data->eFarm, $data->season, $data->ccForecast),
			\farm\Farmer::SEEDLING => (new \series\CultivationUi())->displayBySeedling($data->season, $data->eFarm, $data->items, $data->cSupplier, $data->search->get('supplier')),
			\farm\Farmer::HARVESTING => (new \series\CultivationUi())->displayByHarvesting($data->ccCultivation),
			\farm\Farmer::WORKING_TIME => (new \series\CultivationUi())->displayByWorkingTime($data->eFarm, $data->ccCultivation),
			\farm\Farmer::TOOL => (new \series\CultivationUi())->displayByTool($data->eFarm, $data->cSeries)
		};


	}

});

new AdaptativeView('mapEmpty', function($data, FarmTemplate $t) {

	$t->title = s("Cartographier {value}", $data->eFarm['name']);
	$t->tab = 'map';

	$t->subNav = (new \farm\FarmUi())->getMapSubNav($data->eFarm);

	echo '<h1>';
		echo s("Cartographie");
		echo (new \farm\FarmUi())->getSeasonsTabs($data->eFarm, fn($season) => \farm\FarmUi::urlSoil($data->eFarm, $season), $data->season);
	echo '</h1>';

	echo '<div class="util-block-help">';
		echo '<h4>'.s("Vous venez de créer votre ferme !").'</h4>';
		echo '<p>'.s("Pour travailler avec {siteName}, vous devez maintenant cartographier votre ferme, c'est-à-dire paramétrer les emplacements de vos cultures. Cette première étape vous permettra ensuite d'accéder à l'ensemble des fonctionnalités du site.").'</p>';
		echo '<p>'.s("Vous pouvez paramétrer les emplacements de vos cultures sur trois échelles différentes :").'</p>';
		echo '<ul>';
			echo '<li>'.s("les parcelles, qui sont l'échelle la plus large").'</li>';
			echo '<li>'.s("les blocs, qui sont créés au sein des parcelles").'</li>';
			echo '<li>'.s("les planches, qui sont paramétrées dans les parcelles ou les blocs").'</li>';
		echo '</ul>';
		echo '<p>'.s("Il est nécessaire de créer au minimum une première parcelle, depuis cette page !").'</p>';
	echo '</div>';

	echo '<h3>'.s("Ajouter une première parcelle").'</h3>';
	echo (new \map\ZoneUi())->create($data->eFarm, new Collection())->body;

});

new AdaptativeView('cartography', function($data, FarmTemplate $t) {

	$t->canonical = \farm\FarmUi::urlCartography($data->eFarm, $data->season);
	$t->title = s("Assolement de {value}", $data->eFarm['name']);
	$t->tab = 'map';

	$t->subNav = (new \farm\FarmUi())->getMapSubNav($data->eFarm);

	$t->package('main')->updateNavMap($t->canonical);

	echo '<div class="util-action">';
		echo '<h1>';
			echo s("Cartographie");
			echo (new \farm\FarmUi())->getSeasonsTabs($data->eFarm, fn($season) => \farm\FarmUi::urlCartography($data->eFarm, $season), $data->season);
		echo '</h1>';
		if($data->eFarm->canManage()) {
			echo  '<div>';
				echo  '<a href="/map/zone:create?farm='.$data->eFarm['id'].'&season='.$data->season.'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Nouvelle parcelle").'</a>';
			echo  '</div>';
		}
	echo  '</div>';

	if(
		$data->cZone->count() === 1 and // Une seule parcelle
		$data->eZone['cPlot']->count() === 1 and // Uniquement le bloc inféodé à la parcelle
		$data->eZone['cPlot']->first()['cBed']->count() === 1 // Uniquement la planche inféodé à la parcelle
	) {

		echo '<div class="util-block-help">';
			echo '<h4>'.s("Vous avez créé votre première parcelle !").'</h4>';
			echo '<p>'.s("Vous pouvez maintenant soit ajouter directement des planches permanentes à cette parcelle, soit subdiviser la parcelle en plusieurs blocs. Par exemple, les maraîchers qui travaillent avec des jardins créent généralement un bloc par jardin. Les serres sont également traitées le plus souvent comme un bloc.").'</p>';
			echo '<p>'.s("Libre à vous de choisir l'organisation spatiale qui vous convient le mieux !").'</p>';
		echo '</div>';

	}

	echo (new \map\MapUi())->getFarm($data->eFarm, $data->season, $data->cZone, $data->eZone);

	echo '<div id="cartography-zone" class="stick-xs"></div>';

});

new AdaptativeView('soil', function($data, FarmTemplate $t) {

	$t->canonical = \farm\FarmUi::urlSoil($data->eFarm, $data->season);
	$t->title = s("Assolement de {value}", $data->eFarm['name']);
	$t->tab = 'map';
	$t->subNav = (new \farm\FarmUi())->getMapSubNav($data->eFarm);

	$t->js()->replaceHistory($t->canonical);
	$t->package('main')->updateNavMap($t->canonical);

	echo '<h1>';
		echo s("Assolement");
		echo (new \farm\FarmUi())->getSeasonsTabs($data->eFarm, fn($season) => \farm\FarmUi::urlSoil($data->eFarm, $season), $data->season);
	echo '</h1>';

	echo (new \map\ZoneUi())->getList($data->eFarm, $data->cZone, $data->season);


});

new AdaptativeView('history', function($data, FarmTemplate $t) {

	$t->canonical = \farm\FarmUi::urlHistory($data->eFarm, $data->season);
	$t->title = s("Rotations de {value}", $data->eFarm['name']);
	$t->tab = 'map';
	$t->subNav = (new \farm\FarmUi())->getMapSubNav($data->eFarm);

	$t->package('main')->updateNavMap($t->canonical);

	echo '<div class="util-action">';
		echo '<h1>';
			echo s("Rotations");
			echo (new \farm\FarmUi())->getSeasonsTabs($data->eFarm, fn($season) => \farm\FarmUi::urlHistory($data->eFarm, $season), $data->season);
		echo '</h1>';
		echo '<div>';
			echo '<a '.attr('onclick', 'Lime.Search.toggle("#bed-rotation-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
			if($data->eFarm->canManage()) {
				echo '<a href="/farm/farm:updateSeries?id='.$data->eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('gear-fill').' '.s("Configurer").'</a>';
			}
		echo '</div>';
	echo '</div>';

	echo (new \farm\FarmUi())->getRotationSearch($data->search, $data->selectedSeasons);
	echo (new \map\ZoneUi())->getList($data->eFarm, $data->cZone, $data->season, $data->search);


});

new AdaptativeView('plant', function($data, FarmTemplate $t) {

	$t->tab = 'cultivation';
	$t->subNav = (new \farm\FarmUi())->getCultivationSubNav($data->eFarm);

	$t->title = s("Espèces cultivées de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlCultivationPlants($data->eFarm);

	$t->package('main')->updateNavCultivation($t->canonical);

	echo '<div class="util-action">';
		echo '<h1>';
			echo s("Espèces cultivées");
		echo '</h1>';
		if($data->eFarm->canManage()) {
			echo  '<div>';
				echo ' <a href="/plant/plant:create?farm='.$data->eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Nouvelle espèce").'</span></a>';
			echo  '</div>';
		}
	echo  '</div>';

	echo (new \plant\PlantUi())->manage($data->eFarm, $data->plants, $data->cPlant, $data->search);


});

new AdaptativeView('sellingSales', function($data, FarmTemplate $t) {

	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);

	$t->title = s("Ventes de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingSalesAll($data->eFarm);

	$t->package('main')->updateNavSelling($t->canonical);

	if(
		$data->nSale === 0 and
		$data->type === NULL and
		$data->search->empty()
	) {

		echo '<h1>'.s("Ventes de la ferme").'</h1>';

		echo '<div class="util-block-help">';
			echo '<p>'.s("{siteName} propose de très nombreuses fonctionnalités pour gérer vos ventes. Gérez vos marchés, ouvrez une boutique en ligne avec en option le paiement par carte bancaire, éditez vos bons de livraisons et factures à destinations des professionnels... Et analysez ensuite vos ventes avec des graphiques et statistiques !").'</p>';
			echo '<p>'.s("Avant de créer votre première vente, regardez au préalable comment <customer>créer des clients</customer> et <items>référencer vos produits</items>. Une fois que c'est fait, c'est parti !", ['customer' => '<a href="'.\farm\FarmUi::urlSellingCustomer($data->eFarm).'">', 'items' => '<a href="'.\farm\FarmUi::urlSellingProduct($data->eFarm).'">']).'</p>';
			echo '<a href="/presentation/producteur" class="btn btn-secondary">'.s("En savoir plus").'</a>';
		echo '</div>';

		echo '<br/>';

		echo '<h3>'.s("Ajouter une première vente").'</h3>';

		$eSale = new \selling\Sale([
			'farm' => $data->eFarm,
			'cShop' => new Collection(),
			'customer' => new \selling\Customer(),
			'market' => FALSE
		]);

		echo (new \selling\SaleUi())->create($eSale)->body;

	} else {

		$view = Setting::get('main\viewSellingSales');
		echo (new \farm\FarmUi())->getSellingSalesTitle($data->eFarm, $view);

		echo (new \selling\SaleUi())->getSearch($data->search);

		if($data->search->empty()) {
			echo (new \selling\SaleUi())->getNextSales($data->eFarm, $data->type, $data->nextSales);
		}

		echo (new \selling\SaleUi())->getList($data->eFarm, $data->cSale, $data->nSale, $data->search, hide: ['items'], page: $data->page);

	}

});

new AdaptativeView('/ferme/{id}/clients', function($data, FarmTemplate $t) {

	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);

	$t->title = s("Clients de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingCustomer($data->eFarm);

	$t->package('main')->updateNavSelling($t->canonical);

	if(
		$data->cCustomer->empty() and
		$data->search->empty()
	) {

		echo '<h1>'.s("Clients de la ferme").'</h1>';

		echo '<p class="util-block-help">';
			echo s("Pour vendre, vous devez avoir des clients et c'est ici que ça se passe pour créer un premier client. Deux champs du formulaire sont obligatoires, le nom du client et la catégorie. Les clients professionnels sont facturés HT et les clients particuliers TTC si vous êtes assujetti à la TVA.");
		echo '</p>';

		echo '<br/>';

		echo '<h3>'.s("Ajouter un premier client").'</h3>';

		echo (new \selling\CustomerUi())->create($data->eFarm)->body;

	} else {


		echo '<div class="util-action">';
			echo '<h1>'.s("Clients").'</h1>';
			echo '<div>';
				echo '<a '.attr('onclick', 'Lime.Search.toggle("#customer-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
				if((new \selling\Customer(['farm' => $data->eFarm]))->canWrite()) {
					echo '<a href="/selling/customer:create?farm='.$data->eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Nouveau client").'</span></a>';
				}
			echo '</div>';
		echo '</div>';

		echo (new \selling\CustomerUi())->getSearch($data->eFarm, $data->search);
		echo (new \selling\CustomerUi())->getList($data->eFarm, $data->cCustomer, $data->nCustomer, $data->search, $data->page);

	}


});

new AdaptativeView('/ferme/{id}/produits', function($data, FarmTemplate $t) {

	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);

	$t->title = s("Produits de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingProduct($data->eFarm);

	$t->package('main')->updateNavSelling($t->canonical);

	if(
		$data->cProduct->empty() and
		$data->search->empty()
	) {

		echo '<h1>'.s("Produits de la ferme").'</h1>';

		echo '<p class="util-block-help">';
			echo s("Pour vendre, vous devez définir vos produits et c'est ici que ça se passe pour créer un premier produit. Tous les champs du formulaire sont facultatifs à l'exception bien sûr du nom du produit.");
		echo '</p>';

		echo '<br/>';

		echo '<h3>'.s("Ajouter un premier produit").'</h3>';

		echo (new \selling\ProductUi())->create($data->eFarm)->body;

	} else {


		echo '<div class="util-action">';
			echo '<h1>'.s("Produits").'</h1>';
			echo '<div>';
				echo '<a '.attr('onclick', 'Lime.Search.toggle("#product-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
				if($data->eFarm->canManage()) {
					echo '<a href="/selling/product:create?farm='.$data->eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Nouveau produit").'</span></a>';
				}
			echo '</div>';
		echo '</div>';

		echo (new \selling\ProductUi())->getSearch($data->eFarm, $data->search);
		echo (new \selling\ProductUi())->getList($data->eFarm, $data->cProduct, $data->search);

	}


});

new AdaptativeView('/ferme/{id}/boutiques', function($data, FarmTemplate $t) {

	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);

	$t->title = s("Boutiques de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingShop($data->eFarm);

	$t->package('main')->updateNavSelling($t->canonical);

	$uiShopManage = new \shop\ShopManageUi();

	if($data->cShop->empty()) {

		echo $uiShopManage->getIntroCreate($data->eFarm);

	} else if(
		$data->cShop->count() > 1 and
		$data->eShop->empty()
	) {

		echo $uiShopManage->getList($data->eFarm, $data->cShop);

	} else {

		echo $uiShopManage->getHeader($data->eFarm, $data->eShop, $data->cShop);

		echo (new \shop\ShopUi())->getDetails($data->eShop);

		if(
			$data->eShop['ccPoint']->notEmpty() and
			$data->eShop['cDate']->notEmpty()
		) {
			echo $uiShopManage->getTabsContent($data->eFarm, $data->eShop);
		} else {
			echo $uiShopManage->getInlineContent($data->eFarm, $data->eShop);
		}

	}

});

new AdaptativeView('/ferme/{id}/factures', function($data, FarmTemplate $t) {

	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);

	$t->title = s("Factures pour {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingSalesInvoice($data->eFarm);

	$t->package('main')->updateNavSelling($t->canonical);

	echo (new \farm\FarmUi())->getSellingSalesTitle($data->eFarm, \farm\Farmer::INVOICE);

	echo (new \selling\InvoiceUi())->getSearch($data->search);

	if($data->transfer > 0) {

		echo '<div class="util-block-help">';
			echo '<p>'.s("Vous pouvez maintenant générer les factures des ventes qui ont été réglées par virement bancaire dans vos boutiques en ligne au moins de {value}.", '<b>'.\util\DateUi::textual($data->transferMonth, \util\DateUi::MONTH_YEAR).'</b>').'</p>';
			echo '<a href="/selling/invoice:createCollection?farm='.$data->eFarm['id'].'&month='.$data->transferMonth.'&type='.\selling\Sale::TRANSFER.'" class="btn btn-secondary">'.s("Générer les factures").'</a>';
		echo '</div>';

	}

	if(
		$data->cInvoice->empty() and
		$data->search->empty()
	) {

		echo '<div class="util-block-help">';
			echo '<p>'.s("Vous n'avez pas encore généré de facture à partir de vos ventes !").'</p>';
			echo '<p>'.s("Avec {siteName}, il est possible d'éditer des factures à partir de n'importe laquelle de vos ventes à l'état livré. Vous pouvez également éditer des factures à partir de plusieurs ventes d'un même client ! L'utilisation du module de facturation demande un peu de paramétrage avant d'être utilisé, n'hésitez pas à l'anticiper dès maintenant.").'</p>';
			echo '<a href="/selling/configuration:update?id='.$data->eFarm['id'].'" class="btn btn-secondary">'.s("Configurer la commercialisation").'</a>';
		echo '</div>';

	} else {

		echo (new \selling\InvoiceUi())->getList($data->cInvoice, $data->nInvoice, page: $data->page);

	}

});

new AdaptativeView('/ferme/{id}/etiquettes', function($data, FarmTemplate $t) {

	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);

	$t->title = s("Étiquettes de colisage pour {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingSalesLabel($data->eFarm);

	$t->package('main')->updateNavSelling($t->canonical);

	echo (new \farm\FarmUi())->getSellingSalesTitle($data->eFarm, \farm\Farmer::LABEL);

	echo (new \selling\SaleUi())->getLabels($data->eFarm, $data->cSale);

});

new AdaptativeView('analyzeReport', function($data, FarmTemplate $t) {

	$t->tab = 'analyze';
	$t->subNav = (new \farm\FarmUi())->getAnalyzeSubNav($data->eFarm);

	$t->title = s("Analyse des séries de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlAnalyzeReport($data->eFarm, $data->season);

	$t->package('main')->updateNavAnalyze($t->canonical);

	echo (new \farm\FarmUi())->getAnalyzeReportTitle($data->eFarm, $data->season);

	echo (new \analyze\ReportUi())->getList($data->cReport, $data->search);


});

new AdaptativeView('analyzeWorkingTime', function($data, FarmTemplate $t) {

	$t->tab = 'analyze';
	$t->subNav = (new \farm\FarmUi())->getAnalyzeSubNav($data->eFarm);

	$t->title = s("Analyse du planning de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlAnalyzeWorkingTime($data->eFarm, $data->year, $data->category);

	$t->package('main')->updateNavAnalyze($t->canonical);

	if($data->years === []) {

		echo '<h1>'.s("Analyse du planning").'</h1>';
		echo '<div class="util-info">'.s("L'analyse du planning sera disponible lorsque votre ferme aura démarré son activité !").'</div>';

	} else {

		echo (new \farm\FarmUi())->getAnalyzeWorkingTimeTitle($data->eFarm, $data->years, $data->year, $data->month, $data->week, $data->category);
		echo '<br/>';

		$uiAnalyze = new \series\AnalyzeUi();

		echo match($data->category) {
			\farm\Farmer::TIME => $uiAnalyze->getBestActions($data->eFarm, $data->year, $data->month, $data->week, $data->globalTime, $data->cTimesheetAction, $data->cccTimesheetActionMonthly, $data->cTimesheetCategory, $data->ccTimesheetCategoryMonthly, $data->cTimesheetPlant, $data->ccTimesheetPlantMonthly, $data->cTimesheetSeries, $data->ccTimesheetSeriesMonthly, $data->monthly),
			\farm\Farmer::TEAM => $uiAnalyze->getWorkingTime($data->eFarm, $data->year, $data->ccWorkingTimeMonthly, $data->workingTimeWeekly, $data->ccTimesheetAction),
			\farm\Farmer::PACE => $uiAnalyze->getPace($data->eFarm, $data->years, $data->year, $data->cAction, $data->ccPlant, $data->ccPlantCompare, $data->yearCompare),
			\farm\Farmer::PERIOD => $uiAnalyze->getPeriod($data->cWorkingTimeMonth),
		};

	}


});

new AdaptativeView('analyzeSelling', function($data, FarmTemplate $t) {

	$t->tab = 'analyze';
	$t->subNav = (new \farm\FarmUi())->getAnalyzeSubNav($data->eFarm);

	$t->title = s("Analyse des ventes de {value}", $data->eFarm['name']);

	$t->canonical = \farm\FarmUi::urlAnalyzeSelling($data->eFarm, $data->year, $data->category);

	if($data->week) {
		$t->canonical .= '?week='.$data->week;
	}
	if($data->month) {
		$t->canonical .= '?month='.$data->month;
	}

	$t->js()->replaceHistory($t->canonical);

	$t->package('main')->updateNavAnalyze($t->canonical);

	if($data->years === []) {

		echo '<h1>'.s("Analyse des ventes").'</h1>';
		echo '<div class="util-info">'.s("L'analyse des ventes sera disponible lorsque vous aurez livré votre première commande !").'</div>';

	} else {

		$uiAnalyze = new \selling\AnalyzeUi();

		echo (new \farm\FarmUi())->getAnalyzeSellingTitle($data->eFarm, $data->years, $data->year, $data->month, $data->week, $data->category);

		echo match($data->category) {
			\farm\Farmer::ITEM => $uiAnalyze->getTurnover($data->eFarm, $data->cSaleTurnover, $data->year, $data->month, $data->week),
			default => ''
		};

		echo '<br/>';

		echo match($data->category) {
			\farm\Farmer::ITEM => $uiAnalyze->getBestSeller($data->eFarm, $data->cItemProduct, $data->cItemProductMonthly, $data->cPlant, $data->cccItemPlantMonthly, $data->year, $data->cItemProductCompare, $data->cPlantCompare, $data->yearCompare, $data->years, $data->monthly, $data->month, $data->week, $data->search),
			\farm\Farmer::CUSTOMER => $uiAnalyze->getBestCustomers($data->ccItemCustomer, $data->ccItemCustomerMonthly, $data->year, $data->month, $data->week, $data->monthly, $data->search),
			\farm\Farmer::SHOP => $uiAnalyze->getShop($data->eFarm, $data->cShop, $data->eShop, $data->cSaleTurnover, $data->cItemProduct, $data->cItemProductMonthly, $data->cPlant, $data->cccItemPlantMonthly, $data->ccItemCustomer, $data->year, $data->monthly),
			\farm\Farmer::PERIOD => $uiAnalyze->getPeriod($data->year, $data->cItemMonth, $data->cItemMonthBefore, $data->cItemWeek, $data->cItemWeekBefore),
		};

	}


});

new AdaptativeView('analyzeCultivation', function($data, FarmTemplate $t) {

	$t->tab = 'analyze';
	$t->subNav = (new \farm\FarmUi())->getAnalyzeSubNav($data->eFarm);

	$t->title = s("Analyse des cultures de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlAnalyzeCultivation($data->eFarm, $data->season, $data->category);

	$t->js()->replaceHistory($t->canonical);

	$t->package('main')->updateNavAnalyze($t->canonical);

	$actions = match($data->category) {
		\farm\Farmer::PLANT => '<a '.attr('onclick', 'Lime.Search.toggle("#analyze-plant-search")').' class="btn btn-primary">'.\Asset::icon('search').' '.s("Filtrer").'</a>',
		\farm\Farmer::FAMILY => '<a '.attr('onclick', 'Lime.Search.toggle("#analyze-family-search")').' class="btn btn-primary">'.\Asset::icon('search').' '.s("Filtrer").'</a>',
		\farm\Farmer::ROTATION => '<a href="/farm/farm:updateSeries?id='.$data->eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('gear-fill').' '.s("Configurer").'</a>',
		default => ''
	};

	$uiAnalyze = new \plant\AnalyzeUi();

	echo (new \farm\FarmUi())->getAnalyzeCultivationTitle($data->eFarm, $data->seasons, $data->season, $data->category, $actions);

	echo match($data->category) {
		\farm\Farmer::AREA => $uiAnalyze->getArea($data->eFarm, $data->seasons, $data->season, $data->area),
		\farm\Farmer::PLANT => $uiAnalyze->getPlant($data->season, $data->ccCultivationPlant, $data->search),
		\farm\Farmer::FAMILY => $uiAnalyze->getFamily($data->eFarm, $data->season, $data->area, $data->ccCultivationFamily, $data->search),
		\farm\Farmer::ROTATION => $uiAnalyze->getRotation($data->eFarm, $data->selectedSeasons, $data->area, $data->cFamily, $data->cBed, $data->rotations),
	};


});

new AdaptativeView('/ferme/{id}/configuration', function($data, FarmTemplate $t) {

	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->eFarm);

	$t->title = s("Configuration pour {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSettings($data->eFarm);

	$t->package('main')->updateNavSettings($t->canonical);

	echo (new \farm\FarmUi())->getSettings($data->eFarm, $data->eNews);

});
?>
