<?php
/**
 * Affichage d'une page de navigation
 */
class MainTemplate extends BaseTemplate {

	/**
	 * Display nav ?
	 */
	public bool $nav = TRUE;

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
		\Asset::js('main', 'main.js');

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

	protected function getLogo(string $size): string {

		$h = '<div class="logo-wrapper" style="width: '.$size.'; height: '.$size.'">';
			$h .= '<svg viewBox="0 0 100 100" xml:space="preserve" fill="white" xmlns="http://www.w3.org/2000/svg">';
				$h .= '<path d="m51.942 52.074c-3.2698-2.3914-8.0537-6.2389-9.4642-10.818-1.4783-4.7992-0.63511-10.574 1.8205-14.955 1.0271-1.8322 2.8406-4.0084 4.9389-3.9133 4.8938 0.22193 8.7894 5.5986 10.646 10.132 2.9546 7.216 5.7046 19.033-0.7686 23.38-3.0448 2.0448-6.7056-3.1821-10.316-3.8262-4.0296-0.7188-8.5264-1.6072-12.28 0.02599-3.5162 1.53-6.0331 5.0291-7.859 8.4011-1.3029 2.4061-2.8558 5.3886-1.9553 7.9724 0.9648 2.7684 4.0766 4.8088 6.9502 5.3896 2.6481 0.53521 5.3569-1.0044 7.831-2.0895 2.7949-1.2258 5.355-2.9904 7.7285-4.9089 3.6375-2.9403 5.9521-5.8467 9.8393-10.004"/><path d="m28.381 37.071c-7.5695-5.15-8.2421-6.4251-9.3771-11.146-1.2015-4.9974-0.66061-10.34 1.7334-14.627 1.0242-1.8338 2.8482-3.7115 4.9389-3.9133 3.478-0.33573 7.4638 1.8512 9.3095 4.8181 5.0531 8.123 8.6603 23.592 0.56755 28.694-2.2187 1.3986-4.4837-2.9058-7.0303-3.5328-5.0388-1.2406-10.787-2.2918-15.566-0.26733-3.5308 1.496-6.1621 4.9623-7.859 8.4011-1.2911 2.6164-2.6286 5.9719-1.4356 8.6345 1.3428 2.9969 5.0538 5.0169 8.3285 5.2627 2.2871 0.17166 4.2701-1.3886 6.3001-2.766 2.3933-1.624 4.8963-3.1081 7.1272-4.9489 3.6175-2.9848 6.1861-5.6654 10.073-9.8227"/><path d="m77.745 68.448c-6.1504-6.9726-10.372-5.4062-11.501-13.148-0.70918-4.8634 1.1917-10.117 2.7712-14.482 1.4139-1.695 3.2473-3.107 5.3057-3.3995 3.6145-0.54142 6.8948 2.3928 8.7807 5.7251 4.1709 7.4707 3.4494 17.88-2.3054 28.607-3.3298-2.2302-5.3295-3.6509-9.0495-4.9835-3.7199-1.3325-9.2805-2.5958-13.053-1.0579-3.5068 1.4251-6.328 4.3827-8.66 7.5728-1.6256 2.2495-3.1369 4.9504-2.743 7.7367 0.54918 3.9562 3.0176 4.9894 5.532 6.075 2.8856 1.2458 6.4604 0.8757 9.4276-0.16064 2.9786-1.0403 5.4846-3.3436 7.5063-5.7658 3.0741-3.6831 5.2244-6.3616 10.882-8.4674"/>';
			$h .= '<path d="m29.565 36.684-1.0765 1.3839 64.855 45.792 3.7016-4.7379"/></svg>';
		$h .= '</div>';

		return $h;

	}

	protected function getNav(): string {
		return $this->getDefaultNav();
	}

