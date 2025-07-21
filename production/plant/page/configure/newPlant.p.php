<?php
new Page()
	->get('index', function($data) {

		$cPlant = new Collection();

		$cPlant[] = new \plant\Plant([
			'name' => 'Amarante',
			'fqn' => 'amarante',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('amaranthaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Chanvre',
			'fqn' => 'chanvre',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('cannabaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Chrysanthème',
			'fqn' => 'chrysantheme',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('asteraceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Curcuma',
			'fqn' => 'curcuma',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('zingiberaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Delphinium',
			'fqn' => 'delphinium',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('ranunculaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Gombo',
			'fqn' => 'gombo',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('malvaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Engrais vert',
			'fqn' => 'engrais-vert',
			'cycle' => \plant\Plant::ANNUAL
		]);

		$cFarm = \plant\Plant::model()
			->select('farm')
			->whereFqn('carotte')
			->getColumn('farm');

		foreach($cFarm as $eFarm) {

			foreach($cPlant as $ePlant) {

				$ePlantNew = (clone $ePlant)->merge([
					'farm' => $eFarm
				]);

				if(
					\plant\Plant::model()
						->whereFarm($eFarm)
						->whereFqn($ePlant['fqn'])
						->count() > 0
				) {
					echo "Duplicate for farm ".($eFarm->empty() ? 'base' : 'farm #'.$eFarm['id'])."\n";
					continue;
				}

				\plant\Plant::model()
					->option('add-ignore')
					->insert($ePlantNew);

				echo "Created plant '".$ePlant['fqn']."' for ".($eFarm->empty() ? 'base' : 'farm #'.$eFarm['id'])."\n";

			}

		}

	});
?>