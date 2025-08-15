/**
 * Gestion des remises de prix par article
 */
class PriceInitial {

	static togglePriceDiscountField(linkElement, reference, onHide = null) {

		const selector = '[data-price-discount="'+ reference +'"]';
		const isHidden = qs(selector).classList.contains('hide');

		if(isHidden) {

			this.showUnitPriceDiscountField(linkElement, selector);

		} else {

			this.hideUnitPriceDiscountField(linkElement, selector);

			if(onHide) {
				onHide(qs('[data-price-discount-onhide="'+ reference + '"]'));
			}
		}

	}

	static showUnitPriceDiscountField(linkElement, selector) {

		qsa(selector, node => node.removeHide());

		linkElement.innerHTML = linkElement.dataset.textOff;

	}

	static hideUnitPriceDiscountField(linkElement, selector) {

		qsa(selector, node => node.hide());
		qs(selector +' input').value = '';

		linkElement.innerHTML = linkElement.dataset.textOn;

	}

}
