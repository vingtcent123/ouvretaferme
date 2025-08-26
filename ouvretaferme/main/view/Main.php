<?php
/**
 * Affichage d'une page de navigation
 */
class MainTemplate extends BaseTemplate {

	/**
	 * Display nav ?
	 */
	public bool $hasNav = TRUE;

	/**
	 * Header content
	 */
	public ?string $header = NULL;

	/**
	 * Main content
	 */
	public ?string $main = NULL;

	/**
	 * Main container ?
	 */
	public bool $mainContainer = TRUE;

	/**
	 * Display something in the footer
	 */
	public ?string $footer = NULL;

	/**
	 * Admin page
	 */
	public ?string $admin = NULL;

	public function __construct() {

		parent::__construct();

		\Asset::css('main', 'design.css');

		$this->base = \Lime::getProtocol().'://'.SERVER('HTTP_HOST');

	}

	protected function getHeader(): string {

		$h = '';

		if($this->header === NULL and $this->title !== NULL) {
			$h .= '<div class="container">';
				$h .= '<h1>'.$this->title.'</h1>';
			$h .= '</div>';
		} else  if($this->header !== '') {
			$h .= '<div class="container">';
				$h .= $this->header;
			$h .= '</div>';
		}

		return $h;

	}

	protected function getMain(string $stream):string {

		if($this->main) {
			return $this->main;
		} else {

			$h = '';

			if($this->mainContainer) {
				$h .= '<div class="container">'.$stream.'</div>';
			} else {
				$h .= $stream;
			}

			if($this->data->browserObsolete) {
				$h .= '<div class="util-box-warning util-box-sticked">'.$this->getWarningObsoleteBrowser().'</div>';
			}

			return $h;

		}

	}

	protected function getNav(): string {
		return $this->getDefaultNav();
	}

	protected function getDefaultNav(?string $center = NULL): string {

		if($this->hasNav === FALSE) {
			return '';
		}

		$h = '<div class="nav-wrapper nav-default-wrapper container">';

		if($center === NULL) {

			$h .= '<div class="nav-title">';
				if(OTF_DEMO) {
					$h .= '&nbsp;&nbsp;<a href="'.Lime::getUrl().'" class="btn btn-transparent">'.Asset::icon('escape').' '.s("Quitter la démo").'</a>';
				} else {
					$h .= '<a href="'.Lime::getUrl().'">'.Lime::getName().'</a>';
				}
			$h .= '</div>';

		} else {

			$h .= $center;

		}

		if(
			Lime::getHost() === LIME_HOST and // Uniquement sur www
			currentDate() <= '2024-10-10'
		) {
			$h .= '<a href="https://blog.ouvretaferme.org/" class="nav-news" target="_blank">';
				$h .= '<div class="nav-news-title">'.Asset::icon('cursor-fill').' '.s("Nouveautés").'</div>';
				$h .= '<div class="nav-news-name">'.s("5 octobre 2024").'</div>';
			$h .= '</a>';
		}

		$h .= '<ul class="nav-actions">';

			if($this->data->userDeletedAt) {
				$h .= '<li class="nav-deleted nav-action-optional">';
					$h .= '<a href="/main/account" class="nav-item" title="'.new user\DropUi()->getCloseMessage($this->data->userDeletedAt).'"><span>'.\Asset::icon('exclamation-triangle-fill').'&nbsp;'.s("Compte en cours de fermeture").'&nbsp;'.\Asset::icon('exclamation-triangle-fill').'</span></a>';
				$h .= '</li>';
			}

			if($this->data->isLogged) {

				if($this->data->eUserOnline->notEmpty()) {
					$h .= $this->getUserNavItem($this->data);
				}

			} else {

				$h .= '<li id="signIn-item">';
					$h .= '<a href="'.Lime::getUrl().'/user/signUp" class="nav-item">'.s("Inscription").'</a>';
				$h .= '</li>';

				$h .= '<li id="logIn-item">';
					$h .= '<a href="/user/log:form" class="nav-item">'.s("Connexion").'</a>';
				$h .= '</li>';

			}

		$h .= '</ul>';

		$h .= '</div>';

		return $h;

	}

