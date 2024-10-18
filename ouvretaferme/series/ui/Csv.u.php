<?php
namespace series;

class CsvUi {

	public function __construct() {

		\Asset::css('series', 'csv.css');

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
			'place',
			'species',
			'planting_type',
			'young_plants_seeds',
			'young_plants_tray',
			'sowing_date',
			'planting_date',
			'first_harvest_date',
			'last_harvest_date',
			'use',
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
			'yield_expected',
			'yield_got'
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

	public function getImportFile(array $cultivations, \Collection $cAction): string {

		$h = '';

		$h .= '<table class="tr-even tr-bordered">';

			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th colspan="2">'.s("Espèce").'</th>';
					$h .= '<th></th>';
					$h .= '<th>'.s("Saison").'</th>';
					$h .= '<th>'.s("Implantation").'</th>';
					$h .= '<th>'.s("Récolte").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';

				foreach($cultivations as $cultivation) {

					$cPlant = $cultivation['cPlant'];
					$hasError = FALSE;

					$line = '<td class="td-min-content text-center">';
						if($cPlant->count() === 1) {
							$line .= \plant\PlantUi::getVignette($cPlant->first(), '1.5rem');
						} else {
							$hasError = TRUE;
							$line .= '<span class="color-danger">'.\Asset::icon('exclamation-triangle').'</span>';
						}
					$line.= '</td>';
					$line .= '<td>';
						if($cPlant->empty()) {
							$line .= $cultivation['species'];
						} else if($cPlant->count() === 1) {
							$ePlant = $cPlant->first();
							$line .= encode($ePlant['name']);
						}
						$line .= 'UP';
					$line.= '</td>';
					$line .= '<td>';
						$line .= match($cultivation['place']) {
							Series::GREENHOUSE => \Asset::icon('greenhouse'),
							Series::MIX => \Asset::icon('mix'),
							default => ''
						};
					$line.= '</td>';
					$line .= '<td>';
						$line .= $cultivation['season'].'UP';
					$line.= '</td>';
					$line .= '<td>';

						switch($cultivation['planting_type']) {

							case Cultivation::SOWING :
								$eAction = $cAction[ACTION_SEMIS_DIRECT];
								$line .= '<span style="color: '.$eAction['color'].'" title="'.encode($eAction['name']).'">'.\util\DateUi::numeric($cultivation['sowing_date']).'</span>';
								break;

							case Cultivation::YOUNG_PLANT_BOUGHT :
							case Cultivation::YOUNG_PLANT :
								$eAction = $cAction[ACTION_PLANTATION];
								$line .= '<span style="color: '.$eAction['color'].'" title="'.encode($eAction['name']).'">'.\util\DateUi::numeric($cultivation['planting_date']).'</span>';
								break;

						}

					$line.= '</td>';
					$line .= '<td>';

						if($cultivation['first_harvest_date']) {

							$eAction = $cAction[ACTION_RECOLTE];

							$line .= '<span style="color: '.$eAction['color'].'">';
								if($cultivation['first_harvest_date'] === $cultivation['last_harvest_date']) {
									$line .= \util\DateUi::numeric($cultivation['first_harvest_date']);
								} else {
									$line .= s("{first} → {last}", ['first' => \util\DateUi::numeric($cultivation['first_harvest_date']), 'last' => \util\DateUi::numeric($cultivation['last_harvest_date'])]);
								}
							$line .= '</span>';

						}

					$line.= '</td>';

					$h .= '<tr class="'.($hasError ? 'csv-error' : '').'">';
						$h .= $line;
					$h .= '</tr>';

				}

			$h .= '</tbody>';

		$h .= '</table>';

		return $h;

	}

}
?>
