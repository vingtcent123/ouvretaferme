<?php
namespace account;

class FinancialYear extends FinancialYearElement {

	public function canUpdate(): bool {
		return ($this['status'] === FinancialYearElement::OPEN);
	}

	public function canReadDocument(): bool {
		return $this['status'] === FinancialYearElement::CLOSE;
	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('startDate.loseOperations', function(string $date) use($p): bool {

				if($p->for === 'update') {
					return \journal\OperationLib::countByOldDatesButNotNewDate($this, $date, $this['endDate']) === 0;
				}

				return TRUE;
			})
			->setCallback('startDate.check', function(string $date): bool {

				$eFinancialYear = \account\FinancialYearLib::getFinancialYearSurroundingDate($date, $this['id'] ?? NULL);

				return $eFinancialYear->exists() === FALSE;

			})
			->setCallback('endDate.loseOperations', function(string $date) use($p): bool {

				if($p->for === 'update') {
					return \journal\OperationLib::countByOldDatesButNotNewDate($this, $this['startDate'], $date) === 0;
				}

				return TRUE;

			})
			->setCallback('endDate.check', function(string $date) use($p): bool {

				$eFinancialYear = \account\FinancialYearLib::getFinancialYearSurroundingDate($date, $this['id'] ?? NULL);

				return $eFinancialYear->exists() === FALSE;

			})
			->setCallback('vatFrequency.check', function(?string $vatFrequency) use($p): bool {

				if($p->isBuilt('hasVat') and $this['hasVat'] === FALSE and $vatFrequency === NULL) {
					return TRUE;
				}

				return $vatFrequency !== NULL;

			});

		parent::build($properties, $input, $p);

	}

}
?>
