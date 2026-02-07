<?php
namespace farm;

class ConfigurationLib extends ConfigurationCrud {

	public static function getPropertiesUpdate(): array {
		return ['hasVat', 'vatNumber', 'defaultVat', 'saleClosing', 'defaultVatShipping', 'organicCertifier', 'paymentMode', 'documentCopy', 'pdfNaturalOrder', 'marketSaleDefaultDecimal'];
	}

	public static function createForFarm(\farm\Farm $eFarm): void {

		$eFarm->expects(['legalCountry']);

		$e = new Configuration([
			'farm' => $eFarm,
			'defaultVat' => \selling\SellingSetting::getStartVat($eFarm['legalCountry'])
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

		$newValue = Configuration::model()
			->whereFarm($eFarm)
			->getValue('documentSales');

		for($i = 0; ; $i++) {

			$newValue++;

			if(
				Configuration::model()
					->whereFarm($eFarm)
					->whereDocumentSales('<', $newValue)
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

	public static function getNextDocumentCustomers(\farm\Farm $eFarm): int {

		Configuration::model()->beginTransaction();

		$newValue = Configuration::model()
			->whereFarm($eFarm)
			->getValue('documentCustomers');

		for($i = 0; ; $i++) {

			$newValue++;

			if(
				Configuration::model()
					->whereFarm($eFarm)
					->whereDocumentCustomers('<', $newValue)
					->update([
						'documentCustomers' => $newValue
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
			->select('documentInvoices', 'invoicePrefix')
			->whereFarm($eFarm)
			->get();

		$newValue = $eConfiguration['documentInvoices'];

		for($i = 0; ; $i++) {

			$currentValue = $newValue;
			$newValue++;

			$number = Configuration::getNumber($eConfiguration['invoicePrefix'], $newValue);

			if(\selling\Invoice::model()
				->whereFarm($eFarm)
				->whereNumber($number)
				->exists()) {

				if($i === 100) {
					throw new \Exception("Possible infinite loop");
				}
				
				continue;

			}

			$affected = Configuration::model()
				->whereFarm($eFarm)
				->whereDocumentInvoices($currentValue)
				->update([
					'documentInvoices' => $newValue
				]);

			// Le numéro a pu être incrémenté entre temps
			if($affected > 0) {

				Configuration::model()->commit();

				return $newValue;

			}

		}

	}

	public static function newYear(): void {

		$year = currentYear() - 2000;
		$beforeYear = $year - 1;

		Configuration::model()
			->whereInvoicePrefix('LIKE', '%'.$beforeYear.'%')
			->update([
				'invoicePrefix' => new \Sql('REPLACE(invoicePrefix, '.$beforeYear.', '.$year.')'),
				'documentInvoices' => 0
			]);

	}

}
?>
