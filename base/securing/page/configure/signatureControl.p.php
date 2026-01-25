<?php
new Page()
	->cli('sales', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate();
		$eSale = \selling\SaleLib::getById(GET('sale'));
		if($eSale->notEmpty()) {
			$eSale->validateProperty('farm', $eFarm);
		}

		\securing\SignatureControlLib::controlSales($eFarm, $eSale);

	})
	->cli('hmac', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate();

		\securing\SignatureControlLib::controlHmac($eFarm);

	})
	->cli('rebuild', function($data) {

		$eFarm = \farm\FarmLib::getById(GET('farm'))->validate();

		\securing\SignatureControlLib::rebuild($eFarm);

	});

?>
