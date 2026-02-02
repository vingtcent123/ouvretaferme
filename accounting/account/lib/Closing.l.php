<?php
namespace account;

Class ClosingLib {

	public static function getFarmersAccountCloseOperation(FinancialYear $eFinancialYearPrevious, string $hash, float $amount): \Collection {

		$cOperation = new \Collection();
		if($amount === 0.0) {
			return new \Collection();
		}

		$cAccount = AccountLib::getByClasses([AccountSetting::FARMER_S_ACCOUNT_CLASS, AccountSetting::CAPITAL_CLASS], 'class');
		$eJournalCode = \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_OD);

		$eAccountFarmer = $cAccount[AccountSetting::FARMER_S_ACCOUNT_CLASS];
		$values = [
			'account' => $eAccountFarmer,
			'accountLabel' => \account\AccountLabelLib::pad($eAccountFarmer['class']),
			'date' => $eFinancialYearPrevious['endDate'],
			'paymentDate' => $eFinancialYearPrevious['endDate'],
			'description' => new FinancialYearUi()->getFarmersAccountClose($eFinancialYearPrevious),
			'amount' => abs($amount),
			'type' => $amount < 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
			'financialYear' => $eFinancialYearPrevious,
			'hash' => $hash,
			'journalCode' => $eJournalCode,
		];

		$cOperation->append(new \journal\Operation($values));

		$eAccountCapital = $cAccount[AccountSetting::CAPITAL_CLASS];
		$values = [
			'account' => $eAccountCapital,
			'accountLabel' => \account\AccountLabelLib::pad($eAccountCapital['class']),
			'date' => $eFinancialYearPrevious['endDate'],
			'paymentDate' => $eFinancialYearPrevious['endDate'],
			'description' => new FinancialYearUi()->getFarmersAccountClose($eFinancialYearPrevious),
			'amount' => abs($amount),
			'type' => $amount > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
			'financialYear' => $eFinancialYearPrevious,
			'hash' => $hash,
			'journalCode' => $eJournalCode,
		];

		$cOperation->append(new \journal\Operation($values));

		return $cOperation;

	}

}
