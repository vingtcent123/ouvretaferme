<?php
new Page()
	->get('index', function($data) {

		$cPlant = new Collection();

		$cPlant[] = new \plant\Plant([
			'name' => 'Chou pointu',
			'fqn' => 'chou-pointu',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('brassicaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Consoude',
			'fqn' => 'consoude',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('boraginaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Digitale',
			'fqn' => 'digitale',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('scrophulariaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Estragon',
			'fqn' => 'estragon',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('asteraceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Eucalyptus',
			'fqn' => 'eucalyptus',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('myrtaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Lupin',
			'fqn' => 'lupin',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('fabaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Marjolaine',
			'fqn' => 'marjolaine',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('lamiaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Monarde',
			'fqn' => 'monarde',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('lamiaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Orge',
			'fqn' => 'orge',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('poaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Pois de senteur',
			'fqn' => 'pois-de-senteur',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('fabaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Prune',
			'fqn' => 'prune',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('rosaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Reine marguerite',
			'fqn' => 'reine-marguerite',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('asteraceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Renoncule',
			'fqn' => 'renoncule',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('ranunculaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Scabieuse',
			'fqn' => 'scabieuse',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('dipsacaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Tulipe',
			'fqn' => 'tulipe',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('amaryllidaceae')
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