<?php
namespace shop;

class Catalog extends CatalogElement {

	public function canRead(): bool {

		$this->expects(['farm']);

		if($this['farm']->canSelling()) {
			return TRUE;
		}

		// Catalogue inclus dans les boutiques partagées
		return RangeLib::getOnlineCatalogs()->contains(fn($e) => $e['id'] === $this['id']);

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function validateShop(Shop $eShop): self {

		$eShop->expects(['shared']);

		if(
			$this->empty() or
			$eShop['shared'] === FALSE
		) {
			return $this->validate('canRead');
		} else {

			if(RangeLib::hasCatalog($eShop, $this)) {
				return $this;
			} else {
				throw new \NotAllowedAction();
			}

		}

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('name.check', function(?string $name): bool {
				return ($name !== NULL and Catalog::model()->check('name', $name));
			})
			->setCallback('name.comma', function(?string $name): bool {
				return (
					$name === NULL or
					str_contains($name, ',') === FALSE
				);
			});

		parent::build($properties, $input, $p);

	}

}
?>