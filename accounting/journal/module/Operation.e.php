<?php
namespace journal;

use account\ThirdPartyLib;

class Operation extends OperationElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'account' => \account\Account::getSelection(),
			'thirdParty' => \account\ThirdParty::getSelection(),
			'paymentMethod' => \payment\Method::getSelection(),
			'financialYear' => \account\FinancialYear::getSelection(),
			'cOperationCashflow' => OperationCashflowLib::delegateByOperation(),
			'cOperationLinked' => new OperationModel()
				->select('id', 'operation')
				->delegateCollection('operation')
		];

	}

	public function canUpdate(): bool {

		$this->expects(['vatDeclaration', 'date', 'financialYear']);

		return $this['vatDeclaration']->empty() and \account\FinancialYearLib::isDateInOpenFinancialYear($this['date']) and $this['financialYear']->canUpdate();
	}

	public function canUpdateQuick(): bool {

		return $this->canUpdate() and $this['operation']->empty();
	}

	public function canDelete(): bool {

		$this->expects(['operation']);

		return ($this->notEmpty() and $this->canUpdate() and $this['operation']->empty());

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
				mb_substr($this['accountLabel'], 0, mb_strlen(\account\AccountSetting::CHARGE_ACCOUNT_CLASS)) === (string)\account\AccountSetting::CHARGE_ACCOUNT_CLASS
				or mb_substr($this['accountLabel'], 0, mb_strlen(\account\AccountSetting::PRODUCT_ACCOUNT_CLASS)) === (string)\account\AccountSetting::PRODUCT_ACCOUNT_CLASS
			)
			and $this['financialYear']['id'] === $eFinancialYear['id']
			and $eFinancialYear->canUpdate();

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('financialYear.check', function(?\account\FinancialYear &$eFinancialYear): bool {

				if($eFinancialYear === NULL or $eFinancialYear->empty()) {
					return FALSE;
				}

				$cFinancialYear = \account\FinancialYearLib::getOpenFinancialYears();

				if(in_array($eFinancialYear['id'], $cFinancialYear->getIds())) {
					$eFinancialYear = $cFinancialYear->find(fn ($e) => $e['id'] === $eFinancialYear['id'])->first();
					return TRUE;
				}

				return FALSE;

			})
			->setCallback('account.empty', function(?\account\Account $account): bool {

				return $account !== NULL;

			})
			->setCallback('accountLabel.inconsistency', function(?string $accountLabel): bool {

				$this->expects(['account']);

				$eAccount = \account\AccountLib::getById($this['account']['id']);

				return \account\ClassLib::isFromClass($accountLabel, $eAccount['class']);

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

					if($date >= $eFinancialYear['startDate'] and $date <= $eFinancialYear['endDate']) {
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

				$this->expects(['financialYear']);

				if($this['financialYear']->isAccrualAccounting()) {
					return TRUE;
				}

				return $paymentDate !== NULL;
			})
			->setCallback('paymentMethod.empty', function(?string $paymentDate): bool {

				$this->expects(['financialYear']);

				if($this['financialYear']->isAccrualAccounting()) {
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
			->setCallback('invoice.check', function(?\selling\Invoice $eInvoice) use($input): bool {

				if($eInvoice->empty()) {
					return TRUE;
				}

				$eInvoice->expects(['id']);

				$eInvoice = \selling\InvoiceLib::getById($eInvoice['id']);
				$eFarm = new \farm\Farm(['id' => POST('farm', '?int')]);

				return $eInvoice['farm']->is($eFarm);

			})
		;

		parent::build($properties, $input, $p);

	}

}
?>
