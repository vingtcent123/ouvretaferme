<?php
namespace series;

class CsvUi {

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
			'bed_number_rows',
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

	public function getExportBrinjelHeader(): array {

		return [
			'family',
			'crop',
			'sowing_date',
			'planting_date',
			'first_harvest_date',
			'last_harvest_date',
			'length',
			'rows',
			'planting_type',
			'variety',
			'provider',
			'finished',
			'in_greenhouse',
			'price_per_unit',
			'spacing_plants',
			'unit',
			'yield_per_bed_meter',
			'estimated_greenhouse_loss',
			'seeds_per_gram',
			'seeds_per_hole_seedling',
			'seeds_per_hole_direct',
			'seeds_extra_percentage',
			'container_name',
			'container_size'
		];

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

}
?>
