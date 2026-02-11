<?php
/**
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev preaccounting/configure/calculateSuggestion farm= import=
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod preaccounting/configure/calculateSuggestion farm= import=
 */
new Page()
	->cli('index', function($data) {

		if(get_exists('farm') === FALSE) {
			echo "Please provide farm as get arg.\n";
			return;
		}
		if(get_exists('import') === FALSE) {
			echo "Please provide import id as get arg.\n";
			return;
		}

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		foreach($cFarm as $eFarm) {

			d($eFarm['id']);

			\farm\FarmLib::connectDatabase($eFarm);

			\preaccounting\SuggestionLib::calculateSuggestionsByFarm($eFarm, GET('import'));
		}

	});
?>
