<?php
namespace selling;

class ConfigurationLib extends ConfigurationCrud {

	public static function getPropertiesUpdate(): array {
		return ['legalName', 'invoiceRegistration', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'legalEmail', 'hasVat', 'invoiceVat', 'defaultVat', 'defaultVatShipping', 'organicCertifier', 'paymentMode', 'documentCopy'];
	}

	public static function createForFarm(\farm\Farm $eFarm): void {

		$e = new Configuration([
			'farm' => $eFarm,
			'defaultVat' => SaleLib::getDefaultVat($eFarm)
		]);

		parent::create($e);

	}

	public static function getByFarm(\farm\Farm $eFarm): Configuration {

		return Configuration::model()
			->select(Configuration::getSelection())
			->whereFarm($eFarm)
			->get();

	}

	public static function getNextDocument(\farm\Farm $eFarm, string $property): int {

		for($i = 0; ; $i++) {

			$currentValue = Configuration::model()
				->whereFarm($eFarm)
				->getValue($property);

			$newValue = $currentValue + 1;

			if(
				Configuration::model()
					->whereFarm($eFarm)
					->where($property, $currentValue)
					->update([
						$property => $newValue
					]) > 0
			) {
				return $newValue;
			}

			if($i === 100) {
				throw new \Exception("Possible infinite loop");
			}

		}

	}

}
?>
