<?php
new Page()
	->cli('index', function($data) {

		$c = \selling\Sale::model()
			->select([
				'id',
				'customer' => ['user'],
				'shop' => ['shared', 'farm']
			])
			->whereShop('!=', NULL)
			->whereShopSharedCustomer(NULL)
			->getCollection();

		foreach($c as $e) {

			if($e['shop']['shared'] === FALSE) {
				continue;
			}

			$eb = \selling\Customer::model()
				->select('id')
				->whereFarm($e['shop']['farm'])
				->whereUser($e['customer']['user'])
				->get();

			if($eb->empty()) {
				echo $e['customer']['user']['id'];
				echo '!';
			} else {
				$e['shopSharedCustomer'] = $eb;
				\selling\Sale::model()
					->select('shopSharedCustomer')
					->update($e);
				echo '.';
			}

		}


	});
?>