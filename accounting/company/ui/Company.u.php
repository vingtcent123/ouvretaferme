<?php
namespace company;

class CompanyUi {

	public function __construct() {
		\Asset::css('company', 'company.css');
		\Asset::js('company', 'company.js');
	}

	public static function link(Company $eCompany, bool $newTab = FALSE): string {
		return '<a href="'.self::url($eCompany).'" '.($newTab ? 'target="_blank"' : '').'>'.encode($eCompany['name']).'</a>';
	}

	public static function url(Company $eCompany): string {
		return \Lime::getUrl().'/'.$eCompany['id'].'/company';
	}

	public static function urlSettings(Company $eCompany): string {
		return self::url($eCompany).'/configuration';
	}

	public static function urlJournal(int|Company $company): string {
		return \Lime::getUrl().'/'.(is_int($company) ? $company : $company['id']).'/journal';
	}

	public static function urlAnalyze(int|Company $company): string {
		return \Lime::getUrl().'/'.(is_int($company) ? $company : $company['id']).'/analyze';
	}

	public static function urlOverview(int|Company $company): string {
		return \Lime::getUrl().'/'.(is_int($company) ? $company : $company['id']).'/overview';
	}

	public static function urlAsset(int|Company $company): string {
		return \Lime::getUrl().'/'.(is_int($company) ? $company : $company['id']).'/asset';
	}

	public static function urlBank(int|Company $company): string {
		return \Lime::getUrl().'/'.(is_int($company) ? $company : $company['id']).'/bank';
	}

	public static function urlAccounting(int|Company $company): string {
		return \Lime::getUrl().'/'.(is_int($company) ? $company : $company['id']).'/accounting';
	}

