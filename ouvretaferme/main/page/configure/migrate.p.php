<?php
new Page()
	->cli('index', function($data) {

		$c = \selling\Customer::model()
			->select('id', 'farm')
			->whereNumber(NULL)
			->getCollection();

		foreach($c as $e) {

			$e['document'] = \farm\ConfigurationLib::getNextDocumentCustomers($e['farm']);
			$e['number'] = $e->calculateNumber();

			\selling\Customer::model()
				->select('document', 'number')
				->update($e);

		}

	});
?>