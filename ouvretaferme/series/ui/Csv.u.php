<?php
namespace series;

class CsvUi {

	public function __construct() {

		\Asset::css('series', 'csv.css');
		\Asset::js('series', 'csv.js');

	}

	public static function convertUnit(string $unit): string {

		return match($unit) {
			'kg' => 'kg',
			'bunch' => 'bunch',
			'bte' => 'bunch',
			'botte' => 'bunch',
			'b' => 'bunch',
			'bqt' => 'bunch',
			'unit' => 'unit',
			'pc' => 'unit',
			'pièce' => 'unit',
			'u' => 'unit',
			'p' => 'unit',
			'bqte' => 'kg',
			'' => 'kg',
			default => $unit
		};

	}

	public function getExportTasksHeader(): array {

		return [
			'date',
			'user',
			'category',
			'action',
			'time',
			'series_id',
			'series_name',
			'species',
			'variety',
			'harvest_quantity',
			'harvest_unit',
			'harvest_size',
		];

	}

	public function getExportHarvestsHeader(): array {

		return [
			'date',
			'series_id',
			'series_name',
			'species',
			'variety',
			'harvest_quantity',
			'harvest_unit',
			'harvest_size',
		];

	}

	public function getExportCultivationsHeader($maxVarieties): array {

		$columns = [
			'season',
			'series_id',
			'series_name',
			'mode',
			'use',
			'species',
			'planting_type',
			'seeds_per_hole',
			'young_plants_tray',
			'sowing_date',
			'planting_date',
			'first_harvest_date',
			'last_harvest_date',
			'block_area',
			'block_density',
			'block_spacing_rows',
			'block_spacing_plants',
			'bed_length',
			'bed_density',
			'bed_rows',
			'bed_spacing_plants',
			'finished',
			'harvest_unit',
			'yield_expected_area',
			'yield_got_area'
		];

		for($i = 0; $i < $maxVarieties; $i++) {
			$columns[] = 'variety_name';
			$columns[] = 'variety_part';
		}

		return $columns;

	}

	public function getImportCultivations(\farm\Farm $eFarm): string {

		$form = new \util\FormUi();

		$h = $form->openUrl('/series/csv:doImportCultivations', ['binary' => TRUE, 'method' => 'post']);
			$h .= $form->hidden('id', $eFarm['id']);
			$h .= '<label class="btn btn-primary">';
				$h .= $form->file('csv', ['onchange' => 'this.form.submit()']);
				$h .= s("Importer un fichier CSV depuis mon ordinateur");
			$h .= '</label>';
		$h .= $form->close();

		return $h;

	}

