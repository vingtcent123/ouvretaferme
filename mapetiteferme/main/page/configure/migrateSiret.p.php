<?php
new Page()
->cli('index', function($data) {

	// 1. Regénérer la table Farm : php framework/lime.php -a mapetiteferme -e dev dev/module package=farm module=Farm flags=b
	// 2. Lancer ce script : php framework/lime.php -a mapetiteferme -e dev configure/migrateSiret
	// 3.a Supprimer les champs legalName, legalEmail, invoiceRegistration, invoiceStreet1, invoiceStreet2, invoicePostcode, invoiceCity de sellingConfiguration (dans le code)
	// 3.b Regénérer la table sellingConfiguration

	$cConfiguration = \selling\Configuration::model()
		->select(\selling\Configuration::getSelection())
		->getCollection();

	foreach($cConfiguration as $eConfiguration) {

		$eFarm = $eConfiguration['farm'];

		$eFarm['siret'] = $eConfiguration['invoiceRegistration'];
		$eFarm['legalName'] = $eConfiguration['legalName'];
		$eFarm['legalEmail'] = $eConfiguration['legalEmail'];
		$eFarm['legalStreet1'] = $eConfiguration['invoiceStreet1'];
		$eFarm['legalStreet2'] = $eConfiguration['invoiceStreet2'];
		$eFarm['legalPostcode'] = $eConfiguration['invoicePostcode'];
		$eFarm['legalCity'] = $eConfiguration['invoiceCity'];

		\farm\FarmLib::update($eFarm, ['siret', 'legalName', 'legalEmail', 'legalStreet1', 'legalStreet2', 'legalPostcode', 'legalCity']);

	}

});
?>
