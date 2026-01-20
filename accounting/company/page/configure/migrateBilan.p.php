<?php
/**
 * php framework/lime.php -a ouvretaferme -e prod company/configure/migrateBilan farm=7
 */
new Page()
	->cli('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->whereId(GET('farm'), if: get_exists('farm'))
			->getCollection();

		$eJournalCode = new \company\JournalCode([
			'name' => 'OD Bilan',
			'code' => 'ODB',
			'color' => '#008e00',
			'isCustom' => FALSE,
			'isDisplayed' => FALSE,
			'isReversable' => FALSE,
		]);

		\company\JournalCode::model()->insert($eJournalCode);

		$journalCodeValues = $eJournalCode->getArrayCopy();
		unset($journalCodeValues['id']);


		foreach($cFarm as $eFarm) {

			\farm\FarmLib::connectDatabase($eFarm);

			// Créer un journal d'OD Bilan
			$eJournalCode = new \journal\JournalCode($journalCodeValues);
			\journal\JournalCode::model()->insert($eJournalCode);


		}

	});
?>
