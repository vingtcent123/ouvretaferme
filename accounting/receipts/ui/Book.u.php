<?php
namespace receipts;

class BookUi {

	public function __construct() {

		\Asset::css('receipts', 'receipts.css');
		\Asset::js('receipts', 'receipts.js');

	}

	public static function getName(Book $eBook): string {

		$eBook->expects([
			'paymentMethod' => ['name']
		]);

		return s("Journal n°{position} pour {method}", [
			'position' => $eBook['id'],
			'method' => self::getBadge($eBook)
		]);

	}

	public static function getBadge(Book $eBook): string {

		$eBook->expects([
			'paymentMethod' => ['name']
		]);

		return '<span class="util-badge" style="background-color: '.$eBook['color'].'">'.encode($eBook['paymentMethod']['name']).'</span>';

	}

	public function getHeader(Book $eBook): string {

		if($eBook->empty()) {
			return '<h1>'.s("Livre des recettes").'</h1>';
		}

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= \farm\FarmUi::getNavigation();
				$h .= '<span class="h-menu-label">'.self::getName($eBook).'</span>';
			$h .= '</h1>';
			$h .= '<div>';

				if($eBook->canWrite()) {

					$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('gear-fill').'</a>';
					$h .= '<div class="dropdown-list bg-primary">';
						$h .= '<div class="dropdown-title">'.self::getName($eBook).'</div>';
						$h .= '<a href="'.\farm\FarmUi::urlConnected().'/receipts/book:update?id='.$eBook['id'].'" class="dropdown-item">'.s("Paramétrer le journal").'</a>';

						if($eBook->acceptDelete()) {

							if($eBook->canDelete()) {

								$h .= '<div class="dropdown-divider"></div>';
								$h .= '<div class="dropdown-subtitle">'.\Asset::icon('exclamation-circle').'  '.s("Zone de danger").'  '.\Asset::icon('exclamation-circle').'</div>';
								$h .= '<a data-ajax="'.\farm\FarmUi::urlConnected().'/receipts/book:doDelete" post-id="'.$eBook['id'].'" class="dropdown-item" data-confirm="'.s("Voulez-vous réellement supprimer de manière irréversible ce livre des recettes ?").'">'.s("Supprimer le journal").'</a>';

							}

						}

						$h .= match($eBook['status']) {
							Book::ACTIVE => '<a data-ajax="'.\farm\FarmUi::urlConnected().'/receipts/book:doUpdateStatus" post-id="'.$eBook['id'].'" post-status="'.Book::INACTIVE.'" class="dropdown-item" data-confirm="'.s("Vous ne pourrez plus ajouter de nouvelles opérations dans ce journal et les éventuelles opérations non validées seront supprimées. Continuer ?").'">'.s("Désactiver le journal").'</a>',
							Book::INACTIVE => '<a data-ajax="'.\farm\FarmUi::urlConnected().'/receipts/book:doUpdateStatus" post-id="'.$eBook['id'].'" post-status="'.Book::ACTIVE.'" class="dropdown-item">'.s("Réactiver le journal").'</a>'
						};

					$h .= '</div>';

				}

				if($eBook['operations'] > 0) {
					$h .= ' <a '.attr('onclick', 'Lime.Search.toggle("#line-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a>';
				}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function create(): string {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/receipts/book:doCreate');

			$h .= '<div class="util-info">';
				$h .= '<p>'.s("Ouvretaferme vous permet de tenir un livre des recettes pour les exercices comptables pour lesquels vous avez indiqué tenir exclusivement un livre des recettes.").'</p>';
			$h .= '</div>';

			$h .= $form->submit(s("Ouvrir un livre des recettes"), ['class' => 'btn btn-xl btn-primary', 'data-waiter' => s("Ouverture en cours...")]);

		$h .= $form->close();

		return $h;

	}

	public function update(\receipts\Book $eBook): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax(\farm\FarmUi::urlConnected().'/receipts/book:doUpdate');

			$h .= $form->hidden('id', $eBook['id']);

			$h .= $form->dynamicGroups($eBook, ['color']);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-book-update',
			title: s("Modifier le journal {value}", self::getName($eBook)),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = \receipts\Book::model()->describer($property, [
			'paymentMethod' => s("Moyen de paiement"),
			'hasAccounts' => s("Saisir des numéros de compte pour les opérations de caisse"),
			'account' => s("Numéro de compte lié à la caisse"),
			'bankAccount' => s("Numéro de compte par défaut pour les dépôts et retraits bancaires"),
			'color' => s("Couleur du journal"),
		]);

		switch($property) {

			case 'paymentMethod' :
				$d->values = fn(\receipts\Book $e) => $e['cPaymentMethod'] ?? $e->expects(['cPaymentMethod']);
				$d->labelAfter = \util\FormUi::info(s("Un livre des recettes est toujours lié à un moyen de paiement, qui ne pourra pas être modifié par la suite."));
				$d->attributes = ['mandatory' => TRUE];
				break;

			case 'account':

				$d->labelAfter = \util\FormUi::info(s("Si vous tenez une comptabilité selon le plan comptable agricole, indiquez le numéro de compte auquel est lié ce livre des recettes pour que Ouvretaferme puisse générer vos écritures."));

				$d->group += ['wrapper' => 'account'];
				$d->autocompleteDefault = fn(Book $e) => $e[$property] ?? NULL;

				$query = [];

				foreach(ReceiptsSetting::CLASSES as $position => $account) {
					$query['classPrefixes['.$position.']'] = $account;
				}

				new \account\AccountUi()->query($d, GET('farm', 'farm\Farm'), query: $query);

				break;

			case 'hasAccounts':
				$d->field = 'yesNo';
				$d->labelAfter = \util\FormUi::info(s("En saisissant directement des numéros de compte dans les opérations de caisse, vous pourrez importer votre livre des recettes très facilement dans votre comptabilité."));
				break;

			case 'bankAccount':

				$d->labelAfter = \util\FormUi::info(s("Si vous tenez une comptabilité selon le plan comptable agricole, indiquez le numéro de compte par défaut auquel seront liées les opérations bancaires."));

				$d->group += ['wrapper' => 'bankAccount'];
				$d->autocompleteDefault = fn(Book $e) => $e[$property] ?? NULL;

				new \account\AccountUi()->query($d, GET('farm', 'farm\Farm'), query: ['classPrefix' => \account\AccountSetting::BANK_ACCOUNT_CLASS]);

				break;

		}

		return $d;

	}

}
?>
