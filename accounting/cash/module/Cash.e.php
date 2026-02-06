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

	public function requireAssociateAccount(): bool {

		$this->expects([
			'source', 'financialYear'
		]);

		return (
			$this['source'] === Cash::PRIVATE and
			$this['financialYear']->isCompany() and
			$this['financialYear']->isAccounting()
		);

	}

	public function requireDescription(): bool {

		$this->expects([
			'source'
		]);

		return (
			($this['source'] === Cash::PRIVATE and $this->requireAssociateAccount() === FALSE)
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
			->setCallback('date.financialYear', function(string $date) {

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
			->setCallback('description.empty', function(?string $description = NULL) {

				if($this->requireDescription()) {
					return ($description !== NULL);
				} else {
					$description = NULL;
					return TRUE;
				}

			})
			->setCallback('account.empty', function(\account\Account &$eAccount) {

				if($this->requireAssociateAccount()) {
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