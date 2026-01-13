<?php
namespace selling;

class CsvLib {

	public static function uploadProducts(\farm\Farm $eFarm): bool {

		return \main\CsvLib::upload('import-products-'.$eFarm['id'], function($csv) {

			if(
				count(array_intersect($csv[0], ['type', 'name', 'vat_rate'])) === 3 and
				count(array_intersect($csv[0], ['price_private', 'price_pro'])) > 0
			) {
				$csv = self::convertProducts($csv);
				if($csv === NULL) {
					return NULL;
				}
			} else {
				\Fail::log('main\csvSource');
				return NULL;
			}

			return $csv;

		});

	}

	public static function convertProducts(array $products): ?array {

		$import = [];

		$head = array_shift($products);

		foreach($products as $product) {

			if(count($product) < count($head)) {
				$product = array_merge($product, array_fill(0, count($head) - count($product), ''));
			} else if(count($head) < count($product)) {
				$product = array_slice($product, 0, count($head));
			}

			$line = array_combine($head, $product) + [
				'type' => '',
				'name' => '',
				'unit' => '',
				'price_private' => '',
				'price_pro' => '',
				'vat_rate' => '',
				'additional' => '',
				'reference' => '',
				'description' => '',
				'origin' => '',
				'quality' => '',
				'species' => '',
				'variety' => '',
				'frozen' => '',
				'packaging' => '',
				'composition' => '',
				'allergen' => '',
			];

			$import[] = [
				'profile' => $line['type'] ?: NULL,
				'name' => $line['name'] ?: NULL,
				'unit' => $line['unit'] ?: NULL,
				'price_private' => ($line['price_private'] !== '') ? \main\CsvLib::formatFloat($line['price_private']) : NULL,
				'price_pro' => ($line['price_pro'] !== '') ? \main\CsvLib::formatFloat($line['price_pro']) : NULL,
				'vat_rate' => \main\CsvLib::formatFloat($line['vat_rate']),
				'additional' => $line['additional'] ?: NULL,
				'reference' => $line['reference'] ?: NULL,
				'description' => $line['description'] ?: NULL,
				'origin' => $line['origin'] ?: NULL,
				'quality' => $line['quality'] ?: NULL,
				'species' => $line['species'] ?: NULL,
				'variety' => $line['variety'] ?: NULL,
				'frozen' => (($line['frozen'] ?? 'false') === 'true'),
				'packaging' => $line['packaging'] ?: NULL,
				'composition' => $line['composition'] ?: NULL,
				'allergen' => $line['allergen'] ?: NULL
			];

		}

		return $import;

	}

	public static function resetProducts(\farm\Farm $eFarm): bool {

		return \Cache::redis()->delete('import-products-'.$eFarm['id']);

	}

	public static function importProducts(\farm\Farm $eFarm, array $products): bool {

		$fw = new \FailWatch();

		$cProduct = new \Collection();

		foreach($products as $product) {

			$eProduct = new Product([
				'farm' => $eFarm,
				'profile' => $product['profile'],
				'name' => $product['name'],
				'unit' => $product['eUnit'],
				'private' => ($product['price_private'] !== NULL),
				'privatePrice' => $product['price_private'],
				'pro' => ($product['price_pro'] !== NULL),
				'proPrice' => $product['price_pro'],
				'vat' => $product['vat'],
				'additional' => $product['additional'],
				'origin' => $product['origin'],
				'quality' => $product['quality'] ?? Product::NO,
				'reference' => $product['reference'],
				'description' => new \editor\XmlLib()->fromHtml($product['description']),
				'unprocessedPlant' => $product['ePlant'],
				'unprocessedVariety' => $product['variety'],
				'mixedFrozen' => $product['frozen'],
				'processedPackaging' => $product['packaging'],
				'processedComposition' => $product['composition'],
				'processedAllergen' => $product['allergen'],
			]);

			$cProduct[] = $eProduct;

		}

		if(self::resetProducts($eFarm)) {

			Product::model()->beginTransaction();

			foreach($cProduct as $eProduct) {
				ProductLib::create($eProduct);
			}

			if($fw->ko()) {
				Product::model()->rollBack();
				return FALSE;
			}

			Product::model()->commit();

		}

		return TRUE;

	}

