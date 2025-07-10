<?php
new Page()
	->get('index', function($data) {

		$cPlant = new Collection();

		$cPlant[] = new \plant\Plant([
			'name' => 'Myrtille',
			'fqn' => 'myrtille',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('rosaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Bleuet',
			'fqn' => 'bleuet',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('asteraceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Phacélie',
			'fqn' => 'phacelie',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('boraginaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Dahlia',
			'fqn' => 'dahlia',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('asteraceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Sorgho',
			'fqn' => 'sorgho',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('poaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Seigle',
			'fqn' => 'seigle',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('poaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Tournesol',
			'fqn' => 'tournesol',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('poaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Agastache',
			'fqn' => 'agastache',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('lamiaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Capucine',
			'fqn' => 'capucine',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('tropaeolaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Capucine',
			'fqn' => 'capucine',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('tropaeolaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Cerise',
			'fqn' => 'cerise',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('rosaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Chou romanesco',
			'fqn' => 'chou-romanesco',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('brassicaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Cresson',
			'fqn' => 'cresson',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('brassicaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Mizuna',
			'fqn' => 'mizuna',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('brassicaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Cornichon',
			'fqn' => 'cornichon',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('cucurbitaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Gingembre',
			'fqn' => 'gingembre',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('zingiberaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Livèche',
			'fqn' => 'liveche',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('apiaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Mélisse',
			'fqn' => 'melisse',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('lamiaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Muflier',
			'fqn' => 'muflier',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('plantaginaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Sauge',
			'fqn' => 'sauge',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('lamiaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Zinnia',
			'fqn' => 'zinnia',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('asteraceae')
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