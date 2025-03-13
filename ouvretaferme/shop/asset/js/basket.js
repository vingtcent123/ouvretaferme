document.delegateEventListener('click', '#shop-basket-point div.point-home-wrapper input', function(e) {

	BasketManage.selectHome();

});

document.delegateEventListener('click', '#shop-basket-point div.point-place-wrapper input', function(e) {

	BasketManage.selectPlace();

});

class BasketManage {

	static prefix = 'basket-';
	static version = 'v2';
	static json = null;

	static isEmbed() {
		return document.body.dataset.template.includes('embed');
	}

	/* Fonctions génériques pour la gestion du panier */
	static key(dateId) {
		return this.prefix + this.version +'-'+ dateId;
	}

	static getBasket(dateId, defaultJson = null) {

		this.checkExpiration();

		const basket = this.isEmbed() ?
			this.json :
			localStorage.getItem(this.key(dateId));

		if(!basket) {
			return (defaultJson !== null) ? defaultJson : this.newBasket();
		}

		const basketJson = JSON.parse(basket);

		if(
			basketJson.sale === null &&
			defaultJson !== null
		) {
			return defaultJson;
		} else {
			return basketJson;
		}

	}

	static hasBasket(dateId) {
		return !!(
			this.isEmbed() ?
				this.json :
				localStorage.getItem(this.key(dateId))
		);
	}

	static setBasket(dateId, basket) {

		this.json = JSON.stringify(basket);

		if(this.isEmbed() === false) {
			localStorage.setItem(this.key(dateId), this.json);
		}

	}

	static deleteBasket(dateId) {

		this.json = null;

		if(this.isEmbed() === false) {
			localStorage.removeItem(this.key(dateId));
		}
	}

	static modify(dateId, basket, to) {

		this.setBasket(dateId, basket);

		const pathname = location.pathname;
		const shopAndDatePathname = pathname.substring(0, pathname.lastIndexOf('/'));

		switch(to) {

			case 'basket' :
				window.location.href = shopAndDatePathname +'/panier?modify=1';
				break;

			case 'home' :
				window.location.href = shopAndDatePathname +'?modify=1';
				break;

		}

	}

	static doUpdate(dateId) {

		const pathname = location.pathname;
		const shopAndDatePathname = pathname.substr(1, pathname.lastIndexOf('/') - 1);

		const post = this.getBasket(dateId);
		post.shopDate = dateId

		qs('#shop-basket-point input[name="shopPoint"]:checked', node => post.shopPoint = node.value);
		qs('#shop-basket-submit input[name="terms"]:checked', () => post.terms = 1);

		new Ajax.Query()
			.url('/'+ shopAndDatePathname +'/:doUpdateBasket')
			.body(post)
			.fetch();
	}

	static doCancel(saleId) {

		const pathname = location.pathname;
		const shopAndDatePathname = pathname.substr(1, pathname.lastIndexOf('/') - 1);

		new Ajax.Query()
			.url('/'+ shopAndDatePathname +'/:doCancelSale')
			.body({
				sale: saleId
			})
			.fetch()
			.then(() => {
				this.deleteBasket(dateId);
			});
	}

	static checkBasketButtons(dateId) {

		const basket = this.getBasket(dateId);

		if(basket.sale !== null) {
			ref('basket-update', node => node.removeHide());
			qs('#shop-basket-submit input[name="terms"]', node => node.checked = true);
		} else {
			ref('basket-create', node => node.removeHide());
			qs('#shop-basket-submit input[name="terms"]', node => node.checked = false);
		}

	}

	/* Fonctions de mise à jour du panier et de son affichage */
	static update(dateId, productId, value, step, min, available) {

		let initialNumber = parseFloat(
			qs('[data-product="' + productId + '"][data-field="number"]').dataset.number
		);

		if(isNaN(initialNumber)) {
			initialNumber = 0;
		}

		let newNumber;

		if(
			available !== -1 &&
			min > available
		) {

			newNumber = 0;

		} else {

			switch(typeof value) {

				case 'number' :

					if(value < 0) {
						initialNumber = Math.floor(initialNumber / value) * value;
					} else if(value > 0) {
						initialNumber = Math.ceil(initialNumber / value) * value;
					}

					newNumber = Math.round((initialNumber + value) * 100) / 100;

					if(newNumber <= 0.0) {
						newNumber = 0;
					} else if(newNumber < min) {

						if(value > 0) {
							newNumber = min;
						} else {
							newNumber = 0;
						}

					}

					break;

				case 'object' :

					newNumber = Math.round(parseFloat(value.value) * 100) / 100;

					if(newNumber < min) {
						newNumber = 0;
					}

					newNumber = Math.round(newNumber / step) * step;

					break;

			}

			if(available > -1 && available < newNumber) {
				newNumber = available;
			}

		}

		let basket = this.getBasket(dateId);

		const isModifying = basket.sale !== null;

		if(parseFloat(newNumber) === 0.0) {
			delete basket.products[productId];
		} else {
			basket.products[productId] = {
				number: newNumber
			};
		}

		this.setBasket(dateId, basket);

		// Quand on est sur le résumé du panier, on reload le panier pour tout mettre à jour.
		if(qs('#shop-basket-summary')) {

			this.loadSummary(dateId, basket.sale, null, isModifying);

		} else {

			const target = qs('[data-product="' + productId + '"][data-field="number"]');
			this.updateNumber(target, newNumber);

			this.updateDisplay(dateId);

		}

		return false;

	}