	public static function getProducts(\farm\Farm $eFarm): ?array {

		$import = \Cache::redis()->get('import-products-'.$eFarm['id']);

		if($import === FALSE) {
			return NULL;
		}

		$units = [];
		foreach(UnitLib::getByFarm($eFarm) as $eUnit) {
			$units[mb_strtolower($eUnit['singular'])] = $eUnit;
		}

		$vatRates = SellingSetting::getVatRates($eFarm);

		$errorsCount = 0;
		$errorsGlobal = [
			'vatRates' => [],
			'units' => [],
			'species' => [],
			'references' => [],
		];

		$cachePlants = [];

		$references = [];
		foreach($import as $product) {
			if($product['reference'] !== NULL) {
				$references[] = $product['reference'];
			}
		}

		if($references) {

			$references = array_merge(
				$references,
				Product::model()
					->whereFarm($eFarm)
					->whereReference('IN', $references)
					->getColumn('reference')
			);

			$referencesCount = array_count_values($references);
			$referencesCount = array_filter($referencesCount, fn($value) => $value > 1);

			$errorsGlobal['references'] = array_keys($referencesCount);

		}


		foreach($import as $key => $product) {

			$errors = [];
			$warnings = [];
			$ignore = FALSE;

			if($product['profile'] === NULL) {
				$errors[] = 'profileMissing';
			} else if(in_array($product['profile'], \selling\Product::getProfiles('import')) === FALSE) {
				$errors[] = 'profileInvalid';
			}

			if($product['name'] === NULL) {
				$errors[] = 'nameMissing';
			}

			$import[$key]['eUnit'] = new Unit();

			if($product['unit'] !== NULL) {

				$unit = mb_strtolower($product['unit']);

				if(array_key_exists($unit, $units)) {
					$import[$key]['eUnit'] = $units[$unit];
				} else {
					$errorsGlobal['units'][] = $product['unit'];
					$errors[] = 'unitInvalid';
				}

			}

			if($product['vat_rate'] !== NULL) {

				$vat = array_find_key($vatRates, fn($value) => (float)$value === $product['vat_rate']);

				$import[$key]['vat'] = $vat;

				if($vat === NULL) {
					$errorsGlobal['vatRates'][] = $product['vat_rate'];
					$errors[] = 'vatRateInvalid';
				}

			} else {
				$import[$key]['vat'] = first($vatRates);
			}

			$import[$key]['ePlant'] = new \plant\Plant();

			if($product['species'] !== NULL) {

				if(in_array($product['profile'], Product::getProfiles('unprocessedPlant'))) {

					$plantFqn = toFqn($product['species'], ' ');

					if(empty($cachePlants[$plantFqn])) {

						$cachePlants[$plantFqn] = \plant\Plant::model()
							->select(['id', 'vignette', 'fqn', 'name', 'cycle'])
							->whereFarm($eFarm)
							->whereStatus(\plant\Plant::ACTIVE)
							->or(
								 fn() => $this->whereName($product['species']),
								 fn() => $this->where('REGEXP_REPLACE(REPLACE(name, "-", " "), " +", " ") = '.\plant\Plant::model()->format($plantFqn))
							)
							->get();

					}

					$import[$key]['ePlant'] = $cachePlants[$plantFqn];

					if($cachePlants[$plantFqn]->empty()) {
						$errorsGlobal['species'][] = $product['species'];
						$errors[] = 'speciesInvalid';
					}

				} else {
					$warnings[] = 'speciesIncompatible';
				}

			}

			if($product['quality'] !== NULL) {

				if(in_array($product['profile'], Product::getProfiles('quality')) === FALSE) {
					$warnings[] = 'qualityIncompatible';
					$import[$key]['quality'] = NULL;
				} else if(in_array($product['quality'], Product::model()->getPropertyEnum('quality')) === FALSE) {
					$errors[] = 'qualityInvalid';
				}

			}

			if($product['variety'] !== NULL) {

				if(in_array($product['profile'], Product::getProfiles('unprocessedVariety')) === FALSE) {
					$warnings[] = 'varietyIncompatible';
					$import[$key]['variety'] = NULL;
				}

			}

			if($product['description'] !== NULL) {
				$import[$key]['description'] = '<p>'.encode($product['description']).'</p>';
			}

			if($product['allergen'] !== NULL) {

				if(in_array($product['profile'], Product::getProfiles('processedAllergen')) === FALSE) {
					$warnings[] = 'allergenIncompatible';
					$import[$key]['allergen'] = NULL;
				}

			}

			if($product['packaging'] !== NULL) {

				if(in_array($product['profile'], Product::getProfiles('processedPackaging')) === FALSE) {
					$warnings[] = 'packagingIncompatible';
					$import[$key]['packaging'] = NULL;
				}

			}

			if($product['composition'] !== NULL) {

				if(in_array($product['profile'], Product::getProfiles('processedComposition')) === FALSE) {
					$warnings[] = 'compositionIncompatible';
					$import[$key]['composition'] = NULL;
				}

			}


			if(in_array($product['profile'], Product::getProfiles('mixedFrozen')) === FALSE) {

				if($product['frozen']) {
					$warnings[] = 'frozenIncompatible';
				}

				$import[$key]['frozen'] = NULL;

			}

			$errors = array_filter($errors);
			$errors = array_unique($errors);

			$errorsCount += count($errors);

			$import[$key]['errors'] = $errors;
			$import[$key]['warnings'] = $warnings;
			$import[$key]['ignore'] = $ignore;

		}

		$errorsGlobal['units'] = array_unique($errorsGlobal['units']);
		$errorsGlobal['vatRates'] = array_unique($errorsGlobal['vatRates']);
		$errorsGlobal['species'] = array_unique($errorsGlobal['species']);

		return [
			'import' => $import,
			'errorsCount' => $errorsCount + count($errorsGlobal['species']) + count($errorsGlobal['references']),
			'errorsGlobal' => $errorsGlobal,
		];

	}

}
?>
