<?php
namespace cash;

class Cash extends CashElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'account' => \account\Account::getSelection(),
			'financialYear' => \account\FinancialYear::getSelection(),
		];

	}

	public function acceptCreate(): bool {
		return ($this['register']['status'] === Register::ACTIVE);
	}

	public function acceptUpdate(): bool {
		return ($this['status'] === Cash::DRAFT);
	}

	public function acceptValidate(): bool {
		return ($this['status'] === Cash::DRAFT);
	}

	public function requireAssociateAccount(): bool {

		if(
			$this['source'] !== Cash::PRIVATE or
			$this['date'] === NULL
		) {
			return FALSE;
		}

		$this->expects([
			'financialYear'
		]);

		return (
			$this['financialYear']->isCompany() and
			$this['financialYear']->isAccounting()
		);

	}

	public function requireAccount(): bool {

		$this->expects([
			'source'
		]);

		if(
			in_array($this['source'], [Cash::BANK, Cash::BUY_MANUAL, Cash::SELL_MANUAL, Cash::OTHER]) === FALSE or
			$this['date'] === NULL
		) {
			return FALSE;
		}

		$this->expects([
			'financialYear'
		]);

		return (
			$this['financialYear']->isAccounting()
		);

	}

	public function requireVat(): bool {

		if(
			in_array($this['source'], [Cash::BUY_MANUAL, Cash::SELL_MANUAL, Cash::OTHER]) === FALSE or
			$this['date'] === NULL
		) {
			return FALSE;
		}

		$this->expects([
			'financialYear'
		]);

		return (
			$this['financialYear']->hasVat()
		);

	}

	public function requireDescription(): bool {

		return (
			$this['source'] === Cash::OTHER and
			$this['date'] !== NULL
		);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('type.check', function(string $type) {

				if($this['source'] === Cash::INITIAL) {
					return ($type === Cash::CREDIT);
				} else {
					return in_array($type, [Cash::DEBIT, Cash::CREDIT]);
				}

			})
			->setCallback('date.financialYear', function(string $date) use ($p) {

				if($p->isInvalid('date')) {

					$this['financialYear'] = new \account\FinancialYear();
					return TRUE;

				}

				$this->expects(['source']);

				if($this['source'] === Cash::INITIAL) {

					$this['financialYear'] = new \account\FinancialYear();
					return TRUE;

				} else {

					$this['financialYear'] = \account\FinancialYearLib::getByDate($date);
					return $this['financialYear']->notEmpty();

				}



			})
			->setCallback('date.past', function(string $date) {

				return (
					$this['register']['lastOperation'] === NULL or
					$date >= $this['register']['lastOperation']
				);

			})
			->setCallback('date.future', function(string $date) {

				return ($date <= currentDate());

			})
			->setCallback('description.empty', function(?string &$description = NULL) {

				if($this->requireDescription()) {
					return ($description !== NULL);
				} else {
					$description = NULL;
					return TRUE;
				}

			})
			->setCallback('amountExcludingVat.empty', function(?float &$amount) {

				if($this->requireVat() === FALSE) {
					$amount = NULL;
					return TRUE;
				}

				return ($amount !== NULL);

			})
			->setCallback('vatRate.empty', function(?float &$vatRate) {

				if($this->requireVat() === FALSE) {
					$vatRate = NULL;
					return TRUE;
				}

				return ($vatRate !== NULL);

			})
			->setCallback('vat.empty', function(?float &$amount) {

				if($this->requireVat() === FALSE) {
					$amount = NULL;
					return TRUE;
				}

				return ($amount !== NULL and $amount >= 0.0);

			})
			->setCallback('vat.consistency', function(?float $vat) use ($p) {

				if(
					$this->requireVat() === FALSE or
					$p->isInvalid('amountIncludingVat') or
					$p->isInvalid('amountExcludingVat') or
					$p->isInvalid('vat')
				) {
					return TRUE;
				}

				if(round($vat + $this['amountExcludingVat'], 2) !== round($this['amountIncludingVat'], 2)) {
					throw new \FailException('cash\Cash::amountConsistency');
				}

			})
			->setCallback('account.empty', function(\account\Account &$eAccount) {

				if(
					$this->requireAssociateAccount() or
					$this->requireAccount()
				) {
					return $eAccount->notEmpty();
				} else {
					$eAccount = new \account\Account();
					return TRUE;
				}

			})
			->setCallback('account.check', function(\account\Account $eAccount) {

				if($eAccount->empty()) {
					return TRUE;
				}

				$eAccount = \account\AccountLib::getById($eAccount);

				if($eAccount->empty()) {
					return FALSE;
				}

				if($this->requireAssociateAccount()) {
					return $eAccount->isAssociatePrincipal();
				} else if($this->requireAccount()) {
					return $eAccount->notEmpty();
				} else {
					return TRUE;
				}

			})
			->setCallback('date.future', function(string $date) {

				return ($date <= currentDate());

			});

		parent::build($properties, $input, $p);

	}

}
?>