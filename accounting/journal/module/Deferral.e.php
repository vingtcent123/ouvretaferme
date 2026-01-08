<?php
namespace journal;

class Deferral extends DeferralElement {

	public function acceptDelete(): bool {

		$this->expects(['status']);

		return ($this['status'] === Deferral::PLANNED);

	}

	public static function getSelection(): array {

		return parent::getSelection() + [
				'financialYear' => ['id', 'startDate', 'endDate', 'status'],
			];

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('financialYear.check', function(\account\FinancialYear &$eFinancialYear): bool {

				if($eFinancialYear->empty()) {
					return FALSE;
				}

				$eFinancialYear = \account\FinancialYearLib::getById($eFinancialYear['id']);

				return $eFinancialYear->isOpen();

			})
			->setCallback('endDate.valid', function(?string $date) use($p): bool {

				if($p->isInvalid(['financialYear']) or $p->isInvalid('startDate')) {
					return TRUE;
				}

				$eFinancialYear = \account\FinancialYearLib::getById($this['financialYear']);
				return $date > $eFinancialYear['endDate'];

			})
			->setCallback('amount.check', function(?float $amount) use($p): bool {

				if($p->isInvalid(['operation'])) {
					return TRUE;
				}

				return $amount < $this['operation']['amount'] and $amount > 0;

			})
			->setCallback('amount.incorrect', function(?float $amount): bool {

				return $amount > 0;

			})
			->setCallback('operation.deferrable', function(Operation &$eOperation) use($p): bool {

				if($p->isInvalid(['financialYear'])) {
					return TRUE;
				}

				if($eOperation->empty()) {
					return FALSE;
				}

				$eOperation = OperationLib::getById($eOperation['id']);

				return ((\account\AccountLabelLib::isChargeClass($eOperation['accountLabel']) or
					\account\AccountLabelLib::isProductClass($eOperation['accountLabel'])) and
					\account\FinancialYearLib::isDateInFinancialYear($eOperation['date'], $this['financialYear']));

			})
		;

		parent::build($properties, $input, $p);

	}
}
?>
