document.delegateEventListener('input', '#market-item-list input', function(e) {

	Market.itemListUpdate();

});

class Market {

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

	static itemSaleUpdate(item) {

		const inputUnitPrice = item.qs('input[name^="unitPrice"]');
		const inputNumber = item.qs('input[name^="number"]');
		const inputPrice = item.qs('input[name^="price"]');
		const inputLocked = item.qs('input[name^="locked"]');

		let unitPrice = this.getRealValue(inputUnitPrice);
		let number = this.getRealValue(inputNumber);
		let price = this.getRealValue(inputPrice);

		// Prix unitaire + Quantité non verrouillé
		const checkPrice = () => {

			if(
				unitPrice !== null &&
				number !== null &&
				this.checkPropertyDisabled(inputUnitPrice) === false &&
				this.checkPropertyDisabled(inputNumber) === false
			) {

				price = Math.round(unitPrice * number * 100) / 100;
				inputPrice.value = price;
				this.setEntryValue(item.dataset.item, 'price', price);

				this.unlockProperty(inputUnitPrice);
				this.unlockProperty(inputNumber);
				this.lockProperty(inputPrice);

			} else if(this.checkPropertyDisabled(inputPrice)) { // Le montant total est désactivé mais la saisie a changé

				inputPrice.value = '';
				price = null;

				this.setEntryValue(item.dataset.item, 'price', null);

				this.unlockProperty(inputUnitPrice);
				this.unlockProperty(inputNumber);
				this.unlockProperty(inputPrice);

			}

		};

		// Prix unitaire + Montant total non verrouillé
		const checkNumber = () => {

			if(
				unitPrice !== null &&
				price !== null &&
				this.checkPropertyDisabled(inputUnitPrice) === false &&
				this.checkPropertyDisabled(inputPrice) === false
			) {

				number = Math.round(price / unitPrice * 100) / 100;
				inputNumber.value = number;
				this.setEntryValue(item.dataset.item, 'number', number);

				this.unlockProperty(inputUnitPrice);
				this.lockProperty(inputNumber);
				this.unlockProperty(inputPrice);

			} else if(this.checkPropertyDisabled(inputNumber)) { // Le montant total est désactivé mais la saisie a changé

				number = null;
				inputNumber.value = '';
				this.setEntryValue(item.dataset.item, 'number', null);

				this.unlockProperty(inputUnitPrice);
				this.unlockProperty(inputNumber);
				this.unlockProperty(inputPrice);

			}

		};

		// Quantité + Montant total non verrouillé
		const checkUnitPrice = () => {

			if(
				number !== null &&
				price !== null &&
				this.checkPropertyDisabled(inputNumber) === false &&
				this.checkPropertyDisabled(inputPrice) === false
			) {

				unitPrice = Math.round(price / number * 100) / 100;
				inputUnitPrice.value = unitPrice;
				this.setEntryValue(item.dataset.item, 'unit-price', unitPrice);

				this.lockProperty(inputUnitPrice);
				this.unlockProperty(inputNumber);
				this.unlockProperty(inputPrice);

			} else if(this.checkPropertyDisabled(inputUnitPrice)) { // Le montant total est désactivé mais la saisie a changé

				inputUnitPrice.value = '';
				unitPrice = null;

				this.setEntryValue(item.dataset.item, 'unit-price', null);

				this.unlockProperty(inputUnitPrice);
				this.unlockProperty(inputNumber);
				this.unlockProperty(inputPrice);

			}

		};

		switch(inputLocked.value) {

			case 'unit-price' :
				checkUnitPrice();
				checkPrice();
				checkNumber();
				break;

			case 'price' :
				checkPrice();
				checkUnitPrice();
				checkNumber();
				break;

			case 'number' :
			default :
				checkNumber();
				checkPrice();
				checkUnitPrice();
				break;

		}

	}

	static checkPropertyDisabled(input) {
		return input.parentElement.classList.contains('disabled');
	}

	static unlockProperty(input) {

		const item = input.firstParent('.market-entry');
		const property = input.firstParent('.market-entry-field').dataset.property;

		item.qs('.market-entry-field[data-property="'+ property +'"].disabled', field => field.classList.remove('disabled'));

		const actions = item.qs('.market-entry-actions[data-property="'+ property +'"]');

		actions.qs('.market-entry-lock', action => action.classList.add('hide'));
		actions.qs('.market-entry-erase', action => {
			(input.value === '') ? action.classList.add('hide') : action.classList.remove('hide')
		});

	}

	static lockProperty(input) {

		const entry = input.firstParent('.market-entry');
		const field = input.firstParent('.market-entry-field');
		const property = field.dataset.property;

		entry.qs('.market-entry-field[data-property="'+ property +'"]').classList.add('disabled');

		const actions = entry.qs('.market-entry-actions[data-property="'+ property +'"]');
		actions.qs('.market-entry-lock', action => action.classList.remove('hide'));
		actions.qs('.market-entry-erase', action => action.classList.add('hide'));

		entry.qs('input[name^="locked"]').value = property;

	}

	static itemErase(target) {

		const property = target.firstParent('.market-entry-actions').dataset.property;
		const entry = target.firstParent('.market-entry');
		const field = entry.qs('.market-entry-field[data-property="'+ property +'"]');

		// On efface la valeur en cours
		field.qs('input').value = '';
		field.qs('.market-entry-value').innerHTML = this.getKeyboardEmpty();

		// On ferme le clavier
		if(field.classList.contains('selected')) {
			this.keyboardClose();
		}

		entry.qs('input[name^="locked"]').value = '';

		this.itemSaleUpdate(entry);

	}

