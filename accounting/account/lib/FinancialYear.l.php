<?php
namespace account;
class FinancialYearLib extends FinancialYearCrud {

	private static ?\Collection $cOpenFinancialYear = NULL;

	public static function getPropertiesCreate(): array {
		return ['accountingType', 'startDate', 'endDate', 'hasVat', 'vatFrequency', 'taxSystem', 'legalCategory', 'associates'];
	}
	public static function getPropertiesUpdate(): array {
		return ['accountingType', 'startDate', 'endDate', 'hasVat', 'vatFrequency', 'taxSystem', 'legalCategory', 'associates'];
	}

	public static function getPreviousFinancialYear(FinancialYear $eFinancialYear): FinancialYear {

		$eFinancialYearPrevious = new FinancialYear();

		FinancialYear::model()
			->select(FinancialYear::getSelection())
			->whereEndDate('<=', $eFinancialYear['startDate'])
			->sort(['endDate' => SORT_DESC])
			->get($eFinancialYearPrevious);

		return $eFinancialYearPrevious;

	}

	public static function getNextFinancialYearDates(?FinancialYear $eFinancialYear = NULL): array {

		$eFinancialYear ??= FinancialYearLib::getLastFinancialYear();

		return [
			'startDate' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' +1 day')),
			'endDate' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' +1 year'))
		];

	}

	public static function checkHasAtLeastOne(\Collection $cFinancialYear, \farm\Farm $eFarm): void {

		if($cFinancialYear->empty() === TRUE) {
			throw new \RedirectAction(\company\CompanyUi::urlAccount($eFarm).'/financialYear/:create?message=FinancialYear::toCreate');
		}

	}

	/**
	 * @param FinancialYear $eFinancialYear Exercice sur lequel écrire le bilan d'ouverture
	 */
	public static function openFinancialYear(FinancialYear $eFinancialYear): void {

		FinancialYear::model()->beginTransaction();

		$eFinancialYearPrevious = self::getPreviousFinancialYear($eFinancialYear);
		$cOperation = \journal\OperationLib::getForOpening($eFinancialYearPrevious);

		// 1. Report des soldes
		\journal\OperationLib::createForOpening($cOperation, $eFinancialYear, $eFinancialYearPrevious);

		// 2. Charges et Produits constatés d'avance
		\journal\DeferralLib::deferIntoFinancialYear($eFinancialYearPrevious, $eFinancialYear);

		LogLib::save('open', 'financialYear', ['id' => $eFinancialYear['id']]);

		FinancialYear::model()->update($eFinancialYear, [
			'balanceSheetOpen' => TRUE,
		]);

		FinancialYear::model()->commit();

	}

	public static function closeFinancialYear(FinancialYear $eFinancialYear, array $grantsToRecognize): void {

		if($eFinancialYear['status'] == FinancialYearElement::CLOSE) {
			throw new \NotExpectedAction('Financial year already closed');
		}

		FinancialYear::model()->beginTransaction();

		// Effectuer toutes les opérations de clôture :

		// 1- Calcul des amortissements
		\asset\AssetLib::amortizeAll($eFinancialYear);

		// 2- Reprise sur subventions
		//\asset\AssetLib::finallyRecognizeGrants($eFinancialYear, $grantsToRecognize); // Solde les subventions sélectionnées
		//\asset\AssetLib::recognizeGrants($eFinancialYear); // Quote part des sub restantes à réintégrer au CdR

		// 3- Charges et Produits constatés d'avance
		\journal\DeferralLib::recordDeferralIntoFinancialYear($eFinancialYear);

		// 4- Stocks de fin d'exercice
		\journal\StockLib::recordStock($eFinancialYear);

		// 5- Calcul de la TVA
		if($eFinancialYear['hasVat']) {
			\journal\VatLib::balance($eFinancialYear);
		}

		// Mettre les numéros d'écritures
		\journal\OperationLib::setNumbers($eFinancialYear);

		FinancialYear::model()->update($eFinancialYear, [
			'status' => FinancialYear::CLOSE,
			'closeDate' => new \Sql('NOW()'),
			'balanceSheetClose' => TRUE,
		]);

		LogLib::save('close', 'financialYear', ['id' => $eFinancialYear['id']]);

		FinancialYear::model()->commit();

	}

	public static function getFinancialYearSurroundingDate(string $date, ?int $excludedId): FinancialYear {

		$eFinancialYear = new FinancialYear();

		FinancialYear::model()
			->select(FinancialYear::getSelection())
			->whereStartDate('<=', $date)
			->whereEndDate('>=', $date)
			->whereId('!=', $excludedId, if:$excludedId !== NULL)
			->get($eFinancialYear);


		return $eFinancialYear;

	}
	public static function selectDefaultFinancialYear(): FinancialYear {

		return FinancialYear::model()
			->select(FinancialYear::getSelection())
			->whereStatus(FinancialYearElement::OPEN)
			->get();

	}

	public static function getAll(): \Collection {

		return FinancialYear::model()
			->select(FinancialYear::getSelection())
			->sort(['startDate' => SORT_DESC])
			->getCollection();

	}

	public static function isDateLinkedToFinancialYear(string $date, \Collection $cFinancialYear): bool {

		foreach($cFinancialYear as $eFinancialYear) {

			if(
				$date >= date('Y-m-d', strtotime($eFinancialYear['startDate']))
				and $date <= date('Y-m-d', strtotime($eFinancialYear['endDate']))
			) {
				return TRUE;
			}
		}

		return FALSE;

	}

	public static function getLastFinancialYear(): FinancialYear {

		$eFinancialYear = new FinancialYear();

		FinancialYear::model()
			->select(FinancialYear::getSelection())
			->whereStatus(FinancialYearElement::OPEN)
			->sort(['endDate' => SORT_DESC])
			->get($eFinancialYear);

		return $eFinancialYear;

	}

	public static function getLastClosedFinancialYear(): FinancialYear {

		$eFinancialYear = new FinancialYear();

		FinancialYear::model()
			->select(FinancialYear::getSelection())
			->whereStatus(FinancialYearElement::CLOSE)
			->sort(['endDate' => SORT_DESC])
			->get($eFinancialYear);

		return $eFinancialYear;

	}

	public static function getOpenFinancialYearByDate(string $date): FinancialYear {

		$eFinancialYear = new FinancialYear();

		FinancialYear::model()
			->select(FinancialYear::getSelection())
			->whereStatus(FinancialYearElement::OPEN)
			->whereStartDate('<=', $date)
			->whereEndDate('>=', $date)
			->get($eFinancialYear);

		return $eFinancialYear;

	}

	public static function getOpenFinancialYears(): \Collection {

		if(self::$cOpenFinancialYear === NULL) {
			self::$cOpenFinancialYear = FinancialYear::model()
				->select(FinancialYear::getSelection())
				->whereStatus('=', FinancialYear::OPEN)
				->sort(['endDate' => SORT_DESC])
				->getCollection();
		}

		return self::$cOpenFinancialYear;

	}

	public static function getFinancialYearForDate(string $date): FinancialYear {

		return FinancialYear::model()
			->select(FinancialYear::getSelection())
			->whereStartDate('<=', $date)
			->whereEndDate('>=', $date)
			->get();

	}

	public static function isDateInOpenFinancialYear(string $date): bool {

		$cFinancialYear = self::getOpenFinancialYears();

		foreach($cFinancialYear as $eFinancialYear) {
			if($date >= $eFinancialYear['startDate'] and $date <= $eFinancialYear['endDate']) {
				return TRUE;
			}
		}

		return FALSE;

	}

	public static function getDynamicFinancialYear(\farm\Farm $eFarm, int $financialYearId): FinancialYear {

		if($financialYearId) {

			$eFinancialYear = self::getById($financialYearId);
			self::setDefaultView($eFarm, $eFinancialYear);

			return $eFinancialYear;

		} else if($eFarm->getView('viewAccountingYear') !== NULL) {

			return self::getById($eFarm->getView('viewAccountingYear'));

		} else {

			return self::selectDefaultFinancialYear();

		}

	}

	public static function update(FinancialYear $e, array $properties): void {

		$eFarm = \farm\FarmLib::getById(POST('farm'));

		FinancialYear::model()->beginTransaction();

		parent::update($e, $properties);

		if($eFarm->getView('viewAccountingYear') === $e['id']) {
			self::setDefaultView($eFarm, $e);
		}

		FinancialYear::model()->commit();

	}

	public static function cbUpdate(FinancialYear $e, FinancialYear $eOld): void {

		$changes = [];
		foreach($eOld as $property => $value) {
			if($e[$property] !== $value) {
				$changes[$property] = ['old' => $value, 'new' => $e[$property]];
			}
		}
		LogLib::save('update', 'financialYear', ['id' => $e['id'], 'changes' => $changes]);

	}

	private static function setDefaultView(\farm\Farm $eFarm, FinancialYear $eFinancialYear): void {

		\farm\FarmerLib::setView('viewAccountingYear', $eFarm, $eFinancialYear['id']);
		\farm\FarmerLib::setView('viewAccountingType', $eFarm, $eFinancialYear['accountingType']);
		\farm\FarmerLib::setView('viewAccountingHasVat', $eFarm, $eFinancialYear['hasVat']);

	}

}

?>
