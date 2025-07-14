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
}
