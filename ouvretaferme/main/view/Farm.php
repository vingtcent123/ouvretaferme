<?php
/**
 * Affichage d'une page de navigation pour la ferme
 */
class FarmTemplate extends MainTemplate {

	public string $template = 'farm ';

	public ?string $mainYear = NULL;

	public ?string $mainTitle = NULL;
	public ?string $mainTitleClass = '';

	public ?string $nav = NULL;
	public ?string $subNav = NULL;
	public ?string $subNavTarget = NULL;
	public ?string $section = NULL;

	public function __construct() {

		parent::__construct();

		\Asset::css('farm', 'design.css');

	}

	protected function buildAjax(string $stream): AjaxTemplate {

		$this->buildSections();

		$t = parent::buildAjax($stream);
		$t->qs('body')->removeAttribute('data-section');

		return $t;

	}

	protected function buildHtml(string $stream): string {

		$this->buildSections();

		return parent::buildHtml($stream);

	}

	protected function buildAjaxScroll(AjaxTemplate $t): void {

		if(server_exists('HTTP_X_REQUESTED_HISTORY') === FALSE) {
			$t->package('main')->keepScroll();
		}

	}

	protected function buildAjaxHeader(AjaxTemplate $t): void {

		$t->package('main')->updateHeader(
			$this->nav,
			$this->subNav,
			$this->subNavTarget,
			$this->getFarmSections(),
			$this->getFarmNav(),
			new \farm\FarmUi()->getBreadcrumbs($this->data->eFarm, $this->nav, $this->subNav),
		);

	}

	protected function getFarmSections(): string {

		$eFarm = $this->data->eFarm;

		$sections = [];

		if($eFarm->canProduction()) {
			$sections['production'] = [\Asset::icon('leaf'), s("Produire")];
		}

		if($eFarm->canCommercialisation()) {
			$sections['commercialisation'] = [\Asset::icon('basket3'), s("Vendre")];
		}

		if($this->data->eFarm->hasAccounting() and $this->data->eFarm->canAccounting()) {
			$sections['accounting'] = [\Asset::icon('bank'), s("Comptabilité")];
		}

		$h = '<div id="farm-nav-sections" class="farm-nav-sections-'.count($sections).'">';

			foreach($sections as $name => [$icon, $label]) {

				$h .= '<a '.attr('onclick', 'Farm.changeSection(this, "click")').' '.attr('onmouseenter', 'Farm.changeSection(this, "mouseenter", 200)').' onmouseleave="Farm.clearSection(this)" data-section="'.$name.'" class="farm-nav-section farm-nav-section-'.$name.'">';
					$h .= $icon;
					$h .= '<span>'.$label.'</span>';
					$h .= '<span class="farm-nav-section-down">'.Asset::icon('chevron-down').'</span>';
					$h .= '<span class="farm-nav-section-up">'.Asset::icon('chevron-up').'</span>';
				$h .= '</a>';

			}

		$h .= '</div>';
		
		return $h;
		
	}

	protected function getFarmNav(): string {

		return new \farm\FarmUi()->getMainTabs($this->data->eFarm, $this->nav, $this->subNav);
	}

	protected function getHeader(): string {

		$h = $this->getFarmSections();
		$h .= $this->getFarmNav();

		return $h;

	}

	protected function getMain(string $stream):string {

		if($this->main) {

			$h = '';

			$h .= $this->getMainTitle();
			$h .= $this->main;

			return $h;

		} else {

			$h = '';

			$h .= $this->getMainTitle();
			if($this->data->tip) {
				$h .= new \farm\TipUi()->get($this->data->eFarm, $this->data->tip, $this->data->tipNavigation);
			}
			$h .= parent::getMain($stream);

			return $h;

		}

	}

	protected function getMainTitle():string {

		if($this->mainTitle) {

			$h = '<div class="container farm-template-main-title '.($this->mainYear ? 'farm-template-main-title-with-year' : '').' '.$this->mainTitleClass.'">';
				if($this->mainYear !== NULL) {
					$h .= '<div class="farm-template-main-year">'.$this->mainYear.'</div>';
				}
				$h .= '<div class="farm-template-main-content"><div>'.$this->mainTitle.'</div></div>';
			$h .= '</div>';

			return $h;

		} else {
			return '';
		}

	}

