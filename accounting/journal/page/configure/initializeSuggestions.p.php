<?php

// Exemple d'utilisation :
// Lancer le script php framework/lime.php -a ouvretaferme -e dev journal/configure/initializeSuggestions farm=7
new Page()
	->cli('index', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm', 'int', 7));

		\company\CompanyLib::connectDatabase($eFarm);

		[$cCashflow, ] = \bank\CashflowLib::getAll(new Search(['isReconciliated' => FALSE, 'id' => 1432]), NULL,FALSE);

		foreach($cCashflow as $eCashflow) {
			\preaccounting\SuggestionLib::calculateForCashflow($eFarm, $eCashflow);
		}

	});
