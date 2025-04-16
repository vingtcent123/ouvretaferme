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

	public static function getIcons(): array {

		return ['lait', 'oeuf'];

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('icon.check', function(?string $icon) {

				return (
					$icon == NULL or
					in_array($icon, Department::getIcons())
				);

			});

		parent::build($properties, $input, $p);

	}

}
?>