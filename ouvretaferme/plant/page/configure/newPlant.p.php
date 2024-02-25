<?php
(new Page())
	->get('index', function($data) {

		$ePlant = new \plant\Plant([
			'name' => 'Mesclun',
			'fqn' => 'mesclun',
			'latinName' => '-',
			'cycle' => \plant\Plant::ANNUAL,
			'family' => NULL //\plant\FamilyLib::getByFqn('brassicacees')
		]);

		$cFarm = \plant\Plant::model()
			->select('farm')
			->whereFqn('carotte')
			->getColumn('farm');

		foreach($cFarm as $eFarm) {

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

			\plant\Plant::model()->insert($ePlantNew);
			echo "Created for ".($eFarm->empty() ? 'base' : 'farm #'.$eFarm['id'])."\n";

		}

	});
?>