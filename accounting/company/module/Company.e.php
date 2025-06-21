<?php
namespace company;

class Company extends CompanyElement {

	public function __construct(array $array = []) {

		parent::__construct($array);

	}

	public function isAccrualAccounting() {
		return $this['accountingType'] === Company::ACCRUAL;
	}

	public function isCashAccounting() {
		return $this['accountingType'] === Company::CASH;
	}

}
?>
