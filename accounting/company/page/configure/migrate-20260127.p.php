<?php
/**
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260127
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260127
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		foreach($cFarm as $eFarm) {

			d($eFarm['id']);

			\farm\FarmLib::connectDatabase($eFarm);

			$cOperation = \journal\Operation::model()
				->select(['id', 'operation' => ['id', 'details'], 'details'])
				->whereOperation('!=', NULL)
				->getCollection();

			// On affecte le code standard par défaut
			foreach($cOperation as $eOperation) {

				\journal\Operation::model()->update(
					$eOperation, [
						'details' => new Sql('IF(details IS NULL, '.\journal\Operation::VAT_STD. ', details & '.\journal\Operation::VAT_STD.')')
					]
				);

				\journal\Operation::model()->update(
					$eOperation['operation'], [
						'details' => new Sql('IF(details IS NULL, '.\journal\Operation::VAT_STD. ', details & '.\journal\Operation::VAT_STD.')')
					]
				);
			}

		}

	});
?>
