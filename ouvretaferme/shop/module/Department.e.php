<?php
namespace shop;

class Department extends DepartmentElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
				'shop' => ShopElement::getSelection(),
			];

	}

	public function canRead(): bool {

		$this->expects(['shop']);

		return $this['shop']->canWrite();

	}

}
?>