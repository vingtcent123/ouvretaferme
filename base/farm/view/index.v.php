<?php
new AdaptativeView('planning', function($data, FarmTemplate $t) {

	$t->title = s("Planning de {value}", $data->eFarm['name']);
	$t->nav = 'planning';

	$uiTask = new \series\TaskUi();

	$period = $data->eFarm->getView('viewPlanning');

	switch($period) {

		case \farm\Farmer::DAILY :

			$t->subNav = 'daily';

			$t->template = 'farm farm-planning-daily';
			$t->mainContainer = FALSE;
			$t->footer = '';
			$t->canonical = \farm\FarmUi::urlPlanningDaily($data->eFarm, $data->week);

			$t->mainTitle = $uiTask->getWeekCalendar(
				$data->eFarm,
				$data->week,
				fn($week) => \farm\FarmUi::urlPlanningDaily($data->eFarm, $week)
			);
			echo $uiTask->getWeekSearch($data->eFarm, $data->search, $data->cAction, $data->cZone, new Collection());

			echo $uiTask->getDayPlanning($data->eFarm, $data->week, $data->cccTask, $data->cccTaskAssign, $data->cUserFarm, $data->eUserSelected, $data->seasonsWithSeries, $data->cCategory);

			break;

		case \farm\Farmer::WEEKLY :

			$t->subNav = 'weekly';

			$t->template = 'farm farm-planning-weekly';
			$t->canonical = \farm\FarmUi::urlPlanningWeekly($data->eFarm, $data->week);

			$t->mainTitle = $uiTask->getWeekCalendar(
				$data->eFarm,
				$data->week,
				fn($week) => \farm\FarmUi::urlPlanningWeekly($data->eFarm, $week),
				fn() => $uiTask->getCalendarFilter()
			);

			echo new \main\HomeUi()->getTraining(TRUE);

			echo $uiTask->getWeekSearch($data->eFarm, $data->search, $data->cAction, $data->cZone, $data->cUserFarm);

			echo $uiTask->getWeekPlanning($data->eFarm, $data->week, $data->cccTask, $data->cUserFarm, $data->eUserTime, $data->seasonsWithSeries, $data->cActionMain, $data->cCategory);

			break;

		case \farm\Farmer::YEARLY :

			$t->subNav = 'yearly';

			$t->template = 'farm farm-planning-yearly';

			$t->canonical = \farm\FarmUi::urlPlanningYear($data->eFarm, $data->year, $data->month);

			$t->mainTitle = $uiTask->getYearCalendar(
				$data->eFarm,
				$data->year,
				fn() => $uiTask->getCalendarFilter()
			);

			$t->main = $uiTask->getYearSearch(
				$data->eFarm,
				$data->year,
				fn() => '<div class="container">'.$uiTask->getWeekSearch($data->eFarm, $data->search, $data->cAction, $data->cZone, $data->cUserFarm).'</div>'
			);

			$t->main .= $uiTask->getYearMonths($data->year, $data->month, $data->ccTask);

			$t->footer = '';

			break;

	}

	$t->package('main')->updateNavPlanning($t->canonical, $period);

	$t->js()->replaceHistory($t->canonical);

});

new AdaptativeView('/ferme/{id}/taches/{week}/{action}', function($data, FarmTemplate $t) {

	$t->title = $data->eAction['name'];
	$t->nav = 'planning';

	$t->mainTitle = new \series\TaskUi()->displayByActionTitle($data->eFarm, $data->week, $data->eAction);
	echo new \series\TaskUi()->displayByAction($data->eFarm, $data->week, $data->eAction, $data->cTask);

});

new AdaptativeView('sequence', function($data, FarmTemplate $t) {

	$t->canonical = \farm\FarmUi::urlCultivationSequences($data->eFarm);
	$t->title = s("Itinéraires techniques de {value}", $data->eFarm['name']);

	$t->nav = 'cultivation';
	$t->subNav = 'sequence';

	$h = '<div class="util-action">';
		$h .= '<h1>'.s("Itinéraires techniques").'</h1>';
		$h .=  '<div>';
			$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#sequence-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a>';
			if($data->eFarm->canManage()) {
				$h .= ' <a href="/sequence/sequence:create?farm='.$data->eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Nouvel itinéraire").'</span></a>';
			}
		$h .=  '</div>';
	$h .=  '</div>';
	
	$t->mainTitle = $h;

	if($data->ccCrop->notEmpty() or $data->emptySearch === FALSE) {

		echo new \sequence\SequenceUi()->getSearch($data->eFarm, $data->search, $data->emptySearch);
		echo new \sequence\SequenceUi()->getTabs($data->eFarm, $data->search, $data->sequences);
		echo new \sequence\SequenceUi()->getListByPlants($data->eFarm, $data->ccCrop, $data->cActionMain, $data->search);

	} else {
		echo '<div class="util-block-help">';
			echo '<h4>'.s("Vous êtes sur la page pour créer des itinéraires techniques").'</h4>';
			echo '<p>'.s("Un itinéraire technique contient la liste des interventions à réaliser pour une culture donnée. L'utilisation de cette fonctionnalité est facultative, mais elle permet de créer plus facilement vos séries d'une saison sur l'autre sans avoir à resaisir l'ensemble des interventions !").'</p>';
			echo '<p><b>'.s("Si vous débutez avec {siteName}, il est recommandé de ne pas utiliser cette fonctionnalité immédiatement pour vous laisser le temps de bien prendre en main le reste du site. Vous pourrez y revenir ultérieurement !").'</b></p>';
			if($data->eFarm->canManage()) {
				echo '<a href="/sequence/sequence:create?farm='.$data->eFarm['id'].'" class="btn btn-secondary">'.s("Créer un premier itinéraire technique").'</a>';
			}
		echo '</div>';
	}

});