	protected function getNav(): string {

		$farm = '<div class="nav-title">';

			if(OTF_DEMO) {
				$farm .= '<a href="'.\farm\FarmUi::urlPlanning($this->data->eFarm).'">'.encode($this->data->eFarm['name']).'</a>';
				$farm .= '&nbsp;&nbsp;<a href="'.Lime::getUrl().'" class="btn btn-info">'.Asset::icon('escape').' '.s("Quitter la démo").'</a>';
			} else {

				$canUpdate = (
					$this->data->eFarm->canManage() or
					$this->data->eFarm->canPersonalData()
				);

				$canNavigate = ($this->data->cFarmUser->count() > 1);

				$farm .= '<div class="nav-title-farm">';
					$farm .= '<div>'.\farm\FarmUi::getVignette($this->data->eFarm, '4rem').'</div>';

					if($canUpdate or $canNavigate) {

						$farm .= '<a data-dropdown="bottom-start" data-dropdown-hover="true">'.encode($this->data->eFarm['name']).'  '.Asset::icon('chevron-down').'</a>';
						$farm .= '<div class="dropdown-list bg-primary">';

							if($canUpdate) {

								$farm .= '<div class="dropdown-subtitle">'.encode($this->data->eFarm['name']).'</div>';

								if($this->data->eFarm->canManage()) {
									$farm .= '<a href="/farm/farm:update?id='.$this->data->eFarm['id'].'" class="dropdown-item">'.Asset::icon('gear-fill').'  '.s("Paramétrer la ferme").'</a>';
									$farm .= '<a href="'.\farm\FarmerUi::urlManage($this->data->eFarm).'" class="dropdown-item">'.Asset::icon('people-fill').'  '.s("Gérer l'équipe de la ferme").'</a>';
								}
								if($this->data->eFarm->canPersonalData()) {
									$farm .= '<a href="/farm/farm:export?id='.$this->data->eFarm['id'].'" class="dropdown-item">'.Asset::icon('database-fill').'  '.s("Exporter les données").'</a>';
								}

							}

							if($canUpdate and $canNavigate) {
								$farm .= '<div class="dropdown-divider"></div>';
							}

							if($canNavigate) {

								$farm .= '<div class="dropdown-subtitle">'.s("Mes autres fermes").'</div>';

								foreach($this->data->cFarmUser as $eFarm) {

									if($eFarm->is($this->data->eFarm) === FALSE) {

										if($eFarm->canSection($this->section)) {
											$section = $this->section;
										} else {

											if($eFarm->canProduction()) {
												$section = 'production';
											} else if($eFarm->canCommercialisation()) {
												$section = 'commercialisation';
											} else if($eFarm->canAccounting()) {
												$section = 'accounting';
											} else {
												$section = NULL;
											}

										}

										if($section !== NULL) {
											$farm .= '<a href="'.$eFarm->getUrl($section).'" data-ajax-navigation="never" class="dropdown-item">'.\farm\FarmUi::getVignette($eFarm, '1.75rem').'&nbsp;&nbsp;'.encode($eFarm['name']).'</a>';
										}

									}
								}

							}

						$farm .= '</div>';

					} else {
						$farm .= encode($this->data->eFarm['name']);
					}

				$farm .= '</div>';

			}

		$farm .= '</div>';

		return $this->getDefaultNav($farm);

	}

	protected function buildSections(): void {

		switch($this->nav) {

			case 'planning' :
			case 'cultivation' :
			case 'analyze-production' :
			case 'settings-production' :
				$this->section = 'production';
				$this->template .= ' farm-production ';
				break;

			case 'selling' :
			case 'shop' :
			case 'communications' :
			case 'analyze-commercialisation' :
			case 'settings-commercialisation' :
				$this->section = 'commercialisation';
				$this->template .= ' farm-commercialisation ';
				break;

			case 'bank' :
			case 'journal' :
			case 'assets' :
			case 'analyze-accounting' :
			case 'settings-accounting' :
				$this->section = 'accounting';
				$this->template .= ' farm-accounting ';
				break;

		}

	}

}
?>
