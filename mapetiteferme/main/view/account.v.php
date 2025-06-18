<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	\Asset::css('user', 'user.css');

	$h = '<div class="user-account-header">';
		$h .= (new \media\UserVignetteUi())->getCamera($data->eUserOnline, size: '5rem');
		$h .= '<h1>'.s("Mon compte").'</h1>';
	$h .= '</div>';

	$t->header = $h;

	$h = '<div class="util-buttons">';

		$h .= '<a href="/company/public:create" class="bg-secondary util-button">';

			$h .= '<div>';
				$h .= '<h4>'.s("Créer une autre ferme").'</h4>';
			$h .= '</div>';
			$h .= \Asset::icon('house-door-fill');

		$h .= '</a>';

		$h .= '<a href="/user/settings:updateUser" class="bg-secondary util-button">';

			$h .= '<div>';
				$h .= '<h4>'.s("Modifier mes informations personnelles").'</h4>';
			$h .= '</div>';
			$h .= \Asset::icon('person-fill');

		$h .= '</a>';

		if($data->canUpdate['email']) {

			$h .= '<a href="/user/settings:updateEmail" class="bg-secondary util-button">';

				$h .= '<div>';
					$h .= '<h4>'.s("Changer mon adresse e-mail").'</h4>';
					$h .= '<div class="util-button-text">'.encode($data->eUserOnline['email']).'</div>';
				$h .= '</div>';
				$h .= \Asset::icon('envelope-fill');

			$h .= '</a>';

		}

		if($data->canUpdate['drop']) {

			$h .= '<a href="/user/settings:dropAccount" class="bg-danger util-button">';

				$h .= '<h4>';
				if($data->userDeletedAt) {
					$h .= s("Annuler la fermeture de mon compte");
				} else {
					$h .= s("Fermer mon compte");
				}
				$h .= '</h4>';
				$h .= \Asset::icon('trash');

			$h .= '</a>';

		}

	$h .= '</div>';

	echo $h;

});
?>
