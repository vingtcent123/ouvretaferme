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
			'series_id',
			'series_name',
			'species',
			'planting_type',
			'young_plants_seeds',
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

}
?>
