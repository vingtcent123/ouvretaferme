<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	\Asset::css('user', 'user.css');

	$h = '<div class="user-account-header">';
		$h .= new \media\UserVignetteUi()->getCamera($data->eUserOnline, size: '5rem');
		$h .= '<h1>'.s("Mon compte").'</h1>';
	$h .= '</div>';

	$t->header = $h;

	$h = '<div class="util-buttons">';

		if(\farm\FarmSetting::getPrivilege('access')) {
			$h .= '<a href="/farm/farm:create" class="util-button">';

				$h .= '<div>';
					$h .= '<h4>'.s("Créer une autre ferme").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('house-door-fill');

			$h .= '</a>';

		}

		$h .= '<a href="/user/settings:updateUser" class="util-button">';

			$h .= '<div>';
				$h .= '<h4>'.s("Modifier mes informations personnelles").'</h4>';
			$h .= '</div>';
			$h .= \Asset::icon('person-fill');

		$h .= '</a>';

		if($data->canUpdate['email']) {

			$h .= '<a href="/user/settings:updateEmail" class="util-button">';

				$h .= '<div>';
					$h .= '<h4>'.s("Changer mon adresse e-mail").'</h4>';
					$h .= '<div class="util-button-text">'.encode($data->eUserOnline['email']).'</div>';
				$h .= '</div>';
				$h .= \Asset::icon('envelope-fill');

			$h .= '</a>';

		}

		if($data->nCustomer > 0) {

			$h .= '<a href="/mail/contact:updateOptIn" class="util-button">';

				$h .= '<div>';
					$h .= '<h4>'.s("Gérer mes préférences de communication par e-mail").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('envelope-paper-fill');

			$h .= '</a>';

		}

		if(OTF_DEMO === FALSE) {

			if($data->canUpdate['password']) {

				if($data->canUpdate['cUserAuth']->offsetExists(\user\UserAuth::BASIC)) {
					$text = s("Changer mon mot de passe");
					$span = NULL;
				} else {
					$social = $data->canUpdate['cUserAuth']->first()['type'];
					$span = \Asset::icon('circle-fill').' '.s("Créez un mot de passe pour vous connecter à {siteName} sans passer par {social}", ['social' => ucfirst($social)]);
					$text = s("Créer un mot de passe");
				}

				$h .= '<a href="/user/settings:updatePassword" class="util-button">';

					$h .= '<div>';
						$h .= '<h4>'.$text.'</h4>';
						if($span) {
							$h .= '<span>'.$span.'</span>';
						}
					$h .= '</div>';
					$h .= \Asset::icon('lock-fill');

				$h .= '</a>';

			}

			if($data->canUpdate['drop']) {

				$h .= '<a href="/user/settings:dropAccount" class="util-button util-button-danger">';

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

		}

	$h .= '</div>';

	if($data->eUserOnline['role']['fqn'] === 'customer') {

		$h .= '<h3>'.s("Vous voulez créer votre ferme ?").'</h3>';

		$h .= '<div class="util-block">';
			$h .= '<p>'.s("Vous avez actuellement un compte <i>client</i> que vous pouvez convertir en compte <i>producteur</i> pour créer votre ferme et vous lancer dans l'aventure de la production.<br/>Vous pourrez toujours bien sûr passer commande auprès de vos producteurs préférés.").'</p>';
			$h .= '<a data-ajax="/farm/farmer:become" class="btn btn-secondary" data-confirm="'.s("Passer d'un compte client à un compte producteur ?").'">'.s("Devenir producteur / productrice").'</a>';
		$h .= '</div>';

	}

	echo $h;

});
?>
