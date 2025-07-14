<?php
new Page()
	->get('index', function($data) {

		$cPlant = new Collection();

		$cPlant[] = new \plant\Plant([
			'name' => 'Vesce',
			'fqn' => 'vesce',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('fabaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Sureau',
			'fqn' => 'sureau',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('adoxaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Statice',
			'fqn' => 'statice',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('plumbaginaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Soja',
			'fqn' => 'soja',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('fabaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Sarriette',
			'fqn' => 'sarriette',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('lamiaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Pivoine',
			'fqn' => 'pivoine',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('paeoniaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Oseille',
			'fqn' => 'oseille',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('polygonaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Oca du Pérou',
			'fqn' => 'oca-du-perou',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('oxalidaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Noix',
			'fqn' => 'noix',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('juglandaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Nigelle',
			'fqn' => 'nigelle',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('ranunculaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Mûre',
			'fqn' => 'mure',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('rosaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Millet',
			'fqn' => 'millet',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('poaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Marguerite',
			'fqn' => 'marguerite',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('asteraceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Kiwi',
			'fqn' => 'kiwi',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('actinidiaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Hysope',
			'fqn' => 'hysope',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('lamiaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Hibiscus',
			'fqn' => 'hibiscus',
			'cycle' => \plant\Plant::PERENNIAL,
			'family' => \plant\FamilyLib::getByFqn('malvaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Coquelicot',
			'fqn' => 'coquelicot',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('papaveraceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Arroche',
			'fqn' => 'arroche',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => \plant\FamilyLib::getByFqn('chenopodiaceae')
		]);

		$cPlant[] = new \plant\Plant([
			'name' => 'Achillée millefeuille',
			'fqn' => 'achillee-millefeuille',
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