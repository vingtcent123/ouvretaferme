<?php
namespace selling;

class ConfigurationLib extends ConfigurationCrud {

	public static function getPropertiesUpdate(): array {
		return ['hasVat', 'invoiceVat', 'defaultVat', 'defaultVatShipping', 'organicCertifier', 'paymentMode', 'documentCopy', 'pdfNaturalOrder', 'marketSaleDefaultDecimal'];
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

	public static function updateProperty(\farm\Farm $eFarm, string $property, mixed $value): void {

		Configuration::model()
			->whereFarm($eFarm)
			->update([
				$property => $value
			]);

	}

	public static function getNextDocumentSales(\farm\Farm $eFarm): int {

		Configuration::model()->beginTransaction();

		for($i = 0; ; $i++) {

			$currentValue = Configuration::model()
				->whereFarm($eFarm)
				->getValue('documentSales');

			$newValue = $currentValue + 1;

			if(
				Configuration::model()
					->whereFarm($eFarm)
					->where('documentSales', $currentValue)
					->update([
						'documentSales' => $newValue
					]) > 0
			) {

				Configuration::model()->commit();

				return $newValue;

			}

			if($i === 100) {
				throw new \Exception("Possible infinite loop");
			}

		}

	}

	public static function getNextDocumentInvoices(\farm\Farm $eFarm): int {

		Configuration::model()->beginTransaction();

		$eConfiguration = Configuration::model()
			->select('documentInvoices', 'creditPrefix', 'invoicePrefix')
			->whereFarm($eFarm)
			->get();

		$newValue = $eConfiguration['documentInvoices'];

		for($i = 0; ; $i++) {

			$newValue++;

			$names = [
				Configuration::getNumber($eConfiguration['creditPrefix'], $newValue),
				Configuration::getNumber($eConfiguration['invoicePrefix'], $newValue),
			];

			if(Invoice::model()
				->whereFarm($eFarm)
				->whereName('IN', $names)
				->exists()) {

				if($i === 100) {
					throw new \Exception("Possible infinite loop");
				}
				
				continue;

			}

			Configuration::model()
				->whereFarm($eFarm)
				->update([
					'documentInvoices' => $newValue
				]);

			Configuration::model()->commit();

			return $newValue;

		}

	}

}
?>
