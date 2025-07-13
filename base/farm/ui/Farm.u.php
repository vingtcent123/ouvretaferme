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

	public static function urlCultivation(Farm $eFarm, string $view, ?string $subView = NULL): string {

		return match($view) {
			'series' => self::urlCultivationSeries($eFarm, $subView),
			'forecast' => self::urlCultivationForecast($eFarm),
			'soil' => self::urlCultivationSoil($eFarm, $subView),
			'sequence' => self::urlCultivationSequences($eFarm)
		};

	}

	public static function urlCultivationSeries(Farm $eFarm, ?string $view = NULL, ?int $season = NULL): string {

		$view ??= $eFarm->getView('viewSeries');

		return self::url($eFarm).'/series'.($season ? '/'.$season : '').'?view='.$view;

	}

	public static function urlCultivationForecast(Farm $eFarm, ?int $season = NULL): string {

		return self::url($eFarm).'/previsionnel'.($season ? '/'.$season : '');

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

	public static function urlSelling(Farm $eFarm, string $view): string {

		return match($view) {
			'sale' => self::urlSellingSales($eFarm),
			'product' => self::urlSellingProduct($eFarm),
			'stock' => self::urlSellingStock($eFarm),
			'customer' => self::urlSellingCustomer($eFarm),
			'invoice' => self::urlSellingInvoice($eFarm)
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

	public static function urlShop(Farm $eFarm, string $view): string {

		return match($view) {
			'shop' => self::urlShopList($eFarm),
			'catalog' => self::urlShopCatalog($eFarm),
			'point' => self::urlShopPoint($eFarm)
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

	public static function urlCommunications(Farm $eFarm, string $view): string {

		return match($view) {
			'website' => self::urlCommunicationsWebsite($eFarm),
		};

	}

	public static function urlCommunicationsWebsite(Farm $eFarm): string {
		return '/website/manage?id='.$eFarm['id'];
	}

	public static function urlAnalyzeReport(Farm $eFarm, ?int $season = NULL): string {
		return self::url($eFarm).'/analyses/rapports'.($season ? '/'.$season : '');
	}

	public static function urlAnalyzeProduction(Farm $eFarm, string $view = 'cultivation'): string {

		return match($view) {
			'working-time' => self::urlAnalyzeWorkingTime($eFarm),
			'cultivation' => self::urlAnalyzeCultivation($eFarm)
		};

	}

	public static function urlAnalyzeCommercialisation(Farm $eFarm, string $view = 'sales'): string {

		return match($view) {
			'report' => self::urlAnalyzeReport($eFarm),
			'sales' => self::urlAnalyzeSelling($eFarm),
		};

	}

	public static function urlAnalyzeSelling(Farm $eFarm, ?int $year = NULL, ?string $category = NULL): string {
		return self::url($eFarm).'/analyses/ventes'.($year ? '/'.$year : '').($category ? '/'.$category : '');
	}

	public static function urlAnalyzeCultivation(Farm $eFarm, ?int $season = NULL, ?string $category = NULL): string {
		return self::url($eFarm).'/analyses/cultures'.($season ? '/'.$season : '').($category ? '/'.$category : '');
	}

	public static function urlSettings(Farm $eFarm, string $category): string {
		return self::url($eFarm).'/configuration/'.$category;
	}

	public static function urlSettingsProduction(Farm $eFarm): string {
		return self::urlSettings($eFarm, 'production');
	}

	public static function urlSettingsCommercialisation(Farm $eFarm): string {
		return self::urlSettings($eFarm, 'commercialisation');
	}

	public static function urlSettingsAccounting(Farm $eFarm): string {
		return \company\CompanyUi::urlSettings($eFarm);
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

			$h .= $form->dynamicGroups($eFarm, ['name*', 'place', 'placeLngLat', 'quality']);

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

	public function updateLegal(Farm $eFarm): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/farm:doUpdateLegal', ['autocomplete' => 'off']);

			$h .= $form->hidden('id', $eFarm['id']);
			$h .= $form->asteriskInfo();

			$h .= $form->dynamicGroups($eFarm, ['legalEmail*']);
				$h .= $form->dynamicGroups($eFarm, ['siret', 'legalName*']);
				$h .= $form->addressGroup(s("Siège social de la ferme").\util\FormUi::asterisk(), 'legal', $eFarm);

			$h .= $form->group(
				content: $form->submit(s("Valider"))
			);

		$h .= $form->close();

		return $h;

	}

	public function update(Farm $eFarm): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/farm:doUpdate', ['id' => 'farm-update', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eFarm['id']);

			$h .= $form->group(
				self::p('vignette')->label,
				new \media\FarmVignetteUi()->getCamera($eFarm, size: '10rem')
			);
			$h .= $form->dynamicGroups($eFarm, ['name', 'legalEmail', 'siret', 'legalName']);
			$h .= $form->addressGroup(s("Siège social de la ferme"), 'legal', $eFarm);
			$h .= $form->dynamicGroups($eFarm, ['description', 'startedAt', 'place', 'placeLngLat', 'url', 'quality']);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		$h .= '<h3 class="mt-3 mb-2">'.s("Vous ne souhaitez plus utiliser Ouvretaferme pour cette ferme ?").'</h3>';

		$h .= '<div class="util-buttons">';

			$h .= '<a data-ajax="/farm/farm:doClose" post-id="'.$eFarm['id'].'" data-confirm="'.s("Êtes-vous sûr de vouloir supprimer cette ferme ?").'" class="util-button util-button-danger">';

				$h .= '<h4>'.s("Supprimer la ferme").'</h4>';
				$h .= \Asset::icon('trash');

			$h .= '</a>';

		$h .= '</div>';

		return new \Panel(
			id: 'panel-farm-update',
			title: s("Paramétrer la ferme"),
			body: $h
		);

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

	public function updateProduction(Farm $eFarm): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/farm:doUpdateProduction');

			$h .= $form->hidden('id', $eFarm);

			$h .= $form->dynamicGroups($eFarm, ['featureTime']);

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

			$h .= $form->group(content: '<h3>'.s("Planches de culture").'</h3>');
			$h .= $form->dynamicGroups($eFarm, ['defaultBedLength', 'defaultBedWidth', 'defaultAlleyWidth']);

			$h .= $form->group(content: '<h3>'.s("Rotations").'</h3>');
			$h .= $form->dynamicGroups($eFarm, ['rotationYears', 'rotationExclude']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return $h;

	}

	public function updateEmail(Farm $eFarm): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/farm:doUpdateEmail');

			$h .= $form->hidden('id', $eFarm);

			$h .= $form->group(
				\farm\FarmUi::p('emailBanner')->label,
				new \media\FarmBannerUi()->getCamera($eFarm, width: '500px', height: 'auto')
			);
			$h .= '<br/>';

			$h .= $form->dynamicGroups($eFarm, ['emailFooter']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return $h;

	}

	public function export(Farm $eFarm, int $year): \Panel {

		$form = new \util\FormUi();

		$h = '<h3>'.s("Les données annuelles").'</h3>';

		$h .= $form->openAjax('/farm/farm:export', attributes: ['method' => 'get']);
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

			$h .= '<a href="/series/csv:exportCultivations?id='.$eFarm['id'].'&year='.$year.'" class="util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter le plan de culture").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('journals');
			$h .= '</a>';

			$h .= '<a href="/series/csv:exportSoil?id='.$eFarm['id'].'&year='.$year.'" class="util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter le plan d'assolement").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('map');
			$h .= '</a>';

			$h .= '<a href="/series/csv:exportTasks?id='.$eFarm['id'].'&year='.$year.'" class="util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter les interventions").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('calendar3');
			$h .= '</a>';

			$h .= '<a href="/series/csv:exportHarvests?id='.$eFarm['id'].'&year='.$year.'" class="util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter les récoltes").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('basket2-fill');
			$h .= '</a>';

			$h .= '<a href="/selling/csv:exportSales?id='.$eFarm['id'].'&year='.$year.'" class="util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter les ventes").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('piggy-bank');
			$h .= '</a>';

			$h .= '<a href="/selling/csv:exportItems?id='.$eFarm['id'].'&year='.$year.'" class="util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter les articles vendus").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('boxes');
			$h .= '</a>';

		$h .= '</div>';

		$h .= '<h3>'.s("Les données intégrales").'</h3>';

		$h .= '<div class="util-buttons">';

			$h .= '<a href="/selling/csv:exportProducts?id='.$eFarm['id'].'" class="util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter les produits").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('box');
			$h .= '</a>';

			$h .= '<a href="/selling/csv:exportCustomers?id='.$eFarm['id'].'" class="util-button" data-ajax-navigation="never">';
				$h .= '<div>';
					$h .= '<h4>'.s("Exporter les clients").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('people-fill');
			$h .= '</a>';

		$h .= '</div>';

		return new \Panel(
			id: 'panel-farm-export',
			title: s("Exporter les données de la ferme"),
			body: $h
		);

	}

	public function getMainTabs(Farm $eFarm, ?string $nav, ?string $subNav): string {

		$h = '<nav id="farm-nav">';

			$h .= '<div id="farm-breadcrumbs">'.$this->getBreadcrumbs($eFarm, $nav, $subNav).'</div>';

			if($eFarm->canProduction()) {

				$h .= '<div class="farm-tabs farm-section-production">';
					$h .= $this->getCloseSection();
					$h .= $this->getProductionSection($eFarm, $nav, $subNav);
				$h .= '</div>';

			}

			if($eFarm->canCommercialisation()) {

				$h .= '<div class="farm-tabs farm-section-commercialisation">';
					$h .= $this->getCloseSection();
					$h .= $this->getCommercialisationSection($eFarm, $nav, $subNav);
				$h .= '</div>';

			}

			if($eFarm->hasAccounting() and $eFarm->canAccounting()) {

				$h .= '<div class="farm-tabs farm-section-accounting">';
					$h .= $this->getCloseSection();
					$h .= $this->getAccountingSection($eFarm, $nav, $subNav);
				$h .= '</div>';

			}

		$h .= '</nav>';

		return $h;

	}

	protected function getCloseSection(): string {

		$h = '<a onclick="Farm.closeSection();" class="farm-tab-close">';
			$h .= '<span>'.\Asset::icon('x-lg').'</span>';
		$h .= '</a>';

		return $h;

	}

	public function getBreadcrumbs(Farm $eFarm, ?string $nav, ?string $subNav): string {

		if($nav === NULL) {
			return '';
		}

		$section = $this->getMenu($nav);

		$h = '<div id="farm-breadcrumbs-section">';
			$h .= $section['icon'].'  <b>'.$section['label'].'</b>';
			if($subNav !== NULL) {
				$h .= '  '.\Asset::icon('chevron-right').'  ';
			}
		$h .= '</div>';

		if($subNav !== NULL) {
			$h .= '<div class="farm-breadcrumbs-categories" '.Attr('onrender', 'Farm.scrollBreadCrumbs(this, "'.$subNav.'")').'>';
				foreach($this->getCategories($eFarm, $nav) as $category) {
					$h .= '<a href="'.$this->getCategoryUrl($eFarm, $nav, $category).'" class="farm-breadcrumbs-link '.($subNav === $category ? 'selected' : '').'" data-sub-nav="'.$category.'">'.$this->getCategoryName($eFarm, $nav, $category).'</a>';
				}
			$h .= '</div>';
		}

		return $h;

	}

	protected function getNav(string $section, string $nav, bool $disabled = FALSE, ?string $link = NULL, ?string $complement = NULL): string {

		$h = ($link ? '<a href="'.$link.'"' : '<div').' data-nav="'.$section.'" class="farm-tab '.($link === NULL ? 'farm-tab-subnav' : '').' '.($disabled ? 'disabled' : '').' '.($nav === $section ? 'selected' : '').'">';
			$h .= '<span class="farm-tab-icon">'.$this->getMenu($section)['icon'].'</span>';
			$h .= '<span class="farm-tab-label">'.$this->getMenu($section)['label'].'</span>';
			if($complement !== NULL) {
				$h .= $complement;
			}
		$h .= '</'.($link ? 'a' : 'div').'>';

		return $h;

	}

	protected function getSubNav(Farm $eFarm, string $menu, ?string $subNav): string {

		$h = '<div class="farm-subnav-wrapper">';

			foreach($this->getCategories($eFarm, $menu) as $name) {

				$h .= '<a href="'.$this->getCategoryUrl($eFarm, $menu, $name).'" class="farm-subnav-item '.($name === $subNav ? 'selected' : '').'" data-sub-nav="'.$name.'">';
					$h .= '<span class="farm-subnav-prefix">'.\Asset::icon('chevron-right').' </span>';
					$h .= '<span>'.$this-> getCategoryName($eFarm, $menu, $name).'</span>';
				$h .= '</a>';

			}

		$h .= '</div>';

		return $h;

	}

	protected function getMenu($name): array {

		return match($name) {
			'planning' => [
				'icon' => \Asset::icon('calendar3'),
				'label' => s("Planning")
			],
			'cultivation' => [
				'icon' => \Asset::icon('journals'),
				'label' => s("Cultures")
			],
			'analyze-production' => [
				'icon' => \Asset::icon('bar-chart'),
				'label' => s("Analyse")
			],
			'settings-production' => [
				'icon' => \Asset::icon('gear-fill'),
				'label' => s("Paramétrage")
			],
			'selling' => [
				'icon' => \Asset::icon('piggy-bank'),
				'label' => s("Commercialisation")
			],
			'shop' => [
				'icon' => \Asset::icon('cart'),
				'label' => s("Vente en ligne")
			],
			'communications' => [
				'icon' => \Asset::icon('megaphone'),
				'label' => s("Communication")
			],
			'analyze-commercialisation' => [
				'icon' => \Asset::icon('bar-chart'),
				'label' => s("Analyse")
			],
			'settings-commercialisation' => [
				'icon' => \Asset::icon('gear-fill'),
				'label' => s("Paramétrage")
			],
			'bank' => [
				'icon' => \Asset::icon('piggy-bank'),
				'label' => s("Banque")
			],
			'journal' => [
				'icon' => \Asset::icon('journal-bookmark'),
				'label' => s("Écritures comptables")
			],
			'assets' => [
				'icon' => \Asset::icon('house'),
				'label' => s("Immobilisations")
			],
			'analyze-accounting' => [
				'icon' => \Asset::icon('bar-chart'),
				'label' => s("Analyse")
			],
			'settings-accounting' => [
				'icon' => \Asset::icon('gear-fill'),
				'label' => s("Paramétrage")
			],
		};

	}

	protected function getCategoryUrl(Farm $eFarm, string $section, string $name): string {

		return match($section) {

			'planning' => match($name) {
				Farmer::DAILY => FarmUi::urlPlanningDaily($eFarm),
				Farmer::WEEKLY => FarmUi::urlPlanningWeekly($eFarm),
				Farmer::YEARLY => FarmUi::urlPlanningYear($eFarm)
			},

			'cultivation' => FarmUi::urlCultivation($eFarm, $name),
			'analyze-production' => FarmUi::urlAnalyzeProduction($eFarm, $name),

			'selling' => FarmUi::urlSelling($eFarm, $name),
			'shop' => FarmUi::urlShop($eFarm, $name),
			'communications' => FarmUi::urlCommunications($eFarm, $name),
			'analyze-commercialisation' => FarmUi::urlAnalyzeCommercialisation($eFarm, $name),

			'assets' => \company\CompanyUi::urlAsset($eFarm).'/'.$name,
			'bank' => \company\CompanyUi::urlBank($eFarm).'/'.$name,
			'journal' => \company\CompanyUi::urlJournal($eFarm).'/'.$name,
			'analyze-accounting' => \company\CompanyUi::urlOverview($eFarm, $name),

		};

	}

	protected function getCategoryName(Farm $eFarm, string $section, string $name): string {

		return match($section) {

			'planning' => match($name) {
				Farmer::DAILY => s("Quotidien"),
				Farmer::WEEKLY => s("Hebdomadaire"),
				Farmer::YEARLY => s("Annuel")
			},

			'cultivation' => match($name) {
				'series' => s("Plan de culture"),
				'soil' => s("Plan d'assolement"),
				'forecast' => s("Prévisionnel financier"),
				'sequence' => s("Itinéraires techniques"),
			},

			'analyze-production' => match($name) {
				'working-time' => s("Temps de travail"),
				'cultivation' => s("Cultures")
			},

			'selling' => match($name) {
				'sale' => s("Ventes"),
				'customer' => s("Clients"),
				'product' => s("Produits"),
				'invoice' => s("Factures"),
				'stock' => s("Stocks"),
			},

			'shop' => match($name) {
				'shop' => s("Boutiques"),
				'catalog' => s("Catalogues"),
				'point' => s("Modes de livraison"),
			},

			'communications' => match($name) {
				'website' => s("Site internet")
			},

			'analyze-commercialisation' => match($name) {
				'sales' => s("Ventes"),
				'report' => s("Rapports")
			},

			'assets' => match($name) {
				'acquisition' => s("Acquisitions"),
				'depreciation' => s("Amortissement"),
				'state' => s("État des immos"),
			},

			'bank' => match($name) {
				'cashflow' => s("Opérations bancaires"),
				'import' => s("Imports"),
			},

			'journal' => match($name) {
				'operations' => ($eFarm['company']->empty() or $eFarm['company']->isCashAccounting()) ? s("Journal comptable") : s("Journaux"),
				'accounts' => s("Comptes"),
				'book' => s("Grand livre"),
				'vat' => s("Journaux de TVA"),
			},

			'analyze-accounting' => match($name) {
				'financials' => s("Situation financière"),
				'statements' => s("État comptable"),
			},

		};

	}

	protected function getProductionSection(Farm $eFarm, ?string $nav, ?string $subNav): string {

		$h = '';

		if($eFarm->canPlanning()) {

			$h .= '<div class="farm-tab-wrapper farm-nav-planning">';

				$selectedPeriod = $eFarm->getView('viewPlanning');

				$h .= $this->getNav('planning', $nav, link: FarmUi::urlPlanning($eFarm));

				$h .= '<a class="farm-tab-complement" data-dropdown="bottom-left" data-dropdown-id="farm-tab-planning" data-dropdown-hover="true">';
					$h .= '<span id="farm-tab-planning-period">'.$this->getCategoryName($eFarm, 'planning', $selectedPeriod).'</span> '.\Asset::icon('chevron-down');
				$h .= '</a>';
				$h .= $this->getPlanningMenu($eFarm, subNav: $subNav);


			$h .= '</div>';

		}

		$h .= '<div class="farm-tab-wrapper farm-nav-cultivation">';

			$h .= $this->getNav('cultivation', $nav);

			$h .= $this->getCultivationMenu($eFarm, subNav: $subNav);

		$h .= '</div>';

		if($eFarm->canAnalyze()) {

			$h .= '<div class="farm-tab-wrapper farm-nav-analyze-production">';

				$h .= $this->getAnalyzeTab(
					$eFarm,
					$nav,
					$subNav,
					'production'
				);

			$h .= '</div>';

		}

		if($eFarm->canManage()) {

			$h .= '<div class="farm-tab-wrapper farm-nav-settings-production">';

				$h .= $this->getSettingsTab(
					$eFarm,
					$nav,
					'production',
					FarmUi::urlSettingsProduction($eFarm)
				);

			$h .= '</div>';

		}

		return $h;

	}

	protected function getCommercialisationSection(Farm $eFarm, ?string $nav, ?string $subNav): string {

		$h = '';

		if($eFarm->canSelling()) {

			$h .= '<div class="farm-tab-wrapper farm-nav-selling">';

				$h .= $this->getNav('selling', $nav);

				$h .= $this->getSellingMenu($eFarm, subNav: $subNav);

			$h .= '</div>';
			$h .= '<div class="farm-tab-wrapper farm-nav-shop">';

				if($eFarm['hasShops']) {

					$h .= $this->getNav('shop', $nav);

					$h .= $this->getShopMenu($eFarm, subNav: $subNav);

				} else {

					$h .= $this->getNav('shop', $nav);
					$h .= $this->getEmptyShopMenu($eFarm, subNav: $subNav);

				}

			$h .= '</div>';
			$h .= '<div class="farm-tab-wrapper farm-nav-communication">';

				$h .= $this->getNav('communications', $nav);
				$h .= $this->getCommunicationsMenu($eFarm, subNav: $subNav);

			$h .= '</div>';

		}

		if($eFarm->canAnalyze()) {

			$h .= '<div class="farm-tab-wrapper farm-nav-analyze-commercialisation">';

				$h .= $this->getAnalyzeTab(
					$eFarm,
					$nav,
					$subNav,
					'commercialisation'
				);

			$h .= '</div>';

		}

		if($eFarm->canManage()) {

			$h .= '<div class="farm-tab-wrapper farm-nav-settings-commercialisation">';

				$h .= $this->getSettingsTab(
					$eFarm,
					$nav,
					'commercialisation',
					FarmUi::urlSettingsCommercialisation($eFarm)
				);

			$h .= '</div>';

		}
		return $h;

	}

	protected function getAccountingSection(Farm $eFarm, ?string $nav, ?string $subNav): string {

		$h = '';

		if($eFarm->canAccountEntry()) {

			$h .= '<div class="farm-tab-wrapper farm-nav-bank">';

				$h .= $this->getNav('bank', $nav);

				$h .= $this->getBankMenu($eFarm, subNav: $subNav);

			$h .= '</div>';

			$h .= '<div class="farm-tab-wrapper farm-nav-journal">';

				$h .= $this->getNav('journal', $nav);

				$h .= $this->getOperationsMenu($eFarm, subNav: $subNav);

			$h .= '</div>';

			$h .= '<div class="farm-tab-wrapper farm-nav-assets">';

				$h .= $this->getNav('assets', $nav);

				$h .= $this->getAssetsMenu($eFarm, subNav: $subNav);

			$h .= '</div>';
		}

		if($eFarm->canAnalyze()) {

			$h .= '<div class="farm-tab-wrapper farm-nav-analyze-accounting">';

				$h .= $this->getAnalyzeTab(
					$eFarm,
					$nav,
					$subNav,
					'accounting'
				);

			$h .= '</div>';

		}

		if($eFarm->canManage()) {

			$h .= '<div class="farm-tab-wrapper farm-nav-settings-accounting">';

				$h .= $this->getSettingsTab(
					$eFarm,
					$nav,
					'accounting',
					FarmUi::urlSettingsAccounting($eFarm)
				);

			$h .= '</div>';

		}

		return $h;

	}

	protected function getAnalyzeTab(Farm $eFarm, ?string $nav, ?string $subNav, string $section): string {

		$h = '';

		if($this->getCategories($eFarm, 'analyze-'.$section)) {

			$h .= $this->getNav('analyze-'.$section, $nav);

			$h .= $this->getSubNav(
				$eFarm,
				'analyze-'.$section,
				$subNav
			);

		} else {

			$h .= $this->getNav('analyze-'.$section, $nav, disabled: TRUE);

		}

		return $h;

	}

	protected function getSettingsTab(Farm $eFarm, string $nav, string $section, string $url): string {

		return $this->getNav('settings-'.$section, $nav, link: $url);

	}

	public function getPlanningMenu(Farm $eFarm, ?string $subNav = NULL): string {

		$h = '<div data-dropdown-id="farm-tab-planning-list" class="dropdown-list bg-production">';

			foreach($this->getCategories($eFarm, 'planning') as $name) {

				$h .= '<a href="'.$this->getCategoryUrl($eFarm, 'planning', $name).'" id="farm-tab-planning-'.$name.'" class="dropdown-item '.($name === $subNav ? 'selected' : '').'">'.$this->getCategoryName($eFarm, 'planning', $name).'</a>';

			}

		$h .= '</div>';

		return $h;

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

	public function getCultivationForecastTitle(\farm\Farm $eFarm, ?int $selectedSeason): string {

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= $this->getCategoryName($eFarm, 'cultivation', 'forecast');
			$h .= '</h1>';
			$h .=  '<div>';
				$h .= '<a href="/plant/forecast:create?farm='.$eFarm['id'].'&season='.$selectedSeason.'" class="btn btn-primary">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Ajouter une espèce").'</span></a>';
			$h .=  '</div>';
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
							$h .= '<a href="/farm/farm:updateProduction?id='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('gear-fill').' '.s("Configurer").'</a>';
						}
					}
					break;

			}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getCultivationMenu(Farm $eFarm, ?string $subNav = NULL): string {

		return $this->getSubNav(
			$eFarm,
			'cultivation',
			$subNav
		);

	}

	public static function getSeriesCategories(Farm $eFarm): array {

		$categories = [
			Farmer::AREA => s("Plan de culture"),
			Farmer::SEEDLING => s("Semences et plants"),
			Farmer::HARVESTING => s("Récoltes"),
			Farmer::WORKING_TIME => s("Temps de travail"),
		];

		if($eFarm->hasFeatureTime() === FALSE) {
			unset($categories[Farmer::WORKING_TIME]);
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

	public function getAssetsMenu(Farm $eFarm, ?string $subNav = NULL): string {

		return $this->getSubNav(
			$eFarm,
			'assets',
			$subNav
		);

	}

	public function getOperationsMenu(Farm $eFarm, ?string $subNav = NULL): string {

		return $this->getSubNav(
			$eFarm,
			'journal',
			$subNav
		);

	}

	public function getBankMenu(Farm $eFarm, string $prefix = '', ?string $subNav = NULL): string {

		return $this->getSubNav(
			$eFarm,
			'bank',
			$subNav
		);

	}

	public function getSellingMenu(Farm $eFarm, ?int $season = NULL, string $prefix = '', ?string $subNav = NULL): string {

		return $this->getSubNav(
			$eFarm,
			'selling',
			$subNav
		);

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

	public function getShopMenu(Farm $eFarm, ?string $subNav = NULL): string {

		return $this->getSubNav(
			$eFarm,
			'shop',
			$subNav
		);

	}

	public function getEmptyShopMenu(Farm $eFarm, ?string $subNav = NULL): string {

		$h = '<div class="farm-subnav-wrapper">';
			$h .= '<a href="'.self::urlShopList($eFarm).'" class="farm-subnav-item '.('shop' === $subNav ? 'selected' : '').'" data-sub-nav="shop">';
				$h .= '<span class="farm-subnav-prefix">'.\Asset::icon('chevron-right').' </span>';
				$h .= '<span>'.s("Créer une boutique").'</span>';
			$h .= '</a>';
		$h .= '</div>';

		return $h;

	}

	public function getCommunicationsMenu(Farm $eFarm, ?string $subNav = NULL): string {

		return $this->getSubNav(
			$eFarm,
			'communications',
			$subNav
		);

	}

	public function getCategories(Farm $eFarm, string $menu): array {

		switch($menu) {

			case 'planning' :
				return [Farmer::DAILY, Farmer::WEEKLY, Farmer::YEARLY];

			case 'cultivation' :

				$categories = ['series', 'soil', 'forecast', 'sequence'];

				if($eFarm->canAnalyze() === FALSE) {
					array_delete($categories, 'forecast');
				}

				return $categories;

			case 'analyze-production' :

				$categories = [];

				if(
					$eFarm['hasCultivations'] and
					$eFarm->hasFeatureTime()
				) {
					$categories[] = 'working-time';
				}

				if($eFarm['hasCultivations']) {
					$categories[] = 'cultivation';
				}

				return $categories;

			case 'selling' :

				$categories = ['sale', 'customer', 'product'];

				if($eFarm['hasSales']) {
					$categories[] = 'invoice';
				}

				if($eFarm['featureStock']) {
					$categories[] = 'stock';
				}

				return $categories;

			case 'shop' :
				$categories = ['shop'];

				if($eFarm['hasShops']) {

					$categories[] = 'catalog';
					$categories[] = 'point';

				}

				return $categories;

			case 'communications' :
				return ['website'];

			case 'analyze-commercialisation' :

				$categories = [];

				if($eFarm['hasSales']) {
					$categories[] = 'sales';
				}

				if($eFarm['hasCultivations'] and $eFarm['hasSales']) {
					$categories[] = 'report';
				}

				return $categories;

			case 'assets' :
				return ['acquisition', 'depreciation', 'state'];

			case 'journal' :
				$categories = [
					'operations'
				];

				if($eFarm['company']->empty() or $eFarm['company']->isAccrualAccounting()) {
					$categories[] = 'accounts';
				}

				$categories[] = 'book';
				$categories[] = 'vat';

				return $categories;

			case 'bank' :
				return ['cashflow', 'import'];

			case 'analyze-accounting' :
				return ['financials', 'statements'];

		};

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

			if($selectedView === Farmer::TIME or $selectedView === Farmer::TEAM) {

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
			Farmer::TIME => s("Temps de travail à la ferme"),
			Farmer::TEAM => s("Temps de travail de l'équipe"),
			Farmer::PACE => s("Productivité à la ferme"),
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

			} else {

				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#analyze-selling-search")').' class="btn btn-primary">';
					$h .= \Asset::icon('search').' '.s("Filtrer");
				$h .= '</a>';

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

	public function getSettingsTitle(\farm\Farm $eFarm, string $label, string $selected, string $actions = ''): string {

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">';
					$h .= $label.' '.self::getNavigation();
				$h .= '</a>';
				$h .= '<div class="dropdown-list bg-primary">';
					$h .= '<div class="dropdown-title">'.s("Paramétrage").'</div>';
					$h .= '<a href="/farm/farm:update?id='.$eFarm['id'].'" class="dropdown-item">'.\Asset::icon('gear-fill').'  '.s("Paramétrer la ferme").'</a>';
					$h .= '<a href="/farm/farmer:manage?farm='.$eFarm['id'].' '.($selected === 'team' ? 'selected' : '').'" class="dropdown-item">'.\Asset::icon('people-fill').'  '.s("Gérer l'équipe de la ferme").'</a>';
					$h .= '<a href="'.self::urlSettingsProduction($eFarm).'" class="dropdown-item '.($selected === 'production' ? 'selected' : '').'">'.\Asset::icon('leaf').'  '.s("Paramétrer la production").'</a>';
					$h .= '<a href="'.self::urlSettingsCommercialisation($eFarm).'" class="dropdown-item '.($selected === 'commercialisation' ? 'selected' : '').'">'.\Asset::icon('basket3').'  '.s("Paramétrer la vente").'</a>';
					if($eFarm->hasAccounting()) {
						$h .= '<a href="'.self::urlSettingsAccounting($eFarm).'" class="dropdown-item '.($selected === 'accounting' ? 'selected' : '').'">'.\Asset::icon('bank').'  '.s("Paramétrer la comptabilité").'</a>';
					}
				$h .= '</div>';
			$h .= '</h1>';
			$h .= $actions;
		$h .=  '</div>';

		return $h;

	}

	public function getSettingsCommercialisation(Farm $eFarm): string {

		$h = '<div class="util-buttons">';

			$h .= '<a href="/selling/configuration:update?id='.$eFarm['id'].'" class="util-button">';
				$h .= '<h4>'.s("Les réglages de base").'</h4>';
				$h .= \Asset::icon('gear-fill');
			$h .= '</a>';

			$h .= '<a href="/farm/farm:updateEmail?id='.$eFarm['id'].'" class="util-button">';
				$h .= '<h4>'.s("Les e-mails").'</h4>';
				$h .= \Asset::icon('envelope');
			$h .= '</a>';

			$h .= '<a href="/selling/unit:manage?farm='.$eFarm['id'].'" class="util-button">';
				$h .= '<h4>'.s("Les unités de vente").'</h4>';
				$h .= \Asset::icon('receipt');
			$h .= '</a>';

			$h .= '<a href="/selling/group:manage?farm='.$eFarm['id'].'" class="util-button">';
				$h .= '<h4>'.s("Les groupes de clients").'</h4>';
				$h .= \Asset::icon('people-fill');
			$h .= '</a>';

			$h .= '<a href="/payment/method:manage?farm='.$eFarm['id'].'" class="util-button">';
				$h .= '<h4>'.s("Les moyens de paiement").'</h4>';
				$h .= \Asset::icon('cash-coin');
			$h .= '</a>';

			$h .= '<a href="/payment/stripe:manage?farm='.$eFarm['id'].'" class="util-button">';
				$h .= '<h4>'.s("Le paiement en ligne").'</h4>';
				$h .= \Asset::icon('stripe');
			$h .= '</a>';

		$h .= '</div>';

		return $h;

	}

	public function getSettingsProduction(Farm $eFarm): string {

		$h = '<div class="util-buttons">';

			$h .= '<a href="/farm/farm:updateProduction?id='.$eFarm['id'].'" class="util-button">';
				$h .= '<h4>'.s("Les réglages de base").'</h4>';
				$h .= \Asset::icon('gear-fill');
			$h .= '</a>';

			$h .= '<a href="'.FarmUi::urlCartography($eFarm).'" class="util-button">';
				$h .= '<h4>'.s("Le plan de la ferme").'</h4>';
				$h .= \Asset::icon('geo-alt-fill');
			$h .= '</a>';

			$h .= '<a href="'.\plant\PlantUi::urlManage($eFarm).'" class="util-button">';
				$h .= '<h4>'.s("Les espèces").'</h4>';
				$h .= \Asset::icon('flower3');
			$h .= '</a>';

			$h .= '<a href="/farm/action:manage?farm='.$eFarm['id'].'" class="util-button">';
				$h .= '<h4>'.s("Les interventions").'</h4>';
				$h .= \Asset::icon('list-task');
			$h .= '</a>';

			$h .= '<a href="/farm/tool:manage?farm='.$eFarm['id'].'" class="util-button">';
				$h .= '<h4>'.s("Le matériel").'</h4>';
				$h .= \Asset::icon('hammer');
			$h .= '</a>';

			$h .= '<a href="/farm/tool:manage?farm='.$eFarm['id'].'&routineName=fertilizer" class="util-button">';
				$h .= '<h4>'.s("Les intrants").'</h4>';
				$h .= \Asset::icon('stars');
			$h .= '</a>';

			$h .= '<a href="/farm/supplier:manage?farm='.$eFarm['id'].'" class="util-button">';
				$h .= '<h4>'.s("Les fournisseurs de semences et plants").'</h4>';
				$h .= \Asset::icon('buildings');
			$h .= '</a>';

			if($eFarm->canManage()) {

				$h .= '<a href="/series/csv:importCultivations?id='.$eFarm['id'].'" class="util-button">';
					$h .= '<div>';
						$h .= '<h4>'.s("Importer un plan de culture").'</h4>';
						$h .= '<div class="util-button-text">'.s("Compatible Qrop / Brinjel").'</div>';
					$h .= '</div>';
					$h .= \Asset::icon('upload');
				$h .= '</a>';

			}

		$h .= '</div>';

		return $h;

	}

	public function getPanel(Farm $eFarm): string {

		$h = '<div class="farmer-farms-item">';

			$h .= '<div class="farmer-farms-item-vignette">';
				$h .= self::getVignette($eFarm, '4rem');
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
			$h .= '<div class="farmer-farms-item-buttons">';
				if($eFarm->canProduction()) {
					$h .= '<a href="'.$eFarm->getProductionUrl().'" class="btn btn-production">'.\Asset::icon('leaf').'<br/>'.s("Produire").'</a> ';
				}
				if($eFarm->canCommercialisation()) {
					$h .= '<a href="'.$eFarm->getCommercialisationUrl().'" class="btn btn-commercialisation">'.\Asset::icon('basket3').'<br/>'.s("Vendre").'</a> ';
				}
				if($eFarm->hasAccounting() and $eFarm->canAccounting()) {
					$h .= '<a href="'.$eFarm->getAccountingUrl().'" class="btn btn-accounting">'.\Asset::icon('bank').'<br/>'.s("Comptabilité").'</a> ';
				}

			$h .= '</div>';

		$h .= '</div>';

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

		$eFarm->expects(['id', 'emailBanner']);

		$ui = new \media\FarmBannerUi();

		$class = 'farm-banner-view media-rectangle-view'.' ';
		$style = '';

		if($eFarm['emailBanner'] === NULL) {

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
			'legalName' => s("Raison sociale de la ferme"),
			'legalEmail' => s("Adresse e-mail de la ferme"),
			'siret' => s("Numéro d'immatriculation SIRET"),
			'vignette' => s("Photo de présentation"),
			'description' => s("Présentation de la ferme"),
			'startedAt' => s("Année de création"),
			'place' => s("Lieu de production"),
			'url' => s("Site internet"),
			'rotationYears' => s("Temps de suivi des rotations"),
			'rotationExclude' => s("Espèces cultivées exclues du suivi des rotations"),
			'featureTime' => s("Activer le suivi du temps de travail"),
			'logo' => s("Logo de la ferme"),
			'emailBanner' => s("Bandeau à afficher en haut des e-mails envoyés à vos clients"),
			'emailFooter' => s("Signature à afficher en bas des e-mails envoyés à vos clients"),
			'defaultBedLength' => s("Longueur des planches par défaut"),
			'defaultBedWidth' => s("Largeur travaillée des planches par défaut"),
			'defaultAlleyWidth' => s("Largeur de passe-pied entre les planches par défaut"),
			'quality' => s("Signe de qualité"),
		]);

		switch($property) {

			case 'siret' :
				$d->placeholder = s("Exemple : {value}", '123 456 789 00013');
				$d->attributes['oninput'] = 'Farm.getFarmDataBySiret(this)';
				break;

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