new AdaptativeView('series', function($data, FarmTemplate $t) {

	$t->canonical = \farm\FarmUi::urlCultivationSeries($data->eFarm, season: $data->season);
	$t->title = s("Plan de culture de {value}", $data->eFarm['name']);

	$t->nav = 'cultivation';
	$t->subNav = 'series';
	$t->subNavTarget = $t->canonical;

	$t->js()->replaceHistory($t->canonical);

	$view = $data->eFarm->getView('viewSeries');

	$uiSeries = new \series\SeriesUi();
	$uiFarm = new \farm\FarmUi();

	if(OTF_DEMO and $view === \farm\Farmer::AREA) {
		echo '<div class="util-block bg-secondary color-white">';
			echo '<h4>'.s("Bienvenue sur la démo !").'</h4>';
			echo '<p>'.s("Cette ferme est en libre service partagé entre tous les utilisateurs de {siteName} pour vous aider à découvrir les fonctionnalités de notre site. N'hésitez pas à parcourir toutes les pages et faire toutes sortes d'actions ! Les données de cette ferme sont automatiquement remises à zéro toutes les nuits.").'</p>';
			echo '<p>'.s("Vous pouvez quitter à tout moment la démo en utilisant le lien <i>Quitter la démo</i> en haut de la page.").'</p>';
		echo '</div>';
	}

	$t->mainTitle = $uiFarm->getCultivationSeriesTitle($data->eFarm, $data->season, $view, $data->nSeries, $data->firstSeries);
	$t->mainYear = $uiFarm->getSeasonsTabs($data->eFarm, fn($season) => \farm\FarmUi::urlCultivationSeries($data->eFarm, season: $season), $data->season);

	echo $uiSeries->displayImport($data->eFarm, $data->nSeries, $data->cSeriesImportPerennial, $data->firstSeries, $data->season);

	if($data->nSeries === 0) {

		echo $uiSeries->deleteSeason($data->eFarm, $data->season);

	} else {

		if($data->search !== NULL) {
			echo $uiFarm->getCultivationSeriesSearch($view, $data->eFarm, $data->season, $data->search, $data->cAction);
		}

		echo match($view)  {
			\farm\Farmer::AREA => new \series\CultivationUi()->displayByArea($data->season, $data->eFarm, $data->ccCultivation),
			\farm\Farmer::HARVESTING => new \series\CultivationUi()->displayByHarvesting($data->ccCultivation),
			\farm\Farmer::WORKING_TIME => new \series\CultivationUi()->displayByWorkingTime($data->eFarm, $data->ccCultivation)
		};


	}

});

new AdaptativeView('seedling', function($data, FarmTemplate $t) {

	$t->canonical = \farm\FarmUi::urlCultivationSeedling($data->eFarm, season: $data->season);
	$t->title = s("Semences et plants de {value}", $data->eFarm['name']);
	$t->nav = 'cultivation';
	$t->subNav = 'seedling';

	$t->js()->replaceHistory($t->canonical);

	$uiFarm = new \farm\FarmUi();

	$t->mainTitle = $uiFarm->getCultivationSeedlingTitle($data->eFarm);
	$t->mainYear = $uiFarm->getSeasonsTabs($data->eFarm, fn($season) => \farm\FarmUi::urlCultivationSeedling($data->eFarm, season: $season), $data->season);

	if($data->nSeries > 5) {
		echo $uiFarm->getCultivationSeedlingSearch($data->eFarm, $data->season, $data->search, $data->cSupplier);
	}

	echo ($data->search->get('seedling') === \series\Cultivation::YOUNG_PLANT_BOUGHT) ?
			new \series\CultivationUi()->displayBySeedlingByStartWeek($data->eFarm, $data->season, $data->items, $data->cSupplier, $data->search) :
			new \series\CultivationUi()->displayBySeedling($data->eFarm, $data->items, $data->cSupplier, $data->search);

});

new AdaptativeView('forecast', function($data, FarmTemplate $t) {

	$t->canonical = \farm\FarmUi::urlCultivationForecast($data->eFarm, season: $data->season);
	$t->title = s("Prévisionnel financier de {value}", $data->eFarm['name']);
	$t->nav = 'cultivation';
	$t->subNav = 'forecast';

	$t->js()->replaceHistory($t->canonical);

	$uiFarm = new \farm\FarmUi();

	$t->mainTitle = $uiFarm->getCultivationForecastTitle($data->eFarm, $data->season);
	$t->mainYear = $uiFarm->getSeasonsTabs($data->eFarm, fn($season) => \farm\FarmUi::urlCultivationForecast($data->eFarm, season: $season), $data->season);

	echo new \series\CultivationUi()->displayByForecast($data->ccForecast);

});

