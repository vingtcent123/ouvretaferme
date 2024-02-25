<?php
namespace plant;

class VarietyUi {

	public static function getSeedsWeight1000(Variety $e, int $seeds): ?string {

		if($e['weightSeed1000'] === NULL) {
			return NULL;
		}

		$weight = ($seeds / 1000 * $e['weightSeed1000']);

		if($weight > 1000) {
			return \main\UnitUi::getValue(round($weight / 1000, 1), 'kg');
		} else {
			return \main\UnitUi::getValue(round($weight), 'gram');
		}

	}

	public static function getPlantsWeight(Variety $e, int $plants): ?string {

		if($e['plant'] === NULL) {
			return NULL;
		}

		$weight = ($plants / $e['numberPlantKilogram']);

		return \main\UnitUi::getValue(ceil($weight * 10) / 10, 'kg');

	}

	public function displayByPlant(\farm\Farm $eFarm, Plant $ePlant, \Collection $cVariety, \Collection $cSupplier): \Panel {

		$h = '<div class="util-action">';
			$h .= '<h3>'.encode($ePlant['name']).'</h3>';
			if($eFarm->canManage()) {
				$h .= '<div>';
					$h .= '<a href="/plant/variety:create?farm='.$eFarm['id'].'&plant='.$ePlant['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouvelle variété").'</a>';
				$h .= '</div>';
			}
		$h .= '</div>';

		if($cVariety->empty()) {
			$h .= '<div class="util-info">';
				$h .= s("Vous n'avez pas encore ajouté de variété pour cette espèce.");
			$h .= '</div>';
			if($eFarm->canManage()) {
				$h .= '<h4>'.s("Ajouter une variété").'</h4>';
				$h .= $this->createForm($eFarm, $ePlant, $cSupplier, 'inline');
			}
		} else {

			$h .= '<div class="util-overflow-sm stick-xs">';

				$h .= '<table class="tr-bordered">';
					$h .= '<thead>';
						$h .= '<tr>';
							$h .= '<th>'.s("Nom").'</th>';
							$h .= '<th>'.s("Fournisseur<br/>de semences").'</th>';
							$h .= '<th>'.s("Fournisseur<br/>de plants").'</th>';
							$h .= '<th>'.s("Graines<br/>Poids pour 1000").'</th>';
							$h .= '<th>'.s("Plants<br/>Nombre pour 1 kg").'</th>';
							$h .= '<th></th>';
						$h .= '</tr>';
					$h .= '</thead>';

					$h .= '<tbody>';

					foreach($cVariety as $eVariety) {

						$supplierSeed = $eVariety['supplierSeed']->empty() ? '-' : encode($eVariety['supplierSeed']['name']);
						$supplierPlant = $eVariety['supplierPlant']->empty() ? '-' : encode($eVariety['supplierPlant']['name']);

						$h .= '<tr>';
							$h .= '<td>';
								$h .= encode($eVariety['name']);
							$h .= '</td>';
							$h .= '<td>';
								if($cSupplier->notEmpty()) {
									$h .= $eVariety->quick('supplierSeed', $supplierSeed);
								} else {
									$h .= $supplierSeed;
								}
							$h .= '</td>';
							$h .= '<td>';
								if($cSupplier->notEmpty()) {
									$h .= $eVariety->quick('supplierPlant', $supplierPlant);
								} else {
									$h .= $supplierPlant;
								}
							$h .= '</td>';
							$h .= '<td>';
								$h .= $eVariety->quick('weightSeed1000', $eVariety['weightSeed1000'] ? \main\UnitUi::getValue($eVariety['weightSeed1000'], 'gram') : '-');
							$h .= '</td>';
							$h .= '<td>';
								$h .= $eVariety->quick('numberPlantKilogram', $eVariety['numberPlantKilogram'] ? $eVariety['numberPlantKilogram'] : '-');
							$h .= '</td>';
							$h .= '<td class="text-end">';
								if($eFarm->canManage()) {
									$h .= '<a href="/plant/variety:update?id='.$eVariety['id'].'" class="btn btn-outline-secondary">';
										$h .= \Asset::icon('gear-fill');
									$h .= '</a> ';
									$h .= '<a data-ajax="/plant/variety:doDelete" data-confirm="'.s("Supprimer cette variété ?").'" post-id="'.$eVariety['id'].'" class="btn btn-outline-secondary">';
										$h .= \Asset::icon('trash-fill');
									$h .= '</a>';
								}
							$h .= '</td>';
						$h .= '</tr>';
					}
					$h .= '</tbody>';
				$h .= '</table>';

			$h .= '</div>';

		}

		return new \Panel(
			id: 'panel-variety-list',
			title: s("Les variétés"),
			body: $h,
			close: 'reload'
		);

	}

	public function create(\farm\Farm $eFarm, Plant $ePlant, \Collection $cSupplier): \Panel {

		return new \Panel(
			title: s("Ajouter une variété"),
			body: $this->createForm($eFarm, $ePlant, $cSupplier, 'panel'),
			close: 'reload'
		);

	}

	protected function createForm(\farm\Farm $eFarm, Plant $ePlant, \Collection $cSupplier, string $origin): string {

		$form = new \util\FormUi();

		$eVariety = new Variety([
			'farm' => $eFarm,
			'cSupplier' => $cSupplier
		]);

		$h = $form->openAjax('/plant/variety:doCreate', ['data-ajax-origin' => $origin]);

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->hidden('plant', $ePlant['id']);

			$h .= $form->group(
				s("Espèce"),
				PlantUi::link($ePlant)
			);
			$h .= $form->dynamicGroups($eVariety, ['name']);
			$h .= self::getSuppliers($form, $eVariety);
			$h .= $form->dynamicGroups($eVariety, ['weightSeed1000', 'numberPlantKilogram']);

			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return $h;

	}

	public function update(Variety $eVariety): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/plant/variety:doUpdate');

			$h .= $form->hidden('id', $eVariety['id']);

			$h .= $form->group(
				s("Espèce"),
				PlantUi::link($eVariety['plant'])
			);

			$h .= $form->dynamicGroups($eVariety, ['name']);
			$h .= self::getSuppliers($form, $eVariety);
			$h .= $form->dynamicGroups($eVariety, ['weightSeed1000', 'numberPlantKilogram']);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier une variété de la ferme"),
			body: $h,
			close: 'reload'
		);

	}

	protected static function getSuppliers(\util\FormUi $form, Variety $eVariety): string {

		$eVariety->expects(['farm']);

		if($eVariety['cSupplier']->notEmpty()) {
			return $form->dynamicGroups($eVariety, ['supplierSeed', 'supplierPlant']);
		} else {
			return $form->group(
				s("Fournisseurs de semences et plants pour cette variété"),
				'<div class="util-info">'.s("Pour associer une variété à un fournisseur, <link>créez d'abord une liste de vos fournisseurs habituels</link> !", ['link' => '<a href="/farm/supplier:manage?farm='.$eVariety['farm']['id'].'" target="_blank">']).'</div>'
			);
		}

	}

	/**
	 * Describe properties
	 */
	public static function p(string $property): \PropertyDescriber {

		$d = Variety::model()->describer($property, [
			'fqn' => s("Nom qualifié"),
			'name' => s("Nom"),
			'plant' => s("Espèce"),
			'farm' => s("Ferme"),
			'supplierSeed' => s("Fournisseur de semences pour cette variété"),
			'supplierPlant' => s("Fournisseur de plants pour cette variété"),
			'weightSeed1000' => s("Poids pour 1000 graines"),
			'numberPlantKilogram' => s("Nombre de plants pour 1 kg"),
		]);


		switch($property) {

			case 'supplierSeed' :
			case 'supplierPlant' :
				$d->values = fn(Variety $e) => $e['cSupplier'] ?? $e->expects(['cSupplier']);
				break;

			case 'weightSeed1000' :
				$d->append = s("gramme(s)");
				$d->after = \util\FormUi::info(s("Adapté pour les graines"));
				break;

			case 'numberPlantKilogram' :
				$d->append = s("plant(s)");
				$d->after = \util\FormUi::info(s("Adapté pour les tubercules ou bulbes"));
				break;

		}

		return $d;

	}

}
?>
