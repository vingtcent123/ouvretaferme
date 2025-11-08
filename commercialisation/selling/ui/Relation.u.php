<?php
namespace selling;

class RelationUi {

	public function __construct() {

		\Asset::js('selling', 'relation.js');
		\Asset::css('selling', 'relation.css');

	}

	public function displayByParent(Product $eProduct, \Collection $cRelation): string {

		if($cRelation->empty()) {
			return '<div class="util-info">'.s("Il n'y a aucun produit dans ce groupe.").'</div>';
		}

		$h = '<table class="tr-even">';

			$h .= '<thead>';
				$h .= '<tr>';
					if($cRelation->count() > 1) {
						$h .= '<th></th>';
					}
					$h .= '<th>'.s("Produit").'</th>';
					if($cRelation->count() > 1) {
						$h .= '<th></th>';
					}
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';

			foreach($cRelation as $eRelation) {

				$eProductChild = $eRelation['child'];

				$h .= '<tr>';

					if($cRelation->count() > 1) {

						$h .= '<td class="td-min-content">';
							$h .= '<b>'.$eRelation['position'].'.</b>';
						$h .= '</td>';

					}
					$h .= '<td>';
						$h .= ProductUi::getVignette($eProductChild, '2rem').'  ';
						$h .= ProductUi::link($eProductChild);
					$h .= '</td>';

					if($cRelation->count() > 1) {

						$h .= '<td class="td-min-content">';

							if($eRelation['position'] > 1) {
								$h .= '<a data-ajax="/selling/relation:doIncrementPosition" post-id='.$eRelation['id'].'" post-increment="-1" class="btn btn-sm btn-secondary">'.\Asset::icon('arrow-up').'</a> ';
							} else {
								$h .= '<a class="btn btn-sm disabled">'.\Asset::icon('arrow-up').'</a> ';
							}

							if($eRelation['position'] !== $cRelation->count()) {
								$h .= '<a data-ajax="/selling/relation:doIncrementPosition" post-id='.$eRelation['id'].'" post-increment="1" class="btn btn-sm btn-secondary">'.\Asset::icon('arrow-down').'</a> ';
							} else {
								$h .= '<a class="btn btn-sm disabled">'.\Asset::icon('arrow-down').'</a> ';
							}
						$h .= '</td>';

					}

					$h .= '<td class="td-min-content" style="white-space: nowrap">';

						$h .= '<a data-ajax="/selling/relation:doDelete" post-id="'.$eRelation['id'].'" class="btn btn-outline-secondary">';
							$h .= \Asset::icon('trash-fill');
						$h .= '</a>';

					$h .= '</td>';
				$h .= '</tr>';
			}
			$h .= '</tbody>';
		$h .= '</table>';

		$eRelationCreate = new Relation([
			'farm' => $eProduct['farm'],
			'parent' => $eProduct
		]);

		if($eRelationCreate->canCreate()) {

			$form = new \util\FormUi();
			$id = 'relation-create-'.$eRelationCreate['parent']['id'];

			$h .= '<div id="'.$id.'" data-parent="'.$eRelationCreate['parent']['id'].'">';

				$h .= $form->dynamicField($eRelationCreate, 'child', function($d) use($id) {
					$d->autocompleteDispatch = '#'.$id;
					$d->attributes['class'] = 'form-control-lg';
				});

			$h .= '</div>';

		}

		return $h;

	}

	public function createCollection(\farm\Farm $eFarm, \Collection $cProduct): \Panel {

		$eRelationReference = new Relation([
			'farm' => $eFarm,
			'cProduct' => $cProduct
		]);

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/relation:doCreateCollection');

			$h .= '<div class="util-block-help">';
				$h .= '<h4>'.s("Quel est le rôle des groupes de produits ?").'</h4>';
				$h .= '<p>'.s("Un groupe de produits peut, comme le reste de vos produits, être proposé à la vente sur vos boutiques en ligne. Lorsqu'un groupe de produits est mis en vente, le choix est laissé au client de choisir un des produits du groupe pour compléter sa commande.").'</p>';
				$h .= '<p>'.s("Cela vous permet par exemple de vendre plusieurs variantes d'un même produit. Vous pourriez par exemple créer un groupe <i>Aromatiques</i> et inclure dans ce groupe les produits <i>Persil</i>, <i>Ciboulette</i> et <i>Basilic</i>. Si votre client veut des <i>Aromatiques</i>, alors il devra choisir par les trois possibilités qui s'offrent à lui.").'</p>';
			$h .= '</div>';

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicGroups(new Product(), ['name', 'groupSelection']);
			$h .= $form->dynamicGroup($eRelationReference, 'children*');

			$h .= $form->group(content: $form->submit(s("Créer le groupe de produits")));

		$h .= $form->close();


		return new \Panel(
			id: 'panel-relation-create-collection',
			title: s("Ajouter un groupe de produits"),
			body: $h,
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Sale::model()->describer($property, [
			'children' => s("Produits"),
		]);

		switch($property) {

			case 'children' :
				$d->autocompleteDefault = fn(Relation $e) => $e['cProduct'] ?? new \Collection();
				$d->autocompleteBody = function(\util\FormUi $form, Relation $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id'],
						'profileComposition' => FALSE,
						'profileGroup' => FALSE
					];
				};
				new ProductUi()->query($d, multiple: TRUE);
				$d->group['class'] = 'relation-wrapper';
				break;

			case 'child' :
				$d->placeholder = s("Ajouter un produit");
				$d->autocompleteBody = function(\util\FormUi $form, Relation $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id'],
						'profileComposition' => FALSE,
						'profileGroup' => FALSE
					];
				};
				new ProductUi()->query($d);
				break;

		}

		return $d;

	}

}
?>
