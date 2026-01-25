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

	public static function getNextDocumentInvoices(\farm\Farm $eFarm): int {

		Configuration::model()->beginTransaction();

		$eConfiguration = Configuration::model()
			->select('documentInvoices', 'creditPrefix', 'invoicePrefix')
			->whereFarm($eFarm)
			->get();

		$newValue = $eConfiguration['documentInvoices'];

		for($i = 0; ; $i++) {

			$newValue++;

			$numbers = [
				Configuration::getNumber($eConfiguration['creditPrefix'], $newValue),
				Configuration::getNumber($eConfiguration['invoicePrefix'], $newValue),
			];

			if(\selling\Invoice::model()
				->whereFarm($eFarm)
				->whereNumber('IN', $numbers)
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

	public static function newYear(): void {

		$year = currentYear() - 2000;
		$beforeYear = $year - 1;

		Configuration::model()
			->whereInvoicePrefix('LIKE', '%'.$beforeYear.'%')
			->update([
				'invoicePrefix' => new \Sql('REPLACE(invoicePrefix, '.$beforeYear.', '.$year.')'),
				'documentInvoices' => 0
			]);

		Configuration::model()
			->whereCreditPrefix('LIKE', '%'.$beforeYear.'%')
			->update([
				'creditPrefix' => new \Sql('REPLACE(creditPrefix, '.$beforeYear.', '.$year.')'),
			]);

	}

}
?>
