<?php
namespace farm;

class ConfigurationLib extends ConfigurationCrud {

	public static function getPropertiesUpdate(): array {

		return ['hasVat', 'vatNumber', 'defaultVat', 'saleClosing', 'defaultVatShipping', 'organicCertifier', 'paymentMode', 'documentCopy', 'pdfNaturalOrder', 'marketSaleDefaultDecimal'];
	}

	public static function createForFarm(\farm\Farm $eFarm): void {

		$eFarm->expects(['legalCountry']);

		Configuration::model()->beginTransaction();

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

	public static function getConfigurationForDate(Farm $eFarm, string $field, string $date): ?bool {

		if($field !== 'hasVatAccounting') { // Seul champ à avoir un historique
			return $eFarm->getConf($field);
		}

		$eConfigurationHistory = ConfigurationHistoryLib::getForDate($eFarm, $date);

		if($eConfigurationHistory->empty()) {
			return $eFarm->getConf($field);
		}

		return $eConfigurationHistory['value'][$field];

	}

	public static function update(Configuration $e, array $properties): void {

		Configuration::model()->beginTransaction();

			parent::update($e, $properties);

			if(in_array('hasVatAccounting', $properties) and $e['hasVatAccounting'] !== $e['hasVatAccountingOld']) {

				$fw = new \FailWatch();

				$eConfigurationHistory = new ConfigurationHistory([
					'farm' => $e['farm'],
					'field' => 'hasVatAccounting',
					'value' => $e->extracts(['hasVatAccounting']),
				]);

				$eConfigurationHistory->build(['effectiveAt'], $_POST);

				$fw->validate();

				ConfigurationHistory::model()->option('add-replace')->insert($eConfigurationHistory);
			}

		Configuration::model()->commit();

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

			Configuration::model()
				->whereFarm($eFarm)
				->update([
					'documentSales' => new \Sql('documentSales + 1')
				]);

			$newValue = Configuration::model()
				->whereFarm($eFarm)
				->getValue('documentSales');

		Configuration::model()->commit();

		return $newValue;

	}

	public static function getNextDocumentCustomers(\farm\Farm $eFarm): int {

		Configuration::model()->beginTransaction();

			Configuration::model()
				->whereFarm($eFarm)
				->update([
					'documentCustomers' => new \Sql('documentCustomers + 1')
				]);

			$newValue = Configuration::model()
				->whereFarm($eFarm)
				->getValue('documentCustomers');

		Configuration::model()->commit();

		return $newValue;

	}

	public static function getNextDocumentInvoices(\farm\Farm $eFarm): int {

		Configuration::model()->beginTransaction();

			$prefix = rtrim($eFarm->getConf('invoicePrefix'), '#');

			$current = \selling\Invoice::model()
				->whereFarm($eFarm)
				->where('number REGEXP '.\selling\Invoice::model()->format($prefix.'[0-9]+$'))
				->getValue(new \Sql('MAX(document)'));

			Configuration::model()
				->whereFarm($eFarm)
				->update([
					'documentInvoices' => new \Sql('GREATEST(documentInvoices, '.($current ?? 0).') + 1')
				]);

			$newValue = Configuration::model()
				->whereFarm($eFarm)
				->getValue('documentInvoices');

		Configuration::model()->commit();

		return $newValue;

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
