<?php
namespace main;

Class AdminUi {

	public function __construct() {
		\Asset::css('main', 'admin.css');
	}

	/**
	 * Get navigation in admin
	 */
	public function getNavigation(string $selection): string {

		$packages = [
			'user' => s("Utilisateurs"),
			'farm' => s("Fermes"),
			'dev' => \Asset::icon('code-slash'),
		];

		$h = '<div class="nav">';

			foreach($packages as $package => $name) {
				$h .= '<a href="/'.$package.'/admin/" class="nav-link '.($selection === $package ? 'active' : '').'">'.$name.'</a>';
			}

		$h .= '</div>';

		return $h;

	}

}
?>
