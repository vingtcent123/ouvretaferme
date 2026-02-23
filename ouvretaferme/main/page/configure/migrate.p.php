<?php
new Page()
	->cli('index', function($data) {
dd('si réutilisé, mettre à jour avec les nouvelles unités uniquement au lieu de duplicateForFarm');
		$c = \farm\Farm::model()
			->select('id')
			->getCollection();

		foreach($c as $e) {

			\farm\Farm::model()->beginTransaction();

			\selling\UnitLib::duplicateForFarm($e);

			$cUnitFarm = \selling\Unit::model()
				->select('id', 'fqn')
				->whereFarm($e)
				->whereFqn('!=', NULL)
				->getCollection(index: 'fqn');

			$cUnitGeneric = \selling\Unit::model()
				->select('id', 'fqn')
				->whereFarm(NULL)
				->whereFqn('!=', NULL)
				->getCollection(index: 'fqn');

			foreach($cUnitFarm as $eUnitFarm) {

				$eUnitGeneric = $cUnitGeneric[$eUnitFarm['fqn']];

				echo \selling\Item::model()
					->whereFarm($e)
					->whereUnit($eUnitGeneric)
					->update(['unit' => $eUnitFarm]).'.';

				echo \selling\Product::model()
					->whereFarm($e)
					->whereUnit($eUnitGeneric)
					->update(['unit' => $eUnitFarm]).'.';

			}

			\farm\Farm::model()->commit();

			\farm\Farm::model()->beginTransaction();
			$cUnitFarm = \selling\UnitLib::getByFarm($e);

			foreach($cUnitFarm as $eUnit) {

				if($eUnit['fqn'] !== NULL) {
					continue;
				}

				if(mb_strtolower($eUnit['singular']) === 'litre' or mb_strtolower($eUnit['short']) === 'l') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'liter', limit: 1);
				} else if(mb_strtolower($eUnit['singular']) === 'centilitre' or mb_strtolower($eUnit['short']) === 'cl') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'centiliter', limit: 1);
				} else if(mb_strtolower($eUnit['singular']) === 'millilitre' or mb_strtolower($eUnit['short']) === 'ml') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'milliliter', limit: 1);
				} else if(mb_strtolower($eUnit['singular']) === 'tonne' or mb_strtolower($eUnit['short']) === 't') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'ton', limit: 1);
				} else if(mb_strtolower($eUnit['singular']) === 'unité') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'unit', limit: 1);
				} else if(mb_strtolower($eUnit['singular']) === 'pot') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'pot', limit: 1);
				} else if(mb_strtolower($eUnit['singular']) === 'boite' or mb_strtolower($eUnit['singular']) === 'boîte') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'box', limit: 1);
				} else if(mb_strtolower($eUnit['singular']) === 'barquette') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'tray', limit: 1);
				} else if(mb_strtolower($eUnit['singular']) === 'bouteille') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'bottle', limit: 1);
				} else if(mb_strtolower($eUnit['singular']) === 'colis') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'parcel', limit: 1);
				} else if(mb_strtolower($eUnit['singular']) === 'sachet') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'bag', limit: 1);
				} else if(mb_strtolower($eUnit['singular']) === 'heure') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'hour', limit: 1);
				} else if(mb_strtolower($eUnit['singular']) === 'forfait') {
					$eUnitNew = $cUnitFarm->find(fn($e) => $e['fqn'] === 'package', limit: 1);
				} else {
					continue;
				}

				if($eUnitNew->empty()) {
					echo '!';
				} else {

					$id = $eUnit['id'];
					\selling\Unit::model()->delete($eUnit);

					echo \selling\Unit::model()
						->whereId($eUnitNew)
						->update([
							'id' => $id
						]) ? '.' : '?';

				}

			}
			\farm\Farm::model()->commit();

		}


	});
?>