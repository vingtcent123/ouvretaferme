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

		return $this['farm']->canManage();

	}

	public function canRead(): bool {

		$this->expects(['shop']);
		return $this['shop']->canWrite();

	}

	public function canWrite(): bool {

		$this->expects(['farm', 'shop']);

		return (
			$this['shop']->canWrite() or // Administrateur de la boutique
			$this->isSelf()
		);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('paymentMethod.check', function(\payment\Method $eMethod): bool {

				if($eMethod->empty()) {
					return TRUE;
				}

				$this->expects(['farm']);

				return \payment\MethodLib::isSelectable($this['farm'], $eMethod);

			});

		parent::build($properties, $input, $p);

	}

}
?>
