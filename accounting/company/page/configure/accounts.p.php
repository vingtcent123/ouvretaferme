<?php
/**
 * Script pour rajouter un compte manquant.
 * php framework/lime.php -a ouvretaferme -e prod company/configure/accounts
 */
new Page()
	->cli('index', function($data) {

		$class = '4781';
		$eGenericAccount = \company\GenericAccountLib::getByClass($class);

		if($eGenericAccount->notEmpty()) {
			return;
		}

		$eGenericAccount = new \company\GenericAccount([
			'id' => 293,
			'class' => $class,
			'description' => 'Mali de fusion sur actif circulant',
			'type' => \company\GenericAccount::AGRICULTURAL,
		]);

		\company\GenericAccount::model()->insert($eGenericAccount);

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->getCollection();

		foreach($cFarm as $eFarm) {

			\company\CompanyLib::connectDatabase($eFarm);

			$eAccount = \account\AccountLib::getByClass($class);

			if($eAccount->notEmpty()) {

				d('Farm #'.$eFarm['id'].' already has an account with class '.$class.' (#'.$eAccount['id'].')');

			} else {

				$eGenericAccount['createdBy'] = new \user\User(['id' => 21]);
				\account\Account::model()->insert($eGenericAccount);
				\account\Account::model()
					->whereClass($class)
					->update(['id' => $eGenericAccount['id']]);

			}

		}

	});
?>
