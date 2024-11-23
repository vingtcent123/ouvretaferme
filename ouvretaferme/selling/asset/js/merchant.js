document.addEventListener('keyup', function(e) {

	const market = qs('.merchant');

	if(market === null) {
		return;
	}

	d(e.key);

});

class Merchant {

	static unlockProperty(input) {

		const item = input.firstParent('.merchant');
		const property = input.firstParent('.merchant-field').dataset.property;

		item.qs('.merchant-field[data-property="'+ property +'"].disabled', field => field.classList.remove('disabled'));

		const actions = item.qs('.merchant-actions[data-property="'+ property +'"]');

		actions.qs('.merchant-lock', action => action.classList.add('hide'));
		actions.qs('.merchant-erase', action => {
			(input.value === '') ? action.classList.add('hide') : action.classList.remove('hide')
		});

	}

	static lockProperty(input) {

		const entry = input.firstParent('.merchant');
		const field = input.firstParent('.merchant-field');
		const property = field.dataset.property;

		entry.qs('.merchant-field[data-property="'+ property +'"]').classList.add('disabled');

		const actions = entry.qs('.merchant-actions[data-property="'+ property +'"]');
		actions.qs('.merchant-lock', action => action.classList.remove('hide'));
		actions.qs('.merchant-erase', action => action.classList.add('hide'));

		entry.qs('input[name^="locked"]').value = property;

	}

	static itemErase(target) {

		const property = target.firstParent('.merchant-actions').dataset.property;
		const entry = target.firstParent('.merchant');
		const field = entry.qs('.merchant-field[data-property="'+ property +'"]');

		// On efface la valeur en cours
		field.qs('input').value = '';
		field.qs('.merchant-value').innerHTML = this.getKeyboardEmpty();

		// On ferme le clavier
		if(field.classList.contains('selected')) {
			this.keyboardClose();
		}

		entry.qs('input[name^="locked"]').value = '';

		this.itemSaleUpdate(entry);

	}

	static showEntry(item) {

		qsa('#market-item-sale .merchant:not(.hide)', entry => entry.classList.add('hide'));

		const entry = qs('#merchant-'+ item.dataset.item);

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
			this.keyboardOpen(entry.qs('.merchant-field[data-property="price"]'));
		} else if(locked === 'price') {
			this.keyboardOpen(entry.qs('.merchant-field[data-property="number"]'));
		} else {

			const unit = entry.qs('.merchant-field[data-property="number"] .merchant-value').dataset.unit;

			if(this.isInteger(unit)) {
				this.keyboardOpen(entry.qs('.merchant-field[data-property="number"]'));
			} else {
				this.keyboardOpen(entry.qs('.merchant-field[data-property="price"]'));
			}

		}

	}

	static hideEntry(target) {

		target.firstParent('.merchant').classList.add('hide');

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

		const entry = target.firstParent('.merchant');

		this.keyboardEntry = entry;
		this.keyboardProperty = target.dataset.property;
		this.keyboardField = this.keyboardEntry.qs('.merchant-field[data-property="'+ this.keyboardProperty +'"]');
		this.keyboardValue = 0;

		entry.qs('.merchant-field.selected', field => field.classList.remove('selected'));
		target.classList.add('selected');
		entry.qs('.merchant-keyboard').classList.remove('disabled');

	}

	static keyboardClose() {

		const entry = this.keyboardEntry;

		entry.qs('.merchant-field.selected', field => field.classList.remove('selected'));
		entry.qs('.merchant-keyboard').classList.add('disabled');

		this.keyboardEntry = null;
		this.keyboardProperty = null;
		this.keyboardField = null;
		this.keyboardValue = null;

	}

	static keyboardDigit(digit) {

		this.keyboardValue *= 10;
		this.keyboardValue += digit;

		const unit = this.keyboardField.qs('.merchant-value').dataset.unit;

		const value = this.keyboardValue / (this.isInteger(unit) ? 1 : 100);
		this.keyboardField.qs('input').value = value;

		this.itemSaleUpdate(this.keyboardEntry);

		this.setEntryValueFromKeyboard(value);

	}

	static keyboardRemoveDigit() {

		this.keyboardValue /= 10;
		this.keyboardValue = Math.floor(this.keyboardValue);

		const unit = this.keyboardField.qs('.merchant-value').dataset.unit;

		const value = this.keyboardValue / (this.isInteger(unit) ? 1 : 100);
		this.keyboardField.qs('input').value = value;
		this.itemSaleUpdate(this.keyboardEntry);

		this.setEntryValueFromKeyboard(value);

	}

	static setEntryValueFromKeyboard(value) {

		this.setEntryValue(this.keyboardEntry.dataset.item, this.keyboardProperty, value);

	}

	static isInteger(unit) {
		return (unit === '' || unit === 'bunch' || unit === 'unit' || unit === 'box' || unit === 'gram-250' || unit === 'gram-500');
	}

	static setEntryValue(item, property, value) {

		let text;

		const node = qs('#merchant-'+ item +'-'+ property);

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

	static getKeyboardEmpty() {
		return '-,--';
	}

	static checkPropertyDisabled(input) {
		return input.parentElement.classList.contains('disabled');
	}

	static deleteItem(target) {

		const form = target.firstParent('form');

		form.qsa('.merchant-lines input', input => input.value = '');
		form.dispatchEvent(new CustomEvent("submit"));

	}

	static getRealValue(input) {

		if(input.value === '') {
			return null;
		} else {
			return parseFloat(input.value);
		}

	}

}