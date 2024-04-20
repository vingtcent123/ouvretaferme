<?php
namespace plant;

/**
 * Ui class for plant forms
 *
 */
class PlantUi {

	public function __construct() {

		\Asset::css('plant', 'plant.css');

	}

	public static function link(Plant $ePlant, bool $newTab = FALSE): string {
		return '<a href="'.self::url($ePlant).'" '.($newTab ? 'target="_blank"' : '').'>'.encode($ePlant['name']).'</a>';
	}

	public static function url(Plant $ePlant): string {

		$ePlant->expects(['id']);

		return '/espece/'.$ePlant['id'];

	}

	public function query(\PropertyDescriber $d, bool $multiple = FALSE) {

		$d->prepend ??= \Asset::icon('flower2');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Tapez un nom d'espèce...");
		$d->multiple = $multiple;
		$d->group += ['wrapper' => 'plant'];

		$d->autocompleteUrl = '/plant/:query';
		$d->autocompleteResults = function(Plant $e) {
			return self::getAutocomplete($e);
		};

	}

	public static function getAutocomplete(Plant $ePlant): array {

		\Asset::css('media', 'media.css');

		$item = self::getVignette($ePlant, '2.5rem');
		$item .= '<div>'.encode($ePlant['name']).'</div>';

		return [
			'value' => $ePlant['id'],
			'itemHtml' => $item,
			'itemText' => $ePlant['name']
		];

	}

	public static function getVignette(Plant $ePlant, string $size): string {

		\Asset::css('plant', 'plant.css');

		$ePlant->expects(['fqn']);

		$ui = new \media\PlantVignetteUi();

		if($ePlant['fqn'] !== NULL) {

			$h = '<div class="plant-vignette" style="'.$ui->getSquareCss($size).'">';
				$h .= '<svg width="'.$size.'" height="'.$size.'"><use xlink:href="'.\Asset::path('plant', 'plants.svg').'#'.strtolower($ePlant['fqn']).'"/></svg>';
			$h .= '</div>';

			return $h;

		} else {

			$ePlant->expects(['id', 'vignette']);

			$class = 'media-circle-view ';
			$style = '';

			if($ePlant['vignette'] === NULL) {

				$class .= ' media-vignette-default';
				$content = mb_substr($ePlant['name'], 0, 2);

			} else {

				$format = $ui->convertToFormat($size);

				$style .= 'background-image: url('.$ui->getUrlByElement($ePlant, $format).');';
				$content = '';

			}

			return '<div class="'.$class.'" style="'.$ui->getSquareCss($size).'; '.$style.'">'.$content.'</div>';

		}


	}

	public static function getSoilVignette(string $size): string {
		return '<div class="soil-icon" style="width: '.$size.'; height: '.$size.'"></div>';
	}

	public function getList(\Collection $cPlant) {

		\Asset::css('plant', 'plant.css');

		$h = '';

		foreach($cPlant as $ePlant) {

			$h .= '<a href="'.PlantUi::url($ePlant).'" class="plant-item">';
				$h .= '<div class="plant-item-image">';
					$h .= self::getVignette($ePlant, '4rem');
				$h .= '</div>';
				$h .= '<div class="plant-item-presentation">';
					$h .= '<div class="plant-item-name">';
						$h .= $ePlant['name'];
						if($ePlant['aliases']) {
							$h .= '<small> '.s("ou {aliases}", ['aliases' => encode($ePlant['aliases'])]).'</small>';
						}
					$h .= '</div>';
					$h .= '<div class="plant-item-latin">'.$ePlant['latinName'].'</div>';
				$h .= '</div>';
			$h .= '</a>';

		}

		return $h;

	}

	public function display(Plant $ePlant, \Collection $cItemTurnover): string {

		$h = '<div class="util-vignette">';

			$h .= self::getVignette($ePlant, size: '10rem');

			$h .= '<div>';

				$h .= '<h1>'.encode($ePlant['name']).'</h1>';

				$h .= '<dl class="util-presentation util-presentation-1">';

					if($ePlant['aliases']) {
						$h .= '<dt>'.s("Autres noms").'</dt>';
						$h .= '<dd>'.encode($ePlant['aliases']).'</dd>';
					}

					if($ePlant['latinName']) {
						$h .= '<dt>'.s("Nom latin").'</dt>';
						$h .= '<dd>'.encode($ePlant['latinName']).'</dd>';
					}

					if($ePlant['family']->notEmpty()) {
						$h .= '<dt>'.s("Famille").'</dt>';
						$h .= '<dd><a href="'.FamilyUi::url($ePlant['family'], $ePlant['farm']).'">'.$ePlant['family']['name'].'</a></dd>';
					}

				$h .= '</dl>';

			$h .= '</div>';


		$h .= '</div>';

		if($cItemTurnover->notEmpty()) {

			$h .= (new \selling\AnalyzeUi())->getPlantTurnover($cItemTurnover, NULL, $ePlant);

		}

		return $h;

	}

