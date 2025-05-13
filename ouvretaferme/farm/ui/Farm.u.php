<?php
namespace farm;

class FarmUi {

	public function __construct() {
		\Asset::css('farm', 'farm.css');
		\Asset::js('farm', 'farm.js');
	}

	public static function link(Farm $eFarm, bool $newTab = FALSE): string {
		return '<a href="'.self::urlPlanningWeekly($eFarm).'" '.($newTab ? 'target="_blank"' : '').'>'.encode($eFarm['name']).'</a>';
	}

	public static function getQualities(bool $short = FALSE): array {
		return [
			Farm::ORGANIC => $short ? s("AB") : s("Agriculture biologique"),
			Farm::NATURE_PROGRES => $short ? s("N&P") : s("Nature & Progrès"),
			Farm::CONVERSION => $short ? s("En conversion") : s("En conversion vers l'agriculture biologique"),
		];
	}

	public static function getQualityLogo(string $quality, string $size): string {

		if($quality === Farm::CONVERSION) {
			return s("En conversion");
		} else {
			return '<div class="media-rectangle-view" style="'.\media\MediaUi::getSquareCss($size).'; background-image: url('.\Asset::getPath('farm', $quality.'.png', 'image').');"></div>';
		}

	}

	public static function websiteLink(Farm $eFarm, bool $newTab = TRUE): string {
		if($eFarm['url'] !== NULL) {
			return '<a href="'.encode($eFarm['url']).'" '.($newTab ? 'target="_blank"' : '').'>'.encode($eFarm['name']).'</a>';
		} else {
			return encode($eFarm['name']);
		}
	}

	public static function url(Farm $eFarm): string {
		return '/ferme/'.$eFarm['id'];
	}

	public static function urlPlanning(Farm $eFarm, ?string $view = NULL): string {

		$view ??= $eFarm->getView('viewPlanning');

		return match($view) {
			Farmer::DAILY => self::urlPlanningDaily($eFarm),
			Farmer::WEEKLY => self::urlPlanningWeekly($eFarm),
			Farmer::YEARLY => self::urlPlanningYear($eFarm)
		};

	}

	public static function urlPlanningBase(Farm $eFarm): string {
		return self::url($eFarm).'/planning';
	}

	public static function urlPlanningDaily(Farm $eFarm, ?string $week = NULL): string {

		$week ??= currentWeek();

		$url = self::urlPlanningBase($eFarm).'/'.\farm\Farmer::DAILY;

		if($week !== currentWeek()) {
			$url .= '/'.$week;
		}

		return $url;

	}

	public static function urlPlanningWeekly(Farm $eFarm, ?string $week = NULL): string {

		$week ??= currentWeek();

		$url = self::urlPlanningBase($eFarm).'/'.\farm\Farmer::WEEKLY;

		if($week !== currentWeek()) {
			$url .= '/'.$week;
		}

		return $url;

	}

	public static function urlPlanningAction(Farm $eFarm, string $week, Action $eAction): string {
		return self::url($eFarm).'/taches/'.$week.'/'.$eAction['id'];
	}

	public static function urlPlanningYear(Farm $eFarm, ?int $year = NULL, ?int $month = NULL): string {

		$year ??= (int)date('Y');
		$month ??= (int)date('n');

		$url = self::urlPlanningBase($eFarm).'/'.\farm\Farmer::YEARLY;

		if($month !== (int)date('n') or $year !== (int)date('Y')) {
			$url .= '/'.$year.'/'.$month;
		}

		return $url;

	}

	public static function urlAnalyzeWorkingTime(Farm $eFarm, ?int $year = NULL, ?string $category = NULL): string {
		return self::url($eFarm).'/analyses/planning'.($year ? '/'.$year : '').($category ? '/'.$category : '');
	}

	public static function urlCultivation(Farm $eFarm, ?string $view = NULL, ?string $subView = NULL, ?int $season = NULL): string {

		$view ??= $eFarm->getView('viewCultivation');

		return match($view) {
			Farmer::SERIES => self::urlCultivationSeries($eFarm, $subView, $season),
			Farmer::SOIL => self::urlCultivationSoil($eFarm, $subView, $season),
			Farmer::ROTATION => self::urlHistory($eFarm),
			Farmer::SEQUENCE => self::urlCultivationSequences($eFarm)
		};

	}

	public static function urlCultivationSeries(Farm $eFarm, ?string $view = NULL, ?int $season = NULL): string {

		$view ??= $eFarm->getView('viewSeries');

		return match($view) {
			Farmer::SEQUENCE => self::urlCultivationSequences($eFarm),
			default => self::url($eFarm).'/series'.($season ? '/'.$season : '').'?view='.$view
		};

	}

	public static function urlCultivationSoil(Farm $eFarm, ?string $view = NULL, ?int $season = NULL): string {

		$view ??= $eFarm->getView('viewSoil');

		return match($view) {
			Farmer::PLAN => self::urlSoil($eFarm, $season),
			Farmer::ROTATION => self::urlHistory($eFarm, $season),
		};

	}

	public static function urlCultivationSequences(Farm $eFarm): string {
		return self::url($eFarm).'/itineraires';
	}

	public static function urlCartography(Farm $eFarm, ?int $season = NULL): string {
		return self::url($eFarm).'/carte'.($season ? '/'.$season : '');
	}

	public static function urlSoil(Farm $eFarm, ?int $season = NULL): string {
		return self::url($eFarm).'/assolement'.($season ? '/'.$season : '');
	}

	public static function urlHistory(Farm $eFarm, ?int $season = NULL): string {
		return self::url($eFarm).'/rotation'.($season ? '/'.$season : '');
	}

	public static function urlSelling(Farm $eFarm, ?string $view = NULL): string {

		$view ??= $eFarm->getView('viewSelling');

		return match($view) {
			Farmer::SALE => self::urlSellingSales($eFarm),
			Farmer::PRODUCT => self::urlSellingProduct($eFarm),
			Farmer::STOCK => self::urlSellingStock($eFarm),
			Farmer::CUSTOMER => self::urlSellingCustomer($eFarm),
			Farmer::INVOICE => self::urlSellingInvoice($eFarm)
		};

	}

	public static function urlSellingCustomer(Farm $eFarm): string {
		return self::url($eFarm).'/clients';
	}

	public static function urlSellingProduct(Farm $eFarm): string {
		return self::url($eFarm).'/produits';
	}

	public static function urlSellingStock(Farm $eFarm): string {
		return self::url($eFarm).'/stocks';
	}

	public static function urlSellingInvoice(Farm $eFarm): string {
		return self::url($eFarm).'/factures';
	}

	public static function urlSellingSales(Farm $eFarm, ?string $view = NULL): string {

		$view ??= $eFarm->getView('viewSellingSales');

		return match($view) {
			Farmer::ALL => self::urlSellingSalesAll($eFarm),
			Farmer::PRIVATE => self::urlSellingSalesPrivate($eFarm),
			Farmer::PRO => self::urlSellingSalesPro($eFarm),
			Farmer::INVOICE => self::urlSellingSalesInvoice($eFarm),
			Farmer::LABEL => self::urlSellingSalesLabel($eFarm)
		};

	}

	public static function urlSellingSalesAll(Farm $eFarm): string {
		return self::url($eFarm).'/ventes';
	}

	public static function urlSellingSalesPrivate(Farm $eFarm): string {
		return self::url($eFarm).'/ventes/particuliers';
	}

	public static function urlSellingSalesPro(Farm $eFarm): string {
		return self::url($eFarm).'/ventes/professionnels';
	}

	public static function urlSellingSalesInvoice(Farm $eFarm): string {
		return self::url($eFarm).'/factures';
	}

	public static function urlSellingSalesLabel(Farm $eFarm): string {
		return self::url($eFarm).'/etiquettes';
	}

