<?php
/**
 * Déplace la configuration de TVA de l'exercice comptable vers la configuration de la ferme.
 *
 * php framework/lime.php -a ouvretaferme -e prod dev/module package=farm module=Configuration flags=b
 * php framework/lime.php -a ouvretaferme -e prod dev/module package=farm module=ConfigurationHistory flags=t
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260213
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260213
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

			\preaccounting\SuggestionLib::calculateSuggestionsByFarm($eFarm, 1);

		}

	});
?>
