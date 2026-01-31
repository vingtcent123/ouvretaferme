<?php
namespace user;

class CountryLib extends CountryCrud {

	private static array|\Collection|null $countries = NULL;

	public static function deferred(): Country {

		return self::getCache('list', fn() => self::getAll());

	}

	public static function getAll(): \Collection {

		return Country::model()
			->select([
				'id', 'name'
			])
			->getCollection(index: 'id');

	}

	public static function getForForm(): array|Country {

		if(self::$countries === NULL) {

			$ccCountry = Country::model()
				->select([
					'id',
					'name',
					'positioned' => new \Sql('position IS NOT NULL', 'int')
				])
				->sort([
					new \Sql('position IS NULL'),
					'position' => SORT_ASC,
					'name' => SORT_ASC
				])
				->getCollection(index: ['positioned', NULL]);

			if($ccCountry->count() === 1) {
				self::$countries = $ccCountry->first();
			} else {

				self::$countries = [
					[
						'label' => s("Pays francophones"),
						'values' => $ccCountry[1]
					],
					[
						'label' => s("Autres pays"),
						'values' => $ccCountry[0]
					]
				];

			}

		}

		return self::$countries;

	}

}
?>
