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

}
?>