	public function manage(\farm\Farm $eFarm, array $plants, \Collection $cPlant, \Search $search): string {

		\Asset::css('plant', 'plant.css');

		$h = '<div id="plant-manage">';

			$h .= $this->searchByFarm($eFarm, $search);

			if($plants[Plant::INACTIVE] > 0) {

				$h .= '<br/>';

				$h .= '<div class="tabs-item">';
					$h .= '<a href="'.\farm\FarmUi::urlCultivationPlants($eFarm).'" class="tab-item '.($search->get('status') === Plant::ACTIVE ? 'selected' : '').'"><span>'.s("Espèces actives").' <small>('.$plants[Plant::ACTIVE].')</small></span></a>';
					$h .= '<a href="'.\farm\FarmUi::urlCultivationPlants($eFarm).'/'.Plant::INACTIVE.'" class="tab-item '.($search->get('status') === Plant::INACTIVE ? 'selected' : '').'"><span>'.s("Espèces désactivées").' <small>('.$plants[Plant::INACTIVE].')</small></span></a>';
				$h .= '</div>';

			}

			$h .= $this->getFarmPlants($eFarm, $cPlant);

		$h .= '</div>';

		return $h;

	}

	protected function searchByFarm(\farm\Farm $eFarm, \Search $search): string {

		$form = new \util\FormUi();

		$h = $form->openAjax(\farm\FarmUi::urlCultivationPlants($eFarm), ['id' => 'plant-manage-search', 'method' => 'get']);
			$h .= '<div>';
				$h .= $form->dynamicField(new Plant([
					'farm' => $eFarm
				]), 'id', function($d) use ($search) {
					$d->name = 'plantId';
					$d->attributes = [
						'data-autocomplete-select' => 'submit'
					];
				});
			$h .= '</div>';
			if($search->get('id') !== NULL) {
				$h .= '<a href="'.\farm\FarmUi::urlCultivationPlants($eFarm).'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
			}
		$h .= $form->close();

		return $h;

	}

