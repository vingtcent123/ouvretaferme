<?php
namespace account;

class FinancialYear extends FinancialYearElement {

	public function getLabel(): string {

		if($this->empty()) {
			return '';
		}

		if(substr($this['startDate'], 0, 4) === substr($this['endDate'], 0, 4)) {
			return substr($this['startDate'], 0, 4);
		}

		return substr($this['startDate'], 0, 4).' - '.substr($this['endDate'], 0, 4);

	}

	// Comptabilité à l'engagement
	public function isAccrualAccounting() {
		return FEATURE_ACCOUNTING_ACCRUAL and $this['accountingType'] === FinancialYear::ACCRUAL;
	}

	// Comptabilité de trésorerie
	public function isCashAccounting() {
		return $this['accountingType'] === FinancialYear::CASH;
	}

	public function isCurrent() {
		return $this['startDate'] <= date('Y-m-d') and $this['endDate'] >= date('Y-m-d');
	}

	public function acceptUpdate(): bool {
		return ($this['status'] === FinancialYearElement::OPEN and $this['closeDate'] === NULL);
	}

	public function canReadDocument(): bool {
		return $this['status'] === FinancialYearElement::CLOSE;
	}

	public function acceptImportFec(): bool {

		$this->expects(['nOperation']);
		return $this['nOperation'] === 0;

	}

	public function acceptOpen(): bool {

		return $this['openDate'] === NULL and $this['closeDate'] === NULL and $this['status'] === FinancialYear::OPEN;

	}
	public function isOpen(): bool {

		return $this['openDate'] !== NULL and $this['status'] === FinancialYear::OPEN;

	}
	public function isClosed(): bool {

		return $this['closeDate'] !== NULL and $this['status'] === FinancialYear::CLOSE;

	}

	public function acceptClose(): bool {

		return $this['closeDate'] === NULL and $this['status'] === FinancialYear::OPEN;

	}

	public function acceptReClose(): bool {

		return $this['closeDate'] !== NULL and $this['status'] === FinancialYear::OPEN;

	}

	public function acceptReOpen(): bool {

		return $this['closeDate'] !== NULL and $this['status'] === FinancialYear::CLOSE;

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('accountingType.check', function(?string $accountingType): bool {

				if(FEATURE_ACCOUNTING_ACCRUAL === FALSE) {
					return $accountingType === FinancialYear::CASH;
				}

				return TRUE;
			})
			->setCallback('startDate.loseOperations', function(string $date) use($p): bool {

				if($p->for === 'update') {
					return \journal\OperationLib::countByOldDatesButNotNewDate($this, $date, $this['endDate']) === 0;
				}

				return TRUE;
			})
			->setCallback('startDate.check', function(string $date) use($input): bool {

				if(mb_strlen($date) !== 10 or \util\DateLib::isValid($date) === FALSE) {
					return FALSE;
				}

				// Si on est en création de module de compta (= la BD n'existe pas encore)
				$hasAccounting = ($input['eFarm'] ?? new \farm\Farm())['hasAccounting'] ?? TRUE;
				if($hasAccounting === FALSE) {
					return mb_strlen($date) > 0 and \util\DateLib::isValid($date);
				}

				$eFinancialYear = \account\FinancialYearLib::getFinancialYearSurroundingDate($date, $this['id'] ?? NULL);

				return $eFinancialYear->exists() === FALSE and \util\DateLib::isValid($date);

			})
			->setCallback('endDate.loseOperations', function(string $date) use($p): bool {

				if($p->for === 'update') {
					return \journal\OperationLib::countByOldDatesButNotNewDate($this, $this['startDate'], $date) === 0;
				}

				return TRUE;

			})
			->setCallback('endDate.check', function(string $date) use($input): bool {

				if(mb_strlen($date) !== 10 or \util\DateLib::isValid($date) === FALSE) {
					return FALSE;
				}

				// Si on est en création de module de compta (= la BD n'existe pas encore)
				$hasAccounting = ($input['eFarm'] ?? new \farm\Farm())['hasAccounting'] ?? TRUE;
				if($hasAccounting === FALSE) {
					return mb_strlen($date) > 0 and \util\DateLib::isValid($date);
				}

				$eFinancialYear = \account\FinancialYearLib::getFinancialYearSurroundingDate($date, $this['id'] ?? NULL);

				return $eFinancialYear->exists() === FALSE and \util\DateLib::isValid($date);

			})
			->setCallback('dates.inconsistency', function(?string $endDate) use ($p): bool {

				if($p->isBuilt('startDate') === FALSE or $p->isBuilt('endDate') === FALSE) {
					return TRUE;
				}

				return $this['startDate'] < $this['endDate'];

			})
			->setCallback('hasVat.check', function(?bool $hasVat) use($p, $input): bool {

				return array_key_exists('hasVat', $input);

			})
			->setCallback('vatFrequency.check', function(?string $vatFrequency) use($p): bool {

				if($p->isBuilt('hasVat') and $this['hasVat'] === FALSE and $vatFrequency === NULL) {
					return TRUE;
				}

				return in_array($vatFrequency, FinancialYear::model()->getPropertyEnum('vatFrequency'));
			})
			->setCallback('legalCategory.check', function(?int $legalCategory) use ($p): bool {

				if($p->isBuilt('hasVat') === FALSE or $this['hasVat'] === FALSE) {
					return TRUE;
				}

				return in_array($legalCategory, array_keys(FinancialYearUi::p('legalCategory')->values));

			})
			->setCallback('associates.check', function(?int $associates) use ($p): bool {

				if($p->isBuilt('legalCategory') === FALSE or $this['legalCategory'] !== \company\CompanySetting::CATEGORIE_GAEC) {
					return TRUE;
				}

				return (is_int($associates) and $associates > 0);

			});

		parent::build($properties, $input, $p);

	}

}
?>
