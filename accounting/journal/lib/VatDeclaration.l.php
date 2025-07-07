<?php
namespace journal;

class VatDeclarationLib extends VatDeclarationCrud {
	
	public static function create(VatDeclaration $e): void {

		VatDeclaration::model()->beginTransaction();

		$eFarm = \farm\FarmLib::getById(REQUEST('farm', 'int'))->validate('canManage');
		$eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($eFarm, GET('financialYear', 'int'))->validate('canUpdate');
		
		$search = new \Search(['financialYear' => $eFinancialYear]);
		
		$cOperationWaiting = \journal\OperationLib::getAllForVatDeclaration($search);
		$vatByType = \journal\VatDeclarationLib::sumByVatType($cOperationWaiting);

		$period = self::calculateLastPeriod($eFinancialYear);

		$e['startDate'] = $period['start'];
		$e['endDate'] = $period['end'];
		$e['collectedVat'] = $vatByType['collectedVat'];
		$e['deductibleVat'] = $vatByType['deductibleVat'];
		$e['dueVat'] = $vatByType['dueVat'];
		$e['type'] = VatDeclaration::STATEMENT; // TODO
		$e['financialYear'] = $eFinancialYear;

		VatDeclaration::model()->insert($e);

		$eOperation = new Operation(['vatDeclaration' => $e, 'updatedAt' => new \Sql("NOW()")]);

		Operation::model()
			->select(['vatDeclaration', 'updatedAt'])
			->whereId('IN', $cOperationWaiting->getIds())
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

		$collectedVat = $cOperation->sum(fn($e) => mb_substr($e['accountLabel'], 0, mb_strlen(\Setting::get('account\vatSellClassPrefix'))) === \Setting::get('account\vatSellClassPrefix') ? $e['amount'] : 0);
		$deductibleVat = $cOperation->sum(fn($e) => mb_substr($e['accountLabel'], 0, mb_strlen(\Setting::get('account\vatBuyClassPrefix'))) === \Setting::get('account\vatBuyClassPrefix') ? $e['amount'] : 0);

		$dueVat = $collectedVat - $deductibleVat;

		return [
			'collectedVat' => $collectedVat,
			'deductibleVat' => $deductibleVat,
			'dueVat' => $dueVat,
		];

	}

	public static function calculateLastPeriod(\account\FinancialYear $eFinancialYear): ?array {

		$now = new \DateTime('today');

		// Si la période de début est après la date de fin ou aujourd'hui, rien à faire
		if ($eFinancialYear['startDate'] > $eFinancialYear['endDate'] or $eFinancialYear['startDate'] > date('y-m-d')) {
			return NULL;
		}

		// Calcule les intervalles selon la périodicité
		$intervalSpec = match ($eFinancialYear['vatFrequency']) {
			\account\FinancialYearElement::MONTHLY => 'P1M',
			\account\FinancialYearElement::QUARTERLY => 'P3M',
			\account\FinancialYearElement::ANNUALLY => 'P1Y',
			default => throw new \Exception('Invalid vatFrequency')
		};

		$interval = new \DateInterval($intervalSpec);
		$currentStart = new \DateTime($eFinancialYear['startDate']);
		$currentEnd = (clone $currentStart)->add($interval);

		$lastPeriod = null;

		// Parcourt les périodes tant que la fin de la période est avant aujourd'hui et avant la date de fin
		while ($currentEnd <= $now and $currentEnd <= new \DateTime($eFinancialYear['endDate'])) {
			$lastPeriod = [
				'start' => (clone $currentStart)->format('Y-m-d'),
				'end' => (clone $currentEnd)->format('Y-m-d')
			];

			$currentStart->add($interval);
			$currentEnd->add($interval);
		}

		return $lastPeriod;

	}

}
