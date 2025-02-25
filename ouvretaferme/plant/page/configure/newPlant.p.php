<?php
(new Page())
	->get('index', function($data) {

		$cPlant = new Collection();

		$cPlant[] = new \plant\Plant([
			'name' => 'Pomme',
			'fqn' => 'pomme',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('rosaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Poire',
			'fqn' => 'poire',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('rosaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Abricot',
			'fqn' => 'abricot',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('rosaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Figue',
			'fqn' => 'figue',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('moraceae')
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