	/**
	 * Display a field to search companies
	 *
	 *
	 */
	public function query(\PropertyDescriber $d, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('house-door-fill');
		$d->field = 'autocomplete';

		$d->placeholder = s("Tapez un nom de ferme...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = '/company/search:query';
		$d->autocompleteResults = function(Company $e) {
			return self::getAutocomplete($e);
		};

	}

	public static function getAutocomplete(Company $eCompany): array {

		$item = self::getVignette($eCompany, '2.5rem');
		$item .= '<div>';
		$item .= encode($eCompany['name']).'<br/>';
		$item .= '</div>';

		return [
			'value' => $eCompany['id'],
			'itemHtml' => $item,
			'itemText' => $eCompany['name']
		];

	}

	public function warnFinancialYear(Company $eCompany, \Collection $cFinancialYear): string {

		if($cFinancialYear->notEmpty()) {
			return '';
		}

		$h = '<div class="util-info">';
			$h .= \Asset::icon('leaf').' '.s("Avant de démarrer, rendez-vous <link>dans les paramètres de votre exploitation</link> pour créer votre premier exercice comptable !", ['link' => '<a href="'.CompanyUi::urlAccounting($eCompany).'/financialYear/">']);
		$h .= '</div>';

		return $h;

	}

	public function create(): \Panel {

		$eCompany = new Company();

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/company/public:doCreate', ['id' => 'company-create', 'autocomplete' => 'off']);

			$h .= $form->asteriskInfo();

			$h .= $form->dynamicGroups($eCompany, ['siret*', 'name*', 'addressLine1', 'addressLine2', 'postalCode', 'city', 'isBio']);
			$h .= $form->hidden('nafCode', null);

			$h .= $form->group(
				content: $form->submit(s("Créer ma ferme"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-company-create',
			title: s("Créer ma ferme"),
			body: $h
		);

	}

	public function update(Company $eCompany): string {

		$form = new \util\FormUi();

		$h = $form->openAjax(CompanyUi::url($eCompany).'/company:doUpdate', ['id' => 'company-update', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eCompany['id']);

			$h .= $form->group(
				self::p('vignette')->label,
				new \media\CompanyVignetteUi()->getCamera($eCompany, size: '10rem')
			);
			$h .= $form->group(self::p('siret'), $form->text('siret', $eCompany['siret'], ['disabled' => TRUE]));
			$h .= $form->dynamicGroups($eCompany, ['nafCode', 'name', 'accountingType', 'url', 'addressLine1', 'addressLine2', 'postalCode', 'city', 'isBio']);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return $h;

	}

	public function getMainTabs(Company $eCompany, string $tab): string {

		$prefix = '<span class="company-subnav-prefix">'.\Asset::icon('chevron-right').' </span>';

		$h = '<nav id="company-nav">';

			$h .= '<div class="company-tabs">';

				$h .= '<a href="'.CompanyUi::urlBank($eCompany).'/cashflow" class="company-tab '.($tab === 'bank' ? 'selected' : '').'" data-tab="bank">';
					$h .= '<span class="hide-lateral-down company-tab-icon">'.\Asset::icon('piggy-bank').'</span>';
					$h .= '<span class="hide-lateral-up company-tab-icon">'.\Asset::icon('piggy-bank-fill').'</span>';
					$h .= '<span class="company-tab-label">';
						$h .= s("Banque");
					$h .= '</span>';
				$h .= '</a>';

				$h .= $this->getBankMenu($eCompany, prefix: $prefix, tab: $tab);

				$h .= '<a href="'.CompanyUi::urlJournal($eCompany).'/" class="company-tab '.($tab === 'journal' ? 'selected' : '').'" data-tab="journal">';
					$h .= '<span class="hide-lateral-down company-tab-icon">'.\Asset::icon('journal-bookmark').'</span>';
					$h .= '<span class="hide-lateral-up company-tab-icon">'.\Asset::icon('journal-bookmark-fill').'</span>';
					$h .= '<span class="company-tab-label">';
						$h .= s("Écritures <span>comptables</span>", ['span' => '<span class="hide-xs-down">']);
					$h .= '</span>';
				$h .= '</a>';

				$h .= $this->getJournalMenu($eCompany, prefix: $prefix, tab: $tab);

				$h .= '<a href="'.CompanyUi::urlAsset($eCompany).'/acquisition" class="company-tab '.($tab === 'asset' ? 'selected' : '').'" data-tab="asset">';
					$h .= '<span class="hide-lateral-down company-tab-icon">'.\Asset::icon('house-door').'</span>';
					$h .= '<span class="hide-lateral-up company-tab-icon">'.\Asset::icon('house-door-fill').'</span>';
					$h .= '<span class="company-tab-label">';
						$h .= s("Immobilisations");
					$h .= '</span>';
				$h .= '</a>';

				$h .= $this->getAssetMenu($eCompany, prefix: $prefix, tab: $tab);

				$categories = $this->getAnalyzeCategories($eCompany);
				$selectedCategory = \Setting::get('main\viewAnalyze');

				$h .= '<a href="'.CompanyUi::urlAnalyze($eCompany).'/bank" class="company-tab '.($tab === 'analyze' ? 'selected' : '').'" data-tab="analyze">';
					$h .= '<span class="hide-lateral-down company-tab-icon">'.\Asset::icon('bar-chart').'</span>';
					$h .= '<span class="hide-lateral-up company-tab-icon">'.\Asset::icon('bar-chart-fill').'</span>';
					$h .= '<span class="company-tab-label">';
						$h .= s("Analyse");
					$h .= '</span>';
					if(count($categories) > 1) {
						$h .= '<div class="company-tab-complement" data-dropdown="auto-left" data-dropdown-id="company-tab-analyze" data-dropdown-hover="true">';
							$h .= '<span id="company-tab-analyze-category">'.$categories[$selectedCategory]['label'].'</span>';
							$h .= ' '.\Asset::icon('chevron-down');
						$h .= '</div>';
					}
				$h .= '</a>';

				if(count($categories) > 1) {

					$h .= '<div data-dropdown-id="company-tab-analyze-list" class="dropdown-list bg-secondary">';
					foreach($categories as $category => $categoryData) {
						$h .= '<a href="'.$categoryData['url'].'" id="company-tab-analyze-'.$category.'" class="dropdown-item '.($category === $selectedCategory ? 'selected' : '').'">'.$categoryData['label'].'</a>';
					}
					$h .= '</div>';

				}

				$categories = $this->getOverviewCategories($eCompany);
				$selectedCategory = \Setting::get('main\viewOverview');

				$h .= '<a href="'.CompanyUi::urlOverview($eCompany).'/balance" class="company-tab '.($tab === 'overview' ? 'selected' : '').'" data-tab="overview">';

					$h .= '<span class="hide-lateral-down company-tab-icon">'.\Asset::icon('file-earmark-spreadsheet').'</span>';
					$h .= '<span class="hide-lateral-up company-tab-icon">'.\Asset::icon('file-earmark-spreadsheet-fill').'</span>';
					$h .= '<span class="company-tab-label">';
						$h .= s("Synthèse");
					$h .= '</span>';

					if(count($categories) > 1) {
							$h .= '<div class="company-tab-complement" data-dropdown="auto-left" data-dropdown-id="company-tab-overview" data-dropdown-hover="true">';
								$h .= '<span id="company-tab-overview-category">'.$categories[$selectedCategory]['label'].'</span>';
								$h .= ' '.\Asset::icon('chevron-down');
							$h .= '</div>';
						}
					$h .= '</a>';

				if(count($categories) > 1) {

					$h .= '<div data-dropdown-id="company-tab-overview-list" class="dropdown-list bg-secondary">';
					foreach($categories as $category => $categoryData) {
						$h .= '<a href="'.$categoryData['url'].'" id="company-tab-overview-'.$category.'" class="dropdown-item '.($category === $selectedCategory ? 'selected' : '').'">'.$categoryData['label'].'</a>';
					}
					$h .= '</div>';

				}

				if($eCompany->canWrite() === TRUE) {

					$h .= '<a href="'.CompanyUi::urlSettings($eCompany).'" class="company-tab '.($tab === 'settings' ? 'selected' : '').'" data-tab="settings">';
						$h .= '<span class="hide-lateral-down company-tab-icon">'.\Asset::icon('gear').'</span>';
						$h .= '<span class="hide-lateral-up company-tab-icon">'.\Asset::icon('gear-fill').'</span>';
						$h .= '<span class="company-tab-label hide-xs-down">';
							$h .= s("Paramétrage");
						$h .= '</span>';
					$h .= '</a>';

				}

			$h .= '</div>';

			$h .= '<div id="product-version" class="'.(\Privilege::can('dev\admin') ? '' : 'hide').'">';
				if(LIME_ENV === 'prod') {
					$h .= s("Version {number}", ['number' => \Asset::getVersion()]);
				} else {
					$h .= s("Version : {env}", ['env' => LIME_ENV]);
				}
			$h .= '</div>';

		$h .= '</nav>';

		return $h;

	}

	public function getSettingsSubNav(Company $eCompany): string {

		$selectedView = \Setting::get('main\viewSettings');

		$h = '<nav id="company-subnav">';
			$h .= '<div class="company-subnav-wrapper">';

				foreach(self::getSettingsCategories($eCompany) as $key => ['url' => $url, 'label' => $label]) {
					$h .= '<a href="'.$url.'" class="company-subnav-item '.($key === $selectedView ? 'selected' : '').'">'.$label.'</a> ';
				}

			$h .= '</div>';
		$h .= '</nav>';

		return $h;

	}

	protected static function getSettingsCategories(Company $eCompany): array {

		return [
			'settings' => [
				'url' => CompanyUi::urlSettings($eCompany),
				'label' => s("Paramétrage")
			]
		];

	}


	public function getSettings(Company $eCompany): string {

		$h = '';

		if(get_exists('firstTime') === TRUE) {

			$h .= '<div class="util-block-search stick-xs">';
			$h .= \Asset::icon('leaf').' '.s(
				"Pour commencer, vérifiez le <b>type de comptabilité</b> de votre ferme dans la section ”Les réglages de base”. Puis ensuite, créez votre <b>premier exercice comptable</b>.",
			);
			$h .= '</div>';
		}
		$h .= '<div class="util-block-optional">';

			$h .= '<h2>'.s("La ferme").'</h2>';

			$h .= '<div class="util-buttons">';

				if($eCompany->canManage() === TRUE) {

					$h .= '<a href="'.CompanyUi::url($eCompany).'/company:update?id='.$eCompany['id'].'" class="bg-secondary util-button">';
						$h .= '<h4>'.s("Les réglages de base<br/>de la ferme").'</h4>';
						$h .= \Asset::icon('gear-fill');
					$h .= '</a>';

					$h .= '<a href="'.SubscriptionUi::urlManage($eCompany).'" class="bg-secondary util-button">';
						$h .= '<h4>'.s("L'abonnement<br/>de la ferme").'</h4>';
						$h .= \Asset::icon('cart4');
					$h .= '</a>';

					$h .= '<a href="'.EmployeeUi::urlManage($eCompany).'" class="bg-secondary util-button">';
						$h .= '<h4>'.s("L'équipe").'</h4>';
						$h .= \Asset::icon('people-fill');
					$h .= '</a>';
				}

				$h .= '<a href="'.CompanyUi::urlBank($eCompany).'/account" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les comptes bancaires").'</h4>';
					$h .= \Asset::icon('bank');
				$h .= '</a>';

				$h .= '<a href="'.CompanyUi::urlJournal($eCompany).'/thirdParty" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les tiers").'</h4>';
					$h .= \Asset::icon('person-rolodex');
				$h .= '</a>';

			$h .= '</div>';

		$h .= '</div>';

		$h .= '<div class="util-block-optional">';

		$h .= '<h2>'.s("La comptabilité").'</h2>';

			$h .= '<div class="util-buttons">';

				$h .= '<a href="'.CompanyUi::urlAccounting($eCompany).'/account" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les classes de compte").'</h4>';
					$h .= \Asset::icon('gear-fill');
				$h .= '</a>';

				$h .= '<a href="'.CompanyUi::urlAccounting($eCompany).'/financialYear/" class="bg-secondary util-button">';
					$h .= '<h4>'.s("Les exercices comptables").'</h4>';
					$h .= \Asset::icon('calendar3');
				$h .= '</a>';

			$h .= '</div>';

		$h .= '</div>';

		if($eCompany->canManage() === TRUE) {

			$h .= '<div class="util-block-optional">';

				$h .= '<h2>😭</h2>';

				$h .= '<div class="util-buttons">';

					$h .= '<a data-ajax="/company/company:doClose" post-id="'.$eCompany['id'].'" data-confirm="'.s("Confirmez-vous vouloir supprimer votre ferme ?").'" class="bg-danger util-button">';

						$h .= '<h4>'.s("Supprimer la ferme").'</h4>';
						$h .= \Asset::icon('trash');

					$h .= '</a>';

				$h .= '</div>';

			$h .= '</div>';
		}

		return $h;

	}

	public function getAssetSubNav(Company $eCompany, string $prefix = '', ?string $tab = NULL): string {

		$h = '<nav id="company-subnav">';
			$h .= $this->getAssetMenu($eCompany, tab: 'asset');
		$h .= '</nav>';

		return $h;

	}

	public function getJournalSubNav(Company $eCompany, string $prefix = '', ?string $tab = NULL): string {

		$h = '<nav id="company-subnav">';
			$h .= $this->getJournalMenu($eCompany, tab: 'journal');
		$h .= '</nav>';

		return $h;

	}

	protected static function getJournalCategories(Company $eCompany): array {

		$journalTitle = $eCompany->isCashAccounting() ? s("Journal comptable") : s("Journaux");

		$categories = [
			'journal' => [
				'url' => CompanyUi::urlJournal($eCompany).'/',
				'label' => $journalTitle,
			],
		];

		if($eCompany->isAccrualAccounting()) {

			$categories['account'] = [
				'url' => CompanyUi::urlJournal($eCompany).'/accounts',
				'label' => s("Comptes")
			];

		}

		$categories['book'] = [
			'url' => CompanyUi::urlJournal($eCompany).'/book',
			'label' => s("Grand livre")
		];
		$categories['vat'] = [
			'url' => CompanyUi::urlJournal($eCompany).'/vat',
			'label' => s("Journaux de TVA")
		];

		return $categories;

	}

	protected static function getAssetCategories(Company $eCompany): array {

		return [
			'acquisition' => [
				'url' => CompanyUi::urlAsset($eCompany).'/acquisition',
				'label' => s("Acquisitions")
			],
			'depreciation' => [
				'url' => CompanyUi::urlAsset($eCompany).'/depreciation',
				'label' => s("Amortissements")
			],
			'state' => [
				'url' => CompanyUi::urlAsset($eCompany).'/state',
				'label' => s("État des immos")
			]
		];

	}

	public static function getAnalyzeCategories(Company $eCompany): array {

		return [
			'bank' => [
				'url' => CompanyUi::urlAnalyze($eCompany).'/bank',
				'label' => s("Trésorerie"),
				'longLabel' => s("Suivi de la trésorerie"),
			],
			'charges' => [
				'url' => CompanyUi::urlAnalyze($eCompany).'/charges',
				'label' => s("Charges"),
				'longLabel' => s("Suivi des charges"),
			],
			'result' => [
				'url' => CompanyUi::urlAnalyze($eCompany).'/result',
				'label' => s("Résultat"),
				'longLabel' => s("Suivi du résultat"),
			],
		];

	}

	public function getBankMenu(Company $eCompany, string $prefix = '', ?string $tab = NULL): string {

		$selectedView = ($tab === 'bank') ? \Setting::get('main\viewBank') : NULL;

		$h = '<div class="company-subnav-wrapper">';

			foreach(self::getBankCategories($eCompany) as $key => ['url' => $url, 'label' => $label]) {

				$h .= '<a href="'.$url.'" class="company-subnav-item '.($key === $selectedView ? 'selected' : '').'" data-sub-tab="'.$key.'">';
					$h .= $prefix.'<span>'.$label.'</span>';
				$h .= '</a>';
			}

		$h .= '</div>';

		return $h;

	}

	protected static function getBankCategories(Company $eCompany): array {

		return [
			'cashflow' => [
				'url' => CompanyUi::urlBank($eCompany).'/cashflow',
				'label' => s("Opérations bancaires")
			],
			'import' => [
				'url' => CompanyUi::urlBank($eCompany).'/import',
				'label' => s("Imports de relevés")
			]
		];

	}


	public function getBankSubNav(Company $eCompany): string {

		$h = '<nav id="company-subnav">';
		$h .= $this->getBankMenu($eCompany, tab: 'bank');
		$h .= '</nav>';

		return $h;

	}
	public function getJournalMenu(Company $eCompany, string $prefix = '', ?string $tab = NULL): string {

		$selectedView = ($tab === 'journal') ? \Setting::get('main\viewJournal') : NULL;

		$h = '<div class="company-subnav-wrapper">';

			foreach(self::getJournalCategories($eCompany) as $key => ['url' => $url, 'label' => $label]) {

				$h .= '<a href="'.$url.'" class="company-subnav-item '.($key === $selectedView ? 'selected' : '').'" data-sub-tab="'.$key.'">';
					$h .= $prefix.'<span>'.$label.'</span>';
				$h .= '</a>';
			}

		$h .= '</div>';

		return $h;

	}
	public function getAssetMenu(Company $eCompany, string $prefix = '', ?string $tab = NULL): string {

		$selectedView = ($tab === 'asset') ? \Setting::get('main\viewAsset') : NULL;

		$h = '<div class="company-subnav-wrapper">';

			foreach(self::getAssetCategories($eCompany) as $key => ['url' => $url, 'label' => $label]) {

				$h .= '<a href="'.$url.'" class="company-subnav-item '.($key === $selectedView ? 'selected' : '').'" data-sub-tab="'.$key.'">';
					$h .= $prefix.'<span>'.$label.'</span>';
				$h .= '</a>';
			}

		$h .= '</div>';

		return $h;

	}

	public static function getOverviewCategories(Company $eCompany): array {

		return [
			'balance' => [
				'url' => CompanyUi::urlOverview($eCompany).'/balance',
				'label' => s("Bilans"),
				'longLabel' => s("Les bilans"),
			],
			'accounting' => [
				'url' => CompanyUi::urlOverview($eCompany).'/accounting',
				'label' => s("Balances"),
				'longLabel' => s("Les balances"),
			],
		];

	}

	public function getOverviewMenu(Company $eCompany, string $prefix = '', ?string $tab = NULL): string {

		$selectedView = ($tab === 'overview') ? \Setting::get('main\viewOverview') : NULL;

		$h = '<div class="company-subnav-wrapper">';

			foreach(self::getStatementCategories($eCompany) as $key => ['url' => $url, 'label' => $label]) {

				$h .= '<a href="'.$url.'" class="company-subnav-item '.($key === $selectedView ? 'selected' : '').'" data-sub-tab="'.$key.'">';
					$h .= $prefix.'<span>'.$label.'</span>';
				$h .= '</a>';
			}

		$h .= '</div>';

		return $h;

	}


	public function getOverviewSubNav(Company $eCompany): string {

		$h = '<nav id="company-subnav">';
			$h .= $this->getOverviewMenu($eCompany, tab: 'overview');
		$h .= '</nav>';

		return $h;

	}

	protected static function getStatementCategories(Company $eCompany): array {

		return [
			'balance' => [
				'url' => CompanyUi::urlOverview($eCompany).'/balance',
				'label' => s("Bilans")
			],
			'accounting' => [
				'url' => CompanyUi::urlOverview($eCompany).'/accounting',
				'label' => s("Balances")
			],
		];

	}

	public function getPanel(Company $eCompany): string {

		$h = '';

		$h .= '<a href="'.$eCompany->getHomeUrl().'" class="employee-companies-item">';

			$h .= '<div class="employee-companies-item-vignette">';
				$h .= self::getVignette($eCompany, '6rem');
			$h .= '</div>';
			$h .= '<div class="employee-companies-item-content">';
				$h .= '<h4>';
					$h .= encode($eCompany['name']);
				$h .= '</h4>';
				$h .= '<div class="employee-companies-item-infos">';

					$infos = [];

					$h .= implode(' | ', $infos);

				$h .= '</div>';

			$h .= '</div>';

		$h .= '</a>';

		return $h;

	}

	public static function getVignette(Company $eCompany, string $size): string {

		$eCompany->expects(['id', 'vignette']);

		$class = 'company-vignette-view media-circle-view'.' ';
		$style = '';

		$ui = new \media\CompanyVignetteUi();

		if($eCompany['vignette'] === NULL) {

			$class .= ' media-vignette-default';
			$style .= 'color: var(--muted)';
			$content = \Asset::icon('house-door-fill');

		} else {

			$format = $ui->convertToFormat($size);

			$style .= 'background-image: url('.$ui->getUrlByElement($eCompany, $format).');';
			$content = '';

		}

		return '<div class="'.$class.'" style="'.$ui->getSquareCss($size).'; '.$style.'">'.$content.'</div>';

	}

	public static function getLogo(Company $eCompany, string $size): string {

		$eCompany->expects(['id', 'logo']);

		$ui = new \media\CompanyLogoUi();

		$class = 'company-logo-view media-rectangle-view'.' ';
		$style = '';

		if($eCompany['logo'] === NULL) {

			$class .= ' media-logo-default';
			$style .= '';

		} else {

			$format = $ui->convertToFormat($size);

			$style .= 'background-image: url('.$ui->getUrlByElement($eCompany, $format).');';

		}

		return '<div class="'.$class.'" style="'.$ui->getSquareCss($size).'; '.$style.'"></div>';

	}

	public static function getBanner(Company $eCompany, string $width): string {

		$eCompany->expects(['id', 'banner']);

		$ui = new \media\CompanyBannerUi();

		$class = 'company-banner-view media-rectangle-view'.' ';
		$style = '';

		if($eCompany['banner'] === NULL) {

			$class .= ' media-banner-default';
			$style .= '';

		} else {

			$style .= 'background-image: url('.$ui->getUrlByElement($eCompany, 'm').');';

		}

		return '<div class="'.$class.'" style="width: '.$width.'; max-width: 100%; height: auto; aspect-ratio: 5; '.$style.'"></div>';

	}

	public static function getNavigation(): string {
		return '<span class="h-menu">'.\Asset::icon('chevron-down').'</span>';
	}

	public static function p(string $property): \PropertyDescriber {

		$d = Company::model()->describer($property, [
			'accountingType' => s("Type de comptabilité"),
			'addressLine1' => s("Adresse (ligne 1)"),
			'addressLine2' => s("Adresse (ligne 2)"),
			'city' => s("Ville"),
			'logo' => s("Logo de votre exploitation"),
			'nafCode' => s("Code NAF de votre exploitation (APE)"),
			'name' => s("Nom de votre exploitation"),
			'postalCode' => s("Code postal"),
			'siret' => s("SIRET de votre exploitation"),
			'url' => s("Site internet"),
			'vignette' => s("Photo de présentation"),
			'isBio' => s("En agriculture biologique (ou reconversion) ?"),
		]);

		switch($property) {

			case 'accountingType' :
				$d->values = [
					CompanyElement::ACCRUAL => s("Comptabilité à l'engagement"),
					CompanyElement::CASH => s("Comptabilité de trésorerie"),
				];
				$d->after = \util\FormUi::info(s("Généralement, la comptabilité de <b>trésorerie</b> est choisie en <b>micro-BA</b> ou <b>régime simplifié</b>.<br />La comptabilité à l'<b>engagement</b> est choisie en <b>régime réel</b> (normal ou simplifié)"));
				break;

			case 'siret':
				$d->after = \util\FormUi::info(s("En remplissant le SIRET, les autres informations se rempliront automatiquement !"));
				$d->attributes['oninput'] = 'Company.getCompanyDataBySiret(this)';
				break;

			case 'isBio' :
				$d->field = 'yesNo';
				$d->attributes = [
					'labelOn' => s("Oui"),
					'labelOff' => s("Non"),
				];
				$d->after = \util\FormUi::info(\Asset::icon('leaf').' '.s("Avec le label bio, les modules de production et de commercialisation vous sont offerts !"));
				break;
		}

		return $d;

	}

}
?>