	static calculateTotal(dateId) {

		qsa('.shop-product', product => product.dataset.has = 0);

		let basket = this.getBasket(dateId);
		let amount = 0;
		let articles = 0;

		Object.entries(basket.products).forEach(([productId, {number}]) => {

			const product = qs('.shop-product[data-id="'+ productId +'"]');

			if(number > 0) {

				if(product.length === 0) {
					return;
				}

				const price = parseFloat(product.dataset.price);

				articles++;
				amount += number * price;

				product.qs('.shop-product-number-decrease').classList.remove('shop-product-number-decrease-disabled');

				product.dataset.has = 1;

			} else {
				product.qs('.shop-product-number-decrease').classList.add('shop-product-number-decrease-disabled');
			}

		});

		const formatter = new Intl.NumberFormat('fr-FR', {
			style: 'currency',
			currency: 'EUR'
		});

		return [
			articles,
			formatter.format(amount.toFixed(2))
		];

	}

	static init(dateId, defaultJson) {

		const basket = this.getBasket(dateId, defaultJson);
		this.setBasket(dateId, basket);

		// Met à jour toutes les quantités.
		Object.entries(basket.products).forEach(([productId, {number}]) => {

			const target = qs('[data-product="' + productId + '"][data-field="number"]');

			if(target !== null) {
				this.updateNumber(target, number);
			} else {
				this.deleteBasketProduct(dateId, productId);
			}

		});

		// Met à jour la barre du bas.
		this.updateDisplay(dateId);
	}

	static updateNumber(target, number) {

		target.dataset.number = number;
		target.qs('input.shop-product-number-display', node => node.value = number > 0 ? number : '');
		target.qs('span.shop-product-number-display', node => node.innerHTML = number);

	}

	static updateDisplay(dateId) {

		let totalPrice, totalArticles;
		[totalArticles, totalPrice] = this.calculateTotal(dateId);

		qs('#shop-basket-articles').renderInner(totalArticles + (totalArticles > 1 ? ' articles' : ' article'));
		qs('#shop-basket-price', node => node.renderInner(totalPrice));
		qs('#shop-basket').removeHide();

		if(totalArticles === 0) {
			qs('#shop-basket-next').classList.add('disabled');
			qsa('.shop-basket-empty', node => node.classList.add('disabled'));
		} else {
			qs('#shop-basket-next').classList.remove('disabled');
			qsa('.shop-basket-empty', node => node.classList.remove('disabled'));
		}

	}

	/* Fonctions appelées sur la page de résumé du panier */
	static loadSummary(dateId, saleId, products = null, isModifying = false) {

		const pathname = location.pathname;
		const shopAndDatePathname = pathname.substring(0, pathname.lastIndexOf('/'));

		let basket = this.getBasket(dateId);

		if(saleId !== basket.sale) { // Problème de consistence
			basket = this.emptyBasket(dateId);
		}

		if(products === null) {
			products = basket.products;
		} else {
			history.removeArgument('products');
			this.updateBasketProducts(dateId, products);
		}

		const modifyingArg = isModifying ? '?modify=1' : '';

		new Ajax.Query()
			.url(shopAndDatePathname +'/:getBasket'+ modifyingArg)
			.body({
				products: products,
				date: dateId,
			})
			.fetch()
			.then((json) => {

				const summary = qs('#shop-basket-summary');

				summary.renderInner(json.basketSummary);
				summary.dataset.price = json.basketPrice;

				if(json.basketPrice > 0) {

					qsa('.point-list .point-element', point => {

						if(point.dataset.orderMin !== '') {

							const orderMin = parseFloat(point.dataset.orderMin);

							point.qs('.point-order-min', badge => (json.basketPrice >= orderMin) ?
								badge.classList.add('point-badge-selected') :
								badge.classList.remove('point-badge-selected'));

						}

						if(point.dataset.shipping !== '') {

							const shipping = parseFloat(point.dataset.shipping);
							let shippingActive;

							if(point.dataset.shippingUntil !== '') {

								const shippingUntil = parseFloat(point.dataset.shippingUntil);
								shippingActive = (json.basketPrice < shippingUntil);

								point.qs('.point-shipping', badge => shippingActive ?
									badge.classList.remove('point-badge-selected') :
									badge.classList.add('point-badge-selected'));

							} else {
								shippingActive = true;
							}

						}

					});

				}

				this.loadPrice();

			});

	}

