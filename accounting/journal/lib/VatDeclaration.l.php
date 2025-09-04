<?php
namespace journal;

class VatDeclarationLib extends VatDeclarationCrud {
	
	public static function create(VatDeclaration $e): void {

		VatDeclaration::model()->beginTransaction();

		$eFarm = \farm\FarmLib::getById(REQUEST('farm', 'int'))->validate('canManage');
		$eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($eFarm, GET('financialYear', 'int'))->validate('canUpdate');
		$lastPeriod = \journal\VatDeclarationLib::calculateLastPeriod($eFinancialYear);
		
		$search = new \Search(['financialYear' => $eFinancialYear, 'maxDate' => $lastPeriod['end']]);
		
		$cOperationWaiting = \journal\OperationLib::getAllForVatDeclaration($search);
		$vatByType = \journal\VatDeclarationLib::sumByVatType($cOperationWaiting);

		$period = self::calculateLastPeriod($eFinancialYear);

		$e['startDate'] = $period['start'];
		$e['endDate'] = $period['end'];
		$e['collectedVat'] = $vatByType['collectedVat'];
		$e['deductibleVat'] = $vatByType['deductibleVat'];
		$e['dueVat'] = $vatByType['dueVat'];
		$e['type'] = VatDeclaration::STATEMENT; // pour le moment on ne permet pas les rectificatives
		$e['financialYear'] = $eFinancialYear;

		VatDeclaration::model()->insert($e);

		// Les régularisations
		$eOperation = new Operation(['vatDeclaration' => $e, 'updatedAt' => new \Sql("NOW()"), 'vatAdjustement' => TRUE]);

		Operation::model()
			->select(['vatDeclaration', 'updatedAt', 'vatAdjustement'])
			->whereId('IN', $cOperationWaiting->getIds())
			->where(new \Sql('date < "'.$period['start'].'" OR date > "'.$period['end'].'"'))
			->update($eOperation);

		// Les déclarations standard
		$eOperation = new Operation(['vatDeclaration' => $e, 'updatedAt' => new \Sql("NOW()"), 'vatAdjustement' => FALSE]);

		Operation::model()
			->select(['vatDeclaration', 'updatedAt', 'vatAdjustement'])
			->whereId('IN', $cOperationWaiting->getIds())
			->where(new \Sql('date BETWEEN "'.$period['start'].'" AND "'.$period['end'].'"'))
			->update($eOperation);

		VatDeclaration::model()->commit();

	}
	
	public static function getByFinancialYear(\account\FinancialYear $eFinancialYear): \Collection {

		return VatDeclaration::model()
			->select(VatDeclaration::getSelection())
			->whereFinancialYear($eFinancialYear)
			->sort(['startDate' => SORT_DESC])
			->getCollection();

	}

	public static function sumByVatType(\Collection $cOperation): array {

		$collectedVat = $cOperation->sum(fn($e) => mb_substr($e['accountLabel'], 0, mb_strlen(\account\AccountSetting::VAT_SELL_CLASS_PREFIX)) === \account\AccountSetting::VAT_SELL_CLASS_PREFIX ? $e['amount'] : 0);
		$deductibleVat = $cOperation->sum(fn($e) => mb_substr($e['accountLabel'], 0, mb_strlen(\account\AccountSetting::VAT_BUY_CLASS_PREFIX)) === \account\AccountSetting::VAT_BUY_CLASS_PREFIX ? $e['amount'] : 0);

		$dueVat = $collectedVat - $deductibleVat;

		return [
			'collectedVat' => $collectedVat,
			'deductibleVat' => $deductibleVat,
			'dueVat' => $dueVat,
		];

	}

	public static function calculateAllPeriods(\account\FinancialYear $eFinancialYear): ?array {

		// Si la période de début est après la date de fin ou aujourd'hui, rien à faire
		if ($eFinancialYear['startDate'] >= $eFinancialYear['endDate'] or $eFinancialYear['startDate'] > date('y-m-d')) {
			return NULL;
		}

		// Calcule les intervalles selon la périodicité
		$intervalSpec = match ($eFinancialYear['vatFrequency']) {
			\account\FinancialYearElement::MONTHLY => '1 month',
			\account\FinancialYearElement::QUARTERLY => '3 month',
			\account\FinancialYearElement::ANNUALLY => '1 year',
			default => throw new \Exception('Invalid vatFrequency')
		};

		$startTime = strtotime($eFinancialYear['startDate']);
		$startDate = date('Y-m-d', $startTime);
		$endTime = strtotime('+'.$intervalSpec.' - 1 day', $startTime);
		$endDate = date('Y-m-d', $endTime);

		$allPeriods = [];

		// Parcourt les périodes tant que la fin de la période est avant aujourd'hui et avant la date de fin
		while ($endDate <= $eFinancialYear['endDate']) {

			$allPeriods[] = [
				'start' => $startDate,
				'end' => $endDate,
			];

			$startDate = date('Y-m-d', strtotime('+ '.$intervalSpec, strtotime($startDate)));
			$endDate = date('Y-m-d', strtotime('+'.$intervalSpec.' - 1 day', strtotime($startDate)));
		}

		return $allPeriods;

	}

