<?php
namespace cash;

class RegisterUi {

	public function __construct() {

		\Asset::css('cash', 'cash.css');

	}

	public static function getCircle(Register $eRegister, ?string $size = NULL): string {

		$eRegister->expects(['color']);

		return '<div class="register-circle" style="background-color: '.$eRegister['color'].'; '.($size ? 'width: '.$size.'; height: '.$size.';' : '').'"></div>';

	}

	public function getHeader(Register $eRegisterCurrent, \Collection $cRegister): string {

		if($cRegister->empty()) {
			return '<h1>'.s("Cahier de caisse").'</h1>';
		}

		$h = '<div class="util-action">';
			$h .= '<div>';
				$h .= '<h1 class="mb-0">';
					$h .= '<a class="util-action-navigation h-menu-wrapper" data-dropdown="bottom-start" data-dropdown-hover="true">';
						$h .= \farm\FarmUi::getNavigation();
						$h .= '<span class="h-menu-label">'.encode($eRegisterCurrent['name']).'</span>';
					$h .= '</a>';
					$h .= '<div class="dropdown-list bg-secondary">';
						$h .= '<div class="dropdown-title">'.s("Mes boutiques").'</div>';

						foreach($cRegister as $eRegister) {

							if($eRegister['status'] === Register::INACTIVE) {
								$h .= '<div class="dropdown-subtitle">'.s("Anciens cahiers").'</div>';
							}

							$h .= '<a href="'.\farm\FarmUi::urlCash($eRegister).'" class="dropdown-item '.($eRegister['id'] === $eRegisterCurrent['id'] ? 'selected' : '').'">';
								$h .= encode($eRegister['name']);
								if($eRegister['shared']) {
									$h .= ' <span class="util-badge bg-primary">'.\Asset::icon('people-fill').' '.s("Collective").'</span>';
								}
							$h .= '</a> ';
						}

						$eFarm = \farm\Farm::getConnected();

						if((new Register(['farm' => $eFarm]))->canCreate()) {
							$h .= '<div class="dropdown-divider"></div>';
							$h .= '<a href="'.\farm\FarmUi::urlConnected().'/register/:create" class="dropdown-item">';
								$h .= \Asset::icon('plus-circle').' '.s("Nouveau cahier de caisse");
							$h .= '</a> ';
						}
					$h .= '</div>';
				$h .= '</h1>';
			$h .= '</div>';
			$h .= '<div>';

			if($eRegisterCurrent->canWrite()) {

				$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('gear-fill').'</a>';
				$h .= '<div class="dropdown-list bg-primary">';
					$h .= '<div class="dropdown-title">'.encode($eRegisterCurrent['name']).'</div>';
					$h .= '<a href="/cash/configuration:update?id='.$eRegisterCurrent['id'].'" class="dropdown-item">'.s("Paramétrer la boutique").'</a>';
					$h .= '<a href="/register/:website?id='.$eRegisterCurrent['id'].'&farm='.$eFarm['id'].'" class="dropdown-item">'.s("Intégrer la boutique sur un site internet").'</a>';
					$h .= '<a href="'.\farm\FarmUi::urlCommunicationsContact($eFarm).'?register='.$eRegisterCurrent['id'].'&source=register" class="dropdown-item">'.s("Obtenir les adresses e-mail des clients").'</a>';

					if($eRegisterCurrent->canDelete()) {
						$h .= '<div class="dropdown-divider"></div>';
						$h .= '<div class="dropdown-subtitle">'.\Asset::icon('exclamation-circle').'  '.s("Zone de danger").'  '.\Asset::icon('exclamation-circle').'</div>';
						$h .= '<a data-ajax="/register/:doDelete" post-id="'.$eRegisterCurrent['id'].'" class="dropdown-item" data-confirm="'.s("Voulez-vous réellement supprimer de manière irréversible cette boutique ? Les ventes qui ont eu lieu dans la boutique ne seront pas supprimées.").'">'.s("Supprimer la boutique").'</a>';
					}

				$h .= '</div>';

			}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function create(\cash\Register $eRegister, bool $start = FALSE): \Panel {

		$eRegister->expects(['cPaymentMethod']);

		$eRegister['paymentMethod'] = $eRegister['cPaymentMethod']->find(fn($ePaymentMethod) => $ePaymentMethod['fqn'] === 'cash', limit: 1);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/cash/register:doCreate', ['class' => 'register-form-unknown']);

			$h .= $form->asteriskInfo();

			$h .= $form->dynamicGroup($eRegister, 'color');
			$h .= $form->dynamicGroup($eRegister, 'paymentMethod*');
			$h .= $form->dynamicGroup($eRegister, 'account');

			$h .= $form->group(
				content: $form->submit(
					$start ?
						s("Configurer le cahier de caisse") :
						s("Ajouter le cahier de caisse")
				)
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-register-create',
			title: s("Ajouter un cahier de caisse"),
			body: $h
		);

	}

	public function update(\cash\Register $eRegister): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/cash/register:doUpdate');

			$h .= $form->hidden('id', $eRegister['id']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-register-update',
			title: s("Modifier un client"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = \cash\Register::model()->describer($property, [
			'paymentMethod' => s("Moyen de paiement"),
			'account' => s("Numéro de compte lié"),
			'color' => s("Couleur du cahier"),
		]);

		switch($property) {

			case 'paymentMethod' :
				$d->values = fn(\cash\Register $e) => $e['cPaymentMethod'] ?? $e->expects(['cPaymentMethod']);
				$d->labelAfter = \util\FormUi::info(s("Un cahier de caisse est toujours lié à un moyen de paiement, qui ne pourra pas être modifié par la suite."));
				break;

			case 'account':

				$d->labelAfter = \util\FormUi::info(s("Si vous tenez une comptabilité selon le plan comptable agricole, indiquez le numéro de compte auquel est lié ce cahier de caisse pour que Ouvretaferme puisse générer vos écritures."));

				$d->group += ['wrapper' => 'account'];
				$d->autocompleteDefault = fn(Register $e) => $e[$property] ?? NULL;

				$query = [];

				foreach(CashSetting::ACCOUNTS as $position => $account) {
					$query['classPrefixes['.$position.']'] = $account;
				}

				new \account\AccountUi()->query($d, GET('farm', 'farm\Farm'), query: $query);

				break;

		}

		return $d;

	}

}
?>
