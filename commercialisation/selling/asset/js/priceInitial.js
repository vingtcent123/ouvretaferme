class PriceInitial {

	static toggleUnitPriceDiscountField(selector) {

		const isHidden = qs(selector + ':not([data-unit-price-discount-visible])').classList.contains('hide');

		if(isHidden) {

			this.showUnitPriceDiscountField(selector);

		} else {

			this.hideUnitPriceDiscountField(selector);

		}

	}

	static showUnitPriceDiscountField(selector) {

		qsa(selector, node => node.removeHide());

		qs(selector + '[data-unit-price-discount-visible="1"]').removeHide();
		qs(selector + '[data-unit-price-discount-visible="0"]').hide();
	}

	static hideUnitPriceDiscountField(selector, callback = null) {

		qsa(selector, node => node.hide());
		qs(selector + ' input').value = '';

		if(callback) {
			callback();
		}

		qs(selector + '[data-unit-price-discount-visible="1"]').hide();
		qs(selector + '[data-unit-price-discount-visible="0"]').removeHide();
	}

}
