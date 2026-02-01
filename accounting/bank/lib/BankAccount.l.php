<?php
namespace bank;

class BankAccountLib extends BankAccountCrud {

	public static function getPropertiesCreate(): array {
		return ['description'];
	}

	public static function getAllWithCashflow(): \Collection {

		return BankAccount::model()
			->select(
				BankAccount::getSelection() + [
				'cCashflow' => Cashflow::model()
					->select(Cashflow::getSelection())
					->delegateCollection('account', 'id'),
				'nCashflow' => Cashflow::model()
					->select('id')
					->delegateCollection('account', callback: fn(\Collection $cCashflow) => $cCashflow->count()),
			])
			->sort(['accountId' => SORT_ASC])
			->getCollection(NULL, NULL,  'id');
	}
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

		$eBankAccount = BankAccount::model()
			->select(BankAccount::getSelection())
			->whereBankId($bankId)
			->whereAccountId($accountId)
			->get();

		if($eBankAccount->empty()) {

			$eBankAccount = new BankAccount(['bankId' => $bankId, 'accountId' => $accountId]);

		}

		return $eBankAccount;
	}

	public static function createNew(string $bankId, string $accountId): BankAccount {

		BankAccount::model()->beginTransaction();

		// Check if there is already an account. Set current account to default if there is none.
		$cBankAccountDefault = BankAccount::model()->whereIsDefault(TRUE)->count();

		$eLastAccount = \account\Account::model()
			->select(['accountLabel' => new \Sql('MAX(TRIM(TRAILING "0" FROM class))', 'int')])
			->whereClass('LIKE', \account\AccountSetting::BANK_ACCOUNT_CLASS.'%')
			->get();

		$accountLabel = ($eLastAccount->notEmpty() and empty($eLastAccount['accountLabel']) === FALSE) ?
			($eLastAccount['accountLabel'] + 1):
			\account\AccountSetting::BANK_ACCOUNT_CLASS.'1';

		$description = new BankAccountUi()->getUnknownName().' '.$accountId;

		$eAccount = new \account\Account([
			'class' => $accountLabel,
			'description' => $description,
		]);
		\account\AccountLib::create($eAccount);

		$eBankAccount = new BankAccount();
		$eBankAccount->build(['bankId', 'accountId'], [
			'bankId' => $bankId,
			'accountId' => $accountId,
		]);
		$eBankAccount['isDefault'] = ($cBankAccountDefault === 0);
		$eBankAccount['description'] = $description;
		$eBankAccount['account'] = $eAccount;

		self::create($eBankAccount);

		BankAccount::model()->commit();

		return $eBankAccount;

	}

	public static function update(BankAccount $e, array $properties): void {

		BankAccount::model()->beginTransaction();

			parent::update($e, $properties);

			\account\LogLib::save('update', 'Bank', ['id' => $e['id'], 'properties' => $properties]);

		BankAccount::model()->commit();

	}

	public static function delete(BankAccount $e): void {

		BankAccount::model()->beginTransaction();

			parent::delete($e);

			Cashflow::model()
				->whereAccount($e)
				->delete();

			Import::model()
				->whereAccount($e)
				->delete();

			\account\LogLib::save('delete', 'Bank', ['id' => $e['id']]);

		BankAccount::model()->commit();

	}
}
?>