	static loadPrice() {

		let price = parseFloat(qs('#shop-basket-summary').dataset.price);

		qs('.point-list [name="shopPoint"]:checked', (input) => {

			const element = input.firstParent('.point-element');

			element.qs('.point-order-min', (orderMin) => {

				if(orderMin.classList.contains('point-badge-selected')) {
					qsa('.shop-basket-submit-order-error', node => node.classList.add('hide'));
					qsa('.shop-basket-submit-order-valid', node => node.classList.remove('hide'));
				} else {
					qsa('.shop-basket-submit-order-error', node => node.classList.remove('hide'));
					qsa('.shop-basket-submit-order-valid', node => node.classList.add('hide'));
				}

			}, () => {
				qsa('.shop-basket-submit-order-error', node => node.classList.add('hide'));
				qsa('.shop-basket-submit-order-valid', node => node.classList.remove('hide'));
			})

			element.qs('.point-shipping', (shipping) => {

				if(shipping.classList.contains('point-badge-selected') === false) {
					price += parseFloat(element.dataset.shipping);
				}

			})

		})

		qs('#shop-basket-price', node => node.innerHTML = money(price));

	}

	static deleteProduct(dateId, productId) {

		this.deleteBasketProduct(dateId, productId);

		const basket = this.getBasket(dateId);
		this.loadSummary(dateId, basket.sale);

		return false;

	}

	static empty(source, dateId) {

		if(this.getBasket(dateId).sale === null) {
			if(confirm(source.dataset.confirmNormal) === false) {
				return false;
			}
		} else {
			if(confirm(source.dataset.confirmModify) === false) {
				return false;
			}
		}

		this.emptyBasket(dateId);

		const newLocation = document.location.href.removeArgument('modify');
		window.location.href = newLocation;

		return false;

	}

	static newBasket() {
		return {
			products: {},
			createdAt: Date.now() / 1000,
			sale: null
		};

	}

	static updateBasketProducts(dateId, products) {

		let basket = this.getBasket(dateId);
		basket.products = products;
		this.setBasket(dateId, basket);

		return false;

	}

	static deleteBasketProduct(dateId, productId) {

		let basket = this.getBasket(dateId);
		delete basket.products[productId];

		this.setBasket(dateId, basket);

		return false;

	}

	static emptyBasket(dateId) {

		const basket = this.newBasket();
		this.setBasket(dateId, basket);

		return basket;

	}

	static transfer(target, dateId) {

		const products = this.getBasket(dateId).products;

		let url = target.dataset.url;
		url = url.setArgument('products', JSON.stringify(products));

		window.parent.location = url;

	}

	static doCreate(dateId) {

		const pathname = location.pathname;
		const shopAndDatePathname = pathname.substr(1, pathname.lastIndexOf('/') - 1);

		const basket = this.getBasket(dateId);

		const post = {
			products: basket.products,
			date: dateId,
		};

		qs('#shop-basket-point input[name="shopPoint"]:checked', node => post.shopPoint = node.value);
		qs('#shop-basket-submit input[name="terms"]:checked', () => post.terms = 1);

		new Ajax.Query()
			.url('/'+ shopAndDatePathname +'/:doCreateSale')
			.body(post)
			.fetch();

	}

	static checkExpiration() {

		if(this.isEmbed()) {
			return;
		}

		const time = Date.now() / 1000;

		for(let key in localStorage) {

			if(key.startsWith(this.prefix) === false) {
				continue;
			}

			const basket = JSON.parse(localStorage.getItem(key));

			if(basket['createdAt'] < time - 86400 * 15) {
				delete(localStorage.removeItem(key))
			}

		}
	}

	static selectHome() {

		qs('#shop-basket-address-wrapper', wrapper => wrapper.dataset.type = 'home');

		if(qs('#shop-basket-address-form')) {
			qs('#shop-basket-submit').classList.add('hide');
		} else if(qs('#shop-basket-address-show')) {
			qs('#shop-basket-submit').classList.remove('hide');
		}

		this.loadPrice();

	}

	static selectPlace() {

		qs('#shop-basket-address-wrapper', wrapper => wrapper.dataset.type = 'place');

		qs('#shop-basket-submit').classList.remove('hide');

		this.loadPrice();

	}

}