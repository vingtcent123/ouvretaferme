<?php
new Page()
	->cli('checkSales', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate();
		$eSale = \selling\SaleLib::getById(GET('sale'));
		if($eSale->notEmpty()) {
			$eSale->validateProperty('farm', $eFarm);
		}

		\securing\SignatureControlLib::controlSales($eFarm, $eSale);

	})
	->cli('checkCash', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate();

		\securing\SignatureControlLib::controlCash($eFarm);

	})
	->cli('hmacSales', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate();

		\securing\SignatureControlLib::controlHmac($eFarm, \securing\Signature::SALE);

	})
	->cli('hmacCash', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate();

		\securing\SignatureControlLib::controlHmac($eFarm, \securing\Signature::CASH);

	})
	->cli('rebuildSales', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate();

		\securing\SignatureControlLib::rebuildSales($eFarm);

	})
	->cli('rebuildCash', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate();

		\securing\SignatureControlLib::rebuildCash($eFarm);

	});

?>
