<?php
namespace overview;

class VatDeclaration extends VatDeclarationElement {

	public function canUpdate(): bool {

		$this->expects(['limit', 'to']);

		return $this['limit'] > date('Y-m-d', strtotime('- '.VatDeclarationLib::DELAY_UPDATABLE_AFTER_LIMIT_IN_DAYS.' days')) and
			$this['to'] < date('Y-m-d');

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
