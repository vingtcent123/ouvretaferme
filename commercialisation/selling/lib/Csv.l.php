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
				'origin' => $line['origin'] ?: NULL,
				'quality' => $line['quality'] ?: NULL,
				'species' => $line['species'] ?: NULL,
				'variety' => $line['variety'] ?: NULL,
				'frozen' => (($line['frozen'] ?? 'no') === 'yes'),
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
				'reference' => $product['reference'],
				'unit' => $product['eUnit'],
				'profile' => $product['profile'],
				'name' => $product['name'],
				'private' => ($product['price_private'] !== NULL),
				'privatePrice' => $product['price_private'],
				'pro' => ($product['price_pro'] !== NULL),
				'proPrice' => $product['price_pro'],
				'vat' => $product['vat'],
				'additional' => $product['additional'],
				'origin' => $product['origin'],
				'quality' => $product['quality'] ?? Product::NO,
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

				if(
					$eProduct['reference'] !== NULL and
					Product::model()
						->select('id')
						->whereReference($eProduct['reference'])
						->get($eProduct)
				) {

					ProductLib::update($eProduct, [
						'name',
						'private', 'privatePrice', 'pro', 'proPrice', 'vat',
						'profile', 'additional', 'origin', 'quality',
						'unprocessedPlant', 'unprocessedVariety', 'mixedFrozen', 'processedPackaging', 'processedComposition', 'processedAllergen'
					]);

				} else {

					ProductLib::create($eProduct);

				}

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
			'profiles' => [],
		];
		$infoGlobal = [
			'references' => [],
		];

		$cachePlants = [];

		$references = [];
		foreach($import as $product) {
			if($product['reference'] !== NULL) {
				if(Product::checkReference($product['reference'])) {
					$references[] = $product['reference'];
				}
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

			$infoGlobal['references'] = array_keys($referencesCount);

		}


		foreach($import as $key => $product) {

			$errors = [];
			$warnings = [];
			$ignore = FALSE;

			if($product['profile'] === NULL) {
				$errors[] = 'profileMissing';
			} else if(in_array($product['profile'], \selling\Product::getProfiles('import')) === FALSE) {
				$errors[] = 'profileInvalid';
				$errorsGlobal['profiles'][] = $product['profile'];
			}

			if($product['name'] === NULL) {
				$errors[] = 'nameMissing';
			}

			if(
				$product['reference'] !== NULL and
				Product::checkReference($product['reference']) === FALSE
			) {
				$errors[] = 'referenceInvalid';
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
		$errorsGlobal['profiles'] = array_unique($errorsGlobal['profiles']);

		return [
			'import' => $import,
			'errorsCount' => $errorsCount + count($errorsGlobal['species']),
			'errorsGlobal' => $errorsGlobal,
			'infoGlobal' => $infoGlobal,
		];

	}

	public static function uploadCustomers(\farm\Farm $eFarm): bool {

		return \main\CsvLib::upload('import-customers-'.$eFarm['id'], function($csv) {

			if(
				count(array_intersect($csv[0], ['type'])) === 1
			) {
				$csv = self::convertCustomers($csv);
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

	public static function convertCustomers(array $customers): ?array {

		$import = [];

		$head = array_shift($customers);

		foreach($customers as $customer) {

			if(count($customer) < count($head)) {
				$customer = array_merge($customer, array_fill(0, count($head) - count($customer), ''));
			} else if(count($head) < count($customer)) {
				$customer = array_slice($customer, 0, count($head));
			}

			$line = array_combine($head, $customer) + [
				'type' => '',
				'private_first_name' => '',
				'private_last_name' => '',
				'pro_commercial_name' => '',
				'pro_legal_name' => '',
				'email' => '',
				'invite' => '',
				'phone' => '',
				'groups' => '',
				'pro_contact_name' => '',
				'pro_siret' => '',
				'pro_vat_number' => '',
				'delivery_street_1' => '',
				'delivery_street_2' => '',
				'delivery_postcode' => '',
				'delivery_city' => '',
				'delivery_country' => '',
				'invoice_street_1' => '',
				'invoice_street_2' => '',
				'invoice_postcode' => '',
				'invoice_city' => '',
				'invoice_country' => '',
			];

			$import[] = [
				'type' => $line['type'] ?: NULL,
				'private_first_name' => $line['private_first_name'] ?: NULL,
				'private_last_name' => $line['private_last_name'] ?: NULL,
				'pro_commercial_name' => $line['pro_commercial_name'] ?: NULL,
				'pro_legal_name' => $line['pro_legal_name'] ?: NULL,
				'email' => $line['email'] ?: NULL,
				'invite' => (($line['invite'] ?? 'no') === 'yes'),
				'phone' => $line['phone'] ?: NULL,
				'groups' => trim($line['groups']) ? preg_split('/\s*,\s*/', trim($line['groups'])) : [],
				'pro_contact_name' => $line['pro_contact_name'] ?: NULL,
				'pro_siret' => $line['pro_siret'] ?: NULL,
				'pro_vat_number' => $line['pro_vat'] ?: NULL,
				'delivery_street_1' => $line['delivery_street_1'] ?: NULL,
				'delivery_street_2' => $line['delivery_street_2'] ?: NULL,
				'delivery_postcode' => $line['delivery_postcode'] ?: NULL,
				'delivery_city' => $line['delivery_city'] ?: NULL,
				'delivery_country' => $line['delivery_country'] ?: NULL,
				'invoice_street_1' => $line['invoice_street_1'] ?: NULL,
				'invoice_street_2' => $line['invoice_street_2'] ?: NULL,
				'invoice_postcode' => $line['invoice_postcode'] ?: NULL,
				'invoice_city' => $line['invoice_city'] ?: NULL,
				'invoice_country' => $line['invoice_country'] ?: NULL,
			];

		}

		return $import;

	}

	public static function resetCustomers(\farm\Farm $eFarm): bool {

		return \Cache::redis()->delete('import-customers-'.$eFarm['id']);

	}

	public static function importCustomers(\farm\Farm $eFarm, array $customers): bool {

		$fw = new \FailWatch();

		$cCustomer = new \Collection();

		foreach($customers as $customer) {

			if($customer['eCustomer']->empty()) {
				continue;
			}

			$eCustomer = $customer['eCustomer']->merge([
				'invite' => $customer['invite']
			]);

			$cCustomer[] = $eCustomer;

		}

		if(self::resetCustomers($eFarm)) {

			if($cCustomer->empty()) {
				return TRUE;
			}

			Customer::model()->beginTransaction();

			foreach($cCustomer as $eCustomer) {

				CustomerLib::create($eCustomer);

				if($eCustomer['invite']) {

					\farm\InviteLib::create(new \farm\Invite([
						'farm' => $eFarm,
						'customer' => $eCustomer,
						'type' => \farm\Invite::CUSTOMER,
						'email' => $eCustomer['email']
					]));

				}

			}

			if($fw->ko()) {
				Customer::model()->rollBack();
				return FALSE;
			}

			Customer::model()->commit();

		}

		return TRUE;

	}

	public static function getCustomers(\farm\Farm $eFarm): ?array {

		$import = \Cache::redis()->get('import-customers-'.$eFarm['id']);

		if($import === FALSE) {
			return NULL;
		}

		$countries = [];
		foreach(\user\CountryLib::getAll() as $eCountry) {
			$countries[mb_strtolower($eCountry['name'])] = $eCountry;
		}

		$cCustomerGroup = CustomerGroupLib::getByFarm($eFarm);

		$groups = [];
		foreach($cCustomerGroup as $eCustomerGroup) {
			$groups[mb_strtolower($eCustomerGroup['name'])] = $eCustomerGroup;
		}

		$errorsCount = 0;
		$errorsGlobal = [
			'countries' => [],
			'groups' => [],
		];
		$infoGlobal = [
			'emails' => [],
		];

		foreach($import as $key => $customer) {

			$eCustomer = new Customer([
				'farm' => $eFarm,
				'user' => new \user\User(),
				'type' => $customer['type'],
				'destination' => Customer::INDIVIDUAL,
				'deliveryCountry' => new \user\Country(),
				'invoiceCountry' => new \user\Country()
			]);

			$errors = [];
			$warnings = [];
			$ignore = FALSE;

			if($customer['type'] === NULL) {
				$errors[] = 'typeMissing';
			} else if($customer['type'] === CustomerUi::getCategories()[Customer::PRIVATE]) {
				$eCustomer['type'] = Customer::PRIVATE;
			} else if($customer['type'] === CustomerUi::getCategories()[Customer::PRO]) {
				$eCustomer['type'] = Customer::PRO;
			} else {
				$errors[] = 'typeInvalid';
				$errorsGlobal['types'][] = $customer['type'];
			}

			switch($eCustomer['type']) {

				case Customer::PRIVATE :

					if($customer['private_last_name'] === NULL) {
						$errors[] = 'lastNameMissing';
					}

					if($customer['pro_commercial_name'] !== NULL) {
						$errors[] = 'commercialNameIncompatible';
					}

					if($customer['pro_legal_name'] !== NULL) {
						$errors[] = 'legalNameIncompatible';
					}

					break;

				case Customer::PRO :

					if($customer['private_last_name'] !== NULL) {
						$errors[] = 'lastNameIncompatible';
					}

					if($customer['private_first_name'] !== NULL) {
						$errors[] = 'firstNameIncompatible';
					}

					if($customer['pro_commercial_name'] === NULL) {
						$errors[] = 'commercialNameMissing';
					}

					break;

			}

			if($customer['delivery_country'] !== NULL) {

				$lowerCountry = mb_strtolower($customer['delivery_country']);
				if(isset($countries[$lowerCountry])) {
					$eCustomer['deliveryCountry'] = $countries[$lowerCountry];
				} else {
					$errors[] = 'deliveryCountryError';
					$errorsGlobal['countries'][] = $customer['delivery_country'];
				}

			}

			if($customer['invoice_country'] !== NULL) {

				$lowerCountry = mb_strtolower($customer['invoice_country']);
				if(isset($countries[$lowerCountry])) {
					$eCustomer['invoiceCountry'] = $countries[$lowerCountry];
				} else {
					$errors[] = 'invoiceCountryError';
					$errorsGlobal['countries'][] = $customer['invoice_country'];
				}

			}


			$import[$key]['cCustomerGroup'] = new \Collection();
			$eCustomer['groups'] = [];

			if($customer['groups']) {

				foreach($customer['groups'] as $group) {

					$lowerGroup = mb_strtolower($group);
					
					if(isset($groups[$lowerGroup])) {
						$import[$key]['cCustomerGroup'][] = $groups[$lowerGroup];
						$eCustomer['groups'][] = $groups[$lowerGroup]['id'];
					} else {
						$errors[] = 'groupError';
						$errorsGlobal['groups'][] = $group;
					}

				}

			}

			$hasCountry = ($eCustomer['deliveryCountry']->notEmpty() or $eCustomer['invoiceCountry']->notEmpty());

			$properties = [
				'email', 'phone',
				'deliveryStreet1', 'deliveryStreet2', 'deliveryPostcode', 'deliveryCity',
				'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity',
				'deliveryAddress', 'invoiceAddress',
			];

			$properties = array_merge($properties, match($eCustomer['type']) {
				Customer::PRIVATE => ['firstName', 'lastName'],
				Customer::PRO => array_merge(
					['commercialName', 'legalName', 'contactName'],
					$hasCountry ? ['siret', 'vatNumber'] : []
				),
				default => []
			});

			$p = new \Properties();

			$eCustomer->build($properties, [
				'firstName' => $customer['private_first_name'],
				'lastName' => $customer['private_last_name'],
				'commercialName' => $customer['pro_commercial_name'],
				'legalName' => $customer['pro_legal_name'],
				'contactName' => $customer['pro_contact_name'],
				'phone' => $customer['phone'],
				'email' => $customer['email'],
				'siret' => $customer['pro_siret'],
				'vatNumber' => $customer['pro_vat_number'],
				'deliveryStreet1' => $customer['delivery_street_1'],
				'deliveryStreet2' => $customer['delivery_street_2'],
				'deliveryPostcode' => $customer['delivery_postcode'],
				'deliveryCity' => $customer['delivery_city'],
				'invoiceStreet1' => $customer['invoice_street_1'],
				'invoiceStreet2' => $customer['invoice_street_2'],
				'invoicePostcode' => $customer['invoice_postcode'],
				'invoiceCity' => $customer['invoice_city'],
			], $p);

			foreach($properties as $property) {

				if($p->isBuilt($property) === FALSE) {
					$errors[] = $property.'Error';
				}

			}

			if($customer['email'] === NULL and $customer['invite']) {
				$errors[] = 'inviteNoEmail';
			}

			if(
				$customer['email'] !== NULL and
				Customer::model()
					->whereFarm($eFarm)
					->whereEmail($customer['email'])
					->exists()
			) {

				$warnings[] = 'emailExisting';
				$infoGlobal['emails'][] = $customer['email'];

				$import[$key]['eCustomer'] = new Customer();

			} else {

				$import[$key]['eCustomer'] = $eCustomer;

			}


			$errors = array_filter($errors);
			$errors = array_unique($errors);

			$errorsCount += count($errors);

			$import[$key]['errors'] = $errors;
			$import[$key]['warnings'] = $warnings;
			$import[$key]['ignore'] = $ignore;

		}

		$errorsGlobal['countries'] = array_unique($errorsGlobal['countries']);
		$errorsGlobal['groups'] = array_unique($errorsGlobal['groups']);

		return [
			'import' => $import,
			'errorsCount' => $errorsCount,
			'errorsGlobal' => $errorsGlobal,
			'infoGlobal' => $infoGlobal,
		];

	}

}
?>
