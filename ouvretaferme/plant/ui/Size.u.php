<?php
namespace plant;

class SizeUi {

	public function displayByPlant(\farm\Farm $eFarm, Plant $ePlant, \Collection $cSize): \Panel {

		$h = '';

		if($cSize->empty()) {
			$h .= '<div class="util-info">';
				$h .= s("Vous n'avez pas encore ajouté de calibre pour cette espèce.");
			$h .= '</div>';
			if($eFarm->canManage()) {
				$h .= '<h4>'.s("Ajouter un calibre").'</h4>';
				$h .= $this->createForm($eFarm, $ePlant, 'inline');
			}
		} else {

			if($eFarm->canManage()) {
				$h .= '<div class="text-end">';
					$h .= '<a href="/plant/size:create?farm='.$eFarm['id'].'&plant='.$ePlant['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouveau calibre").'</a>';
				$h .= '</div>';
			}

			$h .= '<table class="tr-bordered">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Nom").'</th>';
						$h .= '<th>'.s("Commentaire").'</th>';
						$h .= '<th>'.s("Calcul du rendement").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cSize as $eSize) {

					$h .= '<tr>';
						$h .= '<td>';
							$h .= encode($eSize['name']);
						$h .= '</td>';
						$h .= '<td>';
							if($eSize['comment'] === NULL) {
								$h .= '-';
							} else {
								$h .= (new \editor\EditorUi())->value($eSize['comment']);
							}
						$h .= '</td>';
						$h .= '<td>';
							$h .= self::p('yield')->values[$eSize['yield']];
						$h .= '</td>';
						$h .= '<td class="text-end">';
							if($eFarm->canManage()) {
								$h .= '<a href="/plant/size:update?id='.$eSize['id'].'" class="btn btn-outline-secondary">';
									$h .= \Asset::icon('gear-fill');
								$h .= '</a> ';
								$h .= '<a data-ajax="/plant/size:doDelete" data-confirm="'.s("Supprimer ce calibre ?").'" post-id="'.$eSize['id'].'" class="btn btn-outline-secondary">';
									$h .= \Asset::icon('trash-fill');
								$h .= '</a>';
							}
						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		}

		return new \Panel(
			id: 'panel-size-list',
			title: s("Les calibres"),
			subTitle: PlantUi::getPanelHeader($ePlant),
			body: $h,
			close: 'reload'
		);

	}

	public function create(\farm\Farm $eFarm, Plant $ePlant): \Panel {

		return new \Panel(
			title: s("Ajouter un calibre"),
			body: $this->createForm($eFarm, $ePlant, 'panel'),
			close: 'reload'
		);

	}

	protected function createForm(\farm\Farm $eFarm, Plant $ePlant, string $origin): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/plant/size:doCreate', ['data-ajax-origin' => $origin]);

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->hidden('plant', $ePlant['id']);

			$h .= $form->group(
				s("Espèce"),
				$form->fake($ePlant['name'])
			);
			$h .= $form->dynamicGroups(new Size(), ['name', 'yield', 'comment'], [
				'yield' => function(\PropertyDescriber $d) {
					$d->default = TRUE;
				}
			]);
			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return $h;

	}

	public function update(Size $eSize): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/plant/size:doUpdate');

			$h .= $form->hidden('id', $eSize['id']);

			$h .= $form->group(
				s("Espèce"),
				$form->fake($eSize['plant']['name'])
			);

			$h .= $form->dynamicGroups($eSize, ['name', 'yield', 'comment']);
			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un calibre de la ferme"),
			body: $h,
			close: 'reload'
		);

	}

	/**
	 * Describe properties
	 */
	public static function p(string $property): \PropertyDescriber {

		$d = Size::model()->describer($property, [
			'name' => s("Nom"),
			'comment' => s("Observations"),
			'plant' => s("Espèce"),
			'farm' => s("Ferme"),
			'yield' => s("Calcul du rendement"),
		]);

		switch($property) {

			case 'yield' :
				$d->field = 'radio';
				$d->values = [
					TRUE => \Asset::icon('check-lg').' '.s("inclus"),
					FALSE => \Asset::icon('x-lg').' '.s("exclu"),
				];
				$d->attributes = [
					'mandatory' => TRUE
				];
				break;

		}

		return $d;

	}

}
?>
