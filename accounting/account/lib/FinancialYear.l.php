<?php
namespace account;
class FinancialYearLib extends FinancialYearCrud {

	public static function getPropertiesCreate(): array {
		return ['startDate', 'endDate', 'hasVat', 'vatFrequency', 'taxSystem'];
	}
	public static function getPropertiesUpdate(): array {
		return ['startDate', 'endDate', 'hasVat', 'vatFrequency', 'taxSystem'];
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
	 * Bilan de clôture
	 */
	public static function closeBalanceSheet(FinancialYear $eFinancialYear): void {


		LogLib::save('closeBalanceSheet', 'financialYear', ['id' => $eFinancialYear['id']]);

	}

	/**
	 * Bilan d'ouverture
	 */
	public static function openBalanceSheet(FinancialYear $eFinancialYear): void {

		LogLib::save('openBalanceSheet', 'financialYear', ['id' => $eFinancialYear['id']]);

	}

	public static function openFinancialYear(): void {

		FinancialYear::model()->beginTransaction();

		$eFinancialYearLast = self::getLastFinancialYear();

		$eFinancialYear = new FinancialYear([
			'status' => FinancialYearElement::OPEN,
			...FinancialYearLib::getNextFinancialYearDates($eFinancialYearLast),
			'hasVat' => $eFinancialYearLast['hasVat'],
			'vatFrequency' => $eFinancialYearLast['vatFrequency'],
			'taxSystem' => $eFinancialYearLast['taxSystem'],
		]);

		// Charges constatées d'avance
		\journal\DeferredChargeLib::recordChargesIntoFinancialYear($eFinancialYear);

		LogLib::save('open', 'financialYear', ['id' => $eFinancialYear['id']]);

		self::create($eFinancialYear);

		FinancialYear::model()->commit();

	}

	public static function closeFinancialYear(FinancialYear $eFinancialYear, array $grantsToRecognize): void {

		if($eFinancialYear['status'] == FinancialYearElement::CLOSE) {
			throw new \NotExpectedAction('Financial year already closed');
		}

		FinancialYear::model()->beginTransaction();

		$eFinancialYear['status'] = FinancialYearElement::CLOSE;
		$eFinancialYear['closeDate'] = new \Sql('NOW()');

		self::update($eFinancialYear, ['status', 'closeDate']);

		// Effectuer toutes les opérations de clôture :

		// 1- Calcul des amortissements
		\asset\AssetLib::depreciateAll($eFinancialYear);

		// Reprise sur subventions
		\asset\AssetLib::finallyRecognizeGrants($eFinancialYear, $grantsToRecognize); // Solde les subventions sélectionnées
		\asset\AssetLib::recognizeGrants($eFinancialYear); // Quote part des sub restantes à réintégrer au CdR

		// 2- Charges constatées d'avance
		\journal\DeferredChargeLib::recordChargesIntoFinancialYear($eFinancialYear);

		// 3- Produits à recevoir
		\journal\AccruedIncomeLib::recordAccruedIncomesIntoFinancialYear($eFinancialYear);

		// 4- Stocks de fin d'exercice
		\journal\StockLib::recordStock($eFinancialYear);

		// 5- Calcul de la TVA
		if($eFinancialYear['hasVat']) {
			\journal\VatLib::balance($eFinancialYear);
		}

		// Mettre les numéros d'écritures
		\journal\OperationLib::setNumbers($eFinancialYear);

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

		return FinancialYear::model()
			->select(FinancialYear::getSelection())
			->whereStatus(FinancialYearElement::OPEN)
			->sort(['endDate' => SORT_DESC])
			->getCollection();

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

			\farm\FarmerLib::setView('viewAnalyzeAccountingYear', $eFarm, $financialYearId);

			return self::getById($financialYearId);

		} else if($eFarm->getView('viewAnalyzeAccountingYear') !== NULL) {

			return self::getById($eFarm->getView('viewAnalyzeAccountingYear'));

		} else {

			return self::selectDefaultFinancialYear();

		}

	}

	public static function getDataCheckForOpenFinancialYears(FinancialYear $eFinancialYear): array {

		if($eFinancialYear['hasVat'] === FALSE) {
			return [];
		}

		// Recherche d'écritures de TVA non déclarées
		$eOperation = \journal\Operation::model()
			->select(['financialYear', 'count' => new \Sql('COUNT(*)', 'int')])
			->whereFinancialYear($eFinancialYear)
			->whereVatDeclaration(NULL)
			->group(['financialYear'])
			->get();

		// Recherche de déclarations de TVA manquantes
		$cVatDeclaration = \journal\VatDeclaration::model()
			->select(['financialYear', 'startDate', 'endDate'])
			->whereFinancialYear($eFinancialYear)
			->getCollection();

		$vatData = ['undeclaredVatOperations' => ($eOperation['count'] ?? 0)];

		$periods = \journal\VatDeclarationLib::calculateAllPeriods($eFinancialYear);
		$missingPeriods = [];
		foreach($periods as $period) {
			$found = $cVatDeclaration->find(fn($e) => $e['financialYear']['id'] === $eFinancialYear['id'] and $e['startDate'] === $period['start'] and $e['endDate'] === $period['end'])->notEmpty();
			if($found === FALSE) {
				$missingPeriods[] = $period;
			}
		}

		$vatData['missingPeriods'] = $missingPeriods;

		return $vatData;

	}
}

?>
