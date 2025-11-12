<?php
namespace shop;

class RelationUi {

	public function __construct() {

		\Asset::js('shop', 'relation.js');

	}

	public function query(\PropertyDescriber $d, bool $multiple = FALSE) {

		new \selling\ProductUi()->query($d, $multiple);

		$d->autocompleteReorder = TRUE;
		$d->autocompleteResults = function(Relation $e) {
			return self::getAutocomplete($e);
		};

		$d->group['wrapper'] = 'children';

	}

	public static function getAutocomplete(Relation $eRelation): array {

		return [
			'value' => $eRelation['child']['id']
		] + \selling\ProductUi::getAutocomplete($eRelation['child']['product']);

	}

	public function create(Product $eProduct): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/shop/relation:doCreate');

			$h .= '<div class="util-block-help">';
				$h .= '<h4>'.s("Quel est le rôle des groupes de produits ?").'</h4>';
				$h .= '<p>'.s("Un groupe de produits peut être proposé à la vente dans vos catalogues ou sur vos boutiques en ligne. Lorsqu'un groupe de produits est mis en vente, le choix est laissé au client de choisir un des produits du groupe pour compléter sa commande.").'</p>';
				$h .= '<p>'.s("Cela vous permet par exemple de vendre plusieurs variantes d'un même produit. Vous pourriez par exemple créer un groupe <i>Aromatiques</i> et inclure dans ce groupe les produits <i>Persil</i>, <i>Ciboulette</i> et <i>Basilic</i>. Si votre client veut des <i>Aromatiques</i>, alors il devra choisir parmi les trois possibilités qui s'offrent à lui.").'</p>';
			$h .= '</div>';

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eProduct['farm']);

			if($eProduct['date']->notEmpty()) {
				$h .= $form->hidden('date', $eProduct['date']);
			}

			if($eProduct['catalog']->notEmpty()) {
				$h .= $form->hidden('catalog', $eProduct['catalog']);
			}

			$h .= $form->dynamicGroups($eProduct, ['parentName', 'children*']);

			$h .= $form->group(content: $form->submit(s("Créer le groupe de produits")));

		$h .= $form->close();


		return new \Panel(
			id: 'panel-relation-create-collection',
			title: s("Ajouter un groupe de produits"),
			body: $h,
		);

	}

}
?>
