<?php
namespace shop;

class DepartmentUi {

	public function __construct() {

		\Asset::css('shop', 'department.css');

	}

	public static function getVignette(Department $eDepartment, string $size): string {

		\Asset::css('shop', 'department.css');

		$eDepartment->expects(['icon']);

		$h = '<div class="department-vignette" style="'.\media\MediaUi::getSquareCss($size).';">';

		if(str_starts_with($eDepartment['icon'], 'bs-')) {
			$h .= \Asset::icon(substr($eDepartment['icon'], 3), ['style' => 'width: 90%; height: 90%']);
		} else {
			$h .= '<svg width="100%" height="100%"><use xlink:href="'.\Asset::getPath('shop', 'departments.svg', 'image').'#'.strtolower($eDepartment['icon']).'"/></svg>';
		}

		$h .= '</div>';

		return $h;


	}

	public function getManage(\shop\Shop $eShop, \Collection $cDepartment): string {

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
			$h .= '<tbody>';

			foreach($cDepartment as $eDepartment) {

				$h .= '<tr>';
					$h .= '<td class="td-min-content">';
						$h .= '<b>'.$eDepartment['position'].'.</b>';
					$h .= '</td>';
					$h .= '<td class="td-min-content">';
						$h .= self::getVignette($eDepartment, '2.5rem');
					$h .= '</td>';
					$h .= '<td>';
						$h .= $eDepartment->quick('name', encode($eDepartment['name']));
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
			$h .= $form->dynamicGroups($eDepartment, ['name*', 'icon']);
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
			$h .= $form->dynamicGroups($eDepartment, ['name', 'icon']);
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
			'name' => s("Nom du rayon"),
			'icon' => s("Icône du rayon")
		]);

		switch($property) {

			case 'icon' :
				$d->field = function(\util\FormUi $form, Department $e) {

					$selectedIcon = $e->empty() ? NULL : $e['icon'];

					$h = '<div class="department-vignette-wrapper">';

						foreach(Department::getIcons() as $icon) {

							$h .= '<label class="department-vignette-icon">';
								$h .= DepartmentUi::getVignette(new Department(['icon' => $icon]), '3rem');
								$h .= $form->inputRadio('icon', $icon, selectedValue: $selectedIcon, attributes: ['class' => 'hide']);
							$h .= '</label>';

						}

					$h .= '</div>';

					return $h;

				};
				break;

		}

		return $d;

	}


}
?>
