<?php
namespace journal;

class Operation extends OperationElement {

	public function canQuickUpdate(): bool {

		return \accounting\FinancialYearLib::isDateInOpenFinancialYear($this['date']);

	}

	public function canDelete(): bool {

		return ($this->exists() === TRUE and $this['operation']->exists() === FALSE);

	}

	public function isClassAccount(int $class): bool {

		$stringClass = (string)$class;
		return str_starts_with($this['accountLabel'], $stringClass);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('account.empty', function(?\accounting\Account $account): bool {

				return $account !== NULL;

			})
			->setCallback('accountLabel.inconsistency', function(?string $accountLabel): bool {

				$this->expects(['account']);

				$eAccount = \accounting\AccountLib::getById($this['account']['id']);

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
			->setCallback('type.empty', function(?string $type): bool {

				return $type !== NULL;

			})
			->setCallback('date.check', function(string $date): bool {

				$cFinancialYear = \accounting\FinancialYearLib::getOpenFinancialYears();

				foreach($cFinancialYear as $eFinancialYear) {

					if($date >= $eFinancialYear['startDate'] && $date <= $eFinancialYear['endDate']) {
						return TRUE;
					}

				}

				return FALSE;

			})
			->setCallback('thirdParty.empty', function(?ThirdParty $eThirdParty): bool {
				return $eThirdParty !== NULL;
			})
			->setCallback('thirdParty.check', function(?ThirdParty $eThirdParty): bool {

				return ThirdPartyLib::getById($eThirdParty['id'])->notEmpty();

			})
			->setCallback('cashflow.check', function(?\bank\Cashflow $eCashflow): bool {

				if($eCashflow->exists() === FALSE) {
					return TRUE;
				}

				$eCashflow = \bank\CashflowLib::getById($eCashflow['id']);

				return $eCashflow->exists();

			});

		parent::build($properties, $input, $p);

	}

}
?>
