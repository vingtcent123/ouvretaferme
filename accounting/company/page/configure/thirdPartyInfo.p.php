<?php
/**
 * php framework/lime.php -a ouvretaferme -e dev company/configure/thirdPartyInfo
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

			$cThirdParty = \account\ThirdParty::model()
				->select(\account\ThirdParty::getSelection() + ['customer' => ['vatNumber', 'siret']])
				->whereCustomer('!=', NULL)
				->getCollection();

			foreach($cThirdParty as $eThirdParty) {
				\account\ThirdParty::model()->update($eThirdParty, $eThirdParty['customer']->extracts(['vatNumber', 'siret']));
			}

		}

	});
?>
