<?php
(new Page())
	->get('index', function($data) {

		$cPlant = new Collection();

		$cPlant[] = new \plant\Plant([
			'name' => 'Blé',
			'fqn' => 'ble',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('poacees')
		]);
		$cPlant[] = new \plant\Plant([
			'name' => 'Avoine',
			'fqn' => 'avoine',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('poacees')
		]);
		$cPlant[] = new \plant\Plant([
			'name' => 'Bourrache',
			'fqn' => 'bourrache',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('boraginacees')
		]);
		$cPlant[] = new \plant\Plant([
			'name' => 'Féverole',
			'fqn' => 'feverole',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('fabacees')
		]);
		$cPlant[] = new \plant\Plant([
			'name' => 'Lin',
			'fqn' => 'lin',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('linacees')
		]);
		$cPlant[] = new \plant\Plant([
			'name' => 'Luzerne',
			'fqn' => 'luzerne',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('fabacees')
		]);
		$cPlant[] = new \plant\Plant([
			'name' => 'Moutarde',
			'fqn' => 'moutarde',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('brassicacees')
		]);
		$cPlant[] = new \plant\Plant([
			'name' => 'Safran',
			'fqn' => 'safran',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('iridacees')
		]);
		$cPlant[] = new \plant\Plant([
			'name' => 'Sarrasin',
			'fqn' => 'sarrasin',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('polygonacees')
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