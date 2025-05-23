<?php
namespace shop;

class RangeUi {

	public function toggle(Range $eRange) {

		return \util\TextUi::switch([
			'id' => 'range-switch-'.$eRange['id'],
			'disabled' => $eRange->canWrite() === FALSE,
			'data-ajax' => $eRange->canWrite() ? '/shop/range:doUpdateStatus' : NULL,
			'post-id' => $eRange['id'],
			'post-status' => ($eRange['status'] === Range::AUTO) ? Range::MANUAL : Range::AUTO
		], $eRange['status'] === Range::AUTO, s("Automatique"), s("Manuelle"), self::getStatusLabel(Range::AUTO), self::getStatusLabel(Range::MANUAL));

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
				$h .= $form->dynamicGroups($eRange, ['catalog*', 'status*']);

				if($eRange['cDateAvailable']->notEmpty()) {
					$h .= $form->dynamicGroup($eRange, 'datesList');
				}

				$h .= $form->group(
					content: $form->submit(s("Associer à la boutique"))
				);

			$h .= $form->close();

		}

		return new \Panel(
			id: 'panel-range-create',
			title: s("Associer un catalogue à cette boutique"),
			body: $h
		);

	}

	public function dissociate(Range $eRange): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/shop/range:doDissociate');

			$h .= $form->hidden('id', $eRange['id']);
			$h .= $form->group(s("Catalogue"), $form->fake($eRange['catalog']['name']));
			$h .= $form->group(s("Retirer le catalogue de toutes les ventes en cours sur la boutique"), $form->yesNo('date'));
			$h .= $form->group(
				content: $form->submit(s("Dissocier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-range-update',
			title: s("Dissocier un catalogue de la boutique"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Range::model()->describer($property, [
			'catalog' => s("Catalogue"),
			'status' => s("Activation"),
			'datesList' => s("Activer le catalogue sur des ventes en cours"),
		]);

		switch($property) {

			case 'catalog' :
				$d->values = fn(Range $e) => $e['cCatalog'] ?? $e->expects(['cCatalog']);
				break;

			case 'status' :
				$d->field = 'radio';
				$d->attributes['mandatory'] = TRUE;
				$d->values = [
					Range::AUTO => s("<u>Automatique</u> → {value}", self::getStatusLabel(Range::AUTO)),
					Range::MANUAL => s("<u>Manuelle</u> → {value}", self::getStatusLabel(Range::MANUAL)),
				];
				break;

			case 'datesList' :
				$d->field = function(\util\FormUi $form, Range $e) {

					$e->expects(['cDateAvailable']);

					if($e['cDateAvailable']->empty()) {
						return '<div class="util-info">'.s("Aucune vente en cours.").'</div>';
					}

					$dates = [];

					foreach($e['cDateAvailable'] as $eDate) {
						$dates[$eDate['id']] = s("Livraison du {value}", \util\DateUi::numeric($eDate['deliveryDate']));
					}

					return $form->checkboxes('datesList[]', $dates);

				};
				$d->group = [
					'wrapper' => 'datesList',
					'for' => FALSE
				];
				break;

		}

		return $d;

	}

	private static function getStatusLabel(string $status): string {

		return match($status) {
			Range::AUTO => s("Catalogue activé par défaut à chaque nouvelle livraison dans la boutique"),
			Range::MANUAL => s("Catalogue à activer manuellement à chaque nouvelle livraison pour autoriser les commandes"),
		};

	}


}
?>
