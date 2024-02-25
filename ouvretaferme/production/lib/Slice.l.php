<?php
namespace production;

class SliceLib extends SliceCrud {

	public static function delegateByCrop(): SliceModel {

		return Slice::model()
			->select([
				'variety' => \plant\Variety::getSelection(),
				'partPercent'
			])
			->delegateCollection('crop', callback: fn($c) => $c->sort(['variety' => ['name']], natural: TRUE));

	}

	public static function createCollection(\Collection $c): void {

		$c->map(fn($e) => self::prepareCreate($e));

		try {
			Slice::model()->insert($c);
		} catch(\DuplicateException) {
			Crop::fail('variety.duplicate');
		}

	}

	public static function create(Slice $e): void {

		self::prepareCreate($e);

		parent::create($e);

	}

	private static function prepareCreate(Slice $e): void {

		$e->expects([
			'crop' => ['farm', 'plant', 'sequence']
		]);

		// Transfert de propriétés
		$e['farm'] = $e['crop']['farm'];
		$e['plant'] = $e['crop']['plant'];
		$e['sequence'] = $e['crop']['sequence'];

	}

	public static function createVariety(\Collection $cSlice) {

		foreach($cSlice as $eSlice) {

			$eVariety = $eSlice['variety'];

			if($eVariety->notEmpty() and $eVariety->offsetExists('id') === FALSE) {
				\plant\VarietyLib::create($eSlice['variety']);
			}

		}

	}

	public static function prepare(Crop|\series\Cultivation $eCrop, array $input): \Collection {

		$eCrop ->expects(['plant', 'farm']);

		if($eCrop instanceof Crop) {
			$unit = \series\Cultivation::PERCENT;
		} else {
			$eCrop->expects(['sliceUnit']);
			$unit = $eCrop['sliceUnit'];
		}

		if(
			array_key_exists('variety', $input) === FALSE or
			array_key_exists('varietyCreate', $input) === FALSE or
			array_key_exists('varietyPart'.ucfirst($unit), $input) === FALSE
		) {
			throw new \FailException('production\Crop::variety.check');
		}

		$cSlice = new \Collection();

		foreach((array)$input['variety'] as $key => $varietyId) {

			// Rien n'est renseigné
			if($varietyId === '') {
				continue;
			}
			// Nouvelle variété
			else if($varietyId === 'new') {

				$varietyCreate = $input['varietyCreate'][$key] ?? '';

				if($varietyCreate === '') {
					throw new \FailException('production\Crop::variety.createEmpty');
				}

				// On vérifie par acquit de conscience s'il n'en n'existe pas une du même nom
				$eVariety = \plant\Variety::model()
					->select('id')
					->whereName($varietyCreate)
					->wherePlant($eCrop['plant'])
					->where('farm IS NULL OR farm = '.$eCrop['farm']['id'])
					->get();

				if($eVariety->empty()) {

					$eVariety = new \plant\Variety([
						'name' => $varietyCreate,
						'plant' => $eCrop['plant'],
						'farm' => $eCrop['farm'],
					]);

				}

				// Variété déjà existante
			} else {

				$eVariety = cast($varietyId, 'plant\Variety');

				if(\plant\Variety::model()
						->select('id', 'name')
						->where('plant IS NULL or plant = '.$eCrop['plant']['id'])
						->where('farm IS NULL OR farm = '.$eCrop['farm']['id'])
						->get($eVariety) === FALSE) {
					throw new \FailException('production\Crop::variety.notExists');
				}

			}

			$part = (int)($input['varietyPart'.ucfirst($unit)][$key] ?? 0);

			if($eCrop instanceof Crop) {
				$eSlice = new Slice([
					'crop' => $eCrop
				]);
			} else {
				$eSlice = new \series\Slice([
					'cultivation' => $eCrop
				]);
			}

			$eSlice['variety'] = $eVariety;
			$eSlice['part'.ucfirst($unit)] = $part;

			$cSlice[] = $eSlice;

		}


		// Une seule variété -> les parts sont automatiquement remplies
		if($cSlice->count() === 1) {

			$cSlice->first()['part'.ucfirst($unit)] = match($unit) {
				\series\Cultivation::PERCENT => 100,
				\series\Cultivation::LENGTH => $eCrop['length'],
				\series\Cultivation::AREA => $eCrop['area']
			};

		} else if($cSlice->count() > 1) {

			$sum = 0;

			foreach($cSlice as $eSlice) {

				$part = $eSlice['part'.ucfirst($unit)];

				if($part <= 0) {
					throw new \FailException('production\Crop::variety.partZero');
				}

				$sum += $part;

			}

			// Contrôle de l'intégrité des données fournies
			switch($unit) {

				case \series\Cultivation::PERCENT :
					if($sum !== 100) {
						throw new \FailException('production\Crop::variety.partsPercent');
					}
					break;

				case \series\Cultivation::AREA :
					if($sum !== $eCrop['area']) {
						throw new \FailException('production\Crop::variety.partsArea', ['area' => $eCrop['area']]);
					}
					break;

				case \series\Cultivation::LENGTH :
					if($sum !== $eCrop['length']) {
						throw new \FailException('production\Crop::variety.partsLength', ['length' => $eCrop['length']]);
					}
					break;

			}

		}

		return $cSlice;

	}

	public static function deleteByCrop(Crop $eCrop): void {

		Slice::model()
			->whereCrop($eCrop)
			->delete();

	}

}
?>
