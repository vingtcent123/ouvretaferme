<?php
new Page()
	->cli('index', function($data) {

		$c = \selling\Customer::model()
			->select(\selling\Customer::getSelection() + ['emailOptIn' => new Sql('emailOptIn')])
			->where('emailOptIn IS NOT NULL')
			->whereEmail('!=', NULL)
			->getCollection();

		foreach($c as $e) {

			\mail\ContactLib::autoCreate($e['farm'], $e['email']);

			\mail\Contact::model()
				->whereFarm($e['farm'])
				->whereEmail($e['email'])
				->update([
					'optIn' => $e['emailOptIn']
				]);

		}

		$c = \selling\Customer::model()
			->select(\selling\Customer::getSelection() + ['emailOptOut' => new Sql('emailOptOut')])
			->where('emailOptOut = 0')
			->whereEmail('!=', NULL)
			->getCollection();

		foreach($c as $e) {

			\mail\ContactLib::autoCreate($e['farm'], $e['email']);

			\mail\Contact::model()
				->whereFarm($e['farm'])
				->whereEmail($e['email'])
				->update([
					'optOut' => $e['emailOptOut']
				]);

		}

	});
?>