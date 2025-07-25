<?php
namespace journal;

use account\ThirdPartyLib;

class Operation extends OperationElement {

	public function canUpdate(): bool {

		$this->expects(['vatDeclaration', 'date']);

		return $this['vatDeclaration']->empty() and \account\FinancialYearLib::isDateInOpenFinancialYear($this['date']);
	}

	public function canDelete(): bool {

		$this->expects(['operation']);

		return ($this->exists() === TRUE and $this->canUpdate() and $this['operation']->exists() === FALSE );

	}

	public function isClassAccount(int $class): bool {

		$this->expects(['accountLabel']);

		$stringClass = (string)$class;
		return str_starts_with($this['accountLabel'], $stringClass);

	}

	public function isVatAdjustement(?array $period): bool {

		if($period === NULL) {
			return FALSE;
		}

		return $this['date'] < $period['start'];

	}

	public function isDeferrable(\account\FinancialYear $eFinancialYear): bool {

		return (
				mb_substr($this['accountLabel'], 0, mb_strlen(\Setting::get('account\chargeAccountClass'))) === (string)\Setting::get('account\chargeAccountClass')
				or mb_substr($this['accountLabel'], 0, mb_strlen(\Setting::get('account\productAccountClass'))) === (string)\Setting::get('account\productAccountClass')
			)
			and $this['financialYear']['id'] === $eFinancialYear['id']
			and $eFinancialYear->canUpdate();

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('account.empty', function(?\account\Account $account): bool {

				return $account !== NULL;

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
			->setCallback('type.empty', function(?string $type): bool {

				return $type !== NULL;

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

				return ThirdPartyLib::getById($eThirdParty['id'])->notEmpty();

			})
			->setCallback('cashflow.check', function(?\bank\Cashflow $eCashflow): bool {

				if($eCashflow->exists() === FALSE) {
					return TRUE;
				}

				$eCashflow = \bank\CashflowLib::getById($eCashflow['id']);

				return $eCashflow->exists();

			})
			->setCallback('paymentDate.empty', function(?string $paymentDate): bool {

				$eCompany = \company\CompanyLib::getCurrent();

				if($eCompany->isAccrualAccounting()) {
					return TRUE;
				}

				return $paymentDate !== NULL;
			})
			->setCallback('paymentMode.empty', function(?string $paymentDate): bool {

				$eCompany = \company\CompanyLib::getCurrent();

				if($eCompany->isAccrualAccounting()) {
					return TRUE;
				}

				return $paymentDate !== NULL;
			})
			->setCallback('document.empty', function(?string $document) use($input): bool {

				if(!var_filter($input['invoiceFile'] ?? NULL, 'string')) {
					return TRUE;
				}

				$ePartner = \account\DropboxLib::getPartner();
				if($ePartner->empty()) {
					return TRUE;
				}

				return $document !== NULL;
			})
		;

		parent::build($properties, $input, $p);

	}

}
?>
