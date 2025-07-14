<?php
namespace journal;

class AccruedIncome extends AccruedIncomeElement {

	public function canDelete(): bool {

		$this->expects(['status']);

		return ($this['status'] === AccruedIncome::PLANNED);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('account.empty', function(?\account\Account $account): bool {

				$eAccount = \account\AccountLib::getById($account['id']);

				return $account !== NULL and \account\ClassLib::isFromClass($eAccount['class'], \Setting::get('account\productAccountClass'));

			})
			->setCallback('accountLabel.inconsistency', function(?string $accountLabel): bool {

				$this->expects(['account']);

				$eAccount = \account\AccountLib::getById($this['account']['id']);

				return str_starts_with($accountLabel, $eAccount['class']) === true;

			})
			->setCallback('date.empty', function(?string $date): bool {

				return $date !== NULL;

			})
			->setCallback('description.empty', function(?string $description): bool {

				return $description !== NULL;

			})
			->setCallback('amount.empty', function(?float $amount): bool {

				return $amount !== NULL;

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
			->setCallback('thirdParty.empty', function(?\account\ThirdParty $eThirdParty): bool {

				return $eThirdParty !== NULL;

			})
			->setCallback('thirdParty.check', function(?\account\ThirdParty $eThirdParty): bool {

				if($eThirdParty->empty()) {
					return TRUE;
				}

				return \account\ThirdPartyLib::getById($eThirdParty['id'])->notEmpty();

			})
		;

		parent::build($properties, $input, $p);

	}

}
?>
