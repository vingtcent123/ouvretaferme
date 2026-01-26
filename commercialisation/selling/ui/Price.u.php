<?php
namespace selling;

class PriceUi {

	public function __construct() {

		\Asset::css('selling', 'priceInitial.css');
		\Asset::js('selling', 'priceInitial.js');

	}

	public function getDiscountLink(string|int $identifier, bool $hasDiscountPrice, ?string $onHide = null): string {

		if($hasDiscountPrice) {
			$placeholder = s("Ajouter une remise");
			$label = s("Prix remisé");
		} else {
			$placeholder = s("Prix remisé");
			$label = s("Ajouter une remise");
		}

		$attributes = [
			'onclick' => 'PriceInitial.switch("'.$identifier.'", '.$onHide.');',
			'data-visible' => $hasDiscountPrice ? '1' : '0',
			'data-price-discount-link' => $identifier,
			'data-placeholder' => $placeholder,
		];

		return \util\FormUi::getFieldAction(\Asset::icon('tags').' <a '.attrs($attributes).'>'.$label.'</a>', 'price-discount-link');

	}

	public function getDiscountTrashAddon(string|int $identifier, ?string $onHide = null): string {

		$attributes = [
			'onclick' => 'PriceInitial.hide(\''.$identifier.'\', '.$onHide.');',
			'data-price-discount-link' => $identifier,
			'title' => s("Supprimer la remise"),
		];

		return '<a '.attrs($attributes).'>'.\Asset::icon('trash').'</a>';

	}

	public function priceWithoutDiscount(string|float $price, string $unit = '', bool $isSmall = TRUE): string {

		$displayedPrice = is_float($price) ? \util\TextUi::money($price) : $price;

		$h = '<div>';
			$h .= '<span class="util-strikethrough '.($isSmall ? 'font-sm' : '').'">';
				$h .= $displayedPrice.$unit;
			$h .= '</span>';
		$h .= '</div>';

		return $h;
	}
}
?>
