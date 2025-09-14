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

	public static function urlManage(\farm\Farm $eFarm): string {
		return \farm\FarmUi::url($eFarm).'/especes';
	}

	public static function getColorCircle(Plant $ePlant): string {

		$ePlant->expects(['color']);

		return '<div class="plant-color-circle" style="background-color: '.$ePlant['color'].'"></div>';

	}

	public function query(\PropertyDescriber $d, bool $multiple = FALSE) {

		$d->prepend ??= \Asset::icon('flower2');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Tapez un nom d'espèce");
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

	public static function getAutocompleteCreate(\farm\Farm $eFarm): array {

		$item = \Asset::icon('plus-circle');
		$item .= '<div>'.s("Ajouter une espèce manquante").'</div>';

		return [
			'type' => 'link',
			'link' => '/plant/plant:create?farm='.$eFarm['id'],
			'itemHtml' => $item
		];

	}

	public static function getVignette(Plant $ePlant, string $size): string {

		\Asset::css('plant', 'plant.css');

		$ePlant->expects(['fqn']);

		$ui = new \media\PlantVignetteUi();

		if($ePlant['fqn'] !== NULL) {

			$h = '<div class="plant-vignette" style="'.$ui->getSquareCss($size).'">';
				$h .= '<svg width="'.$size.'" height="'.$size.'"><use xlink:href="'.\Asset::getPath('plant', 'plants.svg', 'image').'#'.strtolower($ePlant['fqn']).'"/></svg>';
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

			return '<div class="'.$class.'" style="'.$ui->getSquareCss($size).'; '.$style.'">'.encode($content).'</div>';

		}


	}

	public static function getSoilVignette(string $size): string {
		return '<div class="soil-icon" style="width: '.$size.'; height: '.$size.'"></div>';
	}

	public function getSearch(\farm\Farm $eFarm, \Search $search): string {

		$h = '<div id="plant-search" class="util-block-search stick-xs '.($search->empty(['status', 'cFamily']) ? 'hide' : '').'">';

			$form = new \util\FormUi();

			$h .= $form->openAjax(self::urlManage($eFarm), ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';

					$h .= $form->hidden('farm', $eFarm['id']);

					$h .= $form->dynamicField(new Plant([
						'farm' => $eFarm
					]), 'id', function($d) use($search) {
						$d->name = 'plantId';
						$d->autocompleteDefault = $search->get('id');
						$d->attributes = [
							'data-autocomplete-select' => 'submit'
						];
					});

					$h .= $form->select('family', $search->get('cFamily'), $search->get('family'), ['placeholder' => s("Famille...")]);

					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.self::urlManage($eFarm).'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';

				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

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
				$h .= '</div>';
			$h .= '</a>';

		}

		return $h;

	}

	public function display(Plant $ePlant, \farm\Farm $eFarm, \Collection $cItemYear, \Collection $cCrop, \Collection $cActionMain): \Panel {

		$h = '<div class="util-vignette">';

			$h .= self::getVignette($ePlant, size: '10rem');

			$h .= '<div>';

				$h .= '<h1>'.encode($ePlant['name']).'</h1>';

				$h .= '<dl class="util-presentation util-presentation-1">';

					if($ePlant['aliases']) {
						$h .= '<dt>'.s("Autres noms").'</dt>';
						$h .= '<dd>'.encode($ePlant['aliases']).'</dd>';
					}

					if($ePlant['family']->notEmpty()) {
						$h .= '<dt>'.s("Famille").'</dt>';
						$h .= '<dd>'.$ePlant['family']['name'].'</dd>';
					}

				$h .= '</dl>';

			$h .= '</div>';


		$h .= '</div>';
		$h .= '<br/>';
		$h .= '<br/>';

		if($cItemYear->notEmpty()) {

			$h .= new \selling\AnalyzeUi()->getPlantTurnover($cItemYear, NULL, $ePlant);
			$h .= '<br/>';

		}

		if($cCrop->notEmpty()) {
			$h .= '<h3>'.s("Itinéraires techniques").'</h3>';
			$h .= new \sequence\SequenceUi()->getList($eFarm, $cCrop, $cActionMain);
		}

		return new \Panel(
			id: 'panel-plant-display',
			title: s("Espèce"),
			body: $h
		);

	}

	public function getManage(\farm\Farm $eFarm, array $plants, \Collection $cPlant, \Search $search): string {

		if($cPlant->empty()) {
			return '<div class="util-empty">'.s("Il n'y a aucune espèce à afficher...").'</div>';
		}

		\Asset::css('plant', 'plant.css');

		$h = '<div id="plant-manage">';

			if($plants[Plant::INACTIVE] > 0) {

				$h .= '<br/>';

				$h .= '<div class="tabs-item">';
					$h .= '<a href="'.self::urlManage($eFarm).'" class="tab-item '.($search->get('status') === Plant::ACTIVE ? 'selected' : '').'"><span>'.s("Espèces actives").' <span class="tab-item-count">'.$plants[Plant::ACTIVE].'</span></span></a>';
					$h .= '<a href="'.self::urlManage($eFarm).'/'.Plant::INACTIVE.'" class="tab-item '.($search->get('status') === Plant::INACTIVE ? 'selected' : '').'"><span>'.s("Espèces désactivées").' <small class="tab-item-count">'.$plants[Plant::INACTIVE].'</small></span></a>';
				$h .= '</div>';

			}

			$h .= $this->getFarmPlants($eFarm, $cPlant);

		$h .= '</div>';

		return $h;

	}

	protected function getFarmPlants(\farm\Farm $eFarm, \Collection $cPlant): string {

		$hasSafety = $cPlant->match(fn($ePlant) => $ePlant['plantsSafetyMargin'] !== NULL or $ePlant['seedsSafetyMargin'] !== NULL);

		$h = '<div class="util-overflow-xs stick-xs">';

			$h .= '<table class="tr-even">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th></th>';
						$h .= '<th>'.s("Nom").'</th>';
						$h .= '<th class="text-center">'.s("Variétés").'</th>';
						$h .= '<th class="text-center">'.s("Calibres").'</th>';
						if($hasSafety) {
							$h .= '<th>'.s("Marges de sécurité").'</th>';
						}
						$h .= '<th class="hide-xs-down">'.s("Famille").'</th>';
						$h .= '<th>'.s("Activé").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cPlant as $ePlant) {

					$h .= '<tr>';
						$h .= '<td class="util-manage-vignette">';
							if($ePlant['fqn'] === NULL) {
								$h .= new \media\PlantVignetteUi()->getCamera($ePlant, size: '3rem');
							} else {
								$h .= PlantUi::getVignette($ePlant, size: '3rem');
							}
						$h .= '</td>';
						$h .= '<td>';
							$h .= self::getColorCircle($ePlant);
							$h .= self::link($ePlant);
							if($ePlant->isOwner() === FALSE) {
								$h .= ' <span class="plant-manage-locked">'.\Asset::icon('lock-fill').'</span>';
							}
						$h .= '</td>';
						$h .= '<td class="text-center">';
							$h .= '<a href="/plant/variety?id='.$eFarm['id'].'&plant='.$ePlant['id'].'" class="btn btn-outline-primary opacity-'.($ePlant['varieties'] ? '100' : '25').'">'.($ePlant['varieties'] ?? '0').'</a>';
						$h .= '</td>';
						$h .= '<td class="text-center">';
							$h .= '<a href="/plant/size?id='.$eFarm['id'].'&plant='.$ePlant['id'].'" class="btn btn-outline-primary opacity-'.($ePlant['sizes'] ? '100' : '25').'">'.($ePlant['sizes'] ?? '0').'</a>';
						$h .= '</td>';

						if($hasSafety) {

							$h .= '<td>';

								$list = [];

								if($ePlant['plantsSafetyMargin'] !== NULL) {
									$list[] = '<div>'.s("Plants <muted>{value} %</muted>", ['value' => $ePlant['plantsSafetyMargin'], 'muted' => '<span class="color-muted">']).'</div>';
								}

								if($ePlant['seedsSafetyMargin'] !== NULL) {
									$list[] = '<div>'.s("Semences <muted>{value} %</muted>", ['value' => $ePlant['seedsSafetyMargin'], 'muted' => '<span class="color-muted">']).'</div>';
								}

								if($list) {
									$h .= implode('', $list);
								} else {
									$h .= '-';
								}

							$h .= '</td>';

						}

						$h .= '<td class="hide-xs-down">';
							if($ePlant['family']->empty()) {
								$h .= '-';
							} else {
								$h .= encode($ePlant['family']['name']);
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

										$h .= '<a href="/plant/plant:update?id='.$ePlant['id'].'" class="dropdown-item">';
											$h .= s("Modifier l'espèce");
										$h .= '</a>';
										$h .= '<a href="/plant/variety?id='.$eFarm['id'].'&plant='.$ePlant['id'].'" class="dropdown-item">'.s("Gérer les variétés").'</a>';
										$h .= '<a href="/plant/size?id='.$eFarm['id'].'&plant='.$ePlant['id'].'" class="dropdown-item">'.s("Gérer les calibres").'</a>';

										if($ePlant->isOwner()) {

											$h .= '<div class="dropdown-divider"></div>';

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

		$h .= '</div>';

		return $h;

	}

	public function create(\farm\Farm $eFarm, \Collection $cFamily): \Panel {

		return new \Panel(
			id: 'panel-plant-create',
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

			$h .= $form->dynamicGroups($ePlant, ['name*', 'color*', 'family', 'cycle*']);

			$h .= $form->group(content: '<h3>'.s("Marges de sécurité").'</h3>');
			$h .= $form->dynamicGroups($ePlant, ['plantsSafetyMargin', 'seedsSafetyMargin']);

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
			$h .= $form->dynamicGroups($ePlant, ['name', 'color']);

			if($ePlant->isOwner()) {
				$h .= $form->dynamicGroups($ePlant, ['family', 'cycle']);
			}

			$h .= $form->group(content: '<h3>'.s("Marges de sécurité").'</h3>');
			$h .= $form->dynamicGroups($ePlant, ['plantsSafetyMargin', 'seedsSafetyMargin']);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-plant-update',
			title: s("Modifier une espèce"),
			subTitle: self::getPanelHeader($ePlant),
			body: $h,
			close: 'reload'
		);

	}

	public static function getPanelHeader(Plant $ePlant): string {

		return '<div class="panel-header-subtitle">'.self::getVignette($ePlant, '2rem').'  '.encode($ePlant['name']).'</div>';

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Plant::model()->describer($property, [
			'fqn' => s("Nom qualifié"),
			'name' => s("Nom"),
			'color' => s("Couleur"),
			'aliases' => s("Autres noms"),
			'plantsSafetyMargin' => s("Pour le calcul des plants à produire"),
			'seedsSafetyMargin' => s("Pour le calcul des semences à acheter"),
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
				new \plant\PlantUi()->query($d);
				break;

			case 'color' :
				$d->after = \util\FormUi::info(s("Une couleur sombre utilisée pour l'assolement"));
				break;

			case 'aliases' :
				$d->append = '<small>'.s("(séparés par des virgules)").'</small>';
				break;

			case 'plantsSafetyMargin' :
				$d->prepend = \Asset::icon('plus');
				$d->append = '%';
				$d->after = \util\FormUi::info(s("Cette marge de sécurité sera intégrée dans le calcul du nombre de plants nécessaires pour les cultures sur lesquelles vous autoproduisez vos plants."));
				break;

			case 'seedsSafetyMargin' :
				$d->prepend = \Asset::icon('plus');
				$d->append = '%';
				$d->after = \util\FormUi::info(s("Cette marge de sécurité sera intégrée dans le calcul de la quantité de semences à acheter pour vos cultures que vous implantez en semis direct."));
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
