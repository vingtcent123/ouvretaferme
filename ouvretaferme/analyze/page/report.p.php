<?php
(new \analyze\ReportPage(function($data) {

		$data->season = REQUEST('season', 'int');

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))
			->validate('canAnalyze')
			->validateSeason($data->season);

	}))
	->getCreateElement(function($data) {

		return new \analyze\Report([
			'farm' => $data->eFarm,
			'season' => $data->season
		]);


	})
	->create(function($data) {

		$eReportFrom = \analyze\ReportLib::getById(GET('from'));

		if($eReportFrom->notEmpty()) {

			if(
				$eReportFrom['farm']['id'] !== $data->eFarm['id'] or
				$eReportFrom['season'] !== $data->season
			) {
				throw new NotExpectedAction('Consistency');
			}

			$eReportFrom['cCultivation'] = \analyze\CultivationLib::getByReport($eReportFrom, index: ['cultivation']);
			$eReportFrom['cProduct'] = \analyze\ProductLib::getByReport($eReportFrom);

			$plant = $eReportFrom['plant']['id'];

			$data->e['name'] = $eReportFrom['name'];
			$data->e['description'] = $eReportFrom['description'];

		} else {
			$plant = GET('plant');
		}

		$ePlant = \plant\PlantLib::getById($plant);

		if($ePlant->notEmpty()) {

			$ePlant->validate('canRead');

			$data->e['cCultivation'] = \series\CultivationLib::getForReport($data->eFarm, $data->season, $ePlant);

			$data->e['cSeries'] = $data->e['cCultivation']->reduce(function($eCultivation, $c) {
				$c[] = new \analyze\Cultivation([
					'cultivation' => $eCultivation
				]);
				return $c;
			}, new Collection());

			if($eReportFrom->notEmpty()) {
				$data->e['firstSaleAt'] = $eReportFrom['firstSaleAt'];
				$data->e['lastSaleAt'] = $eReportFrom['lastSaleAt'];
			} else {
				$data->e['firstSaleAt'] = $data->season.'-'.Setting::get('farm\seasonBegin');
				$data->e['lastSaleAt'] = date('Y-m-d', strtotime($data->e['firstSaleAt'].' + 1 YEAR - 1 DAY'));
			}

			$data->workingTimeNoSeries = \series\TaskLib::calculateWorkingTimeForReport($data->eFarm, $data->season, $ePlant);
			$data->cProduct = \selling\ProductLib::getForReport($ePlant, $data->e['firstSaleAt'], $data->e['lastSaleAt']);

		} else {
			$data->workingTimeNoSeries = NULL;
			$data->cProduct = new Collection();
		}

		$data->e['plant'] = $ePlant;
		$data->e['from'] = $eReportFrom;
		$data->e['cPlant'] = \series\CultivationLib::getPlantsBySeason($data->eFarm, $data->season);

		$data->e['farm']['selling'] = \selling\ConfigurationLib::getByFarm($data->e['farm']);

		throw new ViewAction($data);

	})
	->doCreate(function($data) {

		// Suppression du rapport d'origine
		$eReportFrom = \analyze\ReportLib::getById(POST('from'));

		if($eReportFrom->notEmpty()) {

			$eReportFrom->validate('canWrite');
			\analyze\ReportLib::delete($eReportFrom);

		}

		throw new RedirectAction(\analyze\ReportUi::url($data->e).'?success=analyze:Report::created');

	});

(new Page())
	->post('products', function($data) {

		$data->season = POST('season', 'int');
		$data->ePlant = \plant\PlantLib::getById(POST('plant'))->validate('canRead');
		$data->ePlant['farm']->validate('canAnalyze');

		$firstSale = POST('firstSaleAt');
		$lastSale = POST('lastSaleAt');

		$data->cProduct = \selling\ProductLib::getForReport($data->ePlant, $firstSale, $lastSale);

		throw new ViewAction($data);

	});

(new \analyze\ReportPage())
	->applyElement(function($data, \analyze\Report $e) {

		$data->eFarm = \farm\FarmLib::getById($e['farm']);
		$data->season = $e['season'];

	})
	->read('/rapport/{id}', function($data) {

		\farm\FarmerLib::register($data->eFarm);

		$data->cCultivation = \analyze\CultivationLib::getByReport($data->e);
		$data->ccProduct = \analyze\ProductLib::getByReport($data->e);

		$data->eTest = \analyze\ReportLib::getTest($data->e);

		$data->cReportSiblings = \analyze\ReportLib::getSiblings($data->e);

		throw new ViewAction($data);

	}, onEmpty: fn($data) => throw new BackAction())
	->doUpdateProperties('doTest', ['testArea', 'testAreaOperator', 'testWorkingTime', 'testWorkingTimeOperator', 'testCosts', 'testCostsOperator', 'testTurnover', 'testTurnoverOperator'], fn() => throw new ReloadAction(), validate: ['canRead'])
	->update()
	->doUpdate(function($data) {
		throw new ReloadAction('analyze', 'Report::updated');
	})
	->doDelete(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlAnalyzeReport($data->eFarm, $data->season).'?success=analyze:Report::deleted');
	});
?>
