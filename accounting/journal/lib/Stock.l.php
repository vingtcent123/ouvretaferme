<?php
namespace journal;

Class StockLib extends StockCrud {

	public static function getPropertiesCreate(): array {
		return ['financialYear', 'type', 'account', 'accountLabel', 'variationAccount', 'variationAccountLabel', 'initialStock', 'finalStock', 'variation'];
	}

	public static function getAllForFinancialYear(\account\FinancialYear $eFinancialYear): \Collection {

		$eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($eFinancialYear);

		$financialYears = [$eFinancialYear['id']];
		if($eFinancialYearPrevious->notEmpty()) {
			$financialYears[] = $eFinancialYearPrevious['id'];
		}


		return Stock::model()
			->select(Stock::getSelection())
			->whereFinancialYear('IN', $financialYears)
			->whereReportedTo(NULL)
			->sort(['createdAt' => SORT_ASC])
			->getCollection();

	}
	public static function delete(Stock $e): void {

		$e->expects(['id']);

		$affected = Stock::model()->delete($e);

		// On supprime le lien
		if($affected > 0) {

			$eStockReported = new Stock(['reportedTo' => NULL, 'updatedAt' => new \Sql('NOW()')]);
			Stock::model()
				->select(['reportedTo', 'updatedAt'])
				->whereReportedTo($e)
				->update($eStockReported);

		}

		\account\LogLib::save('delete', 'Stock', ['id' => $e['id']]);

	}

	public static function hasWaitingStockFromPreviousFinancialYear(\account\FinancialYear $eFinancialYear): bool {

		$eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($eFinancialYear);

		return Stock::model()
			->whereFinancialYear($eFinancialYearPrevious)
			->whereReportedTo(NULL)
			->count() > 0;

	}

	public static function setNewStock(Stock $eStockReference, array $input): void {

		$eStock = clone $eStockReference;
		unset($eStock['id']);
		$eStock['initialStock'] = $eStockReference['finalStock'];

		$fw = new \FailWatch();

		$eStock->build(['finalStock', 'variation', 'financialYear'], $input);

		$fw->validate();

		Stock::model()->beginTransaction();

		Stock::model()->insert($eStock);

		$eStockReference['reportedTo'] = $eStock;
		$eStock['updatedAt'] = new \Sql('NOW()');

		Stock::model()
			->select(['reportedTo', 'updatedAt'])
			->update($eStockReference);

		\account\LogLib::save('set', 'Stock', ['id' => $eStock['id']]);

		Stock::model()->commit();

	}

	public static function recordStock(\account\FinancialYear $eFinancialYear): void {

		$cStock = Stock::model()
			->select(Stock::getSelection())
			->whereFinancialYear($eFinancialYear)
			->getCollection();

		foreach($cStock as $eStock) {

			$eOperationVariation = new Operation([
				'financialYear' => $eFinancialYear,
				'account' => $eStock['variationAccount'],
				'accountLabel' => $eStock['variationAccountLabel'],
				'thirdParty' => NULL,
				'description' => new StockUi()->getTranslation($eStock),
				'amount' => abs($eStock['variation']),
				'type' => $eStock['variation'] > 0 ? Operation::CREDIT : Operation::DEBIT,
				'journalCode' => Operation::OD,
				'date' => $eFinancialYear['endDate'],
				'paymentDate' => $eFinancialYear['endDate'],
			]);

			$eOperationStock = new Operation([
				'financialYear' => $eFinancialYear,
				'account' => $eStock['account'],
				'accountLabel' => $eStock['accountLabel'],
				'thirdParty' => NULL,
				'description' => new StockUi()->getTranslation($eStock),
				'amount' => abs($eStock['variation']),
				'type' => $eStock['variation'] > 0 ? Operation::DEBIT : Operation::CREDIT,
				'journalCode' => Operation::OD,
				'date' => $eFinancialYear['endDate'],
				'paymentDate' => $eFinancialYear['endDate'],
			]);

			Operation::model()->insert($eOperationVariation);
			Operation::model()->insert($eOperationStock);

			$eStock['operation'] = $eOperationStock;
			$eStock['updatedAt'] = new \Sql('NOW()');

			Stock::model()
				->select(['operation', 'updatedAt'])
				->update($eStock);

		}

	}

}
