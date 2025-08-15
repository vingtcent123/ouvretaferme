<?php
namespace selling;

class PriceUi {

	public function __construct() {

		\Asset::js('selling', 'priceInitial.js');

	}

	public function getDiscountLink(string|int $identifier, bool $hasDiscountPrice, ?string $onHide = null): string {

		$attributes = [
			'onclick' => 'PriceInitial.showUnitPriceDiscountField("'.$identifier.'", '.$onHide.');',
			'data-price-discount-link' => $identifier,
			'class' => $hasDiscountPrice ? 'hide' : '',
		];

		return \util\FormUi::getFieldAction('<a '.attrs($attributes).'>'.s("Ajouter une remise").' '.\Asset::icon('caret-down-fill').'</a>');

	}

	public function getDiscountTrashAddon(string|int $identifier, ?string $onHide = null): string {

		$attributes = [
			'onclick' => 'PriceInitial.hideUnitPriceDiscountField(\''.$identifier.'\', '.$onHide.');',
			'data-price-discount-link' => $identifier,
			'title' => s("Supprimer la remise"),
		];

		return '<a '.attrs($attributes).'>'.\Asset::icon('trash').'</a>';

	}
}
?>
