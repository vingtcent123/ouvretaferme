<?php
new Page()
	->cli('index', function($data) {

		$c = \farm\Farm::model()
			->select('id')
			->getCollection();

		foreach($c as $e) {

			\farm\FarmLib::connectDatabase($e);

			\farm\Farm::model()->beginTransaction();

			\payment\MethodLib::duplicateForFarm($e);

			$cMethodFarm = \payment\Method::model()
				->select('id', 'fqn')
				->whereFarm($e)
				->whereFqn('!=', NULL)
				->getCollection(index: 'fqn');

			$cMethodGeneric = \payment\Method::model()
				->select('id', 'fqn')
				->whereFarm(NULL)
				->whereFqn('!=', NULL)
				->getCollection(index: 'fqn');

			foreach($cMethodFarm as $eMethodFarm) {

				$eMethodGeneric = $cMethodGeneric[$eMethodFarm['fqn']];

				echo \selling\Payment::model()
					->whereFarm($e)
					->whereMethod($eMethodGeneric)
					->update(['method' => $eMethodFarm]).'.';

				try {
				echo \cash\Register::model()
					->wherePaymentMethod($eMethodGeneric)
					->update(['paymentMethod' => $eMethodFarm]).'.';

				} catch(Exception) {echo '!';}
				echo \selling\Customer::model()
					->whereFarm($e)
					->whereDefaultPaymentMethod($eMethodGeneric)
					->update(['defaultPaymentMethod' => $eMethodFarm]).'.';

				try {
				echo \journal\Operation::model()
					->wherePaymentMethod($eMethodGeneric)
					->update(['paymentMethod' => $eMethodFarm]).'.';

				} catch(Exception) {echo '!';}
				echo \farm\Configuration::model()
					->whereFarm($e)
					->whereMarketSalePaymentMethod($eMethodGeneric)
					->update(['marketSalePaymentMethod' => $eMethodFarm]).'.';

				try {

				echo \preaccounting\Suggestion::model()
					->wherePaymentMethod($eMethodGeneric)
					->update(['paymentMethod' => $eMethodFarm]).'.';

				} catch(Exception) {echo '!';}
				echo \shop\Share::model()
					->whereFarm($e)
					->wherePaymentMethod($eMethodGeneric)
					->update(['paymentMethod' => $eMethodFarm]).'.';

				echo \shop\Shop::model()
					->whereFarm($e)
					->wherePaymentMethod($eMethodGeneric)
					->update(['paymentMethod' => $eMethodFarm]).'.';

			}

			\farm\Farm::model()->commit();

		}

	});
?>