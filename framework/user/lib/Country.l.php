<?php
namespace user;

class CountryLib extends CountryCrud {

	public static function ask(Country $eCountry): Country {

		if($eCountry->empty()) {
			return $eCountry;
		}

		$callback = fn() => Country::model()
			->select([
				'id', 'name'
			])
			->getCollection(index: 'id');

		return self::getCache('list', $callback)[$eCountry['id']];

	}

	public static function getForSignUp(): array|Country {

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
			return $ccCountry->first();
		} else {

			return [
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

}
?>
