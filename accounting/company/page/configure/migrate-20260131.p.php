<?php
/**
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260131
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260131
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

			$cBankAccount = \bank\BankAccount::model()
				->select(\bank\BankAccount::getSelection())
				->getCollection();

			foreach($cBankAccount as $eBankAccount) {

				\bank\BankAccount::model()->beginTransaction();

					$class = rtrim($eBankAccount['label'], '0');

					$description = empty($eBankAccount['description']) ? new \bank\BankAccountUi()->getDefaultName($class) : $eBankAccount['description'];

					$eAccount = \account\Account::model()
						->select(\account\Account::getSelection())
						->whereClass($class)
						->get();

					if($eAccount->empty()) {

						$eAccount = new \account\Account([
							'class' => $class,
							'description' => $description,
							'createdBy' => new \user\User(['id' => 21])
						]);

						\account\AccountLib::create($eAccount);

					}

					$eBankAccount['account'] = $eAccount;
					$eBankAccount['description'] = $description;

					\bank\BankAccount::model()->update($eBankAccount, $eBankAccount->extracts(['account', 'description']));

					\journal\Operation::model()
						->whereAccountLabel('LIKE', $eAccount['class'].'%')
						->update(['account' => $eAccount]);

					\bank\BankAccount::model()->commit();
			}

		}

	});
?>
