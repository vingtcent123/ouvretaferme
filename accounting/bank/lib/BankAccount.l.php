<?php
namespace bank;

class BankAccountLib extends BankAccountCrud {

	public static function getAll(): \Collection {

		return BankAccount::model()
			->select(BankAccount::getSelection())
			->sort(['accountId' => SORT_ASC])
			->getCollection();
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

		if($eBankAccount->empty()) {

			// Check if there is already an account. Set current account to default if there is none.
			$cBankAccountDefault = BankAccount::model()->whereIsDefault(TRUE)->count();

			$eBankAccount = new BankAccount([
				'bankId' => $bankId,
				'accountId' => $accountId,
				'isDefault' => $cBankAccountDefault === 0,
			]);

			BankAccount::model()->insert($eBankAccount);
		}

		return $eBankAccount;
	}


	public static function update(BankAccount $e, array $properties): void {
		parent::update($e, $properties);

		// Quick label update
		if(in_array('label', $properties) === TRUE) {
			\journal\OperationLib::updateAccountLabels($e);
		}

		\account\LogLib::save('update', 'bank', ['id' => $e['id'], 'properties' => $properties]);
	}
}
?>