	static showEntry(item) {

		qsa('#market-item-sale .market-entry:not(.hide)', entry => entry.classList.add('hide'));

		const entry = qs('#market-entry-'+ item.dataset.item);

		entry.classList.remove('hide');

		this.itemSaleUpdate(entry);

		const inputUnitPrice = entry.qs('input[name^="unitPrice"]');
		const inputNumber = entry.qs('input[name^="number"]');
		const inputPrice = entry.qs('input[name^="price"]');

		let unitPrice = this.getRealValue(inputUnitPrice);
		let number = this.getRealValue(inputNumber);
		let price = this.getRealValue(inputPrice);

		const fields = (number !== null ? 1 : 0)
			+ (price !== null ? 1 : 0)
			+ (unitPrice !== null ? 1 : 0);

		if(fields <= 1) {
			this.unlockProperty(inputUnitPrice);
			this.unlockProperty(inputNumber);
			this.unlockProperty(inputPrice);
		}

		const locked = entry.qs('input[name^="locked"]').value;

		if(locked === 'number') {
			this.keyboardOpen(entry.qs('.market-entry-field[data-property="price"]'));
		} else if(locked === 'price') {
			this.keyboardOpen(entry.qs('.market-entry-field[data-property="number"]'));
		} else {

			const unit = entry.qs('.market-entry-field[data-property="number"] .market-entry-value').dataset.unit;

			if(this.isInteger(unit)) {
				this.keyboardOpen(entry.qs('.market-entry-field[data-property="number"]'));
			} else {
				this.keyboardOpen(entry.qs('.market-entry-field[data-property="price"]'));
			}

		}

	}

	static hideEntry(target) {

		target.firstParent('.market-entry').classList.add('hide');

	}

	static keyboardEntry = null;
	static keyboardField = null;
	static keyboardProperty = null;
	static keyboardValue = 0;

	static keyboardToggle(target) {

		// Déjà sélectionné
		if(this.keyboardField === target) {
			this.keyboardClose();
		} else {
			this.keyboardOpen(target);
		}

	}

	static keyboardOpen(target) {

		const entry = target.firstParent('.market-entry');

		this.keyboardEntry = entry;
		this.keyboardProperty = target.dataset.property;
		this.keyboardField = this.keyboardEntry.qs('.market-entry-field[data-property="'+ this.keyboardProperty +'"]');
		this.keyboardValue = 0;

		entry.qs('.market-entry-field.selected', field => field.classList.remove('selected'));
		target.classList.add('selected');
		entry.qs('.market-entry-keyboard').classList.remove('disabled');

	}

	static keyboardClose() {

		const entry = this.keyboardEntry;

		entry.qs('.market-entry-field.selected', field => field.classList.remove('selected'));
		entry.qs('.market-entry-keyboard').classList.add('disabled');

		this.keyboardEntry = null;
		this.keyboardProperty = null;
		this.keyboardField = null;
		this.keyboardValue = null;

	}

	static keyboardDigit(digit) {

		this.keyboardValue *= 10;
		this.keyboardValue += digit;

		const unit = this.keyboardField.qs('.market-entry-value').dataset.unit;

		const value = this.keyboardValue / (this.isInteger(unit) ? 1 : 100);
		this.keyboardField.qs('input').value = value;

		this.itemSaleUpdate(this.keyboardEntry);

		this.setEntryValueFromKeyboard(value);

	}

	static keyboardRemoveDigit() {

		this.keyboardValue /= 10;
		this.keyboardValue = Math.floor(this.keyboardValue);

		const unit = this.keyboardField.qs('.market-entry-value').dataset.unit;

		const value = this.keyboardValue / (this.isInteger(unit) ? 1 : 100);
		this.keyboardField.qs('input').value = value;
		this.itemSaleUpdate(this.keyboardEntry);

		this.setEntryValueFromKeyboard(value);

	}

	static setEntryValueFromKeyboard(value) {

		this.setEntryValue(this.keyboardEntry.dataset.item, this.keyboardProperty, value);

	}

	static isInteger(unit) {
		return (unit === '' || unit === 'bunch' || unit === 'unit' || unit === 'gram-250' || unit === 'gram-500');
	}

	static setEntryValue(item, property, value) {

		let text;

		const node = qs('#market-entry-'+ item +'-'+ property);

		if(value === null || value === 0) {
			text = this.getKeyboardEmpty();
		} else {

			if(this.isInteger(node.dataset.unit)) {
				text = value;
			} else {
				const textValue = Math.round(value * 100).toString().padStart(3, '0');
				text = textValue.substring(0, textValue.length - 2) +','+ textValue.substring(textValue.length - 2, textValue.length);
			}

		}

		node.innerHTML = text;

	}

	static deleteItem(target) {

		const form = target.firstParent('form');

		form.qsa('.market-entry-lines input', input => input.value = '');
		form.dispatchEvent(new CustomEvent("submit"));

	}

	static getKeyboardEmpty() {
		return '-,--';
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

	static getRealValue(input) {

		if(input.value === '') {
			return null;
		} else {
			return parseFloat(input.value);
		}

	}


}