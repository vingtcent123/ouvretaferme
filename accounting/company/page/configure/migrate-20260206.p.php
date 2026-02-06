<?php
/**
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260206
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260206
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		foreach($cFarm as $eFarm) {

			d($eFarm['id']);

			\farm\FarmLib::connectDatabase($eFarm);

			\account\FinancialYear::model()->pdo()->exec('ALTER TABLE '.\farm\FarmSetting::getDatabaseName($eFarm).'.accountFinancialYearDocument ADD COLUMN `createdAt` datetime NOT NULL DEFAULT NOW();');

		}

	});
?>
