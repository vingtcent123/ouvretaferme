<?php
namespace vat;

class Declaration extends DeclarationElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'createdBy' => \user\User::getSelection(),
			'declaredBy' => \user\User::getSelection(),
			'accountedBy' => \user\User::getSelection(),
		];

	}

	public function isAccounted(): bool {

		return $this->notEmpty() and $this['accountedAt'] !== NULL;

	}

	public function isDeclared(): bool {

		return $this->notEmpty() and $this['declaredAt'] !== NULL;

	}

	public function isPaid(): bool {

		return $this->notEmpty() and $this['paidAt'] !== NULL;

	}

	public function hasData(): bool {

		if($this->empty()) {
			return FALSE;
		}

		$this->expects(['data']);

		return empty($this['data']) === FALSE;

	}

	public function acceptUpdate(): bool {

		$this->expects(['from', 'to']);

		return $this['to'] < date('Y-m-d') and ($this['accountedAt'] ?? NULL) === NULL and $this->isOpenPeriod();
	}

	public function acceptAccount(): bool {

		if($this->exists() === FALSE) {
			return FALSE;
		}

		$this->expects(['declaredAt', 'status']);

		return $this->isDeclared() and $this->isAccounted() === FALSE;

	}

	public function acceptPay(): bool {

		if($this->exists() === FALSE) {
			return FALSE;
		}

		$this->expects(['paidAt']);

		return $this->isAccounted() and $this->isPaid() === FALSE;

	}

	public function acceptDeclare(): bool {

		if($this->exists() === FALSE) {
			return FALSE;
		}

		$this->expects(['declaredAt']);

		return $this['declaredAt'] === NULL;

	}

	public function acceptReset(): bool {

		if($this->exists() === FALSE) {
			return FALSE;
		}

		return $this->acceptUpdate();

	}

	public function isCredit(): bool {

		if($this->exists() === FALSE) {
			return FALSE;
		}

		$this->expects(['data']);

		if(isset($this['data']['0705']) === FALSE) {
			return FALSE;
		}

		return ($this['data']['0705'] ?? 0) > 0;

	}

	public function isDebit(): bool {

		if($this->exists() === FALSE) {
			return FALSE;
		}

		$this->expects(['data']);

		if(isset($this['data']['9992']) === FALSE) {
			return FALSE;
		}

		return ($this['data']['9992'] ?? 0) === 0;

	}

	public function isOpenPeriod(): bool {

		$this->expects(['from', 'to']);

		return $this['to'] <= date('Y-m-d') and date('Y-m-d') <= date('Y-m-d', strtotime($this['limit'].' + '.\vat\VatSetting::DELAY_UPDATABLE_AFTER_LIMIT_IN_DAYS.' DAYS'));

	}
	public function isClosedPeriod(): bool {

		$this->expects(['to']);

		return date('Y-m-d') > date('Y-m-d', strtotime($this['to'].' + '.\vat\VatSetting::DELAY_UPDATABLE_AFTER_LIMIT_IN_DAYS.' DAYS'));
	}
	public function isDeclarationOpenPeriod(): bool {

		$this->expects(['limit']);

		return date('Y-m-d') <= date('Y-m-d', strtotime($this['limit']));
	}


}
?>