	public function getImportFile(\farm\Farm $eFarm, array $data, \Collection $cAction): string {

		['import' => $import, 'errorsCount' => $errorsCount, 'errorsGlobal' => $errorsGlobal, 'infoGlobal' => $infoGlobal] = $data;

		$h = '';

		if($errorsCount > 0) {
			$h .= '<div class="util-block">';
				$h .= '<h4 class="color-danger">'.p("{value} problème a été trouvé dans le fichier CSV", "{value} problèmes ont été trouvés dans le fichier CSV", $errorsCount).'</h4>';
				$h .= '<p>'.s("Vous pouvez parcourir le tableau ci-dessous pour identifier ces problèmes et les corriger. Pour que {siteName} puisse importer vos données sans erreur, il est indispensable que le format CSV soit strictement respecté. Si vous n'êtes pas à l'aise avec cela, nous vous recommandons de ne pas utiliser cette fonctionnalité.").'</p>';
			$h .= '</div>';
		} else {
			$h .= '<div class="util-block">';
				$h .= '<h4>'.s("Vos données sont prêtes à être importées").'</h4>';
				$h .= '<ul>';
					$h .= '<li>'.s("Les séries présentes dans le tableau ci-dessous seront créées et associées à votre ferme").'</li>';
					if($infoGlobal['varieties']) {
						$h .= '<li>'.s("Les variétés manquantes seront automatiquement créées").'</li>';
					}
					if(
						$infoGlobal['beds'] and
						$eFarm['defaultBedWidth']
					) {

						$params = [
							'defaultAlleyWidth' => $eFarm['defaultAlleyWidth'],
							'defaultBedWidth' => $eFarm['defaultBedWidth'],
							'link' => '<a href="/farm/farm:update?id='.$eFarm['id'].'">'
						];

						if($eFarm['defaultAlleyWidth']) {
							$h .= '<li>'.s("Les séries seront créées pour une largeur de planche de {defaultBedWidth} cm et des allées de {defaultAlleyWidth} cm correspondant aux valeurs par défaut de votre ferme (<link>modifier</link>)", $params).'</li>';
						} else {
							$h .= '<li>'.s("Les séries seront créées pour une largeur de planche de {defaultBedWidth} cm correspondant à la largeur des planches par défaut de votre ferme (<link>modifier</link>)", $eFarm).'</li>';
						}
					}
					$h .= '<li>'.s("Il est encore temps de faire des modifications dans votre fichier CSV si vous n'êtes pas totalement satisfait de la version actuelle").'</li>';
					$h .= '<li>'.s("Si vous changez d'avis, vous pourrez toujours supprimer ultérieurement les séries que vous importez maintenant").'</li>';
				$h .= '</ul>';
				$h .= '<a post-id="'.$eFarm['id'].'" class="csv-import-button btn btn-secondary" data-confirm-text="'.p("Importer maintenant {value} série ?", "Importer maintenant {value} séries ?", count($data['import'])).'" onclick="Csv.import(this)">'.s("Importer maintenant").'</a>';
				$h .= '<a class="btn disabled csv-import-waiter">'.s("Importation en cours, merci de patienter...").'</a>';
			$h .= '</div>';
		}

		foreach($errorsGlobal as $type => $values) {

			if(empty($values)) {
				continue;
			}

			switch($type) {

				case 'beds' :
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Largeur des planches").'</h4>';
						$h .= '<p>'.s("Veuillez renseigner la largeur des planches par défaut sur votre ferme pour que {siteName} puisse importer vos données.").'</p>';
						$h .= '<a href="/farm/farm:update?id='.$eFarm['id'].'" class="btn btn-danger">'.s("Configurer les planches").'</a>';
					$h .= '</div>';
					break;

				case 'harvestUnit' :
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Unités de récolte").'</h4>';
						$h .= '<p>'.s("Les unités de récolte peuvent être <i>kg</i>, <i>bunch</i> (pour botte) ou <i>unit</i> (pour unité ou pièce). <br/>Certaines unités de récolte ne correspondent pas et doivent être corrigées dans votre fichier CSV :").'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
					$h .= '</div>';
					break;

				case 'species' :
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Espèces manquantes").'</h4>';
						$h .= '<p>'.s("Les espèces suivantes n'existent pas ou sont désactivées sur votre ferme, corrigez votre fichier CSV pour les faire correspondre à une espèce existante ou ajoutez-les à votre ferme :", ['link' => '<a href="'.\plant\PlantUi::urlManage($eFarm).'" target="_blank">']).'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
						$h .= '<a href="'.\plant\PlantUi::urlManage($eFarm).'" target="_blank" class="btn btn-danger">'.s("Ajouter des espèces").'</a>';
					$h .= '</div>';
					break;

				case 'tools' :
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Plateaux de semis manquants").'</h4>';
						$h .= '<p>'.s("Les plateaux de semis suivants sont utilisés dans le fichier CSV et n'existent pas sur votre ferme :").'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
						$h .= '<a href="'.\farm\ToolUi::urlManage($eFarm).'" target="_blank" class="btn btn-danger">'.s("Ajouter des plateaux de semis").'</a>';
					$h .= '</div>';
					break;

				case 'seasons' :
					$h .= '<div class="util-block">';
						$h .= '<h4 class="color-danger">'.s("Saisons inconnues").'</h4>';
						$h .= '<p>'.s("Vous utilisez dans le fichier CSV des saisons de culture qui n'ont pas été définies à l'échelle de la ferme :").'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
						$h .= '<p>'.s("Les saisons s'ajoutent depuis la page de planification par l'intermédiaire du menu déroulant qui permet de modifier l'affichage de la saison en cours.").'</p>';
						$h .= '<a href="'.\farm\FarmUi::urlCultivation($eFarm, \farm\Farmer::SERIES).'" target="_blank" class="btn btn-danger">'.s("Ajouter des saisons").'</a>';
					$h .= '</div>';
					break;

			}

		}

		foreach($infoGlobal as $type => $values) {

			if(empty($values)) {
				continue;
			}

			switch($type) {

				case 'speciesPerennial' :
					$h .= '<div class="util-block">';
						$h .= '<h4>'.s("Information sur les espèces pérennes").'</h4>';
						$h .= '<p>'.s("L'import n'est pas supporté pour les espèces pérennes, les séries avec les plantes suivantes ne seront pas importées :").'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
					$h .= '</div>';
					break;

				case 'varieties' :
					$h .= '<div class="util-block">';
						$h .= '<h4>'.s("Information sur de nouvelles variétés").'</h4>';
						$h .= '<p>'.s("Les variétés suivantes sont utilisées dans le fichier CSV et seront ajoutées à votre ferme :").'</p>';
						$h .= '<p style="font-style: italic">'.encode(implode(', ', $values)).'</p>';
					$h .= '</div>';
					break;

			}

		}

		$h .= '<div class="util-overflow-lg">';

			$h .= '<table class="tbody-even">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th colspan="2">'.s("Espèce").'</th>';
						$h .= '<th></th>';
						$h .= '<th>'.s("Saison").'</th>';
						$h .= '<th>'.s("Implantation").'</th>';
						$h .= '<th>'.s("Semis").'</th>';
						$h .= '<th>'.s("Plantation").'</th>';
						$h .= '<th>'.s("Récolte").'</th>';
						$h .= '<th>'.s("Espacement").'</th>';
					$h .= '</tr>';
				$h .= '</thead>';

				foreach($import as ['series' => $series, 'cultivations' => $cultivations]) {

					$h .= '<tbody>';

						$h .= '<tr>';
							$h .= '<th colspan="9">';
								$h .= '<b>'.s("Série {value}", encode($series['name'])).'</b>';
								if($series['finished']) {
									$h .= ' '.\Asset::icon('lock-fill');
								}

								switch($series['use']) {

									case Series::BED :
										if($series['bed_length']) {
											$h .= '<span class="color-muted"> / '.s("{value} mL", $series['bed_length']).'</span>';
										}
										break;

									case Series::BLOCK :
										if($series['block_area']) {
											$h .= '<span class="color-muted"> / '.s("{value} m²", $series['block_area']).'</span>';
										}
										break;

								}
							$h .= '</th>';
						$h .= '</tr>';

					foreach($cultivations as $cultivation) {

						$h .= '<tr class="'.($cultivation['errors'] ? 'csv-error' : '').'">';

							$h .= '<td class="td-min-content text-center">';
								if(
									$cultivation['ePlant']->notEmpty() and
									$cultivation['ignore'] === FALSE
								) {
									$h .= \plant\PlantUi::getVignette($cultivation['ePlant'], '2rem');
								} else {
									$h .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').'</span>';
								}
							$h .= '</td>';
							$h .= '<td style="max-width: 15rem">';

								if($cultivation['ePlant']->notEmpty()) {

									$h .= encode($cultivation['ePlant']['name']);

									if($cultivation['ignore']) {
										$h .= '<br/><span class="color-danger">'.s("Culture pérenne non importée").'</span>';
									} else if($cultivation['varieties']) {

										$varieties = [];

										foreach($cultivation['varieties'] as ['variety' => $variety, 'eVariety' => $eVariety]) {

											if($eVariety->empty()) {
												$varieties[] = \Asset::icon('exclamation-triangle').' '.encode($variety);
											} else {
												$varieties[] = encode($eVariety['name']);
											}

										}

										$h .= '<br/><small class="color-muted">'.implode(' / ', $varieties).'</small>';

									}

								} else {
									$h .= '<span class="color-danger">'.encode($cultivation['species']).'</span>';
								}

							$h .= '</td>';
							$h .= '<td>';
								$h .= match($series['mode']) {
									Series::GREENHOUSE => \Asset::icon('greenhouse'),
									Series::MIX => \Asset::icon('mix'),
									default => ''
								};
							$h .= '</td>';
							$h .= '<td>';
								$h .= $series['season'];
							$h .= '</td>';
							$h .= '<td>';
							$h .= match($cultivation['planting_type']) {
								Cultivation::SOWING => s("Semis direct"),
								Cultivation::YOUNG_PLANT_BOUGHT => s("Plant acheté"),
								Cultivation::YOUNG_PLANT => s("Plant autoproduit"),
								default => ''
							};
							$h .= '</td>';
							$h .= '<td>';

							switch($cultivation['planting_type']) {

								case Cultivation::SOWING :
									$eAction = $cAction[ACTION_SEMIS_DIRECT];
									$h .= '<span style="color: '.$eAction['color'].'" title="'.encode($eAction['name']).'">'.($cultivation['sowing_date'] ? \util\DateUi::numeric($cultivation['sowing_date']) : '?').'</span>';
									break;

								case Cultivation::YOUNG_PLANT :
									$eAction = $cAction[ACTION_SEMIS_PEPINIERE];
									$h .= '<span style="color: '.$eAction['color'].'" title="'.encode($eAction['name']).'">'.($cultivation['sowing_date'] ? \util\DateUi::numeric($cultivation['sowing_date']) : '?').'</span>';
									break;

							}

							$h .= '</td>';
							$h .= '<td>';

							switch($cultivation['planting_type']) {

								case Cultivation::YOUNG_PLANT_BOUGHT :
								case Cultivation::YOUNG_PLANT :
									$eAction = $cAction[ACTION_PLANTATION];
									$h .= '<span style="color: '.$eAction['color'].'" title="'.encode($eAction['name']).'">'.($cultivation['planting_date'] ? \util\DateUi::numeric($cultivation['planting_date']) : '?').'</span>';
									break;

							}

							$h .= '</td>';
							$h .= '<td>';

							if($cultivation['first_harvest_date'] or $cultivation['last_harvest_date']) {

								$eAction = $cAction[ACTION_RECOLTE];

								$h .= '<span style="color: '.$eAction['color'].'">';
								if($cultivation['first_harvest_date'] === $cultivation['last_harvest_date']) {
									$h .= \util\DateUi::numeric($cultivation['first_harvest_date']);
								} else {
									$h .= s("{first} → {last}", [
										'first' => $cultivation['first_harvest_date'] ? \util\DateUi::numeric($cultivation['first_harvest_date']) : '?',
										'last' => $cultivation['last_harvest_date'] ? \util\DateUi::numeric($cultivation['last_harvest_date']) : '?'
									]);
								}
								$h .= '</span>';

							}

							if(in_array($cultivation['harvest_unit'], Cultivation::model()->getPropertyEnum('mainUnit'))) {
								$unit = CultivationUi::p('mainUnit')->values[$cultivation['harvest_unit']];
							} else {
								$unit = '<span class="color-danger">'.\Asset::icon('exclamation-triangle').' '.encode($cultivation['harvest_unit']).'</span>';
							}

							$harvests = NULL;

							if($series['use'] === Series::BED and $cultivation['yield_expected_length']) {
								$harvests = s("Attendu {value} {unit} / mL", ['value' => $cultivation['yield_expected_length'], 'unit' => $unit]);
							} else if($cultivation['yield_expected_area']) {
								$harvests = s("Attendu {value} {unit} / m²", ['value' => $cultivation['yield_expected_area'], 'unit' => $unit]);
							} else if($cultivation['harvest_unit']) {
								$harvests = s("Unité de récolte {value}", '<u>'.$unit.'</u>');
							}

							if($harvests) {

								$h .= '<br/><small>'.$harvests.'</small>';

							}

							$h .= '</td>';
							$h .= '<td style="font-size: 0.8rem">';

								$list = [];

								switch($series['use']) {

									case Series::BED :
										if($cultivation['bed_density']) {
											$list[] = s("{value} / m²", $cultivation['bed_density']);
										}
										if($cultivation['bed_rows']) {
											$list[] = p("{value} rang", "{value} rangs", $cultivation['bed_rows']);
										}
										if($cultivation['bed_spacing_plants']) {
											$list[] = s("{value} cm sur le rang", $cultivation['bed_spacing_plants']);
										}
										break;

									case Series::BLOCK :
										if($cultivation['block_density']) {
											$list[] = s("{value} / m²", $cultivation['block_density']);
										}
										if($cultivation['block_spacing_rows']) {
											$list[] = s("{value} cm entre rangs", $cultivation['block_spacing_rows']);
										}
										if($cultivation['block_spacing_plants']) {
											$list[] = s("{value} cm sur le rang", $cultivation['block_spacing_plants']);
										}
										break;

								}

								if(in_array($cultivation['planting_type'], [Cultivation::SOWING, Cultivation::YOUNG_PLANT])) {

									if($cultivation['seeds_per_hole']) {
										$list[] = match($cultivation['planting_type']) {
											Cultivation::SOWING => p("{value} graine / trou", "{value} graines / trou", $cultivation['seeds_per_hole']),
											Cultivation::YOUNG_PLANT => p("{value} graine / plant", "{value} graines / plant", $cultivation['seeds_per_hole'])
										};
									}

								}

								if($cultivation['young_plants_tray']) {

									if($cultivation['eTool']->notEmpty()) {
										$list[] = encode($cultivation['eTool']['name']);
									} else {
										$list[] = \Asset::icon('exclamation-triangle').' '.encode($cultivation['young_plants_tray']).' '.($cultivation['young_plants_tray_size'] ? '('.p("{value} trou", "{value} trous", $cultivation['young_plants_tray_size']).')' : '');
									}

								}

								if(count($list) > 1) {
									$h .= '<ul class="mb-0">';
										$h .= '<li>'.implode('</li><li>', $list).'</li>';
									$h .= '</ul>';
								} else if(count($list) === 1) {
									$h .= $list[0];
								}

							$h .= '</td>';

						$h .= '</tr>';

						if($cultivation['errors']) {

							$errors = [
								'speciesEmpty' => s("L'espèce n'est pas indiquée dans le fichier CSV"),
								'speciesDuplicate' => s("Vous ne pouvez pas créer avoir deux lignes avec la même espèce au sein d'une même série"),
								'modeInvalid' => s("Le mode de culture est incorrect dans le fichier CSV ({value})", implode(', ', Series::model()->getPropertyEnum('mode'))),
								'useInvalid' => s("L'utilisation du sol peut être <i>bed</i> pour des planches ou <i>block</i> en surface libre"),
								'seedlingInvalid' => s("Le mode d'implantation est incorrect dans le fichier CSV"),
								'sowingDateFormat' => s("Le format de la date de semis est invalide dans le fichier CSV"),
								'plantingDateFormat' => s("Le format de la date de plantation est invalide dans le fichier CSV"),
								'firstHarvestDateFormat' => s("Le format de la date de début de récolte est invalide dans le fichier CSV"),
								'lastHarvestDateFormat' => s("Le format de la date de fin de récolte est invalide dans le fichier CSV"),
								'harvestDateConsistency' => s("La date de début de récolte ne peut pas être supérieure à la date de fin de récolte dans le fichier CSV"),
								'harvestDateNull' => s("La date de début ou de fin de récolte est manquante"),
								'harvestUnitEmpty' => s("Il manque l'unité de récolte dans le fichier CSV ({value})", implode(', ', Cultivation::model()->getPropertyEnum('mainUnit'))),
								'varietyParts' => s("Le total des variétés doit faire 100 %"),
								'bedSpacing' => s("Vous ne pouvez pas à la fois indiqué une densité et un nombre de rangs ou un espacement sur le rang"),
								'blockSpacing' => s("Vous ne pouvez pas à la fois indiquer une densité et des espacements sur le rang ou entre rangs"),
							];

							$h .= '<tr>';
								$h .= '<td colspan="9">';
									$h .= '<ul class="mb-0 color-danger">';
										foreach($cultivation['errors'] as $error) {
											$h .= '<li>'.$errors[$error].'</li>';
										}
									$h .= '</ul>';
								$h .= '</td>';
							$h .= '</tr>';

						}

					}

					$h .= '</tbody>';

				}

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

}
?>
