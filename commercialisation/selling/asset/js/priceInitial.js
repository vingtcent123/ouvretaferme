/**
 * Gestion des remises de prix par article
 */
class PriceInitial {

	static showUnitPriceDiscountField(reference) {

		const selector = '[data-price-discount="'+ reference +'"]';

		qsa(selector, node => node.removeHide());
		qs('[data-price-discount-link="'+ reference +'"]')?.hide();

	}

	static hideUnitPriceDiscountField(reference, onHide = null) {

		const selector = '[data-price-discount="'+ reference +'"]';

		qsa(selector, node => node.hide());
		qs(selector +' input').value = '';

		qs('[data-price-discount-link="'+ reference +'"]')?.removeHide();

		if(onHide) {
			onHide(qs('[data-price-discount-onhide="'+ reference + '"]'));
		}

	}

}
