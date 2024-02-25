<?php
namespace farm;

class FarmerUi {

	public function __construct() {
		\Asset::css('farm', 'farm.css');
	}

	public function getMyFarms(\Collection $cFarm): string {

		$h = '<div class="farmer-farms">';

		foreach($cFarm as $eFarm) {
			$h .= (new FarmUi())->getPanel($eFarm);
		}

		$h .= '</div>';

		return $h;

	}

	public function getNoFarms(): string {

		$h = '<h2>'.s("Ma ferme").'</h2>';
		$h .= '<div class="util-block-help">';
			$h .= '<p>'.s("Vous êtes producteur et vous venez de vous inscrire sur {siteName}. Pour commencer à utiliser tous les outils numériques développés pour vous sur la plateforme, configurez maintenant votre ferme en renseignant quelques informations de base !").'</p>';
		$h .= '</div>';
		$h .= '<div class="util-buttons">';
			$h .= '<a href="/farm/farm:create" class="bg-secondary util-button">';
				$h .= '<div>';
					$h .= '<h4>'.s("Démarrer la création de ma ferme").'</h4>';
				$h .= '</div>';
				$h .= \Asset::icon('house-door-fill');
			$h .= '</a>';
		$h .= '</div>';

		return $h;

	}

	public function createUser(\farm\Farm $eFarm): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/farmer:doCreateUser');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$eUser = new \user\User();

			$h .= $form->dynamicGroups($eUser, ['firstName', 'lastName*'], [
				'firstName' => function($d) use ($form) {
					$d->after =  \util\FormUi::info(s("Facultatif"));
				}
			]);

