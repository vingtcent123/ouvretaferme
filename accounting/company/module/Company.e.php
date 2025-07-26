<?php
namespace company;

class Company extends CompanyElement {

	public function __construct(array $array = []) {

		parent::__construct($array);

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
