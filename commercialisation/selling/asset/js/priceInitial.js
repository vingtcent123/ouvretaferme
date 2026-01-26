/**
 * Gestion des remises de prix par article
 */
class PriceInitial {

	static switch(reference, onHide = null) {

		if(qs('[data-price-discount-link="'+ reference +'"]').dataset.visible === '1') {
			this.hide(reference, onHide);
		} else {
			this.show(reference);
		}

	}

	static show(reference) {

		qsa('[data-price-discount="'+ reference +'"]', node => node.removeHide());

		const target = qs('[data-price-discount-link="'+ reference +'"]');

		this.switchDiscount(target);

	}

	static hide(reference, onHide = null) {

		qsa('[data-price-discount="'+ reference +'"]', node => node.hide());
		qs('[data-price-discount="'+ reference +'"] input').value = '';

		const target = qs('[data-price-discount-link="'+ reference +'"]');

		this.switchDiscount(target);

		if(onHide) {
			onHide(qs('[data-price-discount-onhide="'+ reference + '"]'));
		}

	}

	static switchDiscount(target) {

		const placeholder = target.dataset.placeholder;
		target.dataset.placeholder = target.innerHTML;
		target.dataset.visible = (target.dataset.visible === '1') ? '0' : '1';

		target.innerHTML = placeholder;

	}

}
