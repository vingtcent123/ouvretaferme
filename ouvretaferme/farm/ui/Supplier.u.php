<?php
namespace farm;

class SupplierUi {

	public function query(\PropertyDescriber $d, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('buildings');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Tapez le nom du fournisseur...");
		$d->multiple = $multiple;
		$d->group += ['wrapper' => 'supplier'];

		$d->autocompleteUrl = '/farm/supplier:query';
		$d->autocompleteResults = function(Supplier|\Collection $e) {
			return self::getAutocomplete($e);
		};

		$d->attributes = [
			'data-autocomplete-id' => 'supplier'
		];

	}

	public static function getAutocomplete(Supplier $eSupplier): array {

		\Asset::css('media', 'media.css');

		$item = '<div>'.encode($eSupplier['name']).'</div>';

		return [
			'value' => $eSupplier['id'],
			'itemHtml' => $item,
			'itemText' => $eSupplier['name']
		];

	}

	public function manage(\farm\Farm $eFarm, \Collection $cSupplier, \Search $search): string {

		if($cSupplier->empty() and $search->empty()) {

			$h = '<h1>'.s("Fournisseurs de semences et plants").'</h1>';
			$h .= '<div class="util-block-help">';
				$h .= s("Vous n'avez pas encore ajouté de fournisseur de semences et plants à votre ferme. Ajouter des fournisseurs peut être très utile pour faciliter vos commandes !");
			$h .= '</div>';

			$h .= '<h4>'.s("Ajouter un fournisseur").'</h4>';

			$h .= $this->createForm(new Supplier([
				'farm' => $eFarm,
			]), 'inline');

		} else {

			$h = '<div class="util-action">';
				$h .= '<h1>'.s("Fournisseurs de semences et plants").'</h1>';
				$h .= '<div>';
					$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#supplier-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
					$h .= '<a href="/farm/supplier:create?farm='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouveau fournisseur").'</a>';
				$h .= '</div>';
			$h .= '</div>';

			$h .= $this->getSearch($eFarm, $search);

			$h .= '<div class="util-overflow-sm">';

				$h .= '<table class="tr-even">';
					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<th>'.s("Nom").'</th>';
							$h .= '<th></th>';
						$h .= '</tr>';
					$h .= '</thead>';

					$h .= '<tbody>';

					foreach($cSupplier as $eSupplier) {

						$h .= '<tr>';
							$h .= '<td>';
								$h .= $eSupplier->quick('name', encode($eSupplier['name']));
							$h .= '</td>';
							$h .= '<td class="text-end">';

								$h .= '<a href="/farm/supplier:update?id='.$eSupplier['id'].'" class="btn btn-outline-secondary">';
									$h .= \Asset::icon('gear-fill');
								$h .= '</a> ';

								$h .= '<a data-ajax="/farm/supplier:doDelete" data-confirm="'.s("Supprimer ce matériel ?").'" post-id="'.$eSupplier['id'].'" class="btn btn-outline-secondary">';
									$h .= \Asset::icon('trash-fill');
								$h .= '</a>';

							$h .= '</td>';
						$h .= '</tr>';
					}
					$h .= '</tbody>';
				$h .= '</table>';

			$h .= '</div>';

		}

		return $h;

	}

	public function getSearch(\farm\Farm $eFarm, \Search $search): string {

		$h = '<div id="supplier-search" class="util-block-search '.($search->empty() ? 'hide' : '').'">';

			$form = new \util\FormUi();

			$h .= $form->openAjax('/farm/supplier:manage', ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';
					$h .= $form->hidden('farm', $eFarm['id']);
					$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom")]);

					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="/farm/supplier:manage?farm='.$eFarm['id'].'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function create(Supplier $eSupplier): \Panel {

		return new \Panel(
			title: s("Ajouter un nouveau fournisseur"),
			body: $this->createForm($eSupplier, 'panel'),
			close: 'reload'
		);

	}

	public function createForm(Supplier $eSupplier, string $origin): string {

		$eSupplier->expects(['farm']);

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/supplier:doCreate', ['data-ajax-origin' => $origin]);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eSupplier['farm']['id']);
			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eSupplier['farm'])
			);
			$h .= $form->dynamicGroups($eSupplier, ['name*']);
			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return $h;

	}

	public function update(Supplier $eSupplier): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/supplier:doUpdate');

			$h .= $form->hidden('id', $eSupplier['id']);
			$h .= $form->dynamicGroups($eSupplier, ['name']);
			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier le fournisseur"),
			body: $h,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Supplier::model()->describer($property, [
			'name' => s("Nom du fournisseur"),
		]);

		switch($property) {

			case 'id' :
				$d->autocompleteBody = function(\util\FormUi $form, Supplier $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']->empty() ? NULL : $e['farm']['id']
					];
				};
				(new SupplierUi())->query($d);
				break;

		}

		return $d;

	}


}
?>
