<?php
/**
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260226
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260226
 */
new Page()
	->cli('index', function($data) {

		\farm\Farm::model()->pdo()->exec('ALTER TABLE '.(LIME_ENV === 'dev' ? 'dev_' : '').'ouvretaferme.farm ADD COLUMN `electronicScheme` varchar(255) NULL AFTER `siret`, ADD COLUMN `electronicAddress` varchar(255) NULL AFTER `electronicScheme`;');

		$cFarm = \farm\Farm::model()
			->select('id')
			->getCollection();

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			foreach(\company\CompanyLib::PDP_MODULES as $class) {
				try {
					new \ModuleAdministration($class)->init();
				} catch(Exception $e) {
					try {
						new \ModuleAdministration($class)->rebuild([]);
					} catch(Exception $e2) {
						if(LIME_ENV === 'prod') {
							trigger_error($e);
						}
					}
				}
			}
		}
	});
?>
