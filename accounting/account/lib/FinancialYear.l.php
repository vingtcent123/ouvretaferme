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

	/**
	 * @param FinancialYear $eFinancialYear Exercice sur lequel écrire le bilan d'ouverture
	 */
	public static function openFinancialYear(FinancialYear $eFinancialYear, array $journalCodes): void {

		$eFinancialYearPrevious = self::getPreviousFinancialYear($eFinancialYear);

		FinancialYear::model()->beginTransaction();

		OpeningLib::open($eFinancialYearPrevious, $eFinancialYear, $journalCodes);

		LogLib::save('open', 'FinancialYear', ['id' => $eFinancialYear['id']]);

		FinancialYear::model()->update($eFinancialYear, [
			'openDate' => new \Sql('NOW()'),
		]);

		FinancialYear::model()->commit();

	}

	public static function closeFinancialYear(FinancialYear $eFinancialYear): void {

		FinancialYear::model()->beginTransaction();

		// 1- Calcul des amortissements classe 2. + des étalements de subvention
		\asset\AssetLib::amortizeAll($eFinancialYear);

		// 2- Charges et Produits constatés d'avance
		\journal\DeferralLib::recordDeferralIntoFinancialYear($eFinancialYear);

		// Mettre les numéros d'écritures
		\journal\OperationLib::setNumbers($eFinancialYear);

		FinancialYear::model()->update($eFinancialYear, [
			'status' => FinancialYear::CLOSE,
			'closeDate' => new \Sql('NOW()'),
		]);

		LogLib::save('close', 'FinancialYear', ['id' => $eFinancialYear['id']]);

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

		return self::getCache('list', fn() => FinancialYear::model()
			->select(FinancialYear::getSelection())
			->sort(['startDate' => SORT_DESC])
			->getCollection(NULL, NULL, 'id'));

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

	public static function getFinancialYearForDate(string $date, \Collection $cFinancialYear): FinancialYear {

		foreach($cFinancialYear as $eFinancialYear) {
			if($date >= $eFinancialYear['startDate'] and $date <= $eFinancialYear['endDate']) {
				return $eFinancialYear;
			}
		}

		return new FinancialYear();
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

	public static function isDateInFinancialYear(string $date, FinancialYear $eFinancialYear): bool {

		return($date >= $eFinancialYear['startDate'] and $date <= $eFinancialYear['endDate']);

	}

	public static function create(FinancialYear $e): void {

		$eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canManage');

		FinancialYear::model()->beginTransaction();

			FinancialYear::model()->insert($e);

			\farm\Farm::model()->update($eFarm, [
				'hasFinancialYears' => TRUE
			]);

		FinancialYear::model()->commit();


	}

	public static function cbUpdate(FinancialYear $e, FinancialYear $eOld): void {

		$changes = [];
		foreach($eOld as $property => $value) {
			if($e[$property] !== $value) {
				$changes[$property] = ['old' => $value, 'new' => $e[$property]];
			}
		}
		LogLib::save('update', 'FinancialYear', ['id' => $e['id'], 'changes' => $changes]);

	}

	public static function reopen(FinancialYear $eFinancialYear): void {

		// On ne réinitialise pas closeDate car le bilan de clôture a déjà été réalisé
		FinancialYear::model()
			->update($eFinancialYear, ['status' => FinancialYear::OPEN]);

		LogLib::save('reopen', 'FinancialYear', ['id' => $eFinancialYear['id']]);

	}

	public static function reclose(FinancialYear $eFinancialYear): void {

		FinancialYear::model()
			->update($eFinancialYear, ['status' => FinancialYear::CLOSE]);

		LogLib::save('reclose', 'FinancialYear', ['id' => $eFinancialYear['id']]);

	}

}

?>