new AdaptativeView('mapEmpty', function($data, FarmTemplate $t) {

	$t->title = s("Plan de {value}", $data->eFarm['name']);

	$t->nav = 'settings-production';

	$t->mainYear .= new \farm\FarmUi()->getSeasonsTabs($data->eFarm, fn($season) => \farm\FarmUi::urlSoil($data->eFarm, $season), $data->season);
	$t->mainTitle = '<h1>';
		$t->mainTitle .= s("Plan de la ferme");
	$t->mainTitle .= '</h1>';

	echo '<div class="util-block-help mb-2">';
		echo '<h4>'.s("Créez le plan de votre ferme pour démarrer la planification !").'</h4>';
		echo '<p>'.s("Pour travailler votre plan de culture et votre plan d'assolement avec {siteName}, il est préférable de créer le plan de votre ferme, c'est-à-dire paramétrer les emplacements de vos cultures sur trois échelles différentes :").'</p>';
		echo '<ul>';
			echo '<li>'.s("les parcelles, qui sont l'échelle la plus large").'</li>';
			echo '<li>'.s("les jardins, qui sont créés au sein des parcelles").'</li>';
			echo '<li>'.s("les planches, qui sont paramétrées dans les parcelles ou les jardins").'</li>';
		echo '</ul>';
		echo '<p>'.s("Si vous ne souhaitez pas faire le plan de votre ferme maintenant, vous pouvez toujours <link>ajouter une première série dans votre plan de culture</link> !", ['link' => '<a href="'.\farm\FarmUi::urlCultivationSeries($data->eFarm).'">']).'</p>';
	echo '</div>';

	echo '<h3>'.s("Ajouter une première parcelle").'</h3>';
	echo new \map\ZoneUi()->create($data->eFarm, new Collection())->body;

});

new AdaptativeView('cartography', function($data, FarmTemplate $t) {

	$t->nav = 'settings-production';

	$t->canonical = \farm\FarmUi::urlCultivationCartography($data->eFarm, $data->season);
	$t->title = s("Plan de {value}", $data->eFarm['name']);
	$t->nav = 'cultivation';
	$t->subNav = 'map';

	$h = '<div class="util-action">';
		$h .= '<h1>'.s("Plan de la ferme").'</h1>';
		if($data->eFarm->canManage()) {
			$h .=  '<div>';
				$h .=  '<a href="/map/zone:create?farm='.$data->eFarm['id'].'&season='.$data->season.'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouvelle parcelle").'</a>';
			$h .=  '</div>';
		}
	$h .= '</div>';

	$t->mainTitle = $h;
	$t->mainYear = new \farm\FarmUi()->getSeasonsTabs($data->eFarm, fn($season) => \farm\FarmUi::urlCultivationCartography($data->eFarm, $season), $data->season);;

	if(
		$data->cZone->count() === 1 and // Une seule parcelle
		$data->eZone['cPlot']->count() === 1 and // Uniquement le jardininféodé à la parcelle
		$data->eZone['cPlot']->first()['cBed']->count() === 1 // Uniquement la planche inféodé à la parcelle
	) {

		echo '<div class="util-block-help">';
			echo '<h4>'.s("Vous avez créé votre première parcelle !").'</h4>';
			echo '<p>'.s("Vous pouvez maintenant soit ajouter directement des planches permanentes à cette parcelle, soit subdiviser la parcelle en plusieurs jardins. Les serres sont également traitées le plus souvent comme un jardin.").'</p>';
			echo '<p>'.s("Libre à vous de choisir l'organisation spatiale qui vous convient le mieux !").'</p>';
		echo '</div>';

	}

	echo new \map\MapUi()->getFarm($data->eFarm, $data->season, $data->cZone, $data->eZone);

	echo '<div id="cartography-zone" data-season="'.$data->season.'" class="stick-xs"></div>';

});

new AdaptativeView('soil', function($data, FarmTemplate $t) {

	$t->canonical = \farm\FarmUi::urlCultivationSoil($data->eFarm, season: $data->season);
	$t->title = s("Assolement de {value}", $data->eFarm['name']);

	$t->nav = 'cultivation';
	$t->subNav = 'soil';
	$t->subNavTarget = $t->canonical;

	$view = $data->eFarm->getView('viewSoil');

	$uiFarm = new \farm\FarmUi();

	$t->mainYear = $uiFarm->getSeasonsTabs($data->eFarm, fn($season) => \farm\FarmUi::urlCultivationSoil($data->eFarm, season: $season), $data->season);
	$t->mainTitle = $uiFarm->getCultivationSoilTitle($data->eFarm, $data->season, $view, $data->cZone);

	if($data->cZone->notEmpty()) {

		switch($view) {

			case \farm\Farmer::PLAN :

				$form = new \util\FormUi();

				echo '<div id="zone-form-search" class="bed-write"></div>';

				echo $form->openAjax('/series/place:doUpdate', ['id' => 'place-update']);
					echo $form->hidden('cultivation');

					echo new \series\SeriesUi()->getSelector($data->ccCultivation);

					echo '<div class="zone-sticky-overlay-left"></div>';
					echo '<div class="zone-sticky-overlay-right"></div>';

					echo new \map\ZoneUi()->getPlan($data->eFarm, $data->cZone, $data->eZoneSelected, $data->season);

				echo $form->close();

				break;

			case \farm\Farmer::ROTATION :
				echo new \farm\FarmUi()->getRotationSearch($data->search, $data->selectedSeasons);
				echo new \map\ZoneUi()->getRotation($data->eFarm, $data->cZone, $data->season, $data->search);
				break;

		}

	} else {

		echo '<div class="util-block-help">';
			echo '<h4>'.s("Vous êtes sur votre futur plan d'assolement").'</h4>';
			echo '<p>'.s("Vous pourrez consulter votre plan d'assolement dès lors que vous aurez configuré les parcelles et les planches de culture de votre ferme et créé vos premières séries.").'</p>';
			echo '<a href="'.\farm\FarmUi::urlCultivationCartography($data->eFarm, $data->season).'" class="btn btn-secondary">'.s("Créer le plan de ma ferme").'</a>';
		echo '</div>';

	}


});

