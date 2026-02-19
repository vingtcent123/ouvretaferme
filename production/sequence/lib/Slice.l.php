<?php
namespace sequence;

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

			if(
				$eVariety->notEmpty() and
				$eVariety->offsetExists('id') === FALSE
			) {

				try {
					\plant\Variety::model()->insert($eVariety);
				}
					// Variété déjà créée en amont, peuplement de l'ID nécessaire pour continuer les traitements et pas d'erreur de duplicate
				catch(\DuplicateException) {

					\plant\Variety::model()
						->select(\plant\Variety::getSelection())
						->whereFarm($eVariety['farm'])
						->wherePlant($eVariety['plant'])
						->whereName($eVariety['name'])
						->get($eVariety);

				}
			}

		}

	}

	public static function prepare(Crop|\series\Cultivation $eCrop, array $input, string $wrapper): \Collection {

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
			throw new \FailException('sequence\Crop::variety.check', wrapper: $wrapper);
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
					throw new \FailException('sequence\Crop::variety.createEmpty');
				}

				$fw = new \FailWatch();

				$eVariety = new \plant\Variety();
				$eVariety->build(['name'], ['name' => $varietyCreate]);

				if($fw->ko()) {
					throw new \FailException('sequence\Crop::variety.createName');
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
					throw new \FailException('sequence\Crop::variety.notExists', wrapper: $wrapper);
				}

			}

			if($unit === \series\Cultivation::TRAY) {
				$part = (float)($input['varietyPartTray'][$eCrop['sliceTool']['id']][$key] ?? 0.0);
			} else {
				$part = (int)($input['varietyPart'.ucfirst($unit)][$key] ?? 0);
			}

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
		foreach($cSlice as $eSlice) {

			$part = $eSlice['part'.ucfirst($unit)];

			if($part <= 0) {
				throw new \FailException('sequence\Crop::variety.partZero', wrapper: $wrapper);
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
