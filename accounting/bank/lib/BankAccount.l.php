<?php
namespace bank;

class BankAccountLib extends BankAccountCrud {

	public static function getAll(?string $index = NULL): \Collection {

		return BankAccount::model()
			->select(
				BankAccount::getSelection() + [
				'nCashflow' => Cashflow::model()
					->select('id')
					->delegateCollection('account', callback: fn(\Collection $cCashflow) => $cCashflow->count()),
			])
			->sort(['accountId' => SORT_ASC])
			->getCollection(NULL, NULL,  $index);
	}

	public static function getDefaultAccount(): BankAccount {

		$eBankAccount = new BankAccount();

		// Get default account
		BankAccount::model()
			->select(BankAccount::getSelection())
			->whereIsDefault(TRUE)
			->get($eBankAccount);

		if($eBankAccount->notEmpty()) {
			return $eBankAccount;
		}

		// Get the first found account if no default account found
		BankAccount::model()
			->select(BankAccount::getSelection())
			->get($eBankAccount);

		return $eBankAccount;

	}

	public static function getFromOfx(string $bankId, string $accountId): BankAccount {

		$eBankAccount = new BankAccount();

		BankAccount::model()
			->select(BankAccount::getSelection())
			->whereBankId($bankId)
			->whereAccountId($accountId)
			->get($eBankAccount);

		$eLastBankAccount = BankAccount::model()
			->select(['label' => new \Sql('MAX(label)')])
			->get();

		if($eBankAccount->empty()) {

			// Check if there is already an account. Set current account to default if there is none.
			$cBankAccountDefault = BankAccount::model()->whereIsDefault(TRUE)->count();

			$accountLabel = ($eLastBankAccount->notEmpty() and $eLastBankAccount['label'] !== NULL) ? (int)trim($eLastBankAccount['label'], '0') + 1 : \account\AccountSetting::DEFAULT_BANK_ACCOUNT_LABEL;

			$eBankAccount = new BankAccount([
				'bankId' => $bankId,
				'accountId' => $accountId,
				'isDefault' => $cBankAccountDefault === 0,
				'label' => $accountLabel,
			]);

			BankAccount::model()->insert($eBankAccount);
		}

		return $eBankAccount;
	}


	public static function update(BankAccount $e, array $properties): void {
		parent::update($e, $properties);

		// Quick label update
		if(in_array('label', $properties) === TRUE and $e['farm']->usesAccounting()) {
			\journal\OperationLib::updateAccountLabels($e);
		}

		\account\LogLib::save('update', 'Bank', ['id' => $e['id'], 'properties' => $properties]);
	}
}
?>
