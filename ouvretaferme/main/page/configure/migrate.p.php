<?php
new Page()
	->cli('index', function($data) {

		$c = \selling\Sale::model()
			->select(\selling\Sale::getSelection())
			->whereSecured(TRUE)
			->getCollection();

		foreach($c as $e) {

			\securing\SignatureLib::signSale($e);

		}

	});
?>