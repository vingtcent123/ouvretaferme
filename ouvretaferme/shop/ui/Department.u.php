<?php
namespace shop;

class DepartmentUi {

	public function getManage(\shop\Shop $eShop, \Collection $cShare, \Collection $ccRange, \Collection $cDepartment): string {

		if($cDepartment->empty()) {

			$h = '';

			if($eShop->canWrite()) {

				$h .= '<div class="util-block-help">';
					$h .= '<h4>'.s("Ajouter des rayons sur la boutique").'</h4>';
					$h .= '<p>'.s("Les rayons sont une option des boutiques collectives qui permet de classer les produits proposés par les producteurs. Vous pouvez par exemple ajouter des rayons <i>Légumes</i>, <i>Crèmerie</i>, <i>Confitures</i>... et associer les catalogues de vos producteurs au rayon correspondant.").'</p>';
					$h .= '<p>'.s("Lorsque vous créez des rayons, vous avez la possibilité de grouper les produits par rayon plutôt que par producteur sur la boutique.").'</p>';
					$h .= '<a href="/shop/department:create?shop='.$eShop['id'].'" class="btn btn-secondary">'.s("Ajouter un rayon").'</a>';
				$h .= '</div>';

			} else {
				$h .= '<div class="util-empty">'.s("Il n'y a pas de rayon configuré sur cette boutique.").'</div>';
			}

			return $h;

		}

		$h = '<div class="util-title">';

			$h .= '<div></div>';

			$h .= '<div>';
				$h .= '<a href="/shop/department:create?shop='.$eShop['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '. s("Nouveau rayon") .'</a>';
			$h .= '</div>';

		$h .= '</div>';

		$h .= '<table class="tr-even">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th></th>';
					$h .= '<th>'.self::p('name')->label.'</th>';
					$h .= '<th>'.s("Catalogues du rayon").'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';

			$ccRangeDepartment = $ccRange
				->linearize()
				->reindex('department');

			foreach($cDepartment as $eDepartment) {

				$cRange = $ccRangeDepartment[$eDepartment['id']] ?? new \Collection();

				$h .= '<tr>';
					$h .= '<td class="td-min-content">';
						$h .= '<b>'.$eDepartment['position'].'.</b>';
					$h .= '</td>';
					$h .= '<td>';
						$h .= $eDepartment->quick('name', encode($eDepartment['name']));
					$h .= '</td>';
					$h .= '<td>';
						if($cRange->empty()) {
							$h .= '-';
						} else {
							$h .= '<ul class="mb-0">';
								foreach($cRange as $eRange) {
									$eFarm = $cShare[$eRange['farm']['id']]['farm'];
									$h .= '<li>';
										$h .= s("{catalog} de {farm}", ['catalog' => '<b>'.encode($eRange['catalog']['name']).'</b>', 'farm' => ' '.\farm\FarmUi::getVignette($eFarm, '2rem').'  '.encode($eFarm['name'])]);
									$h .= '</li>';
								}
							$h .= '</ul>';
						}
					$h .= '</td>';
					$h .= '<td class="td-min-content">';

						if($eDepartment['position'] > 1) {
							$h .= '<a data-ajax="/shop/department:doIncrementPosition" post-id='.$eDepartment['id'].'" post-increment="-1" class="btn btn-secondary">'.\Asset::icon('arrow-up').'</a>  ';
						} else {
							$h .= '<a class="btn disabled">'.\Asset::icon('arrow-up').'</a>  ';
						}

						if($eDepartment['position'] !== $cDepartment->count()) {
							$h .= '<a data-ajax="/shop/department:doIncrementPosition" post-id='.$eDepartment['id'].'" post-increment="1" class="btn btn-secondary">'.\Asset::icon('arrow-down').'</a>  ';
						} else {
							$h .= '<a class="btn disabled">'.\Asset::icon('arrow-down').'</a>  ';
						}

						$h .= '<a href="/shop/department:update?id='.$eDepartment['id'].'" class="btn btn-outline-secondary">';
							$h .= \Asset::icon('gear-fill');
						$h .= '</a> ';

						$h .= '<a data-ajax="/shop/department:doDelete" data-confirm="'. s("Supprimer ce rayon ?") .'" post-id="'.$eDepartment['id'].'" class="btn btn-outline-secondary">';
							$h .= \Asset::icon('trash-fill');
						$h .= '</a>';

					$h .= '</td>';
				$h .= '</tr>';
			}
			$h .= '</tbody>';
		$h .= '</table>';

		return $h;

	}

	public function create(\shop\Shop $eShop): \Panel {

		$eDepartment = new Department();

		$form = new \util\FormUi();

		$h = $form->openAjax('/shop/department:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('shop', $eShop['id']);
			$h .= $form->dynamicGroups($eDepartment, ['name*']);
			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-department-create',
			title: s("Ajouter un nouveau rayon"),
			body: $h
		);

	}

	public function update(Department $eDepartment): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/shop/department:doUpdate');

			$h .= $form->hidden('id', $eDepartment['id']);
			$h .= $form->dynamicGroups($eDepartment, ['name']);
			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-department-update',
			title: s("Modifier un rayon"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Department::model()->describer($property, [
			'name' => s("Nom du rayon")
		]);

		switch($property) {

		}

		return $d;

	}


}
?>
