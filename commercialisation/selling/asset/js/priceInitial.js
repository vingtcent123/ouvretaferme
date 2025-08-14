/**
 * Gestion des remises de prix par article
 */
class PriceInitial {

	static togglePriceDiscountField(reference, onHide = null) {

		const selector = '[data-price-discount="' + reference + '"]';
		const isHidden = qs(':not(svg)' + selector).classList.contains('hide');

		if(isHidden) {

			this.showUnitPriceDiscountField(selector);

		} else {

			this.hideUnitPriceDiscountField(selector);

			if(onHide) {
				onHide(qs('[data-price-discount-onhide="' + reference + '"]'));
			}
		}

	}

	static showUnitPriceDiscountField(selector) {

		qsa(selector, node => node.removeHide());

		qs('svg' + selector + '.asset-icon-tag-fill').removeHide();
		qs('svg' + selector + '.asset-icon-tag').hide();
	}

	static hideUnitPriceDiscountField(selector) {

		qsa(selector, node => node.hide());
		qs(selector + ' input').value = '';

		qs('svg' + selector + '.asset-icon-tag-fill').hide();
		qs('svg' + selector + '.asset-icon-tag').removeHide();

	}

}
