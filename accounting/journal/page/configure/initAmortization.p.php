<?php

/**
 * php framework/lime.php -a ouvretaferme -e prod journal/configure/initAmortization
 * Recopie les cumuls d'amortissements
 */
new Page()
	->cli('index', function($data) {

		$eFarm = \farm\Farm::model()
     ->select(\farm\Farm::getSelection())
     ->whereHasAccounting(TRUE)
     ->whereId(7)
     ->get();

		\farm\FarmLib::connectDatabase($eFarm);

		$cAsset = \asset\AssetLib::getAll(new Search());

		foreach($cAsset as $eAsset) {

			if($eAsset['cAmortization']->empty()) {
				continue;
			}

			$sumEconomic = $eAsset['cAmortization']->filter(fn($e) => $e['type'] === \asset\Amortization::ECONOMIC)->sum('amount');
			$sumExcess = $eAsset['cAmortization']->filter(fn($e) => $e['type'] === \asset\Amortization::EXCESS)->sum('amount');

			\asset\Asset::model()
				->update($eAsset, [
					'economicAmortization' => $sumEconomic,
					'excessAmortization' => $sumExcess,
				]);

		}

		echo 'done ('.$eFarm['id'].').';
	});
?>
