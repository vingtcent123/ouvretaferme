<?php
namespace company;

class CompanyUi {

	public function __construct() {
		\Asset::css('company', 'company.css');
		\Asset::js('company', 'company.js');
	}

	public static function link(\farm\Farm $eFarm, bool $newTab = FALSE): string {
		return '<a href="'.self::url($eFarm).'" '.($newTab ? 'target="_blank"' : '').'>'.encode($eFarm['name']).'</a>';
	}


	public function getHomeUrl(\farm\Farm $eFarm): string {

		return self::urlJournal($eFarm).'/';

	}

	public static function url(\farm\Farm $eFarm): string {
		return \Lime::getUrl().'/'.$eFarm['id'].'/company';
	}

	public static function urlSettings(\farm\Farm $eFarm): string {
		return self::url($eFarm).'/configuration';
	}

	public static function urlJournal(int|\farm\Farm $farm): string {
		return \Lime::getUrl().'/'.(is_int($farm) ? $farm : $farm['id']).'/journal';
	}

	public static function urlOverview(int|\farm\Farm $farm, ?string $view = NULL): string {
		return \Lime::getUrl().'/'.(is_int($farm) ? $farm : $farm['id']).'/overview'.($view !== NULL ? '/'.$view : '');
	}

	public static function urlAsset(int|\farm\Farm $farm): string {
		return \Lime::getUrl().'/'.(is_int($farm) ? $farm : $farm['id']).'/asset';
	}

	public static function urlBank(int|\farm\Farm $farm): string {
		return \Lime::getUrl().'/'.(is_int($farm) ? $farm : $farm['id']).'/bank';
	}

	public static function urlAccount(int|\farm\Farm $farm): string {
		return \Lime::getUrl().'/'.(is_int($farm) ? $farm : $farm['id']).'/account';
	}

	public function warnFinancialYear(\farm\Farm $eFarm, \Collection $cFinancialYear): string {

		if($cFinancialYear->notEmpty()) {
			return '';
		}

		$h = '<div class="util-info">';
			$h .= \Asset::icon('leaf').' '.s("Avant de démarrer, rendez-vous <link>dans les paramètres de votre exploitation</link> pour créer votre premier exercice comptable !", ['link' => '<a href="'.CompanyUi::urlAccount($eFarm).'/financialYear/">']);
		$h .= '</div>';

		return $h;

	}

	public function create(\farm\Farm $eFarm): \Panel {

		$eCompany = new Company();

		$h = '<h4>'.s("Général").'</h4>';

		if($eFarm->isLegalComplete() === FALSE) {

			$h .= '<div>';
				$h .= s("Il manque certaines informations indispensables à la création de votre comptabilité. Saisissez-les dans ce formulaire :", ['link' => '<a href="/farm/farm:update?id='.$eFarm['id'].'">']);
			$h .= '</div>';


			$form = new \util\FormUi();

			$h .= $form->openAjax('/farm/farm:doUpdate', ['id' => 'farm-update', 'autocomplete' => 'off']);

				$h .= $form->hidden('id', $eFarm['id']);

				$h .= $form->dynamicGroups($eFarm, ['name', 'legalEmail', 'siret', 'legalName']);
				$h .= $form->addressGroup(s("Siège social de la ferme"), 'legal', $eFarm);
			$h .= $form->dynamicGroups($eFarm, ['startedAt']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer "))
			);

			$h .= $form->close();

		} else {

			$form = new \util\FormUi();

			$h .= $form->openAjax('/company/public:doCreate', ['id' => 'company-create', 'autocomplete' => 'off']);

			$h .= '<div>';
				$h .= s("Vous avez configuré certains paramètres pour votre ferme. Pour les modifier, rendez-vous <link>dans les paramètres généraux de votre ferme</link>.", ['link' => '<a href="/farm/farm:update?id='.$eFarm['id'].'">']);
			$h .= '</div>';

			$h .= '<div class="util-block stick-xs bg-background-light">';
				$h .= '<dl class="util-presentation util-presentation-2">';

					foreach(['siret', 'legalName', 'legalEmail'] as $field) {

						$h .= '<dt>'.\farm\FarmUi::p($field)->label.'</dt>';
						$h .= '<dd>'.encode($eFarm[$field]).'</dd>';

					}

					$h .= '<dt>'.s("Siège social de la ferme").'</dt>';
					$h .= '<dd>'.$eFarm->getLegalAddress('html').'</dd>';

				$h .= '</dl>';
			$h .= '</div>';


			$h .= '<h4>'.s("Paramètres de comptabilité").'</h4>';
			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->dynamicGroups($eCompany, ['accountingType']);


			$h .= '<h4>'.s("Premier exercice comptable").'</h4>';

			$h .= $form->dynamicGroups(new \account\FinancialYear(), ['startDate*', 'endDate*']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer les paramètres de ma ferme"))
			);

			$h .= $form->close();
		}

		return new \Panel(
			id: 'panel-company-create',
			title: s("Configurer ma ferme pour la comptabilité"),
			body: $h
		);

	}

