<?php
namespace selling;

class Category extends CategoryElement {

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {
		$this->expects(['farm']);
		return $this['farm']->canManage();
	}

	public static function getIcons(): array {

		return ['fruit', 'legume', 'lait', 'vin', 'biere', 'oeuf', 'fromage', 'champignon', 'plant', 'pain', 'farine', 'bs-flower1', 'miel', 'pot', 'viande', 'charcuterie', 'poisson', 'tisane', 'savon', 'panier', 'bs-box', 'bs-heart', 'bs-star', 'bs-list', 'bs-plus', 'bs-percent'];

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('icon.check', function(?string $icon) {

				return (
					$icon == NULL or
					in_array($icon, \selling\Category::getIcons())
				);

			});

		parent::build($properties, $input, $p);

	}

}
?>