	public static function urlShop(Farm $eFarm, ?string $view = NULL): string {

		$view ??= $eFarm->getView('viewShop');

		return match($view) {
			Farmer::SHOP => self::urlShopList($eFarm),
			Farmer::CATALOG => self::urlShopCatalog($eFarm),
			Farmer::POINT => self::urlShopPoint($eFarm)
		};

	}

	public static function urlShopList(Farm $eFarm): string {
		return self::url($eFarm).'/boutiques';
	}

	public static function urlShopCatalog(Farm $eFarm): string {
		return self::url($eFarm).'/catalogues';
	}

	public static function urlShopPoint(Farm $eFarm): string {
		return self::url($eFarm).'/livraison';
	}

	public static function urlAnalyzeReport(Farm $eFarm, ?int $season = NULL): string {
		return self::url($eFarm).'/analyses/rapports'.($season ? '/'.$season : '');
	}

	public static function urlAnalyze(Farm $eFarm, ?string $view = NULL): string {

		$view ??= $eFarm->getView('viewAnalyze');

		$categories = self::getAnalyzeCategories($eFarm);

		if(array_key_exists($view, $categories) === FALSE) {
			$view = array_key_first($categories);
		}

		return match($view) {
			Farmer::WORKING_TIME => self::urlAnalyzeWorkingTime($eFarm),
			Farmer::REPORT => self::urlAnalyzeReport($eFarm),
			Farmer::SALES => self::urlAnalyzeSelling($eFarm),
			Farmer::CULTIVATION => self::urlAnalyzeCultivation($eFarm)
		};

	}

	public static function urlAnalyzeSelling(Farm $eFarm, ?int $year = NULL, ?string $category = NULL): string {
		return self::url($eFarm).'/analyses/ventes'.($year ? '/'.$year : '').($category ? '/'.$category : '');
	}

	public static function urlAnalyzeCultivation(Farm $eFarm, ?int $season = NULL, ?string $category = NULL): string {
		return self::url($eFarm).'/analyses/cultures'.($season ? '/'.$season : '').($category ? '/'.$category : '');
	}

	public static function urlSettings(Farm $eFarm): string {
		return self::url($eFarm).'/configuration';
	}