			$h .= $form->group(
				content: $form->submit(s("Créer l'utilisateur"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Créer un utilisateur fantôme pour la ferme"),
			body: $h,
			close: 'reload'
		);

	}

	public function updateUser(\farm\Farm $eFarm, \user\User $eUser): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/farmer:doUpdateUser');

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->hidden('user', $eUser['id']);

			$h .= $form->dynamicGroups($eUser, ['firstName', 'lastName'], [
				'firstName' => function($d) use ($form) {
					$d->after =  \util\FormUi::info(s("Facultatif"));
				}
			]);

			$h .= $form->group(
				content: $form->submit(s("Modifier l'utilisateur"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un utilisateur de la ferme"),
			body: $h,
			close: 'reload'
		);

	}

	public function create(Farmer $eFarmer, Farmer $eFarmerLink): \Panel {

		return new \Panel(
			title: s("Inviter un utilisateur dans l'équipe"),
			body: $this->createForm($eFarmer, $eFarmerLink, 'panel'),
			close: 'reload'
		);

	}

	public function manage(Farm $eFarm, \Collection $cFarmer, \Collection $cFarmerInvite, \Collection $cFarmerGhost): string {

		if($cFarmer->empty()) {

			$h = '<h1>'.s("L'équipe").'</h1>';

			$h .= '<div class="util-info">';
				$h .= s("Il n'y a encore personne dans l'équipe de la ferme.");
			$h .= '</div>';

			$h .= '<h4>'.s("Ajouter un premier utilisateur dans l'équipe").'</h4>';
			$h .= $this->createForm(new Farmer([
				'farm' => $eFarm
			]), 'inline');

		} else {

			$h = '<div class="util-action">';

				$h .= '<h1>'.s("L'équipe").'</h1>';

				$h .= '<div>';
					$h .= '<a href="/farm/farmer:create?farm='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Inviter un utilisateur dans l'équipe").'</a>';
				$h .= '</div>';

			$h .= '</div>';

			$h .= '<div class="util-buttons">';

				foreach($cFarmer as $eFarmer) {

					$ePresence = \hr\PresenceUi::getRelevant($eFarmer['user']['cPresence']);

					$properties = [];

					if($eFarmer['farmGhost'] === FALSE) {
						$properties[] = self::p('role')->values[$eFarmer['role']];
					}

					if($ePresence->notEmpty()) {

						switch($ePresence['when'] ) {

							case 'now' :
								if($ePresence['to'] === NULL) {
									$properties[] = s("Actuellement présent à la ferme sans date de départ");
								} else {
									$properties[] = s("Actuellement présent à la ferme jusqu'au {value}", \util\DateUi::numeric($ePresence['to']));
								}
								break;

							case 'past' :
								$properties[] = s("Dernière présence à la ferme du {from} au {to}", ['from' => \util\DateUi::numeric($ePresence['from']), 'to' => \util\DateUi::numeric($ePresence['to'])]);
								break;

							case 'future' :
								if($ePresence['to'] === NULL) {
									$properties[] = s("Prochaine présence à la ferme à partir du {value}", \util\DateUi::numeric($ePresence['from']));
								} else {
									$properties[] = s("Prochaine présence à la ferme du {from} au {to}", ['from' => \util\DateUi::numeric($ePresence['from']), 'to' => \util\DateUi::numeric($ePresence['to'])]);
								}
								break;

						}

					} else {
						$properties[] = \Asset::icon('exclamation-triangle-fill').'&nbsp;&nbsp;'.s("Aucune période de présence à la ferme enregistrée, cet utilisateur ne pourra pas participer aux tâches de la ferme !");
					}

					$h .= '<a href="/farm/farmer:show?id='.$eFarmer['id'].'" class="util-button bg-secondary">';
						$h .= '<div>';
							$h .= '<h4>';
								if($eFarmer['farmGhost']) {
									$h .= \Asset::icon('snapchat').' ';
								}
								$h .= \user\UserUi::name($eFarmer['user']);
							$h .= '</h4>';
							$h .= '<div class="util-button-text">';
								$h .= implode(' | ', $properties);
							$h .= '</div>';
							if($ePresence->empty()) {
								$h .= '<div class="mt-1">';
									$h .= '<div class="btn btn-transparent">';
										$h .= s("Ajouter une présence");
									$h .= '</div>';
								$h .= '</div>';
							}
						$h .= '</div>';
						$h .= \user\UserUi::getVignette($eFarmer['user'], '4rem');
					$h .= '</a>';

				}

			$h .= '</div>';

			if($cFarmerInvite->notEmpty()) {

				$h .= '<h3>'.s("Les invitations en cours").'</h3>';

				$h .= '<div class="util-buttons">';

					foreach($cFarmerInvite as $eFarmer) {

						$h .= '<div class="util-button bg-primary">';
							$h .= '<div>';
								$h .= '<div>';
									if($eFarmer['invite']->empty()) {
										$h .= \Asset::icon('exclamation-triangle-fill').' '.s("Invitation expirée");
									} else if($eFarmer['invite']->isValid() === FALSE) {
										$h .= s("Invitation expirée pour {value}", '<b>'.encode($eFarmer['invite']['email']).'</b>');
									} else {
										$h .= s("Invitation envoyée à {value}", '<b>'.encode($eFarmer['invite']['email']).'</b>');
									}
								$h .= '</div>';
								$h .= '<div class="mt-1">';
									$h .= '<a data-ajax="/farm/farmer:doDeleteInvite" post-id="'.$eFarmer['id'].'" class="btn btn-transparent">';
										$h .= s("Supprimer");
									$h .= '</a> ';
									$h .= '<a data-ajax="/farm/invite:doExtends" post-id="'.$eFarmer['invite']['id'].'" data-confirm="'.s("Voulez-vous vraiment renvoyer un mail d'invitation à cette personne ?").'" class="btn btn-transparent">';
										$h .= s("Renvoyer l'invitation");
									$h .= '</a>';
								$h .= '</div>';
							$h .= '</div>';
							$h .= \user\UserUi::getVignette($eFarmer['user'], '4rem');
						$h .= '</div>';

					}

				$h .= '</div>';

			}

		}

		$h .= '<div class="util-block">';

		if($cFarmerGhost->empty()) {

			$h .= '<h2>'.\Asset::icon('snapchat').' '.s("Les utilisateurs fantômes").'</h2>';

			$h .= '<div class="util-block-help">';
				$h .= s("Vous pouvez créer des utilisateurs fantômes pour votre ferme. Ils peuvent être intégrés à l'équipe de votre ferme et vous pouvez les affecter à vos interventions. Par contre, les utilisateurs fantômes n'ont <u>ni adresse e-mail, ni mot de passe</u> et il est donc impossible de se connecter avec.");
			$h .= '</div>';

			$h .= '<a href="/farm/farmer:createUser?farm='.$eFarm['id'].'" class="btn btn-secondary">'.s("Créer un premier utilisateur fantôme").'</a>';

		} else {

			$h .= '<div class="h-line">';
				$h .= '<h2>'.\Asset::icon('snapchat').' '.s("Les utilisateurs fantômes").'</h2>';
				$h .= '<a href="/farm/farmer:createUser?farm='.$eFarm['id'].'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Créer un utilisateur fantôme").'</a>';
			$h .= '</div>';

			$h .= '<p class="util-block-help">';
				$h .= s("Les utilisateurs fantômes peuvent être intégrés à l'équipe de votre ferme et vous pouvez les affecter à vos interventions. Par contre, les utilisateurs fantômes n'ont <u>ni adresse e-mail, ni mot de passe</u> et il est donc impossible de se connecter avec.");
			$h .= '</p>';

			$h .= '<div class="util-overflow-sm">';

				$h .= '<table class="tr-bordered farm-farmer-table">';
					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<th>'.s("Nom").'</th>';
							$h .= '<th>'.s("Créé le").'</th>';
							$h .= '<th>'.s("Équipe").'</th>';
							$h .= '<th></th>';
						$h .= '</tr>';
					$h .= '</thead>';

					$h .= '<tbody>';

					foreach($cFarmerGhost as $eFarmer) {

						$h .= '<tr>';
							$h .= '<td>'.\Asset::icon('snapchat').' '.\user\UserUi::name($eFarmer['user']).'</td>';
							$h .= '<td>';
								$h .= \util\DateUi::numeric($eFarmer['user']['createdAt'], \util\DateUi::DATE);
							$h .= '</td>';
							$h .= '<td>';
								if($eFarmer['status'] === Farmer::OUT) {
									$h .= '<span class="color-muted">'.\Asset::icon('x').' '.s("Sorti").'</span>';
								} else {
									$h .= '<span class="color-success">'.\Asset::icon('check').' '.s("Intégré").'</span>';
								}
							$h .= '</td>';
							$h .= '<td class="text-end">';

								if($eFarmer['status'] === Farmer::OUT) {
									$h .= '<a data-ajax="/farm/farmer:doUpdateStatus" post-id="'.$eFarmer['id'].'" post-status="'.Farmer::IN.'" class="btn btn-secondary">';
										$h .= \Asset::icon('arrow-up').' '.s("Intégrer à l'équipe");
									$h .= '</a> ';
								}

								$h .= '<a href="/farm/farmer:updateUser?farm='.$eFarm['id'].'&user='.$eFarmer['user']['id'].'" class="btn btn-secondary">';
									$h .= \Asset::icon('gear-fill');
								$h .= '</a>';
								$h .= ' ';

								$h .= match($eFarmer['status']) {
									Farmer::OUT => '<a data-ajax="/farm/farmer:doDeleteUser" post-farm="'.$eFarm['id'].'" post-user="'.$eFarmer['user']['id'].'" class="btn btn-danger" data-confirm="'.s("Cette action est irréversible, voulez-vous réellement supprimer cet utilisateur fantôme ?").'">'.\Asset::icon('trash').'</a>',
									Farmer::IN => '<span class="btn btn-danger" title="'.s("Vous ne pouvez pas supprimer un utilisateur fantôme actuellement intégré à l'équipe !").'" disabled>'.\Asset::icon('trash').'</span>',
									default => ''
								};

							$h .= '</td>';
						$h .= '</tr>';
					}
					$h .= '</tbody>';
				$h .= '</table>';

			$h .= '</div>';

		}

		$h .= '</div>';

		return $h;

	}

	public function showUser(\farm\Farmer $eFarmer, \Collection $cPresence, \Collection $cAbsence): string {

		$h = '<h1>'.\user\UserUi::getVignette($eFarmer['user'], '3rem').' '.\user\UserUi::name($eFarmer['user']).'</h1>';

		$h .= '<dl class="util-presentation util-presentation-1">';


			if($eFarmer['farmGhost']) {

				$h .= '<dt>'.s("Rôle").'</dt>';
				$h .= '<dd>'.s("Fantôme").'</dd>';

			} else {

				$h .= '<dt>'.s("Adresse e-mail").'</dt>';
				$h .= '<dd>'.($eFarmer['user']['visibility'] === \user\User::PUBLIC ? encode($eFarmer['user']['email']) : '<i>'.s("Utilisateur fantôme").'</i>').'</dd>';
				$h .= '<dt>'.s("Rôle").'</dt>';
				$h .= '<dd>'.self::p('role')->values[$eFarmer['role']].'</dd>';

			}

		$h .= '</dl>';

		$h .= '<br/>';

		if($eFarmer['status'] === Farmer::OUT) {

			$h .= '<div class="util-info">'.s("Cet utilisateur a été sorti de l'équipe de la ferme et ne peut donc plus accéder ni être affecté aux interventions sur la ferme.").'</div>';

		} else {

			$h .= '<div>';

				if($eFarmer->canUpdateRole()) {

					$h .= '<a href="/farm/farmer:update?id='.$eFarmer['id'].'" class="btn btn-primary">';
						$h .= s("Configurer l'utilisateur");
					$h .= '</a> ';

				}

				if($eFarmer['farmGhost']) {
					$h .= '<a href="/farm/farmer:create?farm='.$eFarmer['farm']['id'].'&farmer='.$eFarmer['id'].'" class="btn btn-primary">';
						$h .= s("Inviter à créer un compte");
					$h .= '</a> ';
					$h .= '<a data-ajax="/farm/farmer:doUpdateStatus" post-id="'.$eFarmer['id'].'" post-status="'.Farmer::OUT.'" class="btn btn-primary">';
						$h .= s("Sortir de l'équipe");
					$h .= '</a> ';
				} else {

					if($eFarmer['user']->notEmpty()) {
						$h .= '<a data-ajax="/farm/farmer:doDelete" post-id="'.$eFarmer['id'].'" class="btn btn-primary" data-confirm="'.s("Souhaitez-vous réellement retirer cet utilisateur de la ferme ?").'">';
							$h .= s("Sortir de l'équipe");
						$h .= '</a>';
					}

				}

			$h .= '</div>';
			$h .= '<br/>';
			$h .= (new \hr\PresenceUi())->show($eFarmer, $cPresence);
			$h .= '<br/>';
			$h .= (new \hr\AbsenceUi())->show($eFarmer, $cAbsence);

		}

		return $h;

	}

	protected function createForm(Farmer $eFarmer, Farmer $eFarmerLink, string $origin): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/farmer:doCreate', ['data-ajax-origin' => $origin]);

			$h .= $form->asteriskInfo();

			if($eFarmerLink->notEmpty()) {
				$h .= $form->hidden('id', $eFarmerLink['id']);
			}

			$h .= $form->hidden('farm', $eFarmer['farm']['id']);

			$description = '<div class="util-block-help">';
				$description .= '<p>'.s("En invitant un utilisateur à rejoindre l'équipe de votre ferme, vous lui permettrez d'accéder à un grand nombre de données sur votre ferme. Choisissez le rôle que vous donnez à vos invités avec soin :").'</p>';
				$description .= $this->getRoles();
				$description .= '<p>'.s("Pour inviter un utilisateur, saisissez son adresse e-mail. Il recevra un e-mail lui donnant les instructions à suivre, et devra les réaliser dans un délai de trois jours.").'</p>';
			$description .= '</div>';

			$h .= $form->group(content: $description);

			$h .= $form->dynamicGroups($eFarmer, ['email*', 'role*']);

			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();
		
		return $h;

	}
	
	protected function getRoles(): string {
		
		$h = '<ul>';
			$h .= '<li>'.s("<b>Exploitant</b> : accès aux mêmes fonctionnalités que vous").'</li>';
			$h .= '<li>'.s("<b>Saisonnier</b> : peut saisir son propre temps de travail, commenter les interventions et consulter sans modifier les pages sur le planning, les cultures et le parcellaire").'</li>';
			$h .= '<li>'.s("<b>Permanent</b> : comme le saisonnier et peut également saisir de nouvelles interventions, modifier les interventions déjà créées, consulter les pages sur la commercialisation et modifier les ventes et les clients").'</li>';
			$h .= '<li>'.s("<b>Observateur</b> : peut consulter sans modifier les pages sur les cultures, le parcellaire et l'analyse, sans jamais avoir accès au détail de vos ventes et de vos clients").'</li>';
		$h .= '</ul>';

		return $h;
		
	}

	public function update(\farm\Farmer $eFarmer): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/farmer:doUpdate');

			$h .= $form->hidden('id', $eFarmer['id']);

			if($eFarmer->canUpdateRole()) {

				$h .= '<div class="util-block-help">';
					$h .= '<p>'.s("Pour rappel, le rôle que vous donnez à cet utilisateur conditionne les pages auxquelles il pourra accéder sur votre ferme :").'</p>';
					$h .= $this->getRoles();
				$h .= '</div>';

				$h .= $form->dynamicGroup($eFarmer, 'role');

			}

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un utilisateur de la ferme"),
			body: $h,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Farmer::model()->describer($property, [
			'email' => s("Adresse e-mail"),
			'user' => s("Utilisateur"),
			'role' => s("Rôle"),
		]);

		switch($property) {

			case 'email' :
				$d->field = 'email';
				break;

			case 'role' :
				$d->values = [
					Farmer::OWNER => s("Exploitant"),
					Farmer::PERMANENT => s("Permanent"),
					Farmer::SEASONAL => s("Saisonnier"),
					Farmer::OBSERVER => s("Observateur"),
				];
				$d->attributes['mandatory'] = TRUE;
				break;

		}

		return $d;

	}

}
