<?php
namespace shop;

class RangeUi {

	public function toggle(Range $eRange) {

		return \util\TextUi::switch([
			'id' => 'range-switch-'.$eRange['id'],
			'data-ajax' => $eRange->canWrite() ? '/shop/range:doUpdateStatus' : NULL,
			'post-id' => $eRange['id'],
			'post-status' => ($eRange['status'] === Range::ACTIVE) ? Range::INACTIVE : Range::ACTIVE
		], $eRange['status'] === Range::ACTIVE);

	}

	public function create(Range $eRange): \Panel {

		$form = new \util\FormUi();

		if($eRange['cCatalog']->empty()) {

			$h = '<div class="util-empty">';
				$h .= s("Vous n'avez pas de catalogue éligible pour cette boutique.");
				$h .= ' '.match($eRange['shop']['type']) {
					Shop::PRIVATE => s("Étant donné que cette boutique est destinée aux clients particuliers, vous ne pouvez lui associer que des catalogues qui utilisent votre grille tarifaire pour les particuliers."),
					Shop::PRO => s("Étant donné que cette boutique est destinée aux clients professionnels, vous ne pouvez lui associer que des catalogues qui utilisent votre grille tarifaire pour les professionnels.")
				};
			$h .= '</div>';

			$h .= '<a href="'.\farm\FarmUi::urlShopCatalog($eRange['farm']).'" class="btn btn-primary">'.s("Créer un catalogue").'</a>';

		} else {

			$h = '<div class="util-info">';
				$h .= match($eRange['shop']['type']) {
					Shop::PRIVATE => s("Cette boutique est destinée aux clients particuliers, et seuls vos catalogues qui utilisent votre grille tarifaire pour les particuliers sont affichés."),
					Shop::PRO => s("Cette boutique est destinée aux clients professionnels, et seuls vos catalogues qui utilisent votre grille tarifaire pour les professionnels sont affichés."),
				};
			$h .= '</div>';

			$h .= $form->openAjax('/shop/range:doCreate');

				$h .= $form->asteriskInfo();

				$h .= $form->hidden('shop', $eRange['shop']['id']);
				$h .= $form->hidden('farm', $eRange['farm']['id']);
				$h .= $form->dynamicGroups($eRange, ['catalog*', 'regular*']);
				$h .= $form->group(
					content: $form->submit(s("Associer à la boutique"))
				);

			$h .= $form->close();

		}

		return new \Panel(
			id: 'panel-range-create',
			title: s("Associer un catalogue à cette boutique"),
			body: $h,
			close: 'reload'
		);

	}

	public function update(Range $eRange): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/shop/range:doUpdate');

			$h .= $form->hidden('id', $eRange['id']);
			$h .= $form->dynamicGroups($eRange, ['regular']);
			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-range-update',
			title: s("Modifier un rayon"),
			body: $h,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Range::model()->describer($property, [
			'catalog' => s("Catalogue"),
			'regular' => s("Régularité des ventes"),
		]);

		switch($property) {

			case 'catalog' :
				$d->values = fn(Range $e) => $e['cCatalog'] ?? $e->expects(['cCatalog']);
				break;

			case 'regular' :
				$d->field = 'radio';
				$d->attributes['mandatory'] = TRUE;
				$d->values = [
					TRUE => s("Catalogue activé par défaut à chaque nouvelle vente dans la boutique"),
					FALSE => s("Catalogue désactivé par défaut lors d'une nouvelle vente, c'est à vous de l'activer lorsque vous voulez autoriser les commandes !"),
				];
				break;

		}

		return $d;

	}


}
?>
