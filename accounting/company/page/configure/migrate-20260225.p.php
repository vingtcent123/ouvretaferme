<?php
/**
 * Initialise les mentions obligatoires
 *
 * php framework/lime.php -a ouvretaferme -e prod dev/module package=farm module=Configuration flags=b
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260225
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260225
 */
new Page()
	->cli('index', function($data) {


		\farm\Configuration::model()
			->where(TRUE)
			->update([
				'invoiceMandatoryTexts' => TRUE,
				'invoiceCollection' => new \farm\ConfigurationUi()->getInvoiceMention('collection'),
				'invoiceLateFees' => new \farm\ConfigurationUi()->getInvoiceMention('lateFees'),
				'invoiceDiscount' => new \farm\ConfigurationUi()->getInvoiceMention('discount'),
			]);

		$cFarm = \farm\Farm::model()
			->select('id')
			->whereLegalCountry('!=', \user\UserSetting::FR)
			->getCollection();

		\farm\Configuration::model()
			->whereFarm('IN', $cFarm->getIds())
			->update([
				'invoiceMandatoryTexts' => FALSE,
				'invoiceCollection' => NULL,
				'invoiceLateFees' => NULL,
				'invoiceDiscount' => NULL,
			]);

	});
?>
