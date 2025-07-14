<?php
namespace journal;

class DeferredCharge extends DeferredChargeElement {

	public function canDelete(): bool {

		$this->expects(['status']);

		return ($this['status'] === DeferredChargeElement::PLANNED);

	}


	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('date.empty', function(?string $date): bool {

				return $date !== NULL;

			})
			->setCallback('amount.empty', function(?float $amount): bool {

				return $amount !== NULL;

			})
			->setCallback('amount.incorrect', function(?float $amount): bool {

				return $amount > 0;

			})
			->setCallback('date.check', function(string $date): bool {

				$cFinancialYear = \account\FinancialYearLib::getOpenFinancialYears();

				foreach($cFinancialYear as $eFinancialYear) {

					if($date >= $eFinancialYear['startDate'] && $date <= $eFinancialYear['endDate']) {
						return TRUE;
					}

				}

				return FALSE;

			})
		;

		parent::build($properties, $input, $p);

	}


}
?>