	protected function getFarmPlants(\farm\Farm $eFarm, \Collection $cPlant): string {

		$h = '<table class="plant-manage-list tr-even">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th></th>';
					$h .= '<th>'.s("Nom").'</th>';
					$h .= '<th class="text-center">'.s("Variétés").'</th>';
					$h .= '<th class="text-center">'.s("Critères<br/>de&nbsp;qualité").'</th>';
					$h .= '<th>'.s("Famille").'</th>';
					$h .= '<th>'.s("Activé").'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';

			foreach($cPlant as $ePlant) {

				$h .= '<tr>';
					$h .= '<td class="util-manage-vignette">';
						if($ePlant['fqn'] === NULL) {
							$h .= (new \media\PlantVignetteUi())->getCamera($ePlant, size: '3rem');
						} else {
							$h .= PlantUi::getVignette($ePlant, size: '3rem');
						}
					$h .= '</td>';
					$h .= '<td>';
						$h .= self::link($ePlant);
						if($ePlant->isOwner() === FALSE) {
							$h .= ' <span class="plant-manage-locked">'.\Asset::icon('lock-fill').'</span>';
						}
						$h .= '<br />';
						$h .= '<small>'.encode($ePlant['latinName']).'</small>';
					$h .= '</td>';
					$h .= '<td class="text-center">';
						$h .= '<a href="/plant/variety?id='.$eFarm['id'].'&plant='.$ePlant['id'].'" class="btn btn-outline-primary opacity-'.($ePlant['varieties'] ? '100' : '25').'">'.($ePlant['varieties'] ?? '0').'</a>';
					$h .= '</td>';
					$h .= '<td class="text-center">';
						$h .= '<a href="/plant/quality?id='.$eFarm['id'].'&plant='.$ePlant['id'].'" class="btn btn-outline-primary opacity-'.($ePlant['qualities'] ? '100' : '25').'">'.($ePlant['qualities'] ?? '0').'</a>';
					$h .= '</td>';
					$h .= '<td>';
						if($ePlant['family']->empty()) {
							$h .= '-';
						} else {
							$h .= FamilyUi::link($ePlant['family'], $eFarm);
						}
					$h .= '</td>';
					$h .= '<td class="text-center td-min-content">';
						$h .= \util\TextUi::switch([
							'id' => 'plant-switch-'.$ePlant['id'],
							'data-ajax' => '/plant/plant:doUpdateStatus',
							'post-id' => $ePlant['id'],
							'post-status' => ($ePlant['status'] === Plant::ACTIVE) ? Plant::INACTIVE : Plant::ACTIVE
						], $ePlant['status'] === Plant::ACTIVE);
					$h .= '</td>';
					$h .= '<td class="text-end">';

						if($eFarm->canManage()) {

							$h .= '<a class="btn btn-outline-secondary dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<div class="dropdown-title">'.encode($ePlant['name']).'</div>';

									$h .= '<a href="/plant/variety?id='.$eFarm['id'].'&plant='.$ePlant['id'].'" class="dropdown-item">'.s("Gérer les variétés").'</a>';
									$h .= '<a href="/plant/quality?id='.$eFarm['id'].'&plant='.$ePlant['id'].'" class="dropdown-item">'.s("Gérer les critères de qualité").'</a>';

									if($ePlant->isOwner()) {

										$h .= '<div class="dropdown-divider"></div>';

										$h .= '<a href="/plant/plant:update?id='.$ePlant['id'].'" class="dropdown-item">';
											$h .= s("Modifier l'espèce");
										$h .= '</a> ';

										$h .= '<a data-ajax="/plant/plant:doDelete" data-confirm="'.s("Supprimer cette espèce ?").'" post-id="'.$ePlant['id'].'" class="dropdown-item">';
											$h .= s("Supprimer l'espèce");
										$h .= '</a>';

									}

							$h .= '</div>';

						}

					$h .= '</td>';
				$h .= '</tr>';
			}
			$h .= '</tbody>';
		$h .= '</table>';

		return $h;

	}

	public function create(\farm\Farm $eFarm, \Collection $cFamily): \Panel {

		return new \Panel(
			title: s("Ajouter une espèce pour la ferme"),
			body: $this->createForm($eFarm, $cFamily, 'panel'),
			close: 'reload'
		);

	}

	protected function createForm(\farm\Farm $eFarm, \Collection $cFamily, string $origin) {

		$form = new \util\FormUi();

		$ePlant = new Plant([
			'cFamily' => $cFamily,
		]);

		$h = $form->openAjax('/plant/plant:doCreate', ['data-ajax-origin' => $origin]);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= $form->dynamicGroups($ePlant, ['name*', 'latinName*', 'family', 'cycle*']);
			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();
		
		return $h;

	}

	public function update(Plant $ePlant): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/plant/plant:doUpdate');

			$h .= $form->hidden('id', $ePlant['id']);

			if($ePlant->isOwner()) {
				$h .= $form->dynamicGroups($ePlant, ['name', 'latinName', 'family', 'cycle']);
			}

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier une espèce de la ferme"),
			body: $h,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Plant::model()->describer($property, [
			'fqn' => s("Nom qualifié"),
			'name' => s("Nom"),
			'aliases' => s("Autres noms"),
			'latinName' => s("Nom latin"),
			'family' => s("Famille"),
			'cycle' => s("Cycle de culture"),
		]);

		switch($property) {

			case 'id' :
				$d->autocompleteBody = function(\util\FormUi $form, Plant $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']->empty() ? NULL : $e['farm']['id']
					];
				};
				(new \plant\PlantUi())->query($d);
				break;

			case 'aliases' :
				$d->append = '<small>'.s("(séparés par des virgules)").'</small>';
				break;

			case 'family' :
				$d->values = fn(Plant $e) => $e['cFamily'] ?? $e->expects(['cFamily']);
				break;

			case 'cycle' :
				$d->values = [
					Plant::ANNUAL => s("culture annuelle"),
					Plant::PERENNIAL => s("culture pérenne"),
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
