<?php
namespace overview;

class VatDeclaration extends VatDeclarationElement {

	public function acceptUpdate(): bool {

		$this->expects(['from', 'to']);

		return $this['to'] < date('Y-m-d') and ($this['accountedAt'] ?? NULL) === NULL;
	}

	public function acceptAccount(): bool {

		if($this->exists() === FALSE) {
			return FALSE;
		}

		$this->expects(['declaredAt', 'status']);

		return $this['declaredAt'] !== NULL and $this['status'] !== VatDeclaration::DECLARED;

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

		$this->expects(['accountedAt']);

		return $this['accountedAt'] === NULL;

	}

	public static function getSelection(): array {

		return parent::getSelection() + [
			'createdBy' => \user\User::getSelection(),
			'declaredBy' => \user\User::getSelection(),
			'accountedBy' => \user\User::getSelection(),
		];

	}
}
?>
