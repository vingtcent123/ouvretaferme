<?php
namespace accounting;
class FinancialYearLib extends FinancialYearCrud {

	public static function getPropertiesCreate(): array {
		return ['startDate', 'endDate'];
	}
	public static function getPropertiesUpdate(): array {
		return ['startDate', 'endDate'];
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

	public static function getNextFinancialYearDates(): array {

		$eFinancialYear = FinancialYearLib::getLastFinancialYear();

		return [
			'startDate' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' +1 day')),
			'endDate' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' +1 year'))
		];

	}

	public static function checkHasAtLeastOne(\Collection $cFinancialYear, \company\Company $eCompany): void {

		if($cFinancialYear->empty() === TRUE) {
			throw new \RedirectAction(\company\CompanyUi::urlAccounting($eCompany).'/financialYear/:create?message=FinancialYear::toCreate');
		}

	}
	/**
	 * Bilan de clôture
	 */
	public static function closeBalanceSheet(FinancialYear $eFinancialYear): void {

	}

	/**
	 * Bilan d'ouverture
	 */
	public static function openBalanceSheet(FinancialYear $eFinancialYear): void {

	}

	public static function closeFinancialYear(FinancialYear $eFinancialYear, bool $createNew): void {

		if($eFinancialYear['status'] == FinancialYearElement::CLOSE) {
			throw new \NotExpectedAction('Financial year already closed');
		}

		FinancialYear::model()->beginTransaction();

		$eFinancialYear['status'] = FinancialYearElement::CLOSE;
		$eFinancialYear['closeDate'] = new \Sql('NOW()');

		self::update($eFinancialYear, ['status', 'closeDate']);

		// Effectuer toutes les opérations de clôture :

		// Calcul des amortissements
		\asset\AssetLib::depreciateAll($eFinancialYear);

		// Reprise des subventions dont l'amortissement de l'immobilisation est terminé
		\asset\AssetLib::subventionReversal($eFinancialYear);

		// Calcul de la TVA
		\journal\VatLib::balance($eFinancialYear);

		if($createNew === TRUE) {

			$eFinancialYearNew = new FinancialYear([
				'status' => FinancialYearElement::OPEN,
				...FinancialYearLib::getNextFinancialYearDates(),
			]);

			self::create($eFinancialYearNew);

		}

		// Mettre les numéros d'écritures
		\journal\OperationLib::setNumbers($eFinancialYear);

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

		$eFinancialYear = new FinancialYear();

		FinancialYear::model()
			->select(FinancialYear::getSelection())
			->whereStatus(FinancialYearElement::OPEN)
			->get($eFinancialYear);

		return $eFinancialYear;

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
}

?>
