<?php
namespace journal;

Class AccruedIncomeLib extends AccruedIncomeCrud {

	public static function getPropertiesCreate(): array {
		return ['account', 'accountLabel', 'thirdParty', 'description', 'amount', 'date', 'financialYear'];
	}

	public static function getAllProductToReceiveForClosing(\account\FinancialYear $eFinancialYear): \Collection {

		return AccruedIncome::model()
			->select(
				AccruedIncome::getSelection()
				+ ['account' => \account\Account::getSelection()]
				+ ['thirdParty' => \account\ThirdParty::getSelection()]
			)
			->whereFinancialYear($eFinancialYear)
			->sort(['createdAt' => SORT_ASC])
			->getCollection();
	}

	/*
	 * Débite 4181 – Produits à recevoir et crédite 70600000
	 **/
	public static function recordAccruedIncomesIntoFinancialYear($eFinancialYear): void {

		$eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($eFinancialYear);

		$cAccruedIncome = AccruedIncome::model()
			->select(AccruedIncome::getSelection())
			->whereFinancialYear($eFinancialYearPrevious)
			->whereStatus(AccruedIncomeElement::PLANNED)
			->getCollection();

		foreach($cAccruedIncome as $eAccruedIncome) {

			$eOperation4181 = new Operation([
				'financialYear' => $eFinancialYear,
				'account' => \account\AccountLib::getByClass(\Setting::get('account\accruedIncomeClass')),
				'accountLabel' => \account\ClassLib::pad(\Setting::get('account\accruedIncomeClass')),
				'thirdParty' => $eAccruedIncome['thirdParty'],
				'description' => AccruedIncomeUi::getTranslation(AccruedIncome::RECORDED).' - '.$eAccruedIncome['description'],
				'type' => Operation::DEBIT,
				'amount' => $eAccruedIncome['amount'],
				'journalCode' => Operation::OD,
				'date' => $eAccruedIncome['date'],
				'paymentDate' => $eAccruedIncome['date'],
			]);

			Operation::model()->insert($eOperation4181);

			$eOperationCredit = new Operation([
				'financialYear' => $eFinancialYear,
				'account' => $eAccruedIncome['account'],
				'accountLabel' => $eAccruedIncome['accountLabel'],
				'thirdParty' => $eAccruedIncome['thirdParty'],
				'description' => AccruedIncomeUi::getTranslation(AccruedIncome::RECORDED).' - '.$eAccruedIncome['description'],
				'type' => Operation::CREDIT,
				'amount' => $eAccruedIncome['amount'],
				'journalCode' => Operation::OD,
				'date' => $eAccruedIncome['date'],
				'paymentDate' => $eAccruedIncome['date'],
			]);

			Operation::model()->insert($eOperationCredit);

			$eAccruedIncome['operationClosing'] = $eOperationCredit;
			$eAccruedIncome['destinationFinancialYear'] = $eFinancialYear;
			$eAccruedIncome['status'] = AccruedIncome::RECORDED;
			$eAccruedIncome['updatedAt'] = new \Sql('NOW()');

			AccruedIncome::model()
	      ->select(['operationClosing', 'destinationFinancialYear', 'status', 'updatedAt'])
	      ->update($eAccruedIncome);

		}

	}

}
