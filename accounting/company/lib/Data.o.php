<?php
namespace company;

Class DataObserverLib {

	public static function calculateFarmData(\data\Data $eData): void {

		$cFarm = CompanyLib::getAccountingFarms();

		switch($eData['fqn']) {

			case \data\DataSetting::TYPE_ACCOUNTING_PRODUCTS:
				self::extractsProducts($eData, $cFarm);
				break;

			case \data\DataSetting::TYPE_ACCOUNTING_FINANCIAL_YEARS:
				self::extractsFinancialYears($eData, $cFarm);
				break;

			case \data\DataSetting::TYPE_ACCOUNTING_FINANCIAL_YEAR_FEC:
				self::extractsFinancialYearFec($eData, $cFarm);
				break;

			case \data\DataSetting::TYPE_ACCOUNTING_FINANCIAL_YEAR_DOCUMENTS:
				self::extractsFinancialYearDocuments($eData, $cFarm);
				break;

			case \data\DataSetting::TYPE_ACCOUNTING_BANK_ACCOUNTS:
				self::extractsBankAccounts($eData, $cFarm);
				break;

			case \data\DataSetting::TYPE_ACCOUNTING_BANK_IMPORTS:
				self::extractsBankImports($eData, $cFarm);
				break;

			case \data\DataSetting::TYPE_ACCOUNTING_BANK_OPERATIONS:
				self::extractsBankOperations($eData, $cFarm);
				break;

			case \data\DataSetting::TYPE_ACCOUNTING_CASH_OPERATIONS:
				self::extractsCashOperations($eData, $cFarm);
				break;

			case \data\DataSetting::TYPE_ACCOUNTING_RECONCILIATION_OK:
				self::extractsReconciliationOk($eData, $cFarm);
				break;

			case \data\DataSetting::TYPE_ACCOUNTING_RECONCILIATION_KO:
				self::extractsReconciliationKo($eData, $cFarm);
				break;

			case \data\DataSetting::TYPE_ACCOUNTING_JOURNAL_OPERATIONS:
				self::extractsJournalOperations($eData, $cFarm);
				break;

			case \data\DataSetting::TYPE_ACCOUNTING_JOURNAL_ASSETS:
				self::extractsJournalAssets($eData, $cFarm);
				break;
		}

	}

	private static function extractsJournalAssets(\data\Data $eData, \Collection $cFarm): void {

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$eFarmData = new \data\Farm([
				'data' => $eData,
				'farm' => $eFarm,
				'value' => \asset\Asset::model()->count(),
 			]);

			\data\FarmLib::create($eFarmData);

		}

	}

	private static function extractsJournalOperations(\data\Data $eData, \Collection $cFarm): void {

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$eFarmData = new \data\Farm([
				'data' => $eData,
				'farm' => $eFarm,
				'value' => \journal\Operation::model()->count(),
 			]);

			\data\FarmLib::create($eFarmData);

		}

	}

	private static function extractsReconciliationKo(\data\Data $eData, \Collection $cFarm): void {

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$eFarmData = new \data\Farm([
				'data' => $eData,
				'farm' => $eFarm,
				'value' => \preaccounting\Suggestion::model()
					->whereStatus(\preaccounting\Suggestion::REJECTED)
					->count(),
 			]);

			\data\FarmLib::create($eFarmData);

		}

	}

	private static function extractsReconciliationOk(\data\Data $eData, \Collection $cFarm): void {

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$eFarmData = new \data\Farm([
				'data' => $eData,
				'farm' => $eFarm,
				'value' => \preaccounting\Suggestion::model()
					->whereStatus(\preaccounting\Suggestion::VALIDATED)
					->count(),
 			]);

			\data\FarmLib::create($eFarmData);

		}

	}

	private static function extractsCashOperations(\data\Data $eData, \Collection $cFarm): void {

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$eFarmData = new \data\Farm([
				'data' => $eData,
				'farm' => $eFarm,
				'value' => \cash\Cash::model()->count(),
 			]);

			\data\FarmLib::create($eFarmData);

		}

	}

	private static function extractsBankAccounts(\data\Data $eData, \Collection $cFarm): void {

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$eFarmData = new \data\Farm([
				'data' => $eData,
				'farm' => $eFarm,
				'value' => \bank\BankAccount::model()->count(),
 			]);

			\data\FarmLib::create($eFarmData);

		}

	}

	private static function extractsBankImports(\data\Data $eData, \Collection $cFarm): void {

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$eFarmData = new \data\Farm([
				'data' => $eData,
				'farm' => $eFarm,
				'value' => \bank\Import::model()->count(),
 			]);

			\data\FarmLib::create($eFarmData);

		}

	}

	private static function extractsBankOperations(\data\Data $eData, \Collection $cFarm): void {

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$eFarmData = new \data\Farm([
				'data' => $eData,
				'farm' => $eFarm,
				'value' => \bank\Cashflow::model()->count(),
 			]);

			\data\FarmLib::create($eFarmData);

		}

	}

	private static function extractsFinancialYearDocuments(\data\Data $eData, \Collection $cFarm): void {

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$eFarmData = new \data\Farm([
				'data' => $eData,
				'farm' => $eFarm,
				'value' => \account\FinancialYearDocument::model()->count(),
 			]);

			\data\FarmLib::create($eFarmData);

		}

	}

	private static function extractsFinancialYearFec(\data\Data $eData, \Collection $cFarm): void {

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$eFarmData = new \data\Farm([
				'data' => $eData,
				'farm' => $eFarm,
				'value' => \account\Import::model()->count(),
 			]);

			\data\FarmLib::create($eFarmData);

		}

	}

	private static function extractsFinancialYears(\data\Data $eData, \Collection $cFarm): void {

		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			$eFarmData = new \data\Farm([
				'data' => $eData,
				'farm' => $eFarm,
				'value' => \account\FinancialYear::model()->count(),
 			]);

			\data\FarmLib::create($eFarmData);

		}

	}
	private static function extractsProducts(\data\Data $eData, \Collection $cFarm): void {

		$cProduct = \selling\Product::model()
			->select([
				'farm',
				'count' => new \Sql('COUNT(*)', 'int')
			])
			->or(
				fn() => $this->whereProAccount('!=', NULL),
				fn() => $this->wherePrivateAccount('!=', NULL),
			)
			->whereFarm('IN', $cFarm->getIds())
			->group(['farm'])
			->getCollection(NULL, NULL, 'farm');

		foreach($cFarm as $eFarm) {

			$eFarmData = new \data\Farm([
				'data' => $eData,
				'farm' => $eFarm,
				'value' => $cProduct[$eFarm['id']]['count'] ?? 0,
 			]);

			\data\FarmLib::create($eFarmData);

		}
	}

}