	protected function getDefaultNav(?string $center = NULL): string {

		if($this->nav === FALSE) {
			return '';
		}

		$h = '<div class="nav-wrapper nav-default-wrapper container">';

		if($center === NULL) {

			$h .= '<div class="nav-title">';
				$h .= '<a class="nav-logo" href="'.Lime::getUrl().'">';
					$h .= $this->getLogo('100%');
					$h .= '<div class="nav-logo-home">'.\Asset::icon('house-door-fill').'</div>';
				$h .= '</a>';

				$h .= '<a href="'.Lime::getUrl().'">'.Lime::getDomain().'</a>';
			$h .= '</div>';

		} else {

			$h .= $center;

		}

		if(
			Lime::getHost() === LIME_HOST and // Uniquement sur www
			currentDate() <= '2024-10-10'
		) {
			$h .= '<a href="https://blog.mapetiteferme.app/" class="nav-news" target="_blank">';
				$h .= '<div class="nav-news-title">'.Asset::icon('cursor-fill').' '.s("Nouveautés").'</div>';
				$h .= '<div class="nav-news-name">'.s("5 octobre 2024").'</div>';
			$h .= '</a>';
		}

		$h .= '<ul class="nav-actions">';

			if($this->data->userDeletedAt) {
				$h .= '<li class="nav-deleted nav-action-optional">';
					$h .= '<a href="/main/account" class="nav-item" title="'.(new user\DropUi())->getCloseMessage($this->data->userDeletedAt).'"><span>'.\Asset::icon('exclamation-triangle-fill').'&nbsp;'.s("Compte en cours de fermeture").'&nbsp;'.\Asset::icon('exclamation-triangle-fill').'</span></a>';
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

			$h .= '<a class="nav-user nav-item" data-dropdown="bottom" data-dropdown-hover="TRUE">';
				$h .= \user\UserUi::getVignette($data->eUserOnline, '1.75rem');
				$h .= \Asset::icon('chevron-down');
			$h .= '</a>';

			$h .= '<div class="dropdown-list bg-primary">';

				$h .= '<div class="dropdown-title">'.\user\UserUi::name($data->eUserOnline).'</div>';

				$h .= '<a href="'.Lime::getUrl().'" class="dropdown-item">'.s("Accueil").'</a>';

				if(Lime::getHost() === LIME_HOST) {
					$h .= '<a href="/main/account" class="dropdown-item">'.s("Mon compte").'</a>';
				} else {
					$h .= '<a href="'.Lime::getUrl().'/main/account" class="dropdown-item" target="_blank">'.s("Mon compte").'</a>';
				}

				$h .= '<form method="post" action="'.Lime::getUrl().'/user/log:out">';
					$h .= '<button type="submit" class="dropdown-item">'.s("Me déconnecter").'</button>';

					if(Lime::getHost() === LIME_HOST) {
						$h .= '<input type="hidden" name="redirect" value="'.Lime::getProtocol().'://'.SERVER('HTTP_HOST').'"/>';
					} else {
						$h .= '<input type="hidden" name="redirect" value="'.Lime::getUrl().'"/>';
					}
				$h .='</form>';

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

				$h .= '<div class="footer-content">';
					$h .= '<div class="footer-content-text">';
						$h .= Lime::getHost();
					$h .= '</div>';
					$h .= '<div class="footer-content-legal">';
						$h .= '<div>';
							//$h .= '<a href="/presentation/faq">'.s("Foire aux questions").'</a><br/>';
							$h .= '<a href="/presentation/engagements">'.s("Engagements environnementaux").'</a><br/>';
							$h .= '<a href="https://discord.gg/5eYnaSBYMt">'.Asset::icon('discord').'&nbsp;'.s("Signaler un problème").'</a><br/>';
						$h .= '</div>';
						$h .= '<div>';
							$h .= '<a href="/presentation/legal">'.s("Mentions légales").'</a><br/>';
							$h .= '<a href="/presentation/pricing">'.s("Tarifs").'</a><br/>';
							//$h .= '<a href="/presentation/service">'.s("Conditions d'utilisation").'</a><br/>';
							//$h .= '<a href="/presentation/fonctionnalites">'.s("Fonctionnalités").'</a>';
						$h .= '</div>';
					$h .= '</div>';
				$h .= '</div>';

		} else {
			$h .= $this->footer;
		}

		if(
			$this->data->eUserOnline->notEmpty() and
			$this->data->logInExternal !== NULL
		) {
			$h .= new user\UserUi()->logOutExternal($this->data->logInExternal[0]);
		}

		\Asset::jsContent('<script>
				document.addEventListener("DOMContentLoaded", () => {
					Main.checkBrevo('.($this->hasCRM ? 'true' : 'false').')
				})
			</script>');

		return $h;

	}

}
?>
