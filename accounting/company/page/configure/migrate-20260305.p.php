<?php
/**
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260305
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260305
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

			\bank\Cashflow::model()->pdo()->exec("ALTER TABLE farm_".$eFarm['id'].".`bankCashflow` CHANGE `statusCash` `cashStatus` ENUM('waiting','valid','ignored') CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'waiting';");

			\bank\Cashflow::model()
				->join(\cash\Cash::model(), 'm1.id = m2.cashflow')
				->where('m2.source', 'IN', [\cash\Cash::BANK_CASHFLOW])
				->update([
					'cashStatus' => \cash\Cash::VALID,
					'cash' => new Sql('m2.id')
				]);

			\bank\Cashflow::model()
				->whereCashStatus(\bank\Cashflow::WAITING)
				->whereCash(NULL)
				->update([
					'cashStatus' => \bank\Cashflow::WAITING,
				]);

			\selling\Payment::model()
				->join(\cash\Cash::model(), 'm1.sale = m2.sale')
				->where('m2.source', 'IN', [\cash\Cash::SELL_SALE])
				->update([
					'cashStatus' => \cash\Cash::VALID,
					'cash' => new Sql('m2.id')
				]);

			\selling\Payment::model()
				->join(\cash\Cash::model(), 'm1.invoice = m2.invoice')
				->where('m2.source', 'IN', [\cash\Cash::SELL_INVOICE])
				->update([
					'cashStatus' => \cash\Cash::VALID,
					'cash' => new Sql('m2.id')
				]);

			\selling\Payment::model()
				->whereCashStatus(\selling\Payment::WAITING)
				->whereCash(NULL)
				->update([
					'cashStatus' => \selling\Payment::WAITING,
				]);


		}

	});
?>
