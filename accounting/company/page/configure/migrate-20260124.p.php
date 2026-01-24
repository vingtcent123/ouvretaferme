<?php
/**
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260124
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260124
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		$class = 601;

		foreach($cFarm as $eFarm) {

			d($eFarm['id']);

			\farm\FarmLib::connectDatabase($eFarm);

			$eAccount = \account\AccountLib::getByClass($class);
			$eAccountVat = \account\AccountLib::getByClass('44566');

			\account\Account::model()
				->whereId($eAccount['id'])
				->update(['vatAccount' => $eAccountVat]);

		}

	});
?>
