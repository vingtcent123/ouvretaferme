<?php
/**
 * Déplace la configuration de TVA de l'exercice comptable vers la configuration de la ferme.
 *
 * php framework/lime.php -a ouvretaferme -e prod dev/module package=farm module=Configuration flags=b
 * php framework/lime.php -a ouvretaferme -e prod dev/module package=farm module=ConfigurationHistory flags=t
 *
 * DEV
 * php framework/lime.php -a ouvretaferme -e dev company/configure/migrate-20260213
 *
 * PROD
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrate-20260213
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

			\account\FinancialYear::model()->pdo()->exec('ALTER TABLE '.\farm\FarmSetting::getDatabaseName($eFarm).'.accountFinancialYear CHANGE `hasVat` `hasVat` tinyint(1) NULL;');

			$eFinancialYearFirst = \account\FinancialYear::model()
				->select('hasVat', 'startDate')
				->sort(['endDate' => SORT_ASC])
				->get();

			if($eFinancialYearFirst->notEmpty()) {

				$eConfigurationHistory = new \farm\ConfigurationHistory([
					'farm' => $eFarm,
					'field' => 'hasVatAccounting',
					'value' => ['hasVatAccounting' => $eFinancialYearFirst['hasVat']],
					'effectiveAt' => $eFinancialYearFirst['startDate'],
				]);

				\farm\ConfigurationHistory::model()->insert($eConfigurationHistory);

			}

			$eFinancialYear = \account\FinancialYear::model()
				->select('hasVat', 'vatChargeability', 'vatFrequency')
				->sort(['endDate' => SORT_DESC])
				->get();

			if($eFinancialYear->notEmpty()) {

				\farm\Configuration::model()
					->whereFarm($eFarm)
					->update([
						'vatChargeability' => $eFinancialYear['vatChargeability'],
						'vatFrequency' => $eFinancialYear['vatFrequency'],
						'hasVatAccounting' => $eFinancialYear['hasVat'],
					]);

			}

		}

	});
?>
