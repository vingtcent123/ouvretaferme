<?php
namespace user;


class AdminUi {

	public function __construct() {

		\Asset::css('user', 'admin.css');

	}

	/**
	 * Get navigation in identities admin
	 *
	 */
	public function getNavigation(string $selection): string {

		$h = '<div class="nav">';

			$h .= '<a href="/user/admin/" class="nav-link '.($selection === 'user' ? 'active' : '').'">'.s("Parcourir").'</a>';

		if(\Feature::get('user\ban')) {
			$h .= '<a href="/user/admin/ban" class="nav-link '.($selection === 'ban' ? 'active' : '').'">'.s("Bannissements").'</a>';
		}

		$h .= '</div>';

		return $h;

	}

	/**
	 * Display form with default conditions
	 *
	 */
	public function getUsersForm(\Search $search, int $count) {

		$form = new \util\FormUi();

		$h = $form->openAjax('/user/admin/', ['method' => 'get', 'id' => 'form-search']);
			$h .= '<div>';
				$h .= $form->number('id', $search->get('id'), ['placeholder' => 'ID']);
				$h .= $form->text('lastName', $search->get('lastName'), ['placeholder' => 'Nom']);
				$h .= $form->text('email', $search->get('email'), ['placeholder' => 'E-mail']);
				$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
				if($search->notEmpty()) {
					$h .= '<a href="/user/admin/" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				}

				$h .= '<span class="form-search-end">'.p("{value} utilisateur", "{value} utilisateurs", $count).'</span>';
			$h .= '</div>';

		$h .= $form->close();

		return $h;

	}

	public function displayStats(\Collection $cRole, \Collection $cUserDaily, \Collection $cUserActive): string {

		$h = '<div class="user-admin-stats">';

			$h .= '<div class="user-admin-stats-period">'.s("Aujourd'hui").'</div>';
			$h .= $this->getPeriodStats($cRole, $cUserDaily);

			$h .= '<div class="user-admin-stats-period">'.s("30 jours").'</div>';
			$h .= $this->getPeriodStats($cRole, $cUserActive);

		$h .= '</div>';

		return $h;

	}

	protected function getPeriodStats(\Collection $cRole, \Collection $cUserStats): string {

		$h = '<div class="user-admin-stats-roles">';

		foreach($cRole as $eRole) {

			$h .= '<div class="user-admin-stats-role">';
				if($eRole['emoji'] !== NULL) {
					$h .= '<span class="user-admin-stats-emoji">'.$eRole['emoji'].'</span> ';
				}
				$h .= encode($eRole['name']);
			$h .= '</div>';
			$h .= '<div class="user-admin-stats-value">';
				if($cUserStats->offsetExists($eRole['id'])) {
					$h .= $cUserStats[$eRole['id']]['value'];
				} else {
					$h .= '0';
				}
			$h .= '</div>';

		}

		$h .= '</div>';

		return$h;

	}

	/**
	 * Organize the table with the users
	 *
	 */
	public function displayUsers(\Collection $cUser, int $nUser, int $page, \Search $search, bool $isExternalConnected): string {

		if($nUser === 0) {
			return '<div class="util-info">'.s("Il n'y a aucun utilisateur à afficher...").'</div>';
		}

		$h = '<div class="util-overflow-xs stick-sm">';

			$h .= '<table class="tr-bordered tr-even">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="text-center">'.$search->linkSort('id', '#', SORT_DESC).'</th>';
						$h .= '<th>'.$search->linkSort('lastName', s("Nom")).' / '.$search->linkSort('email', s("E-mail")).'</th>';
						$h .= '<th>'.s("Rôle").'</th>';
						$h .= '<th>'.s("Inscription").'</th>';
						$h .= '<th>'.$search->linkSort('ping', s("Connexion"), SORT_DESC).'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';
				foreach($cUser as $eUser) {

					$connectionOptions = [
						'class' => 'dropdown-item'
					];
					if($isExternalConnected) {
						$connectionOptions['class'] .= ' disabled';
						$connectionTitle = s("Déconnectez vous de ce compte pour pouvoir vous reconnecter sur un autre compte.");
					} else {
						$connectionOptions['data-ajax'] = '/user/log:doLoginExternal';
						$connectionOptions['post-user'] = $eUser['id'];
						$connectionTitle = s("Se connecter sur ce compte");
					}

					$auth = [];
					foreach($eUser['auths'] as $eUserAuth) {
						switch($eUserAuth['type']) {
							case 'basic':
								$auth[] = \Asset::icon('envelope', ['title' => s("Email")]);
								break;
							case 'imap':
								$auth[] = \Asset::icon('at', ['title' => s("Imap")]);
								break;
							default:
								$auth[] = ucfirst($eUserAuth['type']);
						}
					}
					$h .= '<tr class="user-admin-status-'.$eUser['status'].'">';
						$h .= '<td class="text-center">'.$eUser['id'].'</td>';
						$h .= '<td>';
							$h .= UserUi::name($eUser).'<br />';
							$h .= '<small>'.encode($eUser['email']).'</small>';
						$h .= '</td>';
						$h .= '<td>';
							if($eUser['role']->empty()) {
								$h .= '/';
							} else {
								if($eUser['role']['emoji'] !== NULL) {
									$h .= '<span style="font-size: 2rem">'.$eUser['role']['emoji'].'</span>';
									$h .= '<span class="hide-xs-down">&nbsp;&nbsp;'.$eUser['role']['name'].'</span>';
								} else {
									$h .= $eUser['role']['name'];
								}
							}
						$h .= '</td>';
						$h .= '<td>';
							$h .= \util\DateUi::numeric($eUser['createdAt'], \util\DateUi::DATE);
						$h .= '</td>';
						$h .= '<td>'.\util\DateUi::numeric($eUser['ping'], \util\DateUi::DATE).'</td>';
						$h .= '<td class="text-center">';
							$h .= '<div>';
								$h .= '<a class="dropdown-toggle btn btn-secondary" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
								$h .= '<div class="dropdown-list">';
									$h .= '<a href="/user/admin/:update?id='.$eUser['id'].'" class="dropdown-item">';
										$h .= s("Modifier l'utilisateur");
									$h .= '</a>';
									$h .= '<a href="/user/admin/ban:form?userToBan='.$eUser['id'].'" class="dropdown-item">';
										$h .= s("Bannir l'utilisateur");
									$h .= '</a>';
									if($eUser['email'] !== NULL) {
										$h .= '<a href="/user/admin/:forgottenPassword?id='.$eUser['id'].'" class="dropdown-item">';
											$h .= s("Générer un lien pour changer le mot de passe");
										$h .= '</a>';
									}
									$h .= '<a href="/user/admin/ban?type=active&user='.$eUser['id'].'" class="dropdown-item">';
										$h .= s("Voir les bannissements");
									$h .= '</a>';
									$h .= '<a '.attrs($connectionOptions).' title="'.$connectionTitle.'">';
										$h .= s("Se connecter en tant que...");
									$h .= '</a> ';
								$h .= '</div>';
							$h .= '</div>';
						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		$h .= \util\TextUi::pagination($page, $nUser / 100);

		return $h;
	}

	public function updateUser(User $eUser): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/user/admin/:doUpdate');

			$h .= $form->hidden('id', $eUser['id']);

			$h .= $form->dynamicGroups($eUser, ['email', 'firstName', 'lastName', 'birthdate']);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: UserUi::name($eUser),
			body: $h,
			close: 'reload'
		);

	}

	public function updateForgottenPassword(User $eUser, ?UserAuth $eUserAuth, int $expires): \Panel {

		$h = '<h4>'.encode($eUser['email']).'</h4>';

		if($eUserAuth === NULL) {
			$h .= '<p class="util-warning">'.p("Cet utilisateur n'est pas éligible au changement de mot de passe.").'</p>';
		} else {

			$h .= '<p class="util-info">'.p("Le lien suivant est à communiquer à l'utilisateur et expirera dans {value} jour.", "Le lien suivant est à communiquer à l'utilisateur et expirera dans {value} jours.", $expires).'</p>';
			$h .= '<pre>'.UserUi::getForgottenPasswordLink($eUserAuth['passwordHash'], $eUser['email']).'</pre>';

		};

		return new \Panel(
			title: s("Lien de réinitialisation de mot de passe"),
			body: $h
		);

	}

}
?>