	protected function getUserNavItem($data): string {

		$h = '<li>';

		$h .= '<a class="nav-user nav-item" data-dropdown="bottom" data-dropdown-hover="true">';
			$h .= \user\UserUi::getVignette($data->eUserOnline, '1.75rem');
			$h .= \Asset::icon('chevron-down');
		$h .= '</a>';

		$h .= '<div class="dropdown-list bg-primary">';

		$h .= '<div class="dropdown-title">'.$data->eUserOnline->getName().'</div>';

		$h .= '<a href="'.Lime::getUrl().'" class="dropdown-item">'.s("Accueil").'</a>';

		if(Lime::getHost() === LIME_HOST) {
			$h .= '<a href="/main/account" class="dropdown-item">'.s("Mon compte").'</a>';
		} else {
			$h .= '<a href="'.Lime::getUrl().'/main/account" class="dropdown-item" target="_blank">'.s("Mon compte").'</a>';
		}

		if(OTF_DEMO === FALSE) {

			$h .= '<form method="post" action="'.Lime::getUrl().'/user/log:out">';
				$h .= '<button type="submit" class="dropdown-item">'.s("Me déconnecter").'</button>';

				if(Lime::getHost() === LIME_HOST) {
					$h .= '<input type="hidden" name="redirect" value="'.Lime::getProtocol().'://'.SERVER('HTTP_HOST').'"/>';
				} else if(Setting::get('shop\domain') === LIME_HOST and isset($data->eShop) /* 404 */ and $data->eShop->notEmpty()) {
					$h .= '<input type="hidden" name="redirect" value="'.\shop\ShopUi::url($data->eShop).'"/>';
				} else {
					$h .= '<input type="hidden" name="redirect" value="'.Lime::getUrl().'"/>';
				}
			$h .='</form>';

		}

		if(Privilege::can('user\admin')) {
			$h .= '<div class="dropdown-divider"></div>';
			$h .= '<a href="'.Lime::getUrl().'/user/admin/" class="dropdown-item">'.\Asset::icon('server').' '.s("Administrer").'</a>';
		}

		$h .= '</div>';

		$h .= '</li>';

		return $h;

	}

	protected function getFooter() {

		$h = '';

		if($this->footer === NULL) {

			if(Lime::getHost() === LIME_HOST) {

				$h .= '<div class="footer-content">';
					$h .= '<div class="footer-content-text">';
						$h .= Lime::getHost();
					$h .= '</div>';
					$h .= '<div class="footer-content-legal">';
						$h .= '<div>';
							$h .= '<h4>'.s("Ressources").'</h4>';
							$h .= '<a href="https://blog.ouvretaferme.org/" target="_blank">'.s("Blog").'</a><br/>';
							$h .= '<a href="'.OTF_DEMO_URL.'/ferme/'.\farm\Farm::DEMO.'/series?view=area">'.s("Explorer la ferme démo").'</a><br/>';
							$h .= '<a href="/presentation/producteur">'.s("Liste des fonctionnalités").'</a>';
						$h .= '</div>';
						$h .= '<div>';
							$h .= '<h4>'.s("Discord").'</h4>';
							$h .= '<a href="https://discord.com/channels/1344219338684497961" target="_blank">'.s("Salons de discussion").'</a><br/>';
							$h .= '<a href="https://discord.gg/bdSNc3PpwQ" target="_blank">'.s("Recevoir une invitation").'</a>';
						$h .= '</div>';
						$h .= '<div>';
							$h .= '<h4>'.s("Usage").'</h4>';
							$h .= '<a href="/presentation/faq">'.s("Questions fréquentes").'</a><br/>';
							$h .= '<a href="/presentation/legal">'.s("Mentions légales").'</a><br/>';
							$h .= '<a href="/presentation/service">'.s("Conditions d'utilisation").'</a><br/>';
							$h .= '<a href="https://blog.ouvretaferme.org/nous-contacter">'.s("Nous contacter").'</a><br/>';
						$h .= '</div>';
					$h .= '</div>';
				$h .= '</div>';

			}

		} else {
			$h .= $this->footer;
		}

		if(
			$this->data->eUserOnline->notEmpty() and
			$this->data->logInExternal !== NULL
		) {
			$h .= new user\UserUi()->logOutExternal($this->data->logInExternal[0]);
		}

		return $h;

	}

}
?>
