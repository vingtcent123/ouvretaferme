<?php
namespace company;

class Company extends CompanyElement {

	public function __construct(array $array = []) {

		parent::__construct($array);

	}

	public function canRemote(): bool {
		return GET('key') === \Setting::get('account\remoteKey');
	}

	// Comptabilité à l'engagement
	public function isAccrualAccounting() {
		return $this->notEmpty() and $this['accountingType'] === Company::ACCRUAL;
	}

	// Comptabilité de trésorerie
	public function isCashAccounting() {
		return $this->empty() or $this['accountingType'] === Company::CASH;
	}

}
?>
