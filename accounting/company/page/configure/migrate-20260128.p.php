<?php
/**
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260128
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260128
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		$oldValues = [\journal\Operation::VAT_STD_OLD, \journal\Operation::VAT_0_OLD, \journal\Operation::VAT_HC_OLD, \journal\Operation::VAT_NS_OLD, \journal\Operation::VAT_HCA_OLD];

		foreach($cFarm as $eFarm) {

			d($eFarm['id']);

			\farm\FarmLib::connectDatabase($eFarm);

			$cOperation = \journal\Operation::model()
				->select(['id', 'details'])
				->whereDetails('!=', NULL)
				->getCollection();

			// On reporte les codes TVA
			foreach($cOperation as $eOperation) {

				foreach($oldValues as $rule) {
					if($eOperation['details']->get() & $rule) {
						$update = [
							'vatRule' => match($rule) {
							\journal\Operation::VAT_STD_OLD => \journal\Operation::VAT_STD,
							\journal\Operation::VAT_0_OLD => \journal\Operation::VAT_0,
							\journal\Operation::VAT_HC_OLD => \journal\Operation::VAT_HC,
							\journal\Operation::VAT_HCA_OLD => \journal\Operation::VAT_HCA,
							}
						];
						\journal\Operation::model()->update($eOperation, $update);
					}
				}

				if($eOperation['details']->get() & \journal\Operation::SELF_CONSUMPTION) {
					\journal\Operation::model()->update($eOperation, ['isSelfConsumption' => TRUE]);
				}
			}

		}

	});
?>