new AdaptativeView('sellingSales', function($data, FarmTemplate $t) {

	$t->title = s("Ventes de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingSales($data->eFarm);

	$t->nav = 'selling';
	$t->subNav = 'sale';
	$t->subNavTarget = $t->canonical;

	if(
		$data->nSale === 0 and
		$data->type === NULL and
		$data->search->empty()
	) {

		$t->mainTitle = '<h1>'.s("Ventes de la ferme").'</h1>';

		echo '<div class="util-block-help">';
			echo '<h4>'.s("Vous êtes sur la page pour gérer vos ventes").'</h4>';
			echo '<p>'.s("Avec {siteName}, vous allez gérer facilement et de façon fiable la commercialisation dans votre ferme :").'</p>';
			echo '<ul>';
				echo '<li>'.s("Référencez <link>votre gamme de produits</link>", ['link' => '<a href="'.\farm\FarmUi::urlSellingProducts($data->eFarm).'">']).'</li>';
				echo '<li>'.s("Créez les ventes de vos <link>clients particuliers et professionnels</link>", ['link' => '<a href="'.\farm\FarmUi::urlSellingCustomers($data->eFarm).'">']).'</li>';
				echo '<li>'.s("Éditez vos devis, bons de livraisons et factures au format PDF").'</li>';
				echo '<li>'.s("Ouvrez <link>des boutiques en ligne</link> avec en option le paiement par carte bancaire", ['link' => '<a href="'.\farm\FarmUi::urlShopList($data->eFarm).'">']).'</li>';
				echo '<li>'.s("Analysez vos ventes avec graphiques et statistiques").'</li>';
			echo '</ul>';
			echo '<p>'.s("Avant de créer votre première vente, regardez au préalable comment <customer>créer des clients</customer> et <items>référencer vos produits</items>. Une fois que c'est fait, c'est parti !", ['customer' => '<a href="'.\farm\FarmUi::urlSellingCustomers($data->eFarm).'">', 'items' => '<a href="'.\farm\FarmUi::urlSellingProducts($data->eFarm).'">']).'</p>';
			echo '<a href="/presentation/producteur" class="btn btn-secondary">'.s("En savoir plus").'</a>';
		echo '</div>';

		echo '<br/>';

		echo '<a href="/selling/sale:create?farm='.$data->eFarm['id'].'" class="btn btn-primary btn-lg">'.s("Ajouter une première vente").'</a>';

	} else {

		$t->mainTitle = new \farm\FarmUi()->getSellingSalesTitle($data->eFarm, $data->eFarm->getView('viewSellingSales'));

		echo new \selling\SaleUi()->getSearch($data->search, $data->cPaymentMethod);

		if($data->search->empty()) {
			echo new \selling\SaleUi()->getNextSales($data->eFarm, $data->type, $data->nextSales);
		}

		echo new \selling\SaleUi()->getList($data->eFarm, $data->cSale, $data->nSale, $data->search, hide: ['items'], page: $data->page, cPaymentMethod: $data->cPaymentMethod);

	}

});

new AdaptativeView('/ferme/{id}/clients', function($data, FarmTemplate $t) {

	$t->title = s("Clients de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingCustomers($data->eFarm);

	$t->nav = 'selling';
	$t->subNav = 'customer';
	$t->subNavTarget = $t->canonical;

	if(
		$data->cCustomer->empty() and
		$data->search->empty()
	) {

		$t->mainTitle = '<h1>'.s("Clients de la ferme").'</h1>';

		echo '<div class="util-block-help">';
			echo '<h4>'.s("Vous êtes sur la page pour gérer votre clientèle").'</h4>';
			echo '<p>'.s("Pour vendre, vous devez avoir des clients et c'est ici que ça se passe pour créer un premier client. Deux champs du formulaire sont obligatoires, le nom du client et la catégorie. Les clients professionnels sont facturés HT et les clients particuliers TTC si vous êtes assujetti à la TVA.").'</p>';
			echo '<p>'.s("Si vous souhaitez utiliser le logiciel de caisse, vous devez utiliser un point de vente pour les particuliers !").'</p>';
		echo '</div>';

		echo '<br/>';

		echo '<h3>'.s("Ajouter un premier client").'</h3>';

		$eCustomer = new \selling\Customer([
			'farm' => $data->eFarm,
			'user' => new \user\User(),
			'nGroup' => $data->cCustomerGroup->count()
		]);

		echo new \selling\CustomerUi()->create($eCustomer)->body;

	} else {
		
		$t->mainTitle = new \farm\FarmUi()->getSellingCustomersTitle($data->eFarm, \farm\Farmer::CUSTOMER, $data->nCustomer);

		echo new \selling\CustomerUi()->getSearch($data->eFarm, $data->search);
		echo new \selling\CustomerUi()->getList($data->eFarm, $data->cCustomer, $data->cCustomerGroup, $data->nCustomer, search: $data->search, page: $data->page);

	}


});

new AdaptativeView('/ferme/{id}/produits', function($data, FarmTemplate $t) {

	$t->title = s("Produits de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingProducts($data->eFarm);

	$t->nav = 'selling';
	$t->subNav = 'product';
	$t->subNavTarget = $t->canonical;

	if(
		array_sum($data->products) === 0 and
		$data->search->empty()
	) {

		$t->mainTitle = '<h1>'.s("Produits de la ferme").'</h1>';

		echo '<div class="util-block-help">';
			echo '<h4>'.s("Vous êtes sur la page pour gérer votre gamme de produits").'</h4>';
			echo '<p>'.s("Pour vendre, vous devez définir vos produits et c'est ici que ça se passe pour créer un premier produit. Pour commencer, sélectionnez le type de votre produit parmi les 6 disponibles !").'</p>';
		echo '</div>';

		echo '<br/>';

		echo '<h3>'.s("Ajouter un premier produit").'</h3>';

		echo new \selling\ProductUi()->create(new \selling\Product([
			'farm' => $data->eFarm,
			'profile' => NULL,
			'cCategory' => $data->cCategory,
			'cUnit' => $data->cUnit,
			'unit' => new \selling\Unit(),
			'private' => TRUE,
			'pro' => FALSE,
			'vat' => $data->eFarm->getSelling('defaultVat'),
		]))->body;

	} else {
		
		$t->mainTitle = new \farm\FarmUi()->getSellingProductsTitle($data->eFarm, \farm\Farmer::PRODUCT, $data->nProduct);

		echo new \selling\ProductUi()->getSearch($data->eFarm, $data->search);
		echo new \selling\ProductUi()->getList($data->eFarm, $data->cProduct, $data->products, $data->cCategory, $data->search);

	}


});

new AdaptativeView('/ferme/{id}/stocks', function($data, FarmTemplate $t) {

	$t->nav = 'selling';
	$t->subNav = 'stock';

	$t->title = s("Stocks de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingStock($data->eFarm);

	$h = '<div class="util-action">';
		$h .= '<h1>'.s("Stocks").'</h1>';
		$h .= '<div>';
			$h .= '<a data-dropdown="bottom-end" class="btn btn-primary dropdown-toggle">'.\Asset::icon('gear-fill').'</a>';
			$h .= '<div class="dropdown-list">';
				$h .= '<div class="dropdown-title">'.s("Stocks").'</div>';
				$h .= '<a href="/selling/stock:add?farm='.$data->eFarm['id'].'" class="dropdown-item">'.s("Activer le suivi du stock pour un produit").'</a>';
				if($data->eFarm['stockNotes'] === NULL) {
					$h .= '<a data-ajax="/selling/stock:doNoteStatus" post-id="'.$data->eFarm['id'].'" post-enable="1" class="dropdown-item">'.s("Ajouter des notes à cette page").'</a>';
				} else {
					$h .= '<a data-ajax="/selling/stock:doNoteStatus" post-id="'.$data->eFarm['id'].'" post-enable="0" class="dropdown-item">'.s("Désactiver les notes de stock").'</a>';
				}
			$h .= '</div>';
		$h .= '</div>';
	$h .= '</div>';
	
	$t->mainTitle = $h;

	if($data->cProduct->empty()) {

		echo '<div class="util-block-help">';
			echo '<p>'.s("Le suivi des stocks permet de connaître le stock de vos différents produits en collectant les informations liées aux entrées et sorties. Cette fonctionnalité est partiellement automatisée, elle est reliée à vos saisies de récoltes pour les entrées de stock, et à vos ventes pour les sorties de stocks !").'</p>';
			echo '<p>'.s("Cette page sera disponible lorsque vous aurez activé le suivi des stocks sur au moins un produit de votre gamme !").'</p>';
			echo '<a href="'.\farm\FarmUi::urlSellingProducts($data->eFarm).'" class="btn btn-secondary">'.s("Voir mes produits").'</a>';
		echo '</div>';

	} else {

		echo new \selling\StockUi()->getNotes($data->eFarm);
		echo new \selling\StockUi()->getList($data->cProduct, $data->cStockBookmark, $data->ccItemPast, $data->cItemFuture, $data->search);

	}


});

new AdaptativeView('/ferme/{id}/contacts', function($data, FarmTemplate $t) {

	$t->title = s("Contacts de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlCommunicationsMailing($data->eFarm);

	$t->nav = 'communications';
	$t->subNav = 'mailing';
	$t->subNavTarget = $t->canonical;

	$t->mainTitle = new \farm\FarmUi()->getMailingTitle($data->eFarm, $data->nContact, \farm\Farmer::CONTACT);

	echo new \mail\ContactUi()->getSearch($data->eFarm, $data->search);
	echo new \mail\ContactUi()->getList($data->eFarm, $data->cContact, $data->nContact, $data->page, $data->search);

});

new AdaptativeView('/ferme/{id}/campagnes', function($data, FarmTemplate $t) {

	$t->title = s("Campagnes de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlCommunicationsMailing($data->eFarm);

	$t->nav = 'communications';
	$t->subNav = 'mailing';
	$t->subNavTarget = $t->canonical;

	$t->mainTitle = new \farm\FarmUi()->getMailingTitle($data->eFarm, $data->nCampaign, \farm\Farmer::CAMPAIGN);

	echo new \mail\CampaignUi()->getList($data->eFarm, $data->cCampaign, $data->nCampaign, $data->page, $data->scheduledByWeek);

});

new AdaptativeView('/ferme/{id}/boutiques', function($data, FarmTemplate $t) {

	$t->nav = 'shop';
	$t->subNav = 'shop';

	$t->title = s("Boutiques de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlShopList($data->eFarm);

	$uiShopManage = new \shop\ShopManageUi();

	if(
		$data->ccShop['selling']->empty() and
		$data->ccShop['admin']->empty()
	) {

		$t->mainTitle = '<h1>'.s("Une boutique pour votre ferme").'</h1>';

		echo $uiShopManage->getIntroCreate($data->eFarm);

	} else {

		$h = '<div class="util-action">';
			$h .= '<h1>'.s("Boutiques en ligne").'</h1>';
			$h .= '<a href="/shop/:create?farm='.$data->eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouvelle boutique").'</a>';
		$h .= '</div>';

		$t->mainTitle = $h;

		if($data->ccShop['selling']->notEmpty()) {

			if($data->ccShop['admin']->notEmpty()) {
				echo '<h2>'.s("Producteur").'</h2>';
			}

			echo $uiShopManage->getList($data->eFarm, $data->ccShop['selling']);

		}

		if($data->ccShop['admin']->notEmpty()) {
			echo '<h2>'.s("Administrateur").'</h2>';
			echo $uiShopManage->getList($data->eFarm, $data->ccShop['admin']);
		}


	}

});

new AdaptativeView('/ferme/{id}/catalogues', function($data, FarmTemplate $t) {

	$t->nav = 'shop';
	$t->subNav = 'catalog';

	$t->title = s("Catalogues de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlShopCatalog($data->eFarm);

	if($data->cCatalog->empty()) {

		$t->mainTitle = '<h1>'.s("Catalogues de vente").'</h1>';

		echo '<div class="util-block-help">';
			echo '<h4>'.s("Vous êtes sur la page pour gérer vos catalogues de vente").'</h4>';
			echo '<p>'.s("Créez des catalogues de vente pour gérer facilement les disponibilités dans vos boutiques en ligne. Les catalogues sont particulièrement indiqués si vous voulez partager facilement la même gamme de produits entre plusieurs boutiques ou participer à des boutiques collectives.").'</p>';
		echo '</div>';

		echo '<br/>';

		echo '<h3>'.s("Créer un premier catalogue").'</h3>';

		echo '<div class="util-block-help">';
			echo s("Les catalogues ne sont pas indispensables au fonctionnement de vos boutiques en ligne, nous vous conseillons de les utiliser uniquement si vous êtes dans le cas mentionné ci-dessus et si vous êtes déjà bien à l'aise avec {siteName}.");
		echo '</div>';

		echo new \shop\CatalogUi()->create($data->eFarm)->body;

	} else {

		$h = '<div class="util-action">';
			$h .= '<h1>'.s("Catalogues de vente").'</h1>';
			$h .= '<div>';
				if($data->eFarm->canManage()) {
					$h .= '<a href="/shop/catalog:create?farm='.$data->eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Nouveau catalogue").'</span></a>';
				}
			$h .= '</div>';
		$h .= '</div>';

		$t->mainTitle = $h;

		echo new \shop\CatalogUi()->getList($data->eFarm, $data->cCatalog, $data->products, $data->eCatalogSelected);

	}


});

new AdaptativeView('/ferme/{id}/livraison', function($data, FarmTemplate $t) {

	$t->nav = 'shop';
	$t->subNav = 'point';

	$t->title = s("Modes de livraison de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlShopPoint($data->eFarm);

	$t->mainTitle = '<h1>'.s("Modes de livraison").'</h1>';
	echo new \shop\PointUi()->getList($data->eFarm, $data->ccPoint, $data->pointsUsed);

});

new AdaptativeView('/ferme/{id}/factures', function($data, FarmTemplate $t) {

	$t->nav = 'selling';
	$t->subNav = 'invoice';

	$t->title = s("Factures pour {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingSalesInvoice($data->eFarm);

	$h = '<div class="util-action">';
		$h .= '<h1>'.s("Factures").'</h1>';
		$h .= '<div>';
			if($data->hasInvoices) {
				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#sale-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
			}
			if($data->hasSales) {
				$h .= '<a class="btn btn-primary" data-dropdown="bottom-end">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Nouvelle facture").'</span></a> ';
				$h .= '<div class="dropdown-list">';
					$h .= '<div class="dropdown-title">'.s("Facturer les ventes").'</div> ';
					$h .= '<a href="/selling/invoice:create?farm='.$data->eFarm['id'].'" class="dropdown-item">'.s("D'un seul client").'</a> ';
					$h .= '<a href="/selling/invoice:createCollection?farm='.$data->eFarm['id'].'" class="dropdown-item">'.s("De plusieurs clients sur un mois donné").'</a> ';
				$h .= '</div>';
			}
		$h .= '</div>';
	$h .= '</div>';
	
	$t->mainTitle = $h;

	echo new \selling\InvoiceUi()->getSearch($data->search);

	echo '<div class="util-block-important">';
		echo '<h3>'.s("Vous vous questionnez sur la facturation électronique ?").'</h3>';
		echo '<p>';
			echo s("Ouvretaferme sera pleinement compatible avec la réforme");
			echo '  '.Asset::icon('arrow-right-circle-fill').'  ';
			echo '<a href="/facturation-electronique-les-mains-dans-les-poches" class="btn btn-transparent">'.s("En savoir plus").'</a>';
		echo '</p>';
	echo '</div>';

	if($data->transfer > 0) {

		echo '<div class="util-block-help">';
			echo '<p>'.s("Vous pouvez maintenant générer les factures des ventes à régler par virement bancaire au mois de {value}.", '<b>'.\util\DateUi::textual($data->transferMonth, \util\DateUi::MONTH_YEAR).'</b>').'</p>';
			echo '<a href="/selling/invoice:createCollection?farm='.$data->eFarm['id'].'&month='.$data->transferMonth.'&type='.\payment\MethodLib::TRANSFER.'" class="btn btn-secondary">'.s("Générer les factures").'</a>';
		echo '</div>';

	}

	if($data->hasInvoices === FALSE) {

		echo '<div class="util-block-help">';
			echo '<h4>'.s("Vous êtes sur la page qui permet de générer les factures de vos ventes").'</h4>';
			echo '<ul>';
				echo '<li>'.s("Éditez vos factures à partir de n'importe laquelle de vos ventes déjà livrée ou à partir de plusieurs ventes d'un même client").'</li>';
				echo '<li>'.s("Envoyez automatiquement les factures par e-mail à vos clients").'</li>';
			echo '</ul>';
			if($data->hasSales) {
				if($data->eFarm->isLegal()) {
					echo '<a href="/selling/invoice:create?farm='.$data->eFarm['id'].'" class="btn btn-secondary">'.s("Créer une première facture").'</a> ';
				} else {
					echo '<p>'.s("Il manque encore quelques informations sur votre ferme pour facturer vos premières ventes.").'</p>';
				}
			} else {
				echo '<p>'.s("Vous ne pouvez pas encore générer de facture car vous n'avez encore livré aucune vente !").'</p>';
				echo '<a href="'.\farm\FarmUi::urlSellingSalesAll($data->eFarm).'" class="btn btn-secondary">'.s("Retourner sur les ventes").'</a>';
			}
		echo '</div>';

		if($data->eFarm->isLegal() === FALSE) {
			echo '<h3>'.s("Informations requises pour facturer vos ventes").'</h3>';
			echo new \farm\FarmUi()->updateLegal($data->eFarm);
		}

	} else {

		echo new \selling\InvoiceUi()->getList($data->cInvoice, $data->nInvoice, page: $data->page);

	}

});

new AdaptativeView('/ferme/{id}/etiquettes', function($data, FarmTemplate $t) {

	$t->nav = 'selling';
	$t->subNav = 'sale';

	$t->title = s("Étiquettes de colisage pour {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingSalesLabel($data->eFarm);

	$t->mainTitle = new \farm\FarmUi()->getSellingSalesTitle($data->eFarm, \farm\Farmer::LABEL);

	echo new \selling\SaleUi()->getLabels($data->eFarm, $data->cSale);

});

new AdaptativeView('analyzeReport', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-commercialisation';
	$t->subNav = 'report';

	$t->title = s("Analyse des séries de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlAnalyzeReport($data->eFarm, $data->season);

	$t->mainYear = new \farm\FarmUi()->getSeasonsTabs($data->eFarm, fn($season) => \farm\FarmUi::urlAnalyzeReport($data->eFarm, season: $season), $data->season);;
	$t->mainTitle = new \farm\FarmUi()->getAnalyzeReportTitle($data->eFarm, $data->season);

	echo new \analyze\ReportUi()->getList($data->cReport, $data->search);


});

new AdaptativeView('analyzeWorkingTime', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-production';
	$t->subNav = 'working-time';

	$t->title = s("Analyse du planning de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlAnalyzeWorkingTime($data->eFarm, $data->year, $data->category);

	if($data->years === []) {

		$t->mainTitle = '<h1>'.s("Analyse du planning").'</h1>';
		echo '<div class="util-block-help">';
			echo '<h4>'.s("Vous n'avez pas encore renseigné de temps de travail").'</h4>';
			echo '<p>'.s("L'analyse du planning sera disponible lorsque votre ferme aura démarré son activité !").'</p>';
		echo '</div>';

	} else {

		$t->mainYear = new \farm\AnalyzeUi()->getYears($data->years, $data->year, $data->month, $data->week, fn($year) => \farm\FarmUi::urlAnalyzeWorkingTime($data->eFarm, $year, $data->category));
		$t->mainTitle = new \farm\FarmUi()->getAnalyzeWorkingTimeTitle($data->eFarm, $data->years, $data->year, $data->month, $data->week, $data->category);

		$uiAnalyze = new \series\AnalyzeUi();

		echo match($data->category) {
			\farm\Farmer::TIME => $uiAnalyze->getBestActions($data->eFarm, $data->year, $data->month, $data->week, $data->globalTime, $data->cTimesheetAction, $data->cccTimesheetActionMonthly, $data->cTimesheetCategory, $data->ccTimesheetCategoryMonthly, $data->cTimesheetPlant, $data->ccTimesheetPlantMonthly, $data->cTimesheetSeries, $data->ccTimesheetSeriesMonthly, $data->monthly),
			\farm\Farmer::TEAM => $uiAnalyze->getWorkingTime($data->eFarm, $data->year, $data->month, $data->week, $data->ccWorkingTimeMonthly, $data->workingTimeWeekly, $data->ccTimesheetAction, $data->ccTimesheetCategory),
			\farm\Farmer::PACE => $uiAnalyze->getPace($data->eFarm, $data->years, $data->year, $data->cAction, $data->ccPlant, $data->ccPlantCompare, $data->yearCompare),
			\farm\Farmer::PERIOD => $uiAnalyze->getPeriod($data->year, $data->cWorkingTimeMonth, $data->cWorkingTimeMonthBefore, $data->cWorkingTimeWeek, $data->cWorkingTimeWeekBefore),
		};

	}


});

new AdaptativeView('analyzeSelling', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-commercialisation';
	$t->subNav = 'sales';

	$t->title = s("Analyse des ventes de {value}", $data->eFarm['name']);

	$t->canonical = \farm\FarmUi::urlAnalyzeSelling($data->eFarm, $data->year, $data->category);

	$arguments = [];
	if($data->week) {
		$arguments[] = 'week='.$data->week;
	}
	if($data->month) {
		$arguments[] = 'month='.$data->month;
	}
	if(get_exists('shop')) {
		$arguments[] = 'shop='.$data->eShop['id'];
	}

	if($arguments) {
		$t->canonical .= '?'.implode('&', $arguments);
	}

	$t->js()->replaceHistory($t->canonical);

	if($data->years === []) {

		$t->mainTitle = '<h1>'.s("Analyse des ventes").'</h1>';
		echo '<div class="util-block-help">';
			echo '<h4>'.s("Vous n'avez pas encore de livré de vente").'</h4>';
			echo '<p>'.s("L'analyse des ventes sera disponible lorsque vous aurez livré votre première commande !").'</p>';
		echo '</div>';

	} else {

		$uiAnalyze = new \selling\AnalyzeUi();

		$t->mainYear = new \farm\AnalyzeUi()->getYears($data->years, $data->year, $data->month, $data->week, fn($year) => \farm\FarmUi::urlAnalyzeSelling($data->eFarm, $year, $data->category));
		$t->mainTitle = new \farm\FarmUi()->getAnalyzeSellingTitle($data->eFarm, $data->year, $data->week, $data->category);

		echo match($data->category) {
			\farm\Farmer::ITEM => $uiAnalyze->getTurnover($data->eFarm, $data->cSaleTurnover, $data->year, $data->month, $data->week),
			default => ''
		};

		echo '<br/>';

		echo match($data->category) {
			\farm\Farmer::ITEM => $uiAnalyze->getBestSeller($data->eFarm, $data->cItemProduct, $data->cItemProductMonthly, $data->cPlant, $data->cccItemPlantMonthly, $data->year, $data->cItemProductCompare, $data->cPlantCompare, $data->yearCompare, $data->salesByYear, $data->monthly, $data->month, $data->week, $data->search),
			\farm\Farmer::CUSTOMER => $uiAnalyze->getBestCustomers($data->ccItemCustomer, $data->ccItemCustomerMonthly, $data->year, $data->month, $data->week, $data->monthly, $data->search),
			\farm\Farmer::SHOP => $data->cShop->empty() ? $uiAnalyze->getEmptyShop() : $uiAnalyze->getShop($data->eFarm, $data->cShop, $data->eShop, $data->cSaleTurnover, $data->cItemProduct, $data->cItemProductMonthly, $data->cPlant, $data->cccItemPlantMonthly, $data->ccItemCustomer, $data->year, $data->monthly),
			\farm\Farmer::PERIOD => $uiAnalyze->getPeriod($data->year, $data->cItemMonth, $data->cItemMonthBefore, $data->cItemWeek, $data->cItemWeekBefore, $data->search),
		};

	}


});

new AdaptativeView('analyzeCultivation', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-production';
	$t->subNav = 'cultivation';

	$t->title = s("Analyse des cultures de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlAnalyzeCultivation($data->eFarm, $data->season, $data->category);

	$t->js()->replaceHistory($t->canonical);

	$actions = match($data->category) {
		\farm\Farmer::PLANT => '<a '.attr('onclick', 'Lime.Search.toggle("#analyze-plant-search")').' class="btn btn-primary">'.\Asset::icon('search').' '.s("Filtrer").'</a>',
		\farm\Farmer::FAMILY => '<a '.attr('onclick', 'Lime.Search.toggle("#analyze-family-search")').' class="btn btn-primary">'.\Asset::icon('search').' '.s("Filtrer").'</a>',
		\farm\Farmer::ROTATION => '<a href="/farm/farm:updateProduction?id='.$data->eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('gear-fill').' '.s("Configurer").'</a>',
		default => ''
	};

	$uiAnalyze = new \plant\AnalyzeUi();

	$t->mainYear = new \plant\AnalyzeUi()->getSeasons($data->eFarm, $data->seasons, $data->season, $data->category);
	$t->mainTitle = new \farm\FarmUi()->getAnalyzeCultivationTitle($data->eFarm, $data->seasons, $data->season, $data->category, $actions);

	echo match($data->category) {
		\farm\Farmer::AREA => $uiAnalyze->getArea($data->eFarm, $data->seasons, $data->season, $data->area),
		\farm\Farmer::PLANT => $uiAnalyze->getPlant($data->season, $data->ccCultivationPlant, $data->search),
		\farm\Farmer::FAMILY => $uiAnalyze->getFamily($data->eFarm, $data->season, $data->area, $data->ccCultivationFamily, $data->search),
		\farm\Farmer::ROTATION => $uiAnalyze->getRotation($data->eFarm, $data->selectedSeasons, $data->area, $data->cFamily, $data->cBed, $data->rotations),
	};


});

new AdaptativeView('/ferme/{id}/configuration/production', function($data, FarmTemplate $t) {

	$t->nav = 'settings-production';

	$t->title = s("Paramétrer la production pour {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSettingsProduction($data->eFarm);

	$t->mainTitle = new \farm\FarmUi()->getSettingsTitle($data->eFarm, s("Paramétrer la production"), 'production').'</h1>';
	$t->mainTitleClass = 'hide-lateral-down';

	echo new \farm\FarmUi()->getSettingsProduction($data->eFarm);

});

new AdaptativeView('/ferme/{id}/configuration/commercialisation', function($data, FarmTemplate $t) {

	$t->nav = 'settings-commercialisation';

	$t->title = s("Paramétrer la vente pour {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSettingsCommercialisation($data->eFarm);

	$t->mainTitle = new \farm\FarmUi()->getSettingsTitle($data->eFarm, s("Paramétrer la vente"), 'commercialisation').'</h1>';
	$t->mainTitleClass = 'hide-lateral-down';

	echo new \farm\FarmUi()->getSettingsCommercialisation($data->eFarm);

});
?>
