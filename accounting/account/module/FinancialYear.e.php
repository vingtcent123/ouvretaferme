<?php
namespace account;

class FinancialYear extends FinancialYearElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'cDocument' => FinancialYearDocument::model()
				->select(FinancialYearDocument::getSelection())
				->whereGeneration('NOT IN', FinancialYearDocument::FAIL)
				->delegateCollection('financialYear', 'type'),
		];

	}

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
		return $this['accountingType'] === FinancialYear::ACCRUAL;
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
		return ($this->isClosed() === FALSE and $this['nOperation'] === 0 and $this->isOpen() === FALSE);

	}

	public function acceptOpen(): bool {

		return $this['openDate'] === NULL and $this['closeDate'] === NULL and $this['status'] === FinancialYear::OPEN;

	}

	public function acceptDelete(): bool {

		return $this['nOperation'] === 0;

	}

	public function isOpen(): bool {

		return $this['openDate'] !== NULL and $this['status'] === FinancialYear::OPEN;

	}
	public function isClosed(): bool {

		return $this['closeDate'] !== NULL and $this['status'] === FinancialYear::CLOSE;

	}

	public function acceptDownloadOpen(): bool {
		return FinancialYearDocumentLib::hasDocument($this, FinancialYearDocumentLib::OPENING);
	}

	public function acceptDownloadOpenDetailed(): bool {
		return FinancialYearDocumentLib::hasDocument($this, FinancialYearDocumentLib::OPENING_DETAILED);
	}

	public function acceptDownloadClose(): bool {
		return FinancialYearDocumentLib::hasDocument($this, FinancialYearDocumentLib::CLOSING);
	}

	public function acceptDownloadCloseDetailed(): bool {
		return FinancialYearDocumentLib::hasDocument($this, FinancialYearDocumentLib::CLOSING_DETAILED);
	}

	public function acceptDownloadIncomeStatement(): bool {
		return FinancialYearDocumentLib::hasDocument($this, FinancialYearDocumentLib::INCOME_STATEMENT);
	}
	public function acceptDownloadIncomeStatementDetailed(): bool {
		return FinancialYearDocumentLib::hasDocument($this, FinancialYearDocumentLib::INCOME_STATEMENT_DETAILED);
	}

	public function acceptDownloadSig(): bool {
		return FinancialYearDocumentLib::hasDocument($this, FinancialYearDocumentLib::SIG);
	}

	public function acceptGenerateOpen(): bool {

		return ($this['openDate'] !== NULL and FinancialYearDocumentLib::canGenerate($this, FinancialYearDocumentLib::OPENING));

	}


	public function acceptGenerateClose(): bool {

		return ($this['openDate'] !== NULL and $this['closeDate'] !== NULL and FinancialYearDocumentLib::canGenerate($this, FinancialYearDocumentLib::CLOSING));

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
			->setCallback('startDate.date', function(string $date) use($p): bool {

				return str_ends_with($date, '-01');

			})
			->setCallback('endDate.date', function(string $date) use($p): bool {

				return date('Y-m-t', strtotime($date)) === $date;

			})
			->setCallback('startDate.loseOperations', function(string $date) use($p): bool {

				if(($this['nOperation'] ?? 0) > 0) {
					return TRUE;
				}

				if($p->for === 'update') {
					return \journal\OperationLib::countByOldDatesButNotNewDate($this, $date, $this['endDate']) === 0;
				}

				return TRUE;
			})
			->setCallback('startDate.check', function(string $date) use($input): bool {

				if(($this['nOperation'] ?? 0) > 0) {
					return TRUE;
				}

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

				if(($this['nOperation'] ?? 0) > 0) {
					return TRUE;
				}

				if($p->for === 'update') {
					return \journal\OperationLib::countByOldDatesButNotNewDate($this, $this['startDate'], $date) === 0;
				}

				return TRUE;

			})
			->setCallback('endDate.check', function(string $date) use($input): bool {

				if(($this['nOperation'] ?? 0) > 0) {
					return TRUE;
				}

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
			->setCallback('endDate.after', function(?string $endDate) use ($p): bool {

				if(($this['nOperation'] ?? 0) > 0) {
					return TRUE;
				}

				if($p->isBuilt('startDate') === FALSE) {
					return TRUE;
				}

				return ($this['startDate'] < $endDate);

			})
			->setCallback('endDate.intervalMin', function(?string $endDate) use ($p): bool {

				if(($this['nOperation'] ?? 0) > 0) {
					return TRUE;
				}

				if($p->isBuilt('startDate') === FALSE or $this['startDate'] >= $endDate) {
					return TRUE;
				}

				$intervalInMonths = round(\util\DateLib::interval($endDate, $this['startDate']) / 60 / 60 / 24 / 30);
				return ($intervalInMonths > 0);

			})
			->setCallback('endDate.intervalMax', function(?string $endDate) use ($p): bool {

				if(($this['nOperation'] ?? 0) > 0) {
					return TRUE;
				}

				if($p->isBuilt('startDate') === FALSE or $this['startDate'] >= $endDate) {
					return TRUE;
				}

				$intervalInMonths = round(\util\DateLib::interval($endDate, $this['startDate']) / 60 / 60 / 24 / 30);
				return ($intervalInMonths <= 24);

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

			})
			->setCallback('vatChargeability.forbidden', function(?string $vatChargeability) use ($p): bool {

				return ($vatChargeability === FinancialYear::CASH);

			})
			->setCallback('accountingMode.check', function(?string &$accountingMode) use ($p): bool {

				if($p->isBuilt('taxSystem') === FALSE or $accountingMode === NULL) {
					return TRUE;
				}

				if($this['taxSystem'] !== FinancialYear::MICRO_BA) {
					$accountingMode = FinancialYear::ACCOUNTING;
				}

				return TRUE;

			})
			->setCallback('accountingType.check', function(?string &$accountingType) use ($p): bool {

				if($p->isBuilt('taxSystem') === FALSE or $accountingType === NULL) {
					return TRUE;
				}

				if($this['taxSystem'] !== FinancialYear::MICRO_BA) {
					$accountingType = FinancialYear::ACCRUAL;
				}

				return TRUE;

			})
		;

		parent::build($properties, $input, $p);

	}

}
?>
