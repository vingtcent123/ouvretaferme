<?php
/**
 * Pour les sites internet des fermes
 */
class WebsiteTemplate extends BaseTemplate {

	/**
	 * Header content
	 */
	public ?string $header = NULL;

	/**
	 * Main content
	 */
	public ?string $main = NULL;

	/**
	 * Display something in the footer
	 */
	public ?string $footer = NULL;

	protected function buildHtml(string $stream): string {

		$this->base = \website\WebsiteUi::url($this->data->eWebsite, '/');

		if($this->data->eWebsite['favicon']) {
			$this->favicon = new \media\WebsiteFaviconUi()->getUrlByElement($this->data->eWebsite, 'm');
		}

		Asset::css('website', 'public.css');
		Asset::css('website', \website\DesignUi::getCSSFile($this->data->eWebsite));

		Asset::cssContent('<style>
@import url("https://fonts.googleapis.com/css2?family=PT+Serif:ital,wght@0,400;0,700;1,400;1,700&display=swap");
</style>');

		$nav = $this->getNav();
		$header = $this->getHeader();
		$main = $this->getMain($stream);
		$footer = $this->getFooter();

		$text = \website\Website::GET('customText', 'customText', $this->data->eWebsite['customText']);
		$background = \website\Website::GET('customBackground', 'customBackground', $this->data->eWebsite['customBackground']);

		$h = '<!DOCTYPE html>';
		$h .= '<html lang="'.$this->lang.'">';

		$h .= '<head>';
			$h .= $this->getHead();
			$h .= Asset::importHtml();
			$h .= \website\DesignUi::getStyles($this->data->eWebsite);
		$h .= '</head>';

		$h .= '<body data-template="'.$this->getTemplate().'" data-text="'.$text.'" data-background="'.strtoupper($background).'" data-touch="no">';

			$h .= '<div class="website-wrapper">';
				$h .= '<nav id="main-nav">'.$nav.'</nav>';
				$h .= '<header>'.$header.'</header>';
				$h .= '<main>'.$main.'</main>';
				$h .= '<footer>'.$footer.'</footer>';
			$h .= '</div>';

		$h .= '</body>';

		$h .= '</html>';

		return $h;

	}

	protected function getHeader(): string {

		$h = '';
		if($this->title !== NULL) {

			if($this->data->eWebpage['banner'] !== NULL) {
				$banner = new \media\WebpageBannerUi()->getUrlByElement($this->data->eWebpage, 'l');
			} else if($this->data->eWebsite['banner'] !== NULL) {
				$banner = new \media\WebsiteBannerUi()->getUrlByElement($this->data->eWebsite, 'l');
			} else {
				$banner = NULL;
			}

			if($banner !== NULL) {

				$h .= '<div class="website-banner" style="background-image: url('.$banner.')">';

			}

				$h .= '<div class="website-header">';

					$h .= '<h1>'.$this->title.'</h1>';

					if(
						get_exists('customize') === FALSE and
						$this->data->eWebsite->canWrite() and
						$this->data->eWebpage->notEmpty()
					) {

						$h .= '<div class="website-admin">';

							$h .= '<a href="'.Lime::getUrl().'/website/manage?id='.$this->data->eWebsite['farm']['id'].'&tab=pages" class="btn btn-secondary" title="'.s("Modifier cette page").'">'.Asset::icon('pencil-fill').'</a>';

							if($this->data->eWebpage['template']['fqn'] === 'news') {
								$h .= ' <a href="'.Lime::getUrl().'/website/manage?id='.$this->data->eWebsite['farm']['id'].'&tab=news" class="btn btn-secondary" title="'.s("Gérer les actualités").'">'.Asset::icon('newspaper').'</a>';
							}

						$h .= '</div>';

					}

				$h .= '</div>';

			if($banner !== NULL) {

				$h .= '</div>';

			}

		} else if($this->header !== '') {
			$h .= $this->header;
		}

		return $h;

	}

	protected function getMain(string $stream):string {

		if($this->main) {
			return $this->main;
		} else {

			$h = $stream;

			$alerts = [];

			if($this->data->eWebsite['status'] === \website\Website::INACTIVE) {
				$alert = '<p>';
					$alert .= Asset::icon('exclamation-triangle-fill').' '.s("Le site de votre ferme est pour le moment hors ligne, vous seul pouvez le consulter !");
				$alert .= '</p>';
				$alerts[] = $alert;
			}

			if(
				$this->data->eWebpage->notEmpty() and
				$this->data->eWebpage['status'] === \website\Webpage::INACTIVE
			) {
				$alert = '<p>';
					$alert .= Asset::icon('exclamation-triangle-fill').' '.s("Cette page est actuellement hors ligne, vous seul pouvez la consulter !");
				$alert .= '</p>';
				$alerts[] = $alert;
			}

			if($this->data->browserObsolete) {
				$alerts[] = $this->getWarningObsoleteBrowser();
			}

			if($alerts) {
				$h .= '<div class="util-box-warning util-box-sticked">'.implode('', $alerts).'</div>';
			}

			return $h;

		}

	}

	protected function getLogo(): string {

		return \website\WebsiteUi::getLogoImage($this->data->eWebsite);

	}

	protected function getNav(): string {

		$hasLogo = ($this->data->eWebsite['logo'] !== NULL);

		$h = '<div class="website-nav '.($hasLogo ? 'website-nav-with-logo' : '').'">';

			if($hasLogo) {
				$h .= '<a href="'.$this->data->url.'" class="website-logo">';
					$h .= $this->getLogo();
				$h .= '</a>';
			} else {
				$h .= '<div class="website-name">';
					$h .= '<a href="'.$this->data->url.'">';
						$h .= encode($this->data->eWebsite['name']);
					$h .= '</a>';
				$h .= '</div>';
			}

			if($this->data->cMenu->notEmpty()) {

				$h .= '<div class="website-menu-wrapper">';

					$h .= '<label for="website-menu-input" class="website-menu-open">'.Asset::icon('list').' '.s("Menu").'</label>';

					$h .= '<input type="checkbox" id="website-menu-input"/>';

					$h .= '<ul class="website-menu">';

						$position = 0;

						foreach($this->data->cMenu as $eMenu) {

							$position++;

							$h .= '<li';

								if($position === 1) {
									$h .= ' class="website-menu-list-first"';
								} else if($position === $this->data->cMenu->count()) {
									$h .= ' class="website-menu-list-last"';
								}

							$h .= '>';

								if($eMenu['url'] !== NULL) {

									// Shop
									if(str_starts_with($eMenu['url'], \shop\ShopUi::domain())) {
										$icon = Asset::icon('basket2-fill');
									}
									// Autre chose
									else {
										$icon = Asset::icon('box-arrow-up-right');
									}

									$h .= '<a href="'.encode($eMenu['url']).'" class="website-menu-item" target="_blank">'.encode($eMenu['label']).' '.$icon.'</a>';


								} else {
									$class = ($this->data->eWebpage->notEmpty() and $eMenu['webpage']['id'] === $this->data->eWebpage['id']) ? 'selected' : '';
									$h .= '<a href="'.\website\WebsiteUi::path($this->data->eWebsite, '/'.$eMenu['webpage']['url']).'" class="website-menu-item '.$class.'">'.encode($eMenu['label']).'</a>';
								}

							$h .= '</li>';

						}

						if(
							$this->data->eWebpageNews['status'] === \website\Webpage::ACTIVE and
							$this->data->cNews->notEmpty()
						) {
							$h .= '<li class="website-menu-list-news">';
								$h .= new \website\NewsUi()->getForMenu($this->data->eWebsite, $this->data->eWebpage, $this->data->eWebpageNews, $this->data->cNews, 3);
							$h .= '</li>';
						}
					$h .= '</ul>';


				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	protected function getFooter(): string {

		if($this->footer === NULL) {

			if($this->data->eWebsite['customDisabledFooter'] === FALSE) {
				$h = '<div>';
				$h .= s("/ Ce site a été créé avec {link} /<br/><small>Logiciel ouvert pour les producteurs</small>", ['link' => '<a href="' . Lime::getUrl() . '">' . Lime::getDomain() . '</a>']);
				$h .= '</div>';

				return $h;
			} else {
				return '';
			}

		} else {
			return $this->footer;
		}

	}

}
?>