	public function update(\farm\Farm $eFarm): string {

		$h = '<h3>'.s("Général").'</h3>';

		$form = new \util\FormUi();

		$h .= $form->openAjax('/'.$eFarm['id'].'/company/company:doUpdate', ['id' => 'company-update', 'autocomplete' => 'off']);

		$h .= '<div>';
			$h .= s("Vous avez configuré certains paramètres pour votre ferme. Pour les modifier, rendez-vous <link>dans les paramètres généraux de votre ferme</link>.", ['link' => '<a href="/farm/farm:update?id='.$eFarm['id'].'">']);
		$h .= '</div>';

		$h .= '<div class="util-block stick-xs bg-background-light mt-1 mb-1">';

			$h .= '<dl class="util-presentation util-presentation-2">';

			foreach(['siret', 'legalName', 'legalEmail'] as $field) {

				$h .= '<dt>'.\farm\FarmUi::p($field)->label.'</dt>';
				$h .= '<dd>'.encode($eFarm[$field]).'</dd>';

			}

			$h .= '<dt>'.s("Siège social de la ferme").'</dt>';
			$h .= '<dd>'.$eFarm->getLegalAddress('html').'</dd>';

			$h .= '</dl>';
		$h .= '</div>';


		$h .= '<h3>'.s("Paramètres de comptabilité").'</h3>';
		$h .= $form->asteriskInfo();

		$h .= $form->hidden('id', $eFarm['company']['id']);
		$h .= $form->dynamicGroups($eFarm['company'], ['accountingType']);

		$h .= $form->group(
			content: $form->submit(s("Enregistrer"))
		);

		$h .= $form->close();

		return $h;

	}

