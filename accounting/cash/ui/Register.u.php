<?php
namespace cash;

class RegisterUi {

	public function __construct() {

		\Asset::css('cash', 'cash.css');

	}

	public static function getName(Register $eRegister): string {

		$eRegister->expects([
			'paymentMethod' => ['name']
		]);

		return s("Cahier de caisse pour {value}", '<span class="util-badge" style="background-color: '.$eRegister['color'].'">'.encode($eRegister['paymentMethod']['name']).'</span>');

	}

	public function getHeader(Register $eRegisterCurrent, \Collection $cRegister): string {

		if($cRegister->empty()) {
			return '<h1>'.s("Cahiers de caisse").'</h1>';
		}

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= '<a class="util-action-navigation h-menu-wrapper" data-dropdown="bottom-start" data-dropdown-hover="true">';
					$h .= \farm\FarmUi::getNavigation();
					$h .= '<span class="h-menu-label">'.self::getName($eRegisterCurrent).'</span>';
				$h .= '</a>';
				$h .= '<div class="dropdown-list bg-secondary">';
					$h .= '<div class="dropdown-title">'.s("Mes cahiers de caisse").'</div>';

					foreach($cRegister as $eRegister) {

						if($eRegister['status'] === Register::INACTIVE) {
							$h .= '<div class="dropdown-subtitle">'.s("Anciens cahiers").'</div>';
						}

						$h .= '<a href="'.\farm\FarmUi::urlCash($eRegister).'" class="dropdown-item '.($eRegister['id'] === $eRegisterCurrent['id'] ? 'selected' : '').'">';
							$h .= self::getName($eRegister);
						$h .= '</a> ';
					}

					$eFarm = \farm\Farm::getConnected();

					if((new Register(['farm' => $eFarm]))->canCreate()) {
						$h .= '<div class="dropdown-divider"></div>';
						$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/register:create" class="dropdown-item">';
							$h .= \Asset::icon('plus-circle').' '.s("Nouveau cahier de caisse");
						$h .= '</a> ';
					}
				$h .= '</div>';
			$h .= '</h1>';
			$h .= '<div>';

			if($eRegisterCurrent->canWrite()) {

				$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('gear-fill').'</a>';
				$h .= '<div class="dropdown-list bg-primary">';
					$h .= '<div class="dropdown-title">'.self::getName($eRegisterCurrent).'</div>';
					$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/register:update?id='.$eRegisterCurrent['id'].'" class="dropdown-item">'.s("Paramétrer le cahier").'</a>';

					if($eRegisterCurrent->acceptDelete()) {

						if($eRegisterCurrent->canDelete()) {

							$h .= '<div class="dropdown-divider"></div>';
							$h .= '<div class="dropdown-subtitle">'.\Asset::icon('exclamation-circle').'  '.s("Zone de danger").'  '.\Asset::icon('exclamation-circle').'</div>';
							$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/register:doDelete" post-id="'.$eRegisterCurrent['id'].'" class="dropdown-item" data-confirm="'.s("Voulez-vous réellement supprimer de manière irréversible ce cahier de caisse ?").'">'.s("Supprimer le cahier").'</a>';

						}

					} else {
						$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/register:doUpdateStatus" post-id="'.$eRegisterCurrent['id'].'" post-status="'.Register::INACTIVE.'" class="dropdown-item">'.s("Désactiver le cahier").'</a>';
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

			$h .= $form->dynamicGroups($eRegister, ['color', 'paymentMethod*', 'account']);

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

			$h .= $form->dynamicGroups($eRegister, ['color', 'account']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-register-update',
			title: s("Modifier le cahier {value}", self::getName($eRegister)),
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
				$d->attributes = ['mandatory' => TRUE];
				break;

			case 'account':

				$d->labelAfter = \util\FormUi::info(s("Si vous tenez une comptabilité selon le plan comptable agricole, indiquez le numéro de compte auquel est lié ce cahier de caisse pour que Ouvretaferme puisse générer vos écritures."));

				$d->group += ['wrapper' => 'account'];
				$d->autocompleteDefault = fn(Register $e) => $e[$property] ?? NULL;

				$query = [];

				foreach(CashSetting::CLASSES as $position => $account) {
					$query['classPrefixes['.$position.']'] = $account;
				}

				new \account\AccountUi()->query($d, GET('farm', 'farm\Farm'), query: $query);

				break;

		}

		return $d;

	}

}
?>
