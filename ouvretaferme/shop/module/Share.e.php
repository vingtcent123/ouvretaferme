<?php
namespace shop;

class Share extends ShareElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'shop' => ShopElement::getSelection(),
			'farm' => \farm\FarmElement::getSelection()
		];

	}

	public function isSelf(): bool {

		$this->expects(['farm']);

		return new Shop(['farm' => $this['farm']])->canWrite();

	}

	public function canRead(): bool {

		$this->expects(['shop']);
		return $this['shop']->canWrite();

	}

	public function canDelete(): bool {

		$this->expects(['farm', 'shop']);

		return (
			$this['shop']->canWrite() or // Administrateur de la boutique
			$this->isSelf()
		);

	}

}
?>