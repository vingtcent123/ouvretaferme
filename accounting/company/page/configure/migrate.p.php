<?php
/**
 * À appeler comme ça : php framework/lime.php -a ouvretaferme -e prod company/configure/migrate module=Account
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		foreach($cFarm as $eFarm) {
			\company\CompanyLib::rebuildTables($eFarm);
		}

	});
?>
