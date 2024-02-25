<?php
namespace plant;

class QualityUi {

	public function displayByPlant(\farm\Farm $eFarm, Plant $ePlant, \Collection $cQuality): \Panel {

		$h = '<div class="util-action">';
			$h .= '<h3>'.encode($ePlant['name']).'</h3>';
			if($eFarm->canManage()) {
				$h .= '<div>';
					$h .= '<a href="/plant/quality:create?farm='.$eFarm['id'].'&plant='.$ePlant['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouveau critère de qualité").'</a>';
				$h .= '</div>';
			}
		$h .= '</div>';

		$h .= '<div class="util-info">';
			$h .= s("Les critères de qualité permettent de renseigner le niveau de qualité des produits récoltés. Cela peut être par exemple un calibre ou un tout autre indicateur pertinent comme par exemple &laquo; Déclassé &raquo;.");
		$h .= '</div>';

		if($cQuality->empty()) {
			$h .= '<div class="util-info">';
				$h .= s("Vous n'avez pas encore ajouté de critère de qualité pour cette espèce.");
			$h .= '</div>';
			if($eFarm->canManage()) {
				$h .= '<h4>'.s("Ajouter un critère de qualité").'</h4>';
				$h .= $this->createForm($eFarm, $ePlant, 'inline');
			}
		} else {

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

				foreach($cQuality as $eQuality) {

					$h .= '<tr>';
						$h .= '<td>';
							$h .= encode($eQuality['name']);
						$h .= '</td>';
						$h .= '<td>';
							if($eQuality['comment'] === NULL) {
								$h .= '-';
							} else {
								$h .= (new \editor\EditorUi())->value($eQuality['comment']);
							}
						$h .= '</td>';
						$h .= '<td>';
							$h .= self::p('yield')->values[$eQuality['yield']];
						$h .= '</td>';
						$h .= '<td class="text-end">';
							if($eFarm->canManage()) {
								$h .= '<a href="/plant/quality:update?id='.$eQuality['id'].'" class="btn btn-outline-secondary">';
									$h .= \Asset::icon('gear-fill');
								$h .= '</a> ';
								$h .= '<a data-ajax="/plant/quality:doDelete" data-confirm="'.s("Supprimer ce critère de qualité ?").'" post-id="'.$eQuality['id'].'" class="btn btn-outline-secondary">';
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
			id: 'panel-quality-list',
			title: s("Les critères de qualité"),
			body: $h,
			close: 'reload'
		);

	}

	public function create(\farm\Farm $eFarm, Plant $ePlant): \Panel {

		return new \Panel(
			title: s("Ajouter un critère de qualité"),
			body: $this->createForm($eFarm, $ePlant, 'panel'),
			close: 'reload'
		);

	}

	protected function createForm(\farm\Farm $eFarm, Plant $ePlant, string $origin): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/plant/quality:doCreate', ['data-ajax-origin' => $origin]);

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->hidden('plant', $ePlant['id']);

			$h .= $form->group(
				s("Espèce"),
				PlantUi::link($ePlant)
			);
			$h .= $form->dynamicGroups(new Quality(), ['name', 'yield', 'comment'], [
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

	public function update(Quality $eQuality): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/plant/quality:doUpdate');

			$h .= $form->hidden('id', $eQuality['id']);

			$h .= $form->group(
				s("Espèce"),
				PlantUi::link($eQuality['plant'])
			);

			$h .= $form->dynamicGroups($eQuality, ['name', 'yield', 'comment']);
			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un critère de qualité de la ferme"),
			body: $h,
			close: 'reload'
		);

	}

	/**
	 * Describe properties
	 */
	public static function p(string $property): \PropertyDescriber {

		$d = Quality::model()->describer($property, [
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
