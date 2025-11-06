<?php

/**
 * php framework/lime.php -a ouvretaferme -e prod journal/configure/setHash
 * Remet des numéros de hash (MEP du 6/11)
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
     ->select(\farm\Farm::getSelection())
     ->whereHasAccounting(TRUE)
     ->whereId(GET('farm'), if: get_exists('farm'))
     ->getCollection();

		foreach($cFarm as $eFarm) {

			\company\CompanyLib::connectSpecificDatabaseAndServer($eFarm);

			$ccOperation = \journal\Operation::model()
				->select(['id', 'hash', 'createdAt'])
				->whereHash(NULL)
				->getCollection(NULL, NULL, ['createdAt', 'id']);

			foreach($ccOperation as $createdAt => $cOperation) {

				$hash = \journal\OperationLib::generateHash();
				\journal\Operation::model()
					->whereId('IN', $cOperation->getIds())
					->update(['hash' => $hash]);

			}

		echo 'done ('.$eFarm['id'].').';

		}
	});
?>