	/**
	 * Display a field to search farms
	 *
	 *
	 */
	public function query(\PropertyDescriber $d, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('house-door-fill');
		$d->field = 'autocomplete';

		$d->placeholder = s("Tapez un nom de ferme...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = '/farm/search:query';
		$d->autocompleteResults = function(Farm $e) {
			return self::getAutocomplete($e);
		};

	}

	public static function getAutocomplete(Farm $eFarm): array {

		$item = self::getVignette($eFarm, '2.5rem');
		$item .= '<div>';
		$item .= encode($eFarm['name']).'<br/>';
			if($eFarm['place'] !== NULL) {
				$item .= '<small>'.encode($eFarm['place']).'</small>';
			}
		$item .= '</div>';

		return [
			'value' => $eFarm['id'],
			'itemHtml' => $item,
			'itemText' => $eFarm['name']
		];

	}

	public function create(): \Panel {

		$eFarm = new Farm();

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/farm/farm:doCreate', ['id' => 'farm-create', 'autocomplete' => 'off']);

			$h .= $form->asteriskInfo();

			$h .= $form->dynamicGroups($eFarm, ['name*', 'startedAt*', 'place', 'placeLngLat', 'defaultBedLength', 'defaultBedWidth', 'defaultAlleyWidth', 'quality']);

			$h .= $form->group(
				content: $form->submit(s("Créer ma ferme"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-farm-create',
			title: s("Créer ma ferme"),
			body: $h
		);

	}

	public function update(Farm $eFarm): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/farm:doUpdate', ['id' => 'farm-update', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eFarm['id']);

			$h .= $form->group(
				self::p('vignette')->label,
				new \media\FarmVignetteUi()->getCamera($eFarm, size: '10rem')
			);
			$h .= $form->dynamicGroups($eFarm, ['name', 'description', 'startedAt', 'place', 'placeLngLat', 'url', 'defaultBedLength', 'defaultBedWidth', 'defaultAlleyWidth', 'quality']);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return $h;

	}

	public function updateStockNotes(Farm $eFarm): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/selling/stock:doUpdateNote', ['autocomplete' => 'off']);

			$h .= $form->hidden('id', $eFarm['id']);

			$h .= $form->dynamicField($eFarm, 'stockNotes', function($d) {
				$d->attributes['style'] = 'height: 20rem';
			});
			$h .= '<br/>';
			$h .= $form->submit(s("Modifier"));

		$h .= $form->close();

		return new \Panel(
			id: 'panel-farm-update-stock-notes',
			title: s("Modifier les notes de stock"),
			body: $h
		);

	}

	public function updateSeries(Farm $eFarm): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/farm:doUpdateSeries');

			$h .= $form->hidden('id', $eFarm);

			$input = '<div class="input-group mb-1">';
				$input .= $form->addon(s("Début en année n - 1 :"));
				$input .= $form->select('calendarMonthStart', array_slice(\util\DateUi::months(), 6, preserve_keys: TRUE), $eFarm['calendarMonthStart'], ['placeholder' => s("année non affichée"), 'onchange' => 'Farm.changeCalendarMonth('.$eFarm['id'].', this)']);
			$input .= '</div>';
			$input .= '<div class="input-group mb-1">';
				$input .= $form->addon(s("Fin en année n + 1 :"));
				$input .= $form->select('calendarMonthStop', array_slice(\util\DateUi::months(), 0, -6, preserve_keys: TRUE), $eFarm['calendarMonthStop'], ['placeholder' => s("année non affichée"), 'onchange' => 'Farm.changeCalendarMonth('.$eFarm['id'].', this)']);
			$input .= '</div>';

			$input .= '<div id="farm-update-calendar-month">';
				$input .= new \series\CultivationUi()->getListSeason($eFarm, date('Y'));
			$input .= '</div>';

			$h .= $form->group(s("Période affichée sur les diagrammes"), $input, ['wrapper' => 'calendarMonthStart calendarMonthStop']);

			$h .= $form->group(content: '<h3>'.s("Rotations").'</h3>');
			$h .= $form->dynamicGroups($eFarm, ['rotationYears', 'rotationExclude']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return $h;

	}

	public function updateFeature(Farm $eFarm): string {

		$form = new \util\FormUi();

		$h = '<div class="util-block-help">';
			$h .= s("Pour que {siteName} corresponde le plus fidèlement possible aux besoins de votre ferme, vous pouvez choisir d'activer ou désactiver certaines fonctionnalités. Désactiver des fonctionnalités simplifie l'interface du site, tandis qu'en activer vous offre plus de possibilités.");
		$h .= '</div>';

		$h .= $form->openAjax('/farm/farm:doUpdateFeature');

			$h .= $form->hidden('id', $eFarm);

			$h .= $form->dynamicGroups($eFarm, ['featureTime', 'featureDocument']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return $h;

	}

	public function export(Farm $eFarm, int $year): string {

		$form = new \util\FormUi();

		$h = '<h2>'.s("Les données annuelles").'</h2>';

		$h .= $form->open(attributes: ['action' => '/farm/farm:export', 'method' => 'get']);
			$h .= '<div class="util-block-search stick-xs" style="display: flex; column-gap: 1rem">';
				$h .= $form->hidden('id', $eFarm['id']);
				$h .= $form->inputGroup(
					$form->addon(s("Année")).
					$form->rangeSelect('year', $eFarm['seasonLast'], $eFarm['seasonFirst'], -1, $year, ['mandatory' => TRUE])
				);
				$h .= $form->submit(s("Valider"));
			$h .= '</div>';
		$h .= $form->close();

		$h .= '<div class="util-buttons">';

			$h .= '<a href="/series/csv:exportCultivations?id='.$eFarm['id'].'&year='.$year.'" class="bg-secondary util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter le plan de culture").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('journals');
			$h .= '</a>';

			$h .= '<a href="/series/csv:exportSoil?id='.$eFarm['id'].'&year='.$year.'" class="bg-secondary util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter le plan d'assolement").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('map');
			$h .= '</a>';

			$h .= '<a href="/series/csv:exportTasks?id='.$eFarm['id'].'&year='.$year.'" class="bg-secondary util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter les interventions").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('calendar3');
			$h .= '</a>';

			$h .= '<a href="/series/csv:exportHarvests?id='.$eFarm['id'].'&year='.$year.'" class="bg-secondary util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter les récoltes").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('basket2-fill');
			$h .= '</a>';

			$h .= '<a href="/selling/csv:exportSales?id='.$eFarm['id'].'&year='.$year.'" class="bg-secondary util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter les ventes").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('piggy-bank');
			$h .= '</a>';

			$h .= '<a href="/selling/csv:exportItems?id='.$eFarm['id'].'&year='.$year.'" class="bg-secondary util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter les articles vendus").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('boxes');
			$h .= '</a>';

		$h .= '</div>';

		$h .= '<h2>'.s("Les données intégrales").'</h2>';

		$h .= '<div class="util-buttons">';

			$h .= '<a href="/selling/csv:exportProducts?id='.$eFarm['id'].'" class="bg-secondary util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter les produits").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('box');
			$h .= '</a>';

			$h .= '<a href="/selling/csv:exportCustomers?id='.$eFarm['id'].'" class="bg-secondary util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter les clients").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('people-fill');
			$h .= '</a>';

		$h .= '</div>';

		return $h;

	}

	public function getMainTabs(Farm $eFarm, string $tab): string {

		$prefix = '<span class="farm-subnav-prefix">'.\Asset::icon('chevron-right').' </span>';

		$h = '<nav id="farm-nav">';

			$h .= '<div class="farm-tabs">';

				if($eFarm->canPlanning()) {

					$categories = $this->getPlanningCategories($eFarm);
					$selectedPeriod = $eFarm->getView('viewPlanning');

					$h .= '<a href="'.FarmUi::urlPlanning($eFarm).'" data-tab="home" class="farm-tab '.($tab === 'home' ? 'selected' : '').'">';
						$h .= '<span class="farm-tab-icon">'.\Asset::icon('calendar3').'</span>';
						$h .= '<span class="farm-tab-label">'.s("Planning").'</span>';
						$h .= '<div class="farm-tab-complement" data-dropdown="bottom-left" data-dropdown-id="farm-tab-planning" data-dropdown-hover="true">';
							$h .= '<span id="farm-tab-planning-period">'.$categories[$selectedPeriod]['label'].'</span> '.\Asset::icon('chevron-down');
						$h .= '</div>';
					$h .= '</a>';
					$h .= '<div data-dropdown-id="farm-tab-planning-list" class="dropdown-list bg-secondary">';
						foreach([Farmer::DAILY, Farmer::WEEKLY, Farmer::YEARLY] as $period) {
							$h .= '<a href="'.$categories[$period]['url'].'" id="farm-tab-planning-'.$period.'" class="dropdown-item '.($period === $selectedPeriod ? 'selected' : '').'">'.$categories[$period]['label'].'</a>';
						}
					$h .= '</div>';

				}

				$h .= '<a href="'.FarmUi::urlCultivation($eFarm).'" data-tab="cultivation" class="farm-tab farm-tab-subnav '.($tab === 'cultivation' ? 'selected' : '').'">';
					$h .= '<span class="farm-tab-icon">'.\Asset::icon('journals').'</span>';
					$h .= '<span class="farm-tab-label">'.s("Production").'</span>';
				$h .= '</a>';

				$h .= $this->getCultivationMenu($eFarm, prefix: $prefix, tab: $tab);

				if($eFarm->canSelling()) {

					$h .= '<a href="'.FarmUi::urlSelling($eFarm).'" data-tab="selling" class="farm-tab farm-tab-subnav '.($tab === 'selling' ? 'selected' : '').'">';
						$h .= '<span class="farm-tab-icon">'.\Asset::icon('piggy-bank').'</span>';
						$h .= '<span class="farm-tab-label">';
							$h .= '<span class="hide-sm-up">'.s("Commerce").'</span>';
							$h .= '<span class="hide-xs-down">'.s("Commercialisation").'</span>';
						$h .= '</span>';
					$h .= '</a>';

					$h .= $this->getSellingMenu($eFarm, prefix: $prefix, tab: $tab);

					$h .= '<a href="'.FarmUi::urlShop($eFarm, $eFarm['hasShops'] ? NULL : Farmer::SHOP).'" data-tab="shop" class="farm-tab '.($eFarm['hasShops'] ? 'farm-tab-subnav' : '').' '.($tab === 'shop' ? 'selected' : '').'">';
						$h .= '<span class="farm-tab-icon">'.\Asset::icon('cart').'</span>';
						$h .= '<span class="farm-tab-label">'.s("Vente en ligne").'</span>';
					$h .= '</a>';

					if($eFarm['hasShops']) {
						$h .= $this->getShopMenu($eFarm, prefix: $prefix, tab: $tab);
					}

				}

				$categories = $this->getAnalyzeCategories($eFarm);

				if($eFarm->canAnalyze() and $categories) {

					$selectedCategory = $eFarm->getView('viewAnalyze');

					if(array_key_exists($selectedCategory, $categories) === FALSE) {
						$selectedCategory = array_key_first($categories);
					}

					$h .= '<a href="'.FarmUi::urlAnalyze($eFarm).'" data-tab="analyze" class="farm-tab '.($tab === 'analyze' ? 'selected' : '').'">';
						$h .= '<span class="farm-tab-icon">'.\Asset::icon('bar-chart').'</span>';
						$h .= '<span class="farm-tab-label">'.s("Analyse").'</span>';
						if(count($categories) > 1) {
							$h .= '<div class="farm-tab-complement" data-dropdown="auto-left" data-dropdown-id="farm-tab-analyze" data-dropdown-hover="true">';
								$h .= '<span id="farm-tab-analyze-category">'.$categories[$selectedCategory].'</span>';
								$h .= ' '.\Asset::icon('chevron-down');
							$h .= '</div>';
						}
					$h .= '</a>';
					if(count($categories) > 1) {
						$h .= '<div data-dropdown-id="farm-tab-analyze-list" class="dropdown-list bg-secondary">';
							foreach($categories as $category => $label) {
								$h .= '<a href="'.FarmUi::urlAnalyze($eFarm, $category).'" id="farm-tab-analyze-'.$category.'" class="dropdown-item '.($category === $selectedCategory ? 'selected' : '').'">'.$label.'</a>';
							}
						$h .= '</div>';
					}

				}

				if($eFarm->canManage()) {
					$h .= '<a href="'.FarmUi::urlSettings($eFarm).'" class="farm-tab '.($tab === 'settings' ? 'selected' : '').'" data-tab="settings">';
						$h .= '<span class="hide-lateral-down farm-tab-icon">'.\Asset::icon('gear').'</span>';
						$h .= '<span class="hide-lateral-up farm-tab-icon">'.\Asset::icon('gear-fill').'</span>';
						$h .= '<span class="farm-tab-label hide-xs-down">';
							$h .= s("Paramétrage");
						$h .= '</span>';
					$h .= '</a>';
				}

				if(currentDate() <= \Setting::get('main\limitTraining')) {
					$h .= '<a href="/presentation/formations" class="farm-tab hide-lateral-down">';
						$h .= '<span class="farm-tab-icon">'.\Asset::icon('book').'</span>';
						$h .= '<span class="farm-tab-label">';
							$h .= s("Formation");
						$h .= '</span>';
					$h .= '</a>';
				}

			$h .= '</div>';

		$h .= '</nav>';

		return $h;

	}

	public function getPlanningSubNav(Farm $eFarm, ?string $week = NULL): string {

		$selectedView = $eFarm->getView('viewPlanning');

		$h = '<nav id="farm-subnav">';
			$h .= '<div class="farm-subnav-wrapper">';

				foreach($this->getPlanningCategories($eFarm, $week) as $key => ['url' => $url, 'label' => $label]) {
					$h .= '<a href="'.$url.'" id="farm-subnav-planning-'.$key.'" class="farm-subnav-item '.($key === $selectedView ? 'selected' : '').'">'.$label.'</a> ';
				}

			$h .= '</div>';
		$h .= '</nav>';

		return $h;
	}

	protected static function getPlanningCategories(Farm $eFarm, ?string $week = NULL): array {

		$categories = [
			Farmer::DAILY => [
				'url' => FarmUi::urlPlanningDaily($eFarm, $week),
				'label' => s("Quotidien")
			],
			Farmer::WEEKLY => [
				'url' => FarmUi::urlPlanningWeekly($eFarm, $week),
				'label' => s("Hebdomadaire")
			],
			Farmer::YEARLY => [
				'url' => FarmUi::urlPlanningYear($eFarm),
				'label' => s("Annuel")
			]
		];

		return $categories;

	}

	public function getCultivationSeriesTitle(\farm\Farm $eFarm, ?int $selectedSeason, string $selectedView, ?int $nSeries = NULL, ?bool $firstSeries = NULL): string {

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">';
					$h .= $this->getSeriesCategories($eFarm)[$selectedView].' '.self::getNavigation();
				$h .= '</a>';
				$h .= '<div class="dropdown-list bg-primary">';
					foreach($this->getSeriesCategories($eFarm) as $key => $value) {
						$h .= '<a href="'.FarmUi::urlCultivationSeries($eFarm, $key).'" class="dropdown-item '.($key === $selectedView ? 'selected' : '').'">'.$value.'</a> ';
					}
				$h .= '</div>';
			$h .= '</h1>';

			switch($selectedView) {

				case \farm\Farmer::AREA :
					$h .=  '<div>';
						if(
							$eFarm->canManage() and
							$firstSeries === FALSE
						) {
							$h .= '<a href="/series/series:createFrom?farm='.$eFarm['id'].'&season='.$selectedSeason.'" class="btn btn-primary" data-ajax-class="Ajax.Query">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Nouvelle série").'</span></a>';
						}
						if($nSeries >= 2) {
							$h .= ' <a class="btn btn-primary" '.attr('onclick', 'Lime.Search.toggle("#series-search")').'>';
								$h .= \Asset::icon('search');
							$h .= '</a>';
						}
					$h .=  '</div>';
					break;

				case \farm\Farmer::FORECAST:
					$h .=  '<div>';
						$h .= '<a href="/plant/forecast:create?farm='.$eFarm['id'].'&season='.$selectedSeason.'" class="btn btn-primary">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Ajouter une espèce").'</span></a>';
					$h .=  '</div>';
					break;

				case \farm\Farmer::SEEDLING :
				case \farm\Farmer::WORKING_TIME :
					$h .=  '<div>';
						if($nSeries >= 2) {
							$h .= '<a class="btn btn-primary" '.attr('onclick', 'Lime.Search.toggle("#series-search")').'>';
								$h .= \Asset::icon('search');
							$h .= '</a>';
						}
					$h .=  '</div>';
					break;

			}

		$h .=  '</div>';

		return $h;

	}

	public function getCultivationSeriesSearch(string $view, \farm\Farm $eFarm, int $season, \Search $search, \Collection $cSupplier, \Collection $cAction): string {

		$h = '<div id="series-search" class="util-block-search stick-xs '.($search->empty(['cAction']) ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = \farm\FarmUi::urlCultivationSeries($eFarm, season: $season);

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';

					if($view === Farmer::SEEDLING) {

						$h .= $form->select('seedling', \series\CultivationUi::p('seedling')->values, $search->get('seedling'), ['placeholder' => s("Implantation")]);

						if($cSupplier->notEmpty()) {
							$h .= $form->select('supplier', $cSupplier, $search->get('supplier'), ['placeholder' => s("Fournisseur")]);
						}

					}

					if($view === Farmer::WORKING_TIME) {
						$h .= $form->select('action', $cAction, $search->get('action'), ['placeholder' => s("Intervention")]);
					}

					if($view === Farmer::AREA) {

						$h .= $form->inputGroup($form->addon(s('Largeur travaillée de planche')).$form->number('bedWidth', $search->get('bedWidth')).$form->addon(s('cm')));

						$h .= $form->dynamicField(new Tool(['farm' => $eFarm]), 'id', function($d) use($search) {

							$d->name = 'tool';

							if($search->get('tool')->notEmpty()) {
								$d->autocompleteDefault = $search->get('tool');
							}

							$d->attributes = [
								'data-autocomplete-select' => 'submit',
								'style' => 'width: 20rem'
							];
						});

					}

					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getCultivationSoilTitle(\farm\Farm $eFarm, int $selectedSeason, string $selectedView, \Collection $cZone): string {

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">';
					$h .= $this->getSoilCategories($eFarm)[$selectedView].' '.self::getNavigation();
				$h .= '</a>';
				$h .= '<div class="dropdown-list bg-primary">';
					foreach($this->getSoilCategories($eFarm) as $key => $value) {
						$h .= '<a href="'.FarmUi::urlCultivationSoil($eFarm, $key).'" class="dropdown-item '.($key === $selectedView ? 'selected' : '').'">'.$value.'</a> ';
					}
				$h .= '</div>';
			$h .= '</h1>';
			$h .= '<div>';

			switch($selectedView) {

				case \farm\Farmer::PLAN :
					if($cZone->notEmpty()) {
						$h .= '<a href="'.\farm\FarmUi::urlCartography($eFarm, $selectedSeason).'" class="btn btn-primary">';
							$h .= \Asset::icon('geo-alt-fill').' ';
							if($eFarm->canManage()) {
								$h .= s("Modifier le plan de la ferme");
							} else {
								$h .= s("Plan de la ferme");
							}
						$h .= '</a>';
					}
					break;

				case \farm\Farmer::ROTATION:
					if($cZone->notEmpty()) {
						$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#bed-rotation-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
						if($eFarm->canManage()) {
							$h .= '<a href="/farm/farm:updateSeries?id='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('gear-fill').' '.s("Configurer").'</a>';
						}
					}
					break;

			}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getCultivationSubNav(Farm $eFarm, ?int $season = NULL): string {

		$h = '<nav id="farm-subnav">';
			$h .= $this->getCultivationMenu($eFarm, $season, tab: 'cultivation');
		$h .= '</nav>';

		return $h;

	}

	public function getCultivationMenu(Farm $eFarm, ?int $season = NULL, string $prefix = '', ?string $tab = NULL): string {

		$selectedView = ($tab === 'cultivation') ? $eFarm->getView('viewCultivation') : NULL;

		$h = '<div class="farm-subnav-wrapper">';

			foreach($this->getCultivationCategories() as $key => $value) {

				$h .= '<a href="'.FarmUi::urlCultivation($eFarm, $key, season: $season).'" class="farm-subnav-item '.($key === $selectedView ? 'selected' : '').'" data-sub-tab="'.$key.'">';
					$h .= $prefix.'<span>'.$value.'</span>';
				$h .= '</a>';

			}

		$h .= '</div>';

		return $h;

	}

	public static function getCultivationCategories(): array {
		return [
			Farmer::SERIES => s("Cultures"),
			Farmer::SOIL => s("Assolement"),
			Farmer::SEQUENCE => s("Itinéraires<hide> techniques</hide>", ['hide' => '<span class="farm-subnav-sequence hide-xs-down">']),
		];
	}

	public static function getSeriesCategories(Farm $eFarm): array {

		$categories = [
			Farmer::AREA => s("Plan de culture"),
			Farmer::SEEDLING => s("Semences et plants"),
			Farmer::FORECAST => s("Prévisionnel financier"),
			Farmer::HARVESTING => s("Récoltes"),
			Farmer::WORKING_TIME => s("Temps de travail"),
		];

		if($eFarm->hasFeatureTime() === FALSE) {
			unset($categories[Farmer::WORKING_TIME]);
		}

		if($eFarm->canAnalyze() === FALSE) {
			unset($categories[Farmer::FORECAST]);
		}

		return $categories;

	}

	public static function getSoilCategories(Farm $eFarm): array {

		return [
			Farmer::PLAN => s("Plan d'assolement"),
			Farmer::ROTATION => s("Rotations"),
		];

	}

	public function getRotationSearch(\Search $search, array $seasons): string {

		$seen = [];
		$firstSeason = first($seasons);
		$lastSeason = last($seasons);

		foreach($seasons as $season) {

			if($season !== $firstSeason) {
				$seen[$season] = s("jamais entre {start} et {stop}", ['start' => $season, 'stop' => $firstSeason]);
			} else {
				$seen[$season] = s("jamais en {value}", $season);
			}

		}

		$seen[1] = s("au moins 1 fois entre {start} et {stop}", ['start' => last($seasons), 'stop' => first($seasons)]);

		for($i = 1; $i <= count($seasons); $i++) {
			$seen[$i] = p("exactement {value} fois entre {start} et {stop}", "exactement {value} fois entre {start} et {stop}", $i, ['start' => last($seasons), 'stop' => first($seasons)]);
		}

		$h = '<div id="bed-rotation-search" class="util-block-search stick-xs '.($search->empty(['cFamily']) ? 'hide' : '').' mt-1">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';
					$h .= $form->select('family', $search->get('cFamily'), $search->get('family'), ['placeholder' => s("Famille..."), 'onchange' => 'Farm.changeSearchFamily(this)']);
					$h .= $form->inputGroup(
						$form->addon(s("Cultivée")).
						$form->select('seen', $seen, $search->get('seen', $lastSeason), ['mandatory' => TRUE]),
						['class' => 'bed-rotation-search-seen '.($search->get('family')->notEmpty() ? NULL : 'hide')]
					);
					$h .= '<label><input type="checkbox" name="bed" value="1" '.($search->get('bed') ? 'checked="checked"' : '').'/> '.s("Uniquement les planches permanentes").'</label>';
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getSellingSubNav(Farm $eFarm): string {

		$h = '<nav id="farm-subnav">';
			$h .= $this->getSellingMenu($eFarm, tab: 'selling');
		$h .= '</nav>';

		return $h;

	}

	public function getSellingMenu(Farm $eFarm, ?int $season = NULL, string $prefix = '', ?string $tab = NULL): string {

		$selectedView = ($tab === 'selling') ? $eFarm->getView('viewSelling') : NULL;

		$h = '<div class="farm-subnav-wrapper">';

			foreach($this->getSellingCategories($eFarm) as $key => $value) {

				$h .= '<a href="'.FarmUi::urlSelling($eFarm, $key).'" class="farm-subnav-item '.($key === $selectedView ? 'selected' : '').'" data-sub-tab="'.$key.'">';
					$h .= $prefix.'<span>'.$value.'</span>';
				$h .= '</a>';

			}

		$h .= '</div>';

		return $h;

	}

	protected static function getSellingCategories(Farm $eFarm): array {

		$categories = [
			Farmer::SALE => s("Ventes"),
			Farmer::CUSTOMER => s("Clients"),
			Farmer::PRODUCT => s("Produits"),
		];

		if($eFarm['hasSales']) {
			$categories += [
				Farmer::INVOICE => s("Factures")
			];
		}

		if($eFarm['featureStock']) {
			$categories += [
				Farmer::STOCK => s("Stocks")
			];
		}

		return $categories;

	}

	public function getSellingSalesTitle(Farm $eFarm, string $selectedView): string {

		$categories = $this->getSellingSalesCategories();

		$title = $categories[$selectedView];

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">';
					$h .= $title.' '.self::getNavigation();
				$h .= '</a>';
				$h .= '<div class="dropdown-list bg-primary">';
					foreach($categories as $key => $value) {
						if($value === NULL) {
							$h .= '<div class="dropdown-divider"></div>';
						} else {
							$h .= '<a href="'.self::urlSellingSales($eFarm, $key).'" class="dropdown-item '.($key === $selectedView ? 'selected' : '').'">'.$value.'</a>';
						}
					}
				$h .= '</div>';
			$h .= '</h1>';

			switch($selectedView) {

				case Farmer::ALL :
				case Farmer::PRIVATE :
				case Farmer::PRO :
					$h .= '<div>';
						$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#sale-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
						$h .= '<a href="/selling/sale:create?farm='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Nouvelle vente").'</span></a> ';
					$h .= '</div>';
					break;

			}

		$h .= '</div>';

		return $h;

	}

	protected static function getSellingSalesCategories(): array {
		return [
			Farmer::ALL => s("Toutes les ventes"),
			Farmer::PRO => s("Ventes aux professionnels"),
			Farmer::PRIVATE => s("Ventes aux particuliers"),
			NULL,
			Farmer::LABEL => s("Étiquettes de colisage"),
		];
	}

	public function getShopSubNav(Farm $eFarm): string {

		$h = '<nav id="farm-subnav">';
			$h .= $this->getShopMenu($eFarm, tab: 'shop');
		$h .= '</nav>';

		return $h;

	}

	public function getShopMenu(Farm $eFarm, string $prefix = '', ?string $tab = NULL): string {

		$selectedView = ($tab === 'shop') ? $eFarm->getView('viewShop') : NULL;

		$h = '<div class="farm-subnav-wrapper">';

			foreach($this->getShopCategories($eFarm) as $key => $value) {

				$h .= '<a href="'.FarmUi::urlShop($eFarm, $key).'" class="farm-subnav-item '.($key === $selectedView ? 'selected' : '').'" data-sub-tab="'.$key.'">';
					$h .= $prefix.'<span>'.$value.'</span>';
				$h .= '</a>';

			}

		$h .= '</div>';

		return $h;

	}

	public static function getShopCategories(Farm $eFarm): array {

		$categories = [
			Farmer::SHOP => s("Boutiques")
		];

		if($eFarm['hasShops']) {

			$categories += [
				Farmer::CATALOG => s("Catalogues"),
				Farmer::POINT => s("Modes de livraison"),
			];

		}

		return $categories;

	}

	public function getAnalyzeSubNav(Farm $eFarm): string {

		$h = '<nav id="farm-subnav">';
			$h .= $this->getAnalyzeMenu($eFarm, tab: 'analyze');
		$h .= '</nav>';

		return $h;

	}

	public function getAnalyzeMenu(Farm $eFarm, string $prefix = '', ?string $tab = NULL): string {

		$selectedView = ($tab === 'analyze') ? $eFarm->getView('viewAnalyze') : NULL;

		$h = '<div class="farm-subnav-wrapper">';

			foreach($this->getAnalyzeCategories($eFarm) as $key => $value) {

				$h .= '<a href="'.FarmUi::urlAnalyze($eFarm, $key).'" class="farm-subnav-item '.($key === $selectedView ? 'selected' : '').'" data-sub-tab="'.$key.'">';
					$h .= $prefix.'<span>'.$value.'</span>';
				$h .= '</a>';

			}

		$h .= '</div>';

		return $h;

	}

	protected static function getAnalyzeCategories(Farm $eFarm): array {

		$categories = [];

		if(
			$eFarm['hasCultivations'] and
			$eFarm->hasFeatureTime()
		) {
			$categories[Farmer::WORKING_TIME] = s("Temps de travail");
		}

		if($eFarm['hasSales']) {
			$categories[Farmer::SALES] = s("Ventes");
		}

		if($eFarm['hasCultivations']) {
			$categories[Farmer::CULTIVATION] = s("Cultures");
		}

		if($eFarm['hasCultivations'] and $eFarm['hasSales']) {
			$categories[Farmer::REPORT] = s("Rapports");
		}

		return $categories;

	}

	public function getAnalyzeReportTitle(\farm\Farm $eFarm, int $selectedSeason): string {

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= s("Rapports de production");
			$h .= '</h1>';
			$h .=  '<div>';
				if(new \analyze\Report(['farm' => $eFarm])->canCreate()) {
					$h .=  '<a href="/analyze/report:create?farm='.$eFarm['id'].'&season='.$selectedSeason.'" class="btn btn-primary">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Nouveau rapport").'</span></a>';
				}
			$h .=  '</div>';
		$h .=  '</div>';

		return $h;

	}

	public function getAnalyzeWorkingTimeTitle(Farm $eFarm, array $years, int $selectedYear, ?int $selectedMonth, ?string $selectedWeek, string $selectedView): string {

		$categories = $this->getAnalyzeWorkingTimeCategories();

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">';
					$h .= $categories[$selectedView].' '.self::getNavigation();
				$h .= '</a>';
				$h .= '<div class="dropdown-list bg-primary">';
					foreach($categories as $key => $value) {
						$h .= '<a href="'.\farm\FarmUi::urlAnalyzeWorkingTime($eFarm, $selectedYear, $key).'" class="dropdown-item '.($key === $selectedView ? 'selected' : '').'">'.$value.'</a> ';
					}
				$h .= '</div>';
			$h .= '</h1>';

			if($selectedView === Farmer::TIME) {

				$h .= '<a class="dropdown-toggle btn btn-primary" data-dropdown="bottom-end">';
					$h .= \Asset::icon('calendar2-week-fill').'<span class="hide-sm-down"> '.s("Calendrier").'</span>';
				$h .= '</a>';
				$h .= '<div class="dropdown-list dropdown-list-minimalist">';
					$h .= \util\FormUi::weekSelector(
						$selectedYear,
						\farm\FarmUi::urlAnalyzeWorkingTime($eFarm, $selectedYear, $selectedView).'?week={current}',
						\farm\FarmUi::urlAnalyzeWorkingTime($eFarm, $selectedYear, $selectedView).'?month={current}',
						defaultWeek: $selectedWeek,
						showYear: FALSE
					);
				$h .= '</div>';

			}
		$h .= '</div>';

		return $h;

	}

	protected static function getAnalyzeWorkingTimeCategories(): array {
		return [
			Farmer::TIME => s("Suivi du temps de travail"),
			Farmer::PACE => s("Suivi de la productivité"),
			Farmer::TEAM => s("Temps de travail de l'équipe"),
			Farmer::PERIOD => s("Saisonnalité du travail"),
		];
	}

	public function getAnalyzeSellingTitle(\farm\Farm $eFarm, int $selectedYear, ?string $selectedWeek, string $selectedView): string {

		$categories = $this->getAnalyzeSellingCategories($eFarm);

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">';
					$h .= $categories[$selectedView].' '.self::getNavigation();
				$h .= '</a>';
				$h .= '<div class="dropdown-list bg-primary">';
					foreach($categories as $key => $value) {
						$h .= '<a href="'.\farm\FarmUi::urlAnalyzeSelling($eFarm, $selectedYear, $key).'" class="dropdown-item '.($key === $selectedView ? 'selected' : '').'">'.$value.'</a> ';
					}
				$h .= '</div>';
			$h .= '</h1>';

			if($selectedView !== Farmer::PERIOD) {

				$h .= '<a class="dropdown-toggle btn btn-primary" data-dropdown="bottom-end">';
					$h .= \Asset::icon('calendar2-week-fill').'<span class="hide-sm-down"> '.s("Calendrier").'</span>';
				$h .= '</a>';
				$h .= '<div class="dropdown-list dropdown-list-minimalist">';
					$h .= \util\FormUi::weekSelector(
						$selectedYear,
						\farm\FarmUi::urlAnalyzeSelling($eFarm, $selectedYear, $selectedView).'?week={current}',
						\farm\FarmUi::urlAnalyzeSelling($eFarm, $selectedYear, $selectedView).'?month={current}',
						defaultWeek: $selectedWeek,
						showYear: FALSE
					);
				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	protected static function getAnalyzeSellingCategories(Farm $eFarm): array {

		$categories = [
			\farm\Farmer::ITEM => s("Ventes par culture"),
			\farm\Farmer::CUSTOMER => s("Ventes par client"),
			\farm\Farmer::SHOP => s("Boutiques en ligne"),
			\farm\Farmer::PERIOD => s("Saisonnalité des ventes")
		];

		if($eFarm->canPersonalData() === FALSE) {
			unset($categories[Farmer::CUSTOMER]);
			unset($categories[Farmer::SHOP]);
		}

		return $categories;

	}

	public function getAnalyzeCultivationTitle(\farm\Farm $eFarm, array $seasons, int $selectedSeason, string $selectedView, $actions): string {

		$categories = $this->getAnalyzeCultivationCategories();

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">';
					$h .= $categories[$selectedView].' '.self::getNavigation();
				$h .= '</a>';
				$h .= '<div class="dropdown-list bg-primary">';
					foreach($categories as $key => $value) {
						$h .= '<a href="'.\farm\FarmUi::urlAnalyzeCultivation($eFarm, $selectedSeason, $key).'" class="dropdown-item '.($key === $selectedView ? 'selected' : '').'">'.$value.'</a> ';
					}
				$h .= '</div>';
			$h .= '</h1>';
			$h .= $actions;
		$h .= '</div>';

		return $h;

	}

	protected static function getAnalyzeCultivationCategories(): array {
		return [
			\farm\Farmer::AREA => s("Surfaces cultivées"),
			\farm\Farmer::PLANT => s("Espèces cultivées"),
			\farm\Farmer::FAMILY => s("Familles cultivées"),
			\farm\Farmer::ROTATION => s("Rotations"),
		];
	}

	public function getSettingsSubNav(Farm $eFarm): string {

		$selectedView = $eFarm->getView('viewSettings');

		$h = '<nav id="farm-subnav">';
			$h .= '<div class="farm-subnav-wrapper">';

				foreach($this->getSettingsCategories($eFarm) as $key => ['url' => $url, 'label' => $label]) {
					$h .= '<a href="'.$url.'" class="farm-subnav-item '.($key === $selectedView ? 'selected' : '').'">'.$label.'</a> ';
				}

			$h .= '</div>';
		$h .= '</nav>';

		return $h;

	}

	protected static function getSettingsCategories(Farm $eFarm): array {

		$categories = [
			Farmer::SETTINGS => [
				'url' => FarmUi::urlSettings($eFarm),
				'label' => s("Paramétrage")
			],
			Farmer::WEBSITE => [
				'url' => '/website/manage?id='.$eFarm['id'],
				'label' => s("Site internet")
			]
		];

		return $categories;

	}

	public function getWebsiteSubNav(Farm $eFarm, ?string $page = NULL): string {

		$h = '<nav id="farm-subnav" class="farm-subnav-settings">';
			$h .= '<div class="farm-subnav-wrapper">';
				$h .= '<a href="'.self::urlSettings($eFarm).'" class="farm-subnav-link">'.s("Configuration").'</a>';
				$h .= '<div class="farm-subnav-separator">'.\Asset::icon('chevron-right').'</div>';
				$h .= '<a href="/website/manage?id='.$eFarm['id'].'" class="farm-subnav-link">'.s("Site internet").'</a>';
				$h .= '<div class="farm-subnav-separator">'.\Asset::icon('chevron-right').'</div>';
				$h .= '<div class="farm-subnav-text">'.encode($page).'</div>';
			$h .= '</div>';
		$h .= '</nav>';

		return $h;

	}

	public function getSeasonsTabs(Farm $eFarm, \Closure $url, int $selectedSeason): string {

		$h = ' <a data-dropdown="bottom-start" data-dropdown-hover="true" data-dropdown-offset-x="2" class="nav-year">'.s("Saison {year}", ['year' => $selectedSeason]).'  '.\Asset::icon('chevron-down').'</a>';

		$h .= '<div class="dropdown-list bg-primary">';

			$h .= '<div class="dropdown-title">'.s("Changer de saison").'</div>';

			for($season = $eFarm['seasonLast']; $season >= $eFarm['seasonFirst']; $season--) {
				$h .= '<a href="'.$url($season).'" class="dropdown-item '.($season === $selectedSeason ? 'selected' : '').'">'.s("Saison {year}", ['year' => $season]).'</a>';
			}

			if(OTF_DEMO === FALSE) {

				$h .= '<div class="dropdown-divider"></div>';

				$newLast = $eFarm['seasonLast'] + 1;
				$newFirst = $eFarm['seasonFirst'] - 1;

				if($newLast > date('Y') + 10) {
					$alert = s("Il n'est pas possible d'ajouter des saisons plus de dix ans en avance !");
					$h .= '<div class="dropdown-item farm-tab-disabled" data-alert="'.$alert.'" title="'.$alert.'">';
						$h .= s("Ajouter la saison {year}", ['year' => $newLast]);
					$h .= '</div> ';
				} else {
					$h .= '<a data-ajax="/farm/farm:doSeasonLast" post-id="'.$eFarm['id'].'" post-increment="1" class="dropdown-item">';
						$h .= s("Ajouter la saison {year}", ['year' => $newLast]);
					$h .= '</a> ';
				}

				$h .= '<a data-ajax="/farm/farm:doSeasonFirst" post-id="'.$eFarm['id'].'" post-increment="-1" class="dropdown-item">';
					$h .= s("Ajouter la saison {year}", ['year' => $newFirst]);
				$h .= '</a> ';

			}

		$h .= '</div>';

		return $h;

	}

	public function getSettings(Farm $eFarm, \website\News $eNews): string {

		$h = '';

		$h .= '<h2>'.s("La ferme").'</h2>';

		$h .= '<div class="util-block">';

			$h .= '<div class="util-buttons">';

				$h .= '<a href="/farm/farm:update?id='.$eFarm['id'].'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les réglages de base<br/>de la ferme").'</h4>';
					$h .= \Asset::icon('gear-fill');
				$h .= '</a>';

				$h .= '<a href="'.FarmerUi::urlManage($eFarm).'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("L'équipe").'</h4>';
					$h .= \Asset::icon('people-fill');
				$h .= '</a>';

				$h .= '<a href="/website/manage?id='.$eFarm['id'].'" class="bg-secondary util-button hide-lateral-down">';
					$h .= '<h4>';
						if($eFarm['website']->empty()) {
							$h .= s("Créer le site internet<br/>de la ferme");
						} else {
							$h .= s("Le site internet");
						}
					$h .= '</h4>';
					$h .= \Asset::icon('globe');
				$h .= '</a>';

				$h .= '<a href="/farm/farm:updateFeature?id='.$eFarm['id'].'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Activer ou désactiver des fonctionnalités").'</h4>';
					$h .= \Asset::icon('toggle2-on');
				$h .= '</a>';

			$h .= '</div>';

		$h .= '</div>';

		$h .= '<h2>'.s("La production").'</h2>';

		$h .= '<div class="util-block">';

			$h .= '<div class="util-buttons">';

				$h .= '<a href="/farm/farm:updateSeries?id='.$eFarm['id'].'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les réglages de base<br/>pour produire").'</h4>';
					$h .= \Asset::icon('gear-fill');
				$h .= '</a>';

				$h .= '<a href="'.FarmUi::urlCartography($eFarm).'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Le plan de la ferme").'</h4>';
					$h .= \Asset::icon('geo-alt-fill');
				$h .= '</a>';

				$h .= '<a href="'.\plant\PlantUi::urlManage($eFarm).'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les espèces").'</h4>';
					$h .= \Asset::icon('flower3');
				$h .= '</a>';

				$h .= '<a href="/farm/action:manage?farm='.$eFarm['id'].'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les interventions").'</h4>';
					$h .= \Asset::icon('list-task');
				$h .= '</a>';

				$h .= '<a href="/farm/tool:manage?farm='.$eFarm['id'].'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Le matériel").'</h4>';
					$h .= \Asset::icon('hammer');
				$h .= '</a>';

				$h .= '<a href="/farm/tool:manage?farm='.$eFarm['id'].'&routineName=fertilizer" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les intrants").'</h4>';
					$h .= \Asset::icon('stars');
				$h .= '</a>';

				$h .= '<a href="/farm/supplier:manage?farm='.$eFarm['id'].'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les fournisseurs de semences et plants").'</h4>';
					$h .= \Asset::icon('buildings');
				$h .= '</a>';

			$h .= '</div>';

		$h .= '</div>';

		$h .= '<h2>'.s("La commercialisation").'</h2>';

		$h .= '<div class="util-block">';

			$h .= '<div class="util-buttons">';

				$h .= '<a href="/selling/configuration:update?id='.$eFarm['id'].'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les réglages de base<br>pour vendre").'</h4>';
					$h .= \Asset::icon('gear-fill');
				$h .= '</a>';

				$h .= '<a href="/selling/unit:manage?farm='.$eFarm['id'].'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les unités de vente").'</h4>';
					$h .= \Asset::icon('receipt');
				$h .= '</a>';

				$h .= '<a href="/payment/method:manage?farm='.$eFarm['id'].'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les moyens de paiement").'</h4>';
					$h .= \Asset::icon('cash-coin');
				$h .= '</a>';

				$h .= '<a href="/payment/stripe:manage?farm='.$eFarm['id'].'" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Le paiement en ligne").'</h4>';
					$h .= \Asset::icon('stripe');
				$h .= '</a>';

			$h .= '</div>';

		$h .= '</div>';

		$h .= '<h2>'.s("Les ressources").'</h2>';

		$h .= '<div class="util-block">';

			$h .= '<div class="util-buttons">';

				$h .= '<a href="'.OTF_DEMO_URL.'/ferme/'.Farm::DEMO.'/series?view=area" class="bg-demo util-button" target="_blank">';

					$h .= '<div>';
						$h .= '<h4>'.s("Explorer la ferme démo").'</h4>';
						$h .= '<div class="util-button-text">'.s("Découvrez comment utiliser {siteName} à partir d'une ferme qui utilise la plupart des fonctionnalités du site !").'</div>';
					$h .= '</div>';
					$h .= \Asset::icon('eyeglasses');

				$h .= '</a>';

				$h .= '<a href="https://blog.ouvretaferme.org/" class="bg-secondary util-button" target="_blank">';

					$h .= '<div>';
						$h .= '<h4>'.s("Explorer le blog {siteName}").'</h4>';
						$h .= '<div class="util-button-text">'.s("Découvrez les nouvelles fonctionnalités, des ressources et la feuille de route de {siteName} !").'</div>';
					$h .= '</div>';
					$h .= \Asset::icon('book');

				$h .= '</a>';

				$h .= '<a href="/farm/tip?farm='.$eFarm['id'].'" class="bg-secondary util-button">';

					$h .= '<h4>'.s("Voir toutes les astuces").'</h4>';
					$h .= \Asset::icon('lightbulb');

				$h .= '</a>';

				if($eFarm->canPersonalData()) {

					$h .= '<a href="/farm/farm:export?id='.$eFarm['id'].'" class="bg-secondary util-button">';
						$h .= '<h4>'.s("Exporter les données de la ferme").'</h4>';
						$h .= \Asset::icon('download');
					$h .= '</a>';

				}

				if($eFarm->canManage()) {

					$h .= '<a href="/series/csv:importCultivations?id='.$eFarm['id'].'" class="bg-secondary util-button">';
						$h .= '<h4>'.s("Importer un plan de culture").'</h4>';
						$h .= '<div class="util-button-text">'.s("Compatible Qrop / Brinjel").'</div>';
						$h .= \Asset::icon('upload');
					$h .= '</a>';

				}

			$h .= '</div>';

		$h .= '</div>';

		$h .= new \main\HomeUi()->getBlog($eNews, FALSE);

		$h .= '<h2>😭</h2>';


			$h .= '<div class="util-buttons">';

				$h .= '<a data-ajax="/farm/farm:doClose" post-id="'.$eFarm['id'].'" data-confirm="'.s("Êtes-vous sûr de vouloir supprimer cette ferme ?").'" class="bg-danger util-button">';

					$h .= '<h4>'.s("Supprimer la ferme").'</h4>';
					$h .= \Asset::icon('trash');

				$h .= '</a>';


		$h .= '</div>';

		return $h;

	}

	public function getPanel(Farm $eFarm): string {

		$h = '';

		$h .= '<a href="'.$eFarm->getHomeUrl().'" class="farmer-farms-item">';

			$h .= '<div class="farmer-farms-item-vignette">';
				$h .= self::getVignette($eFarm, '6rem');
			$h .= '</div>';
			$h .= '<div class="farmer-farms-item-content">';
				$h .= '<h4>';
					$h .= encode($eFarm['name']);
				$h .= '</h4>';
				$h .= '<div class="farmer-farms-item-infos">';

					$infos = [];

					if($eFarm['place']) {
						$infos[] = \Asset::icon('geo-fill').' '.encode($eFarm['place']);
					}

					$h .= implode(' | ', $infos);

				$h .= '</div>';

			$h .= '</div>';

		$h .= '</a>';

		return $h;

	}

	public static function getVignette(Farm $eFarm, string $size): string {

		$eFarm->expects(['id', 'vignette']);

		$class = 'farm-vignette-view media-circle-view'.' ';
		$style = '';

		$ui = new \media\FarmVignetteUi();

		if($eFarm['vignette'] === NULL) {

			$class .= ' media-vignette-default';
			$style .= 'color: var(--muted)';
			$content = \Asset::icon('house-door-fill');

		} else {

			$format = $ui->convertToFormat($size);

			$style .= 'background-image: url('.$ui->getUrlByElement($eFarm, $format).');';
			$content = '';

		}

		return '<div class="'.$class.'" style="'.$ui->getSquareCss($size).'; '.$style.'">'.$content.'</div>';

	}

	public static function getLogo(Farm $eFarm, string $size): string {

		$eFarm->expects(['id', 'logo']);

		$ui = new \media\FarmLogoUi();

		$class = 'farm-logo-view media-rectangle-view'.' ';
		$style = '';

		if($eFarm['logo'] === NULL) {

			$class .= ' media-logo-default';
			$style .= '';

		} else {

			$format = $ui->convertToFormat($size);

			$style .= 'background-image: url('.$ui->getUrlByElement($eFarm, $format).');';

		}

		return '<div class="'.$class.'" style="'.$ui->getSquareCss($size).'; '.$style.'"></div>';

	}

	public static function getBanner(Farm $eFarm, string $width): string {

		$eFarm->expects(['id', 'banner']);

		$ui = new \media\FarmBannerUi();

		$class = 'farm-banner-view media-rectangle-view'.' ';
		$style = '';

		if($eFarm['banner'] === NULL) {

			$class .= ' media-banner-default';
			$style .= '';

		} else {

			$style .= 'background-image: url('.$ui->getUrlByElement($eFarm, 'm').');';

		}

		return '<div class="'.$class.'" style="width: '.$width.'; max-width: 100%; height: auto; aspect-ratio: 5; '.$style.'"></div>';

	}

	public static function getNavigation(): string {
		return '<span class="h-menu">'.\Asset::icon('chevron-down').'</span>';
	}

	public static function p(string $property): \PropertyDescriber {

		$d = Farm::model()->describer($property, [
			'name' => s("Nom de la ferme"),
			'vignette' => s("Photo de présentation"),
			'description' => s("Présentation de la ferme"),
			'startedAt' => s("Année de création"),
			'place' => s("Siège d'exploitation"),
			'url' => s("Site internet"),
			'rotationYears' => s("Temps de suivi des rotations"),
			'rotationExclude' => s("Espèces cultivées exclues du suivi des rotations"),
			'featureTime' => s("Activer le suivi du temps de travail"),
			'featureDocument' => s("Permettre l'édition de devis et de bons de livraison"),
			'logo' => s("Logo de la ferme"),
			'banner' => s("Bandeau à afficher en haut des e-mails envoyés à vos clients"),
			'defaultBedLength' => s("Longueur des planches par défaut"),
			'defaultBedWidth' => s("Largeur travaillée des planches par défaut"),
			'defaultAlleyWidth' => s("Largeur de passe-pied entre les planches par défaut"),
			'quality' => s("Signe de qualité"),
		]);

		switch($property) {

			case 'place' :
				new \main\PlaceUi()->query($d);
				break;

			case 'placeLngLat' :
				$d->field = NULL;
				break;

			case 'rotationYears' :
				$d->append = s("saisons");
				break;

			case 'rotationExclude' :
				$d->after = \util\FormUi::info(s("Vous pouvez exclure des espèces cultivées de la gestion des rotations, cela peut par exemple être utile pour les cultures très courtes."));
				$d->autocompleteDefault = fn(Farm $e) => $e['cPlantRotationExclude'] ?? $e->expects(['cPlantRotationExclude']);
				$d->autocompleteBody = function(\util\FormUi $form, Farm $e) {
					return [
						'farm' => $e['id']
					];
				};
				new \plant\PlantUi()->query($d, TRUE);
				$d->group = ['wrapper' => 'rotationExclude'];
				break;

			case 'featureTime' :
				$d->field = 'yesNo';

				$h = s("Le suivi du temps de travail permet de :");
				$h .= '<ul>';
					$h .= '<li>'.s("Saisir votre temps de travail ou celui de votre équipe pour chaque intervention").'</li>';
					$h .= '<li>'.s("Analyser avec des graphiques la répartition du travail et la productivité à la ferme").'</li>';
				$h .= '</ul>';

				$d->after = \util\FormUi::info($h);
				break;

			case 'featureDocument' :

				$d->values = [
					Farm::ALL => s("Pour tous les clients"),
					Farm::PRIVATE => s("Pour les clients particuliers"),
					Farm::PRO => s("Pour les clients professionnels"),
					Farm::DISABLED => s("Désactiver cette fonctionnalité"),
				];

				$d->field = 'select';
				$d->attributes['mandatory'] = TRUE;

				$h = s("L'édition des factures reste toujours disponible pour tous les clients.");
				$d->after = \util\FormUi::info($h);
				break;

			case 'defaultBedLength' :
				$d->append = s("m");
				$d->after = \util\FormUi::info(s("Si vous travaillez sur planches permanentes ou temporaires, indiquez ici la longueur habituelle de vos planches."));
				break;

			case 'defaultBedWidth' :
				$d->append = s("cm");
				$d->after = \util\FormUi::info(s("Si vous travaillez sur planches permanentes ou temporaires, indiquez ici la largeur travaillée habituelle de vos planches."));
				break;

			case 'defaultAlleyWidth' :
				$d->append = s("cm");
				$d->after = \util\FormUi::info(s("Si vous travaillez sur planches permanentes ou temporaires, indiquez ici la largeur habituelle des passe-pieds entre vos planches."));
				break;

			case 'quality' :
				$d->values = self::getQualities();
				$d->placeholder = s("Aucun");
				$d->after = \util\FormUi::info(s("Pour rappel, {siteName} ne peut être utilisé que par les fermes sous l'un de ces signes de qualité."));
				break;

			case 'stockNotes' :
				$d->attributes = [
					'onrender' => 'this.focus();'
				];
				break;

		}

		return $d;

	}

}
?>