	public static function calculateCurrentPeriod(\account\FinancialYear $eFinancialYear): ?array {

		$today = date('Y-m-d');
		// Si aujourd'hui est avant le début de l'année comptable ou après la fin de l'année comptable, rien à faire
		if($today < $eFinancialYear['startDate'] or $today > $eFinancialYear['endDate']) {
			return NULL;
		}

		// Calcule les intervalles selon la périodicité
		$intervalSpec = match ($eFinancialYear['vatFrequency']) {
			\account\FinancialYearElement::MONTHLY => '1 month',
			\account\FinancialYearElement::QUARTERLY => '3 month',
			\account\FinancialYearElement::ANNUALLY => '1 year',
			default => throw new \Exception('Invalid vatFrequency')
		};

		$startTime = strtotime($eFinancialYear['startDate']);
		$startDate = date('Y-m-d', $startTime);
		$endTime = strtotime('+'.$intervalSpec.' - 1 day', $startTime);
		$endDate = date('Y-m-d', $endTime);

		$lastPeriod = NULL;

		// Parcourt les périodes tant que la fin de la période est avant aujourd'hui et avant la date de fin
		while ($endDate <=$eFinancialYear['endDate'] and $endDate <= $eFinancialYear['endDate']) {

			if($startDate <= $today and $endDate >= $today) {
				return ['start' => $startDate, 'end' => $endDate];
			}

			$startDate = date('Y-m-d', strtotime('+ '.$intervalSpec, strtotime($startDate)));
			$endDate = date('Y-m-d', strtotime('+'.$intervalSpec.' - 1 day', strtotime($startDate)));

		}

		return NULL;
	}

	public static function calculateLastPeriod(\account\FinancialYear $eFinancialYear): ?array {

		// Si la période de début est après la date de fin ou aujourd'hui, rien à faire
		if ($eFinancialYear['startDate'] >= $eFinancialYear['endDate'] or $eFinancialYear['startDate'] > date('y-m-d')) {
			return NULL;
		}

		// Calcule les intervalles selon la périodicité
		$intervalSpec = match ($eFinancialYear['vatFrequency']) {
			\account\FinancialYearElement::MONTHLY => '1 month',
			\account\FinancialYearElement::QUARTERLY => '3 month',
			\account\FinancialYearElement::ANNUALLY => '1 year',
			default => throw new \Exception('Invalid vatFrequency')
		};

		$startTime = strtotime($eFinancialYear['startDate']);
		$startDate = date('Y-m-d', $startTime);
		$endTime = strtotime('+'.$intervalSpec.' - 1 day', $startTime);
		$endDate = date('Y-m-d', $endTime);

		$lastPeriod = NULL;

		// Parcourt les périodes tant que la fin de la période est avant aujourd'hui et avant la date de fin
		while ($endDate <= date('Y-m-d') and $endDate <= $eFinancialYear['endDate']) {

			$lastPeriod = [
				'start' => $startDate,
				'end' => $endDate,
			];

			$startDate = date('Y-m-d', strtotime('+ '.$intervalSpec, strtotime($startDate)));
			$endDate = date('Y-m-d', strtotime('+'.$intervalSpec.' - 1 day', strtotime($startDate)));

		}

		return $lastPeriod;

	}

	public static function listMissingPeriods(\account\FinancialYear $eFinancialYear): array {

		$cVatDeclaration = \journal\VatDeclaration::model()
      ->select(['financialYear', 'startDate', 'endDate'])
      ->whereFinancialYear($eFinancialYear)
      ->getCollection();

		$periods = self::calculateAllPeriods($eFinancialYear);
		$missingPeriods = [];

		foreach($periods as $period) {

			$found = $cVatDeclaration->find(fn($e) =>
				$e['financialYear']['id'] === $eFinancialYear['id']
				and $e['startDate'] === $period['start']
				and $e['endDate'] === $period['end']
			)->notEmpty();

			if($found === FALSE) {
				$missingPeriods[] = $period;
			}

		}

		return $missingPeriods;

	}

}