	public function getMainTabs(\farm\Farm $eFarm, string $tab): string {

		$prefix = '<span class="company-subnav-prefix">'.\Asset::icon('chevron-right').' </span>';

		$h = '<nav id="company-nav">';

			$h .= '<div class="company-tabs">';

				$h .= '<a href="'.CompanyUi::urlBank($eFarm).'/cashflow" class="company-tab '.($tab === 'bank' ? 'selected' : '').'" data-tab="bank">';
					$h .= '<span class="hide-lateral-down company-tab-icon">'.\Asset::icon('piggy-bank').'</span>';
					$h .= '<span class="hide-lateral-up company-tab-icon">'.\Asset::icon('piggy-bank-fill').'</span>';
					$h .= '<span class="company-tab-label">';
						$h .= s("Banque");
					$h .= '</span>';
				$h .= '</a>';

				$h .= $this->getBankMenu($eFarm, prefix: $prefix, tab: $tab);

				$h .= '<a href="'.CompanyUi::urlJournal($eFarm).'/" class="company-tab '.($tab === 'journal' ? 'selected' : '').'" data-tab="journal">';
					$h .= '<span class="hide-lateral-down company-tab-icon">'.\Asset::icon('journal-bookmark').'</span>';
					$h .= '<span class="hide-lateral-up company-tab-icon">'.\Asset::icon('journal-bookmark-fill').'</span>';
					$h .= '<span class="company-tab-label">';
						$h .= s("Écritures <span>comptables</span>", ['span' => '<span class="hide-xs-down">']);
					$h .= '</span>';
				$h .= '</a>';

				$h .= new \journal\JournalUi()->getJournalMenu($eFarm, prefix: $prefix, tab: $tab);

				$h .= '<a href="'.CompanyUi::urlAsset($eFarm).'/acquisition" class="company-tab '.($tab === 'asset' ? 'selected' : '').'" data-tab="asset">';
					$h .= '<span class="hide-lateral-down company-tab-icon">'.\Asset::icon('house-door').'</span>';
					$h .= '<span class="hide-lateral-up company-tab-icon">'.\Asset::icon('house-door-fill').'</span>';
					$h .= '<span class="company-tab-label">';
						$h .= s("Immobilisations");
					$h .= '</span>';
				$h .= '</a>';

				$h .= $this->getAssetMenu($eFarm, prefix: $prefix, tab: $tab);

				$categories = $this->getAnalyzeCategories($eFarm);
				$selectedCategory = \Setting::get('main\viewAnalyze');

				$h .= '<a href="'.CompanyUi::urlOverview($eFarm).'/bank" class="company-tab '.($tab === 'analyze' ? 'selected' : '').'" data-tab="analyze">';
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

				$categories = $this->getOverviewCategories($eFarm);
				$selectedCategory = \Setting::get('main\viewOverview');

				$h .= '<a href="'.CompanyUi::urlOverview($eFarm).'/balance" class="company-tab '.($tab === 'overview' ? 'selected' : '').'" data-tab="overview">';

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

				if($eFarm->canManage()) {

					$h .= '<a href="'.CompanyUi::urlSettings($eFarm).'" class="company-tab '.($tab === 'settings' ? 'selected' : '').'" data-tab="settings">';
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

	public function getSettingsSubNav(\farm\Farm $eFarm): string {

		$selectedView = \Setting::get('main\viewSettings');

		$h = '<nav id="company-subnav">';
			$h .= '<div class="company-subnav-wrapper">';

				foreach(self::getSettingsCategories($eFarm) as $key => ['url' => $url, 'label' => $label]) {
					$h .= '<a href="'.$url.'" class="company-subnav-item '.($key === $selectedView ? 'selected' : '').'">'.$label.'</a> ';
				}

			$h .= '</div>';
		$h .= '</nav>';

		return $h;

	}

	protected static function getSettingsCategories(\farm\Farm $eFarm): array {

		return [
			'settings' => [
				'url' => CompanyUi::urlSettings($eFarm),
				'label' => s("Paramétrage")
			]
		];

	}


	public function getSettings(\farm\Farm $eFarm): string {

		$h = '';

			$h .= '<div class="util-buttons">';

				if($eFarm->canManage() === TRUE) {

					$h .= '<a href="'.CompanyUi::url($eFarm).'/company:update?id='.$eFarm['id'].'" class="util-button">';
						$h .= '<h4>'.s("Les réglages de base").'</h4>';
						$h .= \Asset::icon('gear-fill');
					$h .= '</a>';

					$h .= '<a href="'.SubscriptionUi::urlManage($eFarm).'" class="util-button">';
						$h .= '<h4>'.s("L'abonnement<br/>de la ferme").'</h4>';
						$h .= \Asset::icon('cart4');
					$h .= '</a>';

					/*$h .= '<a href="'.EmployeeUi::urlManage($eFarm).'" class="util-button">';
						$h .= '<h4>'.s("L'équipe").'</h4>';
						$h .= \Asset::icon('people-fill');
					$h .= '</a>';*/
				}

				$h .= '<a href="'.CompanyUi::urlBank($eFarm).'/account" class="util-button">';
					$h .= '<h4>'.s("Les comptes bancaires").'</h4>';
					$h .= \Asset::icon('bank');
				$h .= '</a>';

				$h .= '<a href="'.CompanyUi::urlAccount($eFarm).'/thirdParty" class="util-button">';
					$h .= '<h4>'.s("Les tiers").'</h4>';
					$h .= \Asset::icon('person-rolodex');
				$h .= '</a>';

				$h .= '<a href="'.CompanyUi::urlAccount($eFarm).'/account" class="util-button">';
					$h .= '<h4>'.s("Les classes de compte").'</h4>';
					$h .= \Asset::icon('gear-fill');
				$h .= '</a>';

				$h .= '<a href="'.CompanyUi::urlAccount($eFarm).'/financialYear/" class="util-button">';
					$h .= '<h4>'.s("Les exercices comptables").'</h4>';
					$h .= \Asset::icon('calendar3');
				$h .= '</a>';

			$h .= '</div>';

		return $h;

	}

	public function getAssetSubNav(\farm\Farm $eFarm, string $prefix = '', ?string $tab = NULL): string {

		$h = '<nav id="company-subnav">';
			$h .= $this->getAssetMenu($eFarm, tab: 'asset');
		$h .= '</nav>';

		return $h;

	}

	protected static function getAssetCategories(\farm\Farm $eFarm): array {

		return [
			'acquisition' => [
				'url' => CompanyUi::urlAsset($eFarm).'/acquisition',
				'label' => s("Acquisitions")
			],
			'depreciation' => [
				'url' => CompanyUi::urlAsset($eFarm).'/depreciation',
				'label' => s("Amortissements")
			],
			'state' => [
				'url' => CompanyUi::urlAsset($eFarm).'/state',
				'label' => s("État des immos")
			]
		];

	}

	public static function getAnalyzeCategories(\farm\Farm $eFarm): array {

		return [
			'bank' => [
				'url' => CompanyUi::urlOverview($eFarm).'/financials:bank',
				'label' => s("Trésorerie"),
				'longLabel' => s("Suivi de la trésorerie"),
			],
			'charges' => [
				'url' => CompanyUi::urlOverview($eFarm).'/financials:charges',
				'label' => s("Charges"),
				'longLabel' => s("Suivi des charges"),
			],
			'result' => [
				'url' => CompanyUi::urlOverview($eFarm).'/financials:result',
				'label' => s("Résultat"),
				'longLabel' => s("Suivi du résultat"),
			],
		];

	}

	public function getBankMenu(\farm\Farm $eFarm, string $prefix = '', ?string $tab = NULL): string {

		$selectedView = ($tab === 'bank') ? \Setting::get('main\viewBank') : NULL;

		$h = '<div class="company-subnav-wrapper">';

			foreach(self::getBankCategories($eFarm) as $key => ['url' => $url, 'label' => $label]) {

				$h .= '<a href="'.$url.'" class="company-subnav-item '.($key === $selectedView ? 'selected' : '').'" data-sub-nav="'.$key.'">';
					$h .= $prefix.'<span>'.$label.'</span>';
				$h .= '</a>';
			}

		$h .= '</div>';

		return $h;

	}

	protected static function getBankCategories(\farm\Farm $eFarm): array {

		return [
			'cashflow' => [
				'url' => CompanyUi::urlBank($eFarm).'/cashflow',
				'label' => s("Opérations bancaires")
			],
			'import' => [
				'url' => CompanyUi::urlBank($eFarm).'/import',
				'label' => s("Imports de relevés")
			]
		];

	}


	public function getBankSubNav(\farm\Farm $eFarm): string {

		$h = '<nav id="company-subnav">';
		$h .= $this->getBankMenu($eFarm, tab: 'bank');
		$h .= '</nav>';

		return $h;

	}
	public function getAssetMenu(\farm\Farm $eFarm, string $prefix = '', ?string $tab = NULL): string {

		$selectedView = ($tab === 'asset') ? \Setting::get('main\viewAsset') : NULL;

		$h = '<div class="company-subnav-wrapper">';

			foreach(self::getAssetCategories($eFarm) as $key => ['url' => $url, 'label' => $label]) {

				$h .= '<a href="'.$url.'" class="company-subnav-item '.($key === $selectedView ? 'selected' : '').'" data-sub-nav="'.$key.'">';
					$h .= $prefix.'<span>'.$label.'</span>';
				$h .= '</a>';
			}

		$h .= '</div>';

		return $h;

	}

	public static function getOverviewCategories(\farm\Farm $eFarm): array {

		return [
			\farm\Farmer::BALANCE_SHEET => [
				'url' => CompanyUi::urlOverview($eFarm).'/statements:bilans',
				'label' => s("Bilans"),
				'longLabel' => s("Les bilans"),
			],
			\farm\Farmer::TRIAL_BALANCE => [
				'url' => CompanyUi::urlOverview($eFarm).'/statements:balances',
				'label' => s("Balances"),
				'longLabel' => s("Les balances"),
			],
		];

	}

	public function getOverviewMenu(\farm\Farm $eFarm, string $prefix = '', ?string $tab = NULL): string {

		$selectedView = ($tab === 'overview') ? \Setting::get('main\viewOverview') : NULL;

		$h = '<div class="company-subnav-wrapper">';

			foreach(self::getStatementCategories($eFarm) as $key => ['url' => $url, 'label' => $label]) {

				$h .= '<a href="'.$url.'" class="company-subnav-item '.($key === $selectedView ? 'selected' : '').'" data-sub-nav="'.$key.'">';
					$h .= $prefix.'<span>'.$label.'</span>';
				$h .= '</a>';
			}

		$h .= '</div>';

		return $h;

	}


	public function getOverviewSubNav(\farm\Farm $eFarm): string {

		$h = '<nav id="company-subnav">';
			$h .= $this->getOverviewMenu($eFarm, tab: 'overview');
		$h .= '</nav>';

		return $h;

	}

	protected static function getStatementCategories(\farm\Farm $eFarm): array {

		return [
			'balance' => [
				'url' => CompanyUi::urlOverview($eFarm).'/balance',
				'label' => s("Bilans")
			],
			'accounting' => [
				'url' => CompanyUi::urlOverview($eFarm).'/accounting',
				'label' => s("Balances")
			],
		];

	}

	public function getPanel(\farm\Farm $eFarm): string {

		$h = '';

		$h .= '<a href="'.CompanyUi::getHomeUrl($eFarm).'" class="employee-companies-item">';

			$h .= '<div class="employee-companies-item-vignette">';
				$h .= \farm\FarmUi::getVignette($eFarm, '6rem');
			$h .= '</div>';
			$h .= '<div class="employee-companies-item-content">';
				$h .= '<h4>';
					$h .= encode($eFarm['name']);
				$h .= '</h4>';
				$h .= '<div class="employee-companies-item-infos">';

					$infos = [];

					$h .= implode(' | ', $infos);

				$h .= '</div>';

			$h .= '</div>';

		$h .= '</a>';

		return $h;

	}

	public static function getNavigation(): string {
		return '<span class="h-menu">'.\Asset::icon('chevron-down').'</span>';
	}


	public static function getDropdownMenuTitle(array $categories, string $selectedView): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';

				$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">';
					$h .= $categories[$selectedView]['longLabel'].' '.'<span class="h-menu">'.\Asset::icon('chevron-down').'</span>';
				$h .= '</a>';

				$h .= '<div class="dropdown-list bg-primary">';

					foreach($categories as $category => $categoryData) {
						$h .= '<a href="'.$categoryData['url'].'" class="dropdown-item '.($category === $selectedView ? 'selected' : '').'">'.$categoryData['longLabel'].'</a> ';
					}

				$h .= '</div>';

			$h .= '</h1>';

		$h .= '</div>';

		return $h;
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
