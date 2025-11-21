<?php
namespace journal;

class Deferral extends DeferralElement {

	public function acceptDelete(): bool {

		$this->expects(['status']);

		return ($this['status'] === Deferral::PLANNED);

	}

	public static function getSelection(): array {

		return parent::getSelection() + [
				'initialFinancialYear' => ['id', 'startDate', 'endDate', 'status'],
			];

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('endDate.valid', function(?string $date) use($p): bool {

				if($p->isBuilt('startDate') === FALSE) {
					return TRUE;
				}

				$eFinancialYear = \account\FinancialYearLib::getById($this['initialFinancialYear']);
				return $date > $eFinancialYear['endDate'];

			})
			->setCallback('amount.check', function(?float $amount) use($p): bool {

				if($p->isBuilt('operation') === FALSE) {
					return TRUE;
				}


				$eOperation = OperationLib::getById($this['operation']['id']);
				return $amount < $eOperation['amount'] and $amount > 0;

			})
			->setCallback('amount.incorrect', function(?float $amount): bool {

				return $amount > 0;

			})
		;

		parent::build($properties, $input, $p);

	}
}
?>
