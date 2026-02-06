<?php
namespace cash;

class RegisterUi {

	public function __construct() {

		\Asset::css('cash', 'cash.css');
		\Asset::js('cash', 'cash.js');

	}

	public static function getName(Register $eRegister): string {

		$eRegister->expects([
			'paymentMethod' => ['name']
		]);

		return s("Journal de caisse pour {value}", self::getBadge($eRegister));

	}

	public static function getBadge(Register $eRegister): string {

		$eRegister->expects([
			'paymentMethod' => ['name']
		]);

		return '<span class="util-badge" style="background-color: '.$eRegister['color'].'">'.encode($eRegister['paymentMethod']['name']).'</span>';

	}

	public function getHeader(Register $eRegisterCurrent, \Collection $cRegister): string {

		if($cRegister->empty()) {
			return '<h1>'.s("Journaux de caisse").'</h1>';
		}

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= '<a class="util-action-navigation h-menu-wrapper" data-dropdown="bottom-start" data-dropdown-hover="true">';
					$h .= \farm\FarmUi::getNavigation();
					$h .= '<span class="h-menu-label">'.self::getName($eRegisterCurrent).'</span>';
				$h .= '</a>';
				$h .= '<div class="dropdown-list bg-secondary">';
					$h .= '<div class="dropdown-title">'.s("Mes journaux de caisse").'</div>';

					foreach($cRegister as $eRegister) {

						if($eRegister['status'] === Register::INACTIVE) {
							$h .= '<div class="dropdown-subtitle">'.s("Anciens journaux").'</div>';
						}

						$h .= '<a href="'.\farm\FarmUi::urlCash($eRegister).'" class="dropdown-item '.($eRegister['id'] === $eRegisterCurrent['id'] ? 'selected' : '').'">';
							$h .= self::getName($eRegister);
						$h .= '</a> ';
					}

					$eFarm = \farm\Farm::getConnected();

					if((new Register(['farm' => $eFarm]))->canCreate()) {
						$h .= '<div class="dropdown-divider"></div>';
						$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/register:create" class="dropdown-item">';
							$h .= \Asset::icon('plus-circle').' '.s("Nouveau journal de caisse");
						$h .= '</a> ';
					}
				$h .= '</div>';
			$h .= '</h1>';
			$h .= '<div>';

				if($eRegisterCurrent->canWrite()) {

					$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('gear-fill').'</a>';
					$h .= '<div class="dropdown-list bg-primary">';
						$h .= '<div class="dropdown-title">'.self::getName($eRegisterCurrent).'</div>';
						$h .= '<a href="'.\farm\FarmUi::urlConnected().'/cash/register:update?id='.$eRegisterCurrent['id'].'" class="dropdown-item">'.s("Paramétrer le journal").'</a>';

						if($eRegisterCurrent->acceptDelete()) {

							if($eRegisterCurrent->canDelete()) {

								$h .= '<div class="dropdown-divider"></div>';
								$h .= '<div class="dropdown-subtitle">'.\Asset::icon('exclamation-circle').'  '.s("Zone de danger").'  '.\Asset::icon('exclamation-circle').'</div>';
								$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/register:doDelete" post-id="'.$eRegisterCurrent['id'].'" class="dropdown-item" data-confirm="'.s("Voulez-vous réellement supprimer de manière irréversible ce journal de caisse ?").'">'.s("Supprimer le journal").'</a>';

							}

						} else {

							$h .= match($eRegisterCurrent['status']) {
								Register::ACTIVE => '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/register:doUpdateStatus" post-id="'.$eRegisterCurrent['id'].'" post-status="'.Register::INACTIVE.'" class="dropdown-item" data-confirm="'.s("Vous ne pourrez plus ajouter de nouvelles opérations dans ce journal et les éventuelles opérations non validées seront supprimées. Continuer ?").'">'.s("Désactiver le journal").'</a>',
								Register::INACTIVE => '<a data-ajax="'.\farm\FarmUi::urlConnected().'/cash/register:doUpdateStatus" post-id="'.$eRegisterCurrent['id'].'" post-status="'.Register::ACTIVE.'" class="dropdown-item">'.s("Réactiver le journal").'</a>'
							};

						}

					$h .= '</div>';

				}

				if($eRegisterCurrent['operations'] > 0) {
					$h .= ' <a '.attr('onclick', 'Lime.Search.toggle("#cash-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a>';
				}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function create(\cash\Register $eRegister, bool $start = FALSE): \Panel {

		$eRegister->expects(['cPaymentMethod']);

		$eRegister['paymentMethod'] = $eRegister['cPaymentMethod']->find(fn($ePaymentMethod) => $ePaymentMethod['fqn'] === \payment\MethodLib::CASH, limit: 1);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/cash/register:doCreate', ['class' => 'register-form-unknown']);

			$h .= $form->asteriskInfo();

			$h .= $form->dynamicGroups($eRegister, ['color', 'paymentMethod*', 'account']);

			$h .= $form->group(
				content: $form->submit(
					$start ?
						s("Configurer le journal de caisse") :
						s("Ajouter le journal de caisse")
				)
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-register-create',
			title: s("Ajouter un journal de caisse"),
			body: $h
		);

	}

	public function update(\cash\Register $eRegister): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/cash/register:doUpdate');

			$h .= $form->hidden('id', $eRegister['id']);

			$h .= $form->dynamicGroups($eRegister, ['color', 'account', 'bankAccount']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-register-update',
			title: s("Modifier le journal {value}", self::getName($eRegister)),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = \cash\Register::model()->describer($property, [
			'paymentMethod' => s("Moyen de paiement"),
			'account' => s("Numéro de compte lié à la caisse"),
			'bankAccount' => s("Numéro de compte par défaut pour les dépôts et retraits bancaires"),
			'color' => s("Couleur du journal"),
		]);

		switch($property) {

			case 'paymentMethod' :
				$d->values = fn(\cash\Register $e) => $e['cPaymentMethod'] ?? $e->expects(['cPaymentMethod']);
				$d->labelAfter = \util\FormUi::info(s("Un journal de caisse est toujours lié à un moyen de paiement, qui ne pourra pas être modifié par la suite."));
				$d->attributes = ['mandatory' => TRUE];
				break;

			case 'account':

				$d->labelAfter = \util\FormUi::info(s("Si vous tenez une comptabilité selon le plan comptable agricole, indiquez le numéro de compte auquel est lié ce journal de caisse pour que Ouvretaferme puisse générer vos écritures."));

				$d->group += ['wrapper' => 'account'];
				$d->autocompleteDefault = fn(Register $e) => $e[$property] ?? NULL;

				$query = [];

				foreach(CashSetting::CLASSES as $position => $account) {
					$query['classPrefixes['.$position.']'] = $account;
				}

				new \account\AccountUi()->query($d, GET('farm', 'farm\Farm'), query: $query);

				break;

			case 'bankAccount':

				$d->labelAfter = \util\FormUi::info(s("Si vous tenez une comptabilité selon le plan comptable agricole, indiquez le numéro de compte par défaut auquel seront liées les opérations bancaires."));

				$d->group += ['wrapper' => 'bankAccount'];
				$d->autocompleteDefault = fn(Register $e) => $e[$property] ?? NULL;

				new \account\AccountUi()->query($d, GET('farm', 'farm\Farm'), query: ['classPrefix' => \account\AccountSetting::BANK_ACCOUNT_CLASS]);

				break;

		}

		return $d;

	}

}
?>
