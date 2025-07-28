document.delegateEventListener('input', '#market-item-list input', function(e) {

	Market.itemListUpdate();

});

class Market {

	static toggleMoney(sale) {
		qs('#sale-money-'+ sale).toggle();
	}

		static updateCustomerMoney(sale, value, target) {

		const input = parseFloat(target.value);

		if(isNaN(input) || input < value) {
			qs('#sale-money-'+ sale +'-custom').innerHTML = '';
		} else {
			qs('#sale-money-'+ sale +'-custom').innerHTML = money(input - value, 2);
		}

	}

	static itemListUpdate() {

		const items = qsa('#market-item-list .market-item input[name^="unitPrice"]:not(:placeholder-shown)').length;
		const banner = qs('#market-item-banner');

		if(items === 0) {
			banner.classList.add('hide');
		} else {

			banner.classList.remove('hide');

			if(items === 1) {
				qs('#market-item-banner-one').classList.remove('hide');
				qs('#market-item-banner-more').classList.add('hide');
			} else {
				qs('#market-item-banner-one').classList.add('hide');
				qs('#market-item-banner-more').classList.remove('hide');
				qs('#market-item-banner-items').innerHTML = items;
			}

		}

		Market.itemListHighlight();

	}

	static itemEmpty() {

		qsa('#market-item-list .market-item input', input => input.value = '');

		Market.itemListUpdate();

	}


	static itemListHighlight() {

		qsa('#market-item-list .market-item', node => {

			const input = node.qs('input[name^="unitPrice"]');

			if(input === null) {
				return;
			}

			node.classList.remove('market-item-highlight');
			node.classList.remove('market-item-error');

			try {

				if(this.checkNumber(input) > 0) {
					node.classList.add('market-item-highlight');
				}

			} catch(e) {

				node.classList.add('market-item-error');

			}

		});

	}

	static checkNumber(input) {

		const value = (input.value === '') ? null : parseFloat(input.value);

		if(value === null) {
			return 0;
		}

		if(Math.round(value * 100) / 100 !== value) {
			throw 'Syntax error';
		}

		return 1;

	}


}