<?php
namespace bank;

class AccountLib extends AccountCrud {

	public static function getByClass(string $class): Account {

		$eAccount = new Account();

		\accounting\Account::model()
       ->select(\accounting\Account::getSelection())
       ->whereClass('=', $class)
       ->get($eAccount);

		return $eAccount;

	}

	public static function getAll(): \Collection {

		return Account::model()
			->select(Account::getSelection())
			->sort(['accountId' => SORT_ASC])
			->getCollection();
	}

	public static function getDefaultAccount(): Account {

		$eAccount = new Account();

		// Get default account
		Account::model()
			->select(Account::getSelection())
			->whereIsDefault(TRUE)
			->get($eAccount);

		if($eAccount->exists() === TRUE) {
			return $eAccount;
		}

		// Get the first found account if no default account found
		Account::model()
			->select(Account::getSelection())
			->get($eAccount);

		return $eAccount;

	}

	public static function getFromOfx(string $bankId, string $accountId): Account {

		$eAccount = new Account();

		Account::model()
			->select(Account::getSelection())
			->whereBankId($bankId)
			->whereAccountId($accountId)
			->get($eAccount);

		if($eAccount->exists() === FALSE) {

			// Check if there is already an account. Set current account to default if there is none.
			$cAccountDefault = Account::model()->whereIsDefault(TRUE)->count();

			$eAccount = new Account([
				'bankId' => $bankId,
				'accountId' => $accountId,
				'isDefault' => $cAccountDefault === 0,
			]);

			Account::model()->insert($eAccount);
		}

		return $eAccount;
	}


	public static function update(Account $e, array $properties): void {
		parent::update($e, $properties);

		// Quick label update
		if(in_array('label', $properties) === TRUE) {
			\journal\OperationLib::updateAccountLabels($e);
		}
	}
}
?>
