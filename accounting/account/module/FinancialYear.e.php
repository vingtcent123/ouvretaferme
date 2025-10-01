<?php
namespace account;

class FinancialYear extends FinancialYearElement {

	// Comptabilité à l'engagement
	public function isAccrualAccounting() {
		return $this->notEmpty() and $this['accountingType'] === FinancialYear::ACCRUAL;
	}

	// Comptabilité de trésorerie
	public function isCashAccounting() {
		return $this->empty() or $this['accountingType'] === FinancialYear::CASH;
	}

	public function isCurrent() {

		$this->expects(['startDate', 'endDate']);

		return $this['startDate'] <= date('Y-m-d') and $this['endDate'] >= date('Y-m-d');

	}

	public function canUpdate(): bool {
		return ($this['status'] === FinancialYearElement::OPEN);
	}

	public function canReadDocument(): bool {
		return $this['status'] === FinancialYearElement::CLOSE;
	}

	public function acceptClose(): bool {

		return \journal\StockLib::hasWaitingStockFromPreviousFinancialYear($this) === FALSE and $this['balanceSheetClose'] === FALSE;

	}

	public function acceptOpen(): bool {

		return $this['balanceSheetOpen'] === FALSE and $this['balanceSheetClose'] === FALSE and $this['status'] === FinancialYear::OPEN;

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
