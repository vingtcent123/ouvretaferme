/**
 * Form submission
 */
document.delegateEventListener('submit', 'form[data-ajax-form]', function(e) {

	e.preventDefault();
	e.stopImmediatePropagation();

	submitAjaxForm(this);

});

function submitAjaxForm(form) {

	const method = (form.getAttribute('method') ?? 'POST').toUpperCase();

	let object = form.hasAttribute('data-ajax-class') ?
		eval(form.getAttribute('data-ajax-class')) :
		Ajax.Navigation;

	const body = form.form();
	let url = form.getAttribute('data-ajax-form');

	const disable = form.qsa('button[data-submit-waiter]');

	disable.forEach((button) => {
		if(button.dataset.submitWaiter !== '') {
			button.dataset.submitOriginal = button.innerHTML;
			button.innerHTML = button.dataset.submitWaiter;
		}
		button.classList.add('disabled');
	})

	const enable = () => disable.forEach((button) => {
		if(button.dataset.submitWaiter !== '') {
			button.innerHTML = button.dataset.submitOriginal;
			button.dataset.submitOriginal = null;
		}
		button.classList.remove('disabled');
	});

	switch(method) {

		case 'GET' :

			url += url.includes('?') ? '&' : '?';
			url += new URLSearchParams(body).toString();

			new object(form)
				.method(method)
				.url(url)
				.fetch()
				.then(() => enable(), () => enable());

			break;

		case 'POST' :

			new object(form)
				.method(method)
				.url(url)
				.skipHistory()
				.body(body)
				.fetch()
				.then(() => enable(), () => enable());

			break;

		default :
			throw 'Method should be GET or POST';

	}

}

document.delegateEventListener('click', '[data-ajax-submit]', function(e) {

	e.preventDefault();
	e.stopImmediatePropagation();

	const url = this.getAttribute('data-ajax-submit');
	const form = this.hasAttribute('data-ajax-target') ? qs(this.getAttribute('data-ajax-target')) : this.firstParent('form');

	if(form.dataset.ajaxSubmitMethod === undefined) {
		form.dataset.ajaxSubmitMethod = form.getAttribute('method');
	}

	if(form === null) {
		throw "No form found for data-ajax-submit";
	}

	form.qsa('[data-form-zombie]', node => node.remove());

	if(this.dataset.ajaxBody !== undefined) {

		JSON.parse(this.dataset.ajaxBody).forEach(([key, value]) => {
			form.insertAdjacentHTML('afterbegin', '<input type="hidden" '+ 'name'.attr(key) +' '+ 'value'.attr(value) +' data-form-zombie="1"/>');
		});

	} else {

		this.post().forEach((value, key) => {
			form.insertAdjacentHTML('afterbegin', '<input type="hidden" '+ 'name'.attr(key) +' '+ 'value'.attr(value) +' data-form-zombie="1"/>');
		});

	}

	if(this.hasAttribute('data-ajax-method')) {
		form.setAttribute('method', this.getAttribute('data-ajax-method'));
	} else if(form.dataset.ajaxSubmitMethod !== undefined) {
		form.setAttribute('method', form.dataset.ajaxSubmitMethod);
	}

	if(this.dataset.ajaxNavigation === 'never') {
		form.setAttribute('action', url);
		form.submit();
	} else {
		form.setAttribute('action', 'javascript:;');
		form.setAttribute('data-ajax-form', url);
		form.dispatchEvent(new CustomEvent("submit"));
	}


});

document.delegateEventListener('input', 'input[data-type="fqn"]', function(e) {

	if(this.value.match(/^[a-z0-9\-]*$/) === null) {
		this.parentNode.classList.add('form-error-field');
	} else {
		this.parentNode.classList.remove('form-error-field');
	}

});

document.delegateEventListener('input', 'div.form-range input[type="range"]', function(e) {

	RangeField.updateLabel(e.target);

});

document.delegateEventListener('dropdownAfterShow', '.form-range-label', function(e) {

	const range = e.target.previousSibling;
	const list = e.detail.list;

	list.qs('[type="number"]').select();
	list.qs('[type="number"]').value = range.value;

});

/**
 * Manipulates ranges
 */
class RangeField {

	static setValue(target) {

		const newValue = target.firstParent('.form-range-label').qs('[type="number"]').value;

		// On va mettre à jour le range
		target.firstParent('.form-range').qs('[type="range"]', rangeField => {

			rangeField.dataset.internalInput = 'true';
			rangeField.value = newValue;
			rangeField.dispatchEvent(new Event("input"));

		});

	}

	static updateLabel(target) {

		if(target.dataset.internalInput === 'true') {
			target.dataset.internalInput = 'false';
			return;
		}
		const label = target.firstParent('div.form-range').qs('[type="number"]');
		label.value = target.value;

	}

}

/**
 * Manipulates switch
 */
class SwitchField {

	static change(target, onchange) {

		const active = target.classList.contains('field-switch-off');

		target.toggleSwitch();

		const input = target.qs('input');

		input.value = active ? target.dataset.valueOn : target.dataset.valueOff;

		if(onchange) {
			onchange(input);
		}

	}

	static updateLabel(target) {

		const label = target.firstParent('div.form-range').qs('[type="number"]');
		label.value = target.value;

	}

}

/**
 * Manipulates calculations
 */
document.delegateEventListener('input', '[data-calculation]', function(e) {
	CalculationField.calculateValue(this);
});

class CalculationField {

	/**
	 * Calculates the final value according to the input content
	 */
	static calculateValue(target) {
		const currentOperation = target.value.replace(/,/g, '.').replace(/=/g, '');
		const hiddenElement = target.firstParent('div').qs('input[type="hidden"]');
		const resultElement = target.firstParent('div').qs('[data-calculated]');

		if(!currentOperation) {
			hiddenElement.setAttribute('value', ''); // reset value
			resultElement.hide();
		}

		try {
			const result = round(eval(currentOperation));

			if(isNaN(result) === false) {
				if(result.toString() === currentOperation.toString()) {
					resultElement.hide();
				} else {
					resultElement.removeHide();
				}
				resultElement.innerHTML = '= ' + result;
				hiddenElement.setAttribute('value', result);
			} else {
				hiddenElement.setAttribute('value', ''); // reset value
			}
		} catch(e) {
			hiddenElement.setAttribute('value', ''); // reset value
		}
	}

	/**
	 * Sets the field to the given value
	 */
	static setValue(hiddenTarget, value) {

		hiddenTarget.setAttribute('value', value);

		const resultElement = hiddenTarget.firstParent('div').qs('[data-calculated]');

		if(resultElement) {

			resultElement.hide(); // hides because values are the same (calculated + input)
			resultElement.innerHTML = '= ' + value;

			// Updates the input field
			const operationElement = resultElement.firstParent('div').qs('input[data-calculation="1"]');

			if(operationElement) {
				operationElement.value = value;
			}
		}

	}

	static getValue(hiddenTarget) {

		return parseFloat(hiddenTarget.getAttribute('value'));

	}
}

/**
 * Manipulates dates
 */

document.delegateEventListener('input', '[data-week-number]', function(e) {
	DateField.updateWeeks(this);
});

document.delegateEventListener('input', '[data-week-selector]', function(e) {
	DateField.updateWeeks(qs(this.dataset.weekSelector));
});

class DateField {

	static weeks = {};

	/* Handle fallback <input type="week"/> */
	static startFieldWeek(selector) {

		const wrapper = qs(selector);

		if(wrapper.length === 0) {
			return;
		}

		wrapper.qsa('div.field-week-fields input',function(node) {

			node.addEventListener('input', e => {

				const weekNumber = wrapper.qs('[data-fallback="weekNumber"]').value.padStart(2, '0');
				const year = wrapper.qs('[data-fallback="year"]').value;

				wrapper.qs('input.field-week-value').value = year +'-W'+ weekNumber;

			});

		});

	};

	static changeFieldWeek(selector, target) {

		const wrapper = qs(selector);
		const week = target.dataset.week;

		wrapper.qs('[data-fallback="year"]').value = week.substring(0, 4);
		wrapper.qs('[data-fallback="weekNumber"]').value = parseInt(week.substring(6, 8));

		wrapper.qs('input.field-week-value').value = week;

		this.updateWeeks(wrapper.qs('[data-fallback="weekNumber"]'));

	};

	static openWeekSelector(target) {

		const wrapper = target.firstParent('.field-week-wrapper');

		if(wrapper.dataset.withYear === 'true') {

			const year = parseInt(wrapper.qs('[data-fallback="year"]').value);

			if(
				isNaN(year) === false &&
				year >= 2000 &&
				year <= (new Date().getFullYear() + 10)
			) {

				const link = wrapper.qs('.field-week-selector-navigation-after');

				if(parseInt(link.getAttribute('post-year')) !== year) {

					link.setAttribute('post-year', year);
					link.click();

				}

			}

		}

		Lime.Dropdown.open(wrapper.qs('[data-fallback="weekNumber"]'), "center-center");


	}

	static onInputFieldWeek(selector) {

		const wrapper = qs(selector);

		const year = parseInt(wrapper.qs('[data-fallback="year"]').value);
		const week = parseInt(wrapper.qs('[data-fallback="weekNumber"]').value);

		wrapper.qs('input.field-week-value').value = year +'-W'+ (week < 10 ? '0' : '') + week;

		this.updateWeeks(qs('[data-fallback="weekNumber"]'));

	};

	static blurFieldWeek(target) {

		if(isTouch()) {
			target.blur();
		}

	};

	/* Handle fallback <input type="month"/> */
	static startFieldMonth(selector, display) {

		const wrapper = qs(selector);

		if(wrapper.length === 0) {
			return;
		}

		if(display === 'auto') {

			const test = document.createElement('input');
			test.type = 'month'; // Vérification de la disponibilité du type sur le navigateur du client

			display = (test.type === 'month') ? 'native' : 'fallback';

		}

		if(display === 'native') {
			wrapper.qs('div.field-month-native').classList.remove('hide');
		} else {
			wrapper.qs('div.field-month-fallback').classList.remove('hide');
		}

		wrapper.qsa('div.field-month-fallback input, select', node => {

			node.addEventListener('input', () => {

				let monthNumber = wrapper.querySelector('[data-fallback="monthNumber"]').value.padStart(2, '0');
				let year = wrapper.querySelector('[data-fallback="year"]').value;

				wrapper.querySelector('div.field-month-native input').value = year +'-'+ monthNumber;

			});

		});

	};

	static updateWeeks(selector) {

		const label = qs(selector.dataset.weekNumber);
		const weekNumber = parseInt(selector.value);

		if(weekNumber) {

			let year;

			if(selector.dataset.yearSelector) {
				qs(selector.dataset.yearSelector, node => year = node.value, () => year = new Date().getFullYear());
			} else {
				year = new Date().getFullYear();
			}

			const startDate = this.getDateOfISOWeek(1, weekNumber, year);
			const endDate = this.getDateOfISOWeek(7, weekNumber, year);

			label.innerHTML = this.formatDateElement(startDate.getDate()) +'/'+ this.formatDateElement(1 + startDate.getMonth()) +'&nbsp;'+ Lime.Asset.icon('arrow-right') +'&nbsp;'+ this.formatDateElement(endDate.getDate()) +'/'+ this.formatDateElement(1 + endDate.getMonth());

		} else {
			label.innerHTML = '';
		}

	};

	static formatDateElement(value) {
		return (value < 10 ? '0' : '') + value;
	}

	static getDateOfISOWeek(day, week, year) {
		const simple = new Date(year, 0, 1 + (week - 1) * 7);
		const dow = simple.getDay();
		if(dow <= 4) {
			simple.setDate(simple.getDate() - simple.getDay() + day);
		} else {
			simple.setDate(simple.getDate() + 7 + day - simple.getDay());
		}
		return simple;
	}

	static onclickWeek(url, target) {

		new Ajax.Navigation()
			.url(url.replace('{current}', target.dataset.week))
			.fetch();

	}

	static onclickMonth(url, target) {

		new Ajax.Navigation()
			.url(url.replace('{current}', target.dataset.month))
			.fetch();

	}

}

class CheckboxField {

	static all(target, checked, selector, callback = undefined) {

		target.qsa(selector, field => {

			if(field.disabled === true) {
				return;
			}

			field.checked = checked;

			if(typeof callback !== 'undefined') {
				callback(field);
			}

		});

	}

	static check(target, name, value) {

		const form = target.firstParent('form');
		form.qsa('[name^="'+ name +'"]', field => {

			if(field.disabled === true) {
				return;
			}

			field.checked = value;

		});

	}

}

class ColorField {

	static update(field) {

		const wrapper = field.firstParent('.field-color');

		wrapper.qs('input[type="checkbox"]', checkbox => checkbox.checked = false);

		const hiddenField = wrapper.qs('input[type="hidden"]');
		hiddenField.value = field.value;
		hiddenField.dispatchEvent(new CustomEvent("input"));

	}

	static setEmpty(field, emptyColor = '#000000') {

		const wrapper = field.firstParent('.field-color')

		const hiddenField = wrapper.qs('input[type="hidden"]');
		wrapper.qs('input[type="color"]').value = emptyColor;
		hiddenField.value = (field.checked ? '' : emptyColor);
		hiddenField.dispatchEvent(new CustomEvent("input"));

	}

}

class SelectDropdownField {

	static select(target) {

		const head = target.firstParent('.form-dropdown-list').previousSibling.qs('.form-dropdown-head');

		const selectedItem = target.firstParent('.dropdown-item');
		head.innerHTML = selectedItem.qs('.form-dropdown-content').innerHTML;

		const items = target.firstParent('.dropdown-list');
		items.qs('.dropdown-item.selected', item => item.classList.remove('selected'));
		selectedItem.classList.add('selected');

	}

}

/**
 * Manipulates field with a limit
 */
const selector = 'div.form-character-count-wrapper input[type=text], div.form-character-count-wrapper textarea';

document.delegateEventListener('focus', selector, function(e) {
	CounterField.refresh(this);
	CounterField.show(this);
});

document.delegateEventListener('blur', selector, function(e) {
	CounterField.hide(this);
});

document.delegateEventListener('input', selector, function(e) {
	CounterField.refresh(this);
});

class CounterField {

	static show(input, visible) {

		const counter = qs(".form-character-count[data-limit-for="+ input.id +"]");

		counter.style.display = 'flex';

		if(input.tagName == "TEXTAREA") {

			const originalHeight = input.offsetHeight;
			const newHeight = originalHeight - counter.offsetHeight;

			input.style.height = newHeight +'px';
			input.setAttribute('data-field-height', originalHeight);

		}
	};

	static hide(input, visible) {

		const counter = qs(".form-character-count[data-limit-for="+ input.id +"]");

		counter.style.display = '';

		if(input.tagName == "TEXTAREA") {

			const originalHeight = input.getAttribute('data-field-height');

			input.style.height = originalHeight +'px';
			input.removeAttribute('data-field-height');

		}
	};

	static refresh(input) {

		const counter = qs(".form-character-count[data-limit-for="+ input.id +"]");
		const wrapper = input.firstParent('.form-character-count-wrapper');

		const limit = parseInt(input.getAttribute("data-limit"));

		const count = input.value.length;
		const countLeft = limit - count;

		counter.classList.remove("error");
		counter.classList.add("focus");
		wrapper.classList.remove("form-error-field");

		if(countLeft < 0) {
			counter.classList.add("error");
			counter.classList.remove("focus");
			wrapper.classList.add("form-error-field");
		}

		if(input.tagName === 'TEXTAREA') {
			counter.innerHTML = count +'/'+ limit;
		} else {
			counter.innerHTML = countLeft;
		}
	}


};

/**
 *  Autocomplete features
 */
class AutocompleteField {

	static queryTimeout = null;
	static ignoreNextFocus = false;
	static clickListener = {};

	static getDropdown(input) {

		const dropdownId = input.id +'-autocomplete';

		if(this.hasDropdown(input) === false) {

			const dropdown = document.createElement('div');
			dropdown.id = dropdownId;
			dropdown.classList.add('autocomplete-dropdown');

			if(input.dataset.autocompleteTextual) {
				dropdown.classList.add('autocomplete-dropdown-textual');
			}

			input.insertAdjacentElement('afterend', dropdown);
		}

		return qs('#'+ dropdownId);

	}

	static hasDropdown(input) {
		return (qs('#'+ input.id +'-autocomplete') !== null);
	}

	static start(input) {

		input.addEventListener('focusin', () => {

			if(AutocompleteField.ignoreNextFocus) {
				AutocompleteField.ignoreNextFocus = false;
				return;
			}

			if(input.id === null) {
				throw 'Missing ID for input';
			}

			this.init(input); // Init autocomplete field once
			this.query(input);

			input.setSelectionRange(0, input.value.length);

			if(this.isFullscreen() === false) {

				this.clickListener[input.id] = (e) => {

					let target;
					for(target = e.target; target && target !== input; target = target.parentNode);

					if(target !== input) {
						this.internalRemove(input);
					} else {
						watch();
					}

				};

				const watch = () => setTimeout(() => document.addEventListener('click', this.clickListener[input.id], {once: true}), 0);

				watch();

			}

		});

	};

	static init(input) {

		if(input.hasAttribute('data-autocomplete')) {
			return;
		}

		input.setAttribute('data-autocomplete', 'on');
		input.setAttribute('autocomplete', 'off');

		input.addEventListener('focusin', () => {
			input.setSelectionRange(0, input.value.length);
		});

		// Workaround pour téléphone qui lance deux events 'input' pour un seul changement dans le champ
		input.lastValue = null;

		input.addEventListener('input', () => {

			if(input.value !== input.lastValue) {
				input.lastValue = input.value;
				this.change(input);
			}

		});

		input.addEventListener('keydown', e => {

			switch(e.key) {

				case 'Enter' : // Enter

					e.preventDefault();

					if(this.hasDropdown(input)) {

						this.getDropdown(input).qs('li.selected', input => {
							input.dispatchEvent(new CustomEvent('autocompleteEnter'));
						});

					}

					break;

				case 'ArrowUp' :
					this.hover(input, 'up', e);
					break;

				case 'ArrowDown' :
					this.hover(input, 'down', e);
					break;
			}


		});

	};

	static change(input) {

		this.onUpdate(input);

		if(this.queryTimeout !== null) {
			clearTimeout(this.queryTimeout);
			this.queryTimeout = null;
		}

		this.queryTimeout = setTimeout(() => {
			this.queryTimeout = null;
			this.query(input);
		}, 200);

	};

	static query(input) {

		const url = input.dataset.autocompleteUrl;
		const body = new URLSearchParams();

		if(input.dataset.autocompleteBody) {
			Object.entries(JSON.parse(input.dataset.autocompleteBody)).forEach(([key, value]) => {
				if(value !== null && typeof value === 'object') {
					Object.entries(value).forEach(([subKey, subValue]) => {
						body.append(key +'['+ subKey +']', subValue);
					});
				} else {
					body.append(key, value);
				}
			});
		}

		const items = input.dataset.autocompleteItems;
		const value = input.value;

		const field = input.getAttribute('data-autocomplete-field');
		const multiple = (field.indexOf('[]') !== -1);

		if(multiple === false) {
			qs('#'+ items).innerHTML = '';
		}

		this.onBeforeQuery(input, {
			body: body
		});

		AutocompleteField.move(input);

		body.set('query', value);

		new Ajax.Query(input)
			.url(url)
			.body(body)
			.fetch()
			.then((json) => {

				// Le focus est perdu suite au déplacement précédent
				if(document.activeElement !== input) {
					AutocompleteField.ignoreNextFocus = true;
					input.focus();
				}

				AutocompleteField.source(input, json.results);

				this.onSource(input, {
					query: value,
					results: json.results
				});

			});

	};

	static onUpdate(input) {
		this.dispatch(input, 'autocompleteUpdate', {});
	};

	static onSource(input, detail) {
		this.dispatch(input, 'autocompleteSource', detail);
	};

	static onBeforeQuery(input, detail) {
		this.dispatch(input, 'autocompleteBeforeQuery', detail);
	};

	static onSelect(input, detail) {

		document.activeElement.blur();

		switch(input.dataset.autocompleteSelect) {

			case 'submit' :
				this.internalRemove(input);
				input.dispatchEvent(new CustomEvent("submit"));
				break;

			case 'event' :
				this.dispatch(input, 'autocompleteSelect', detail);
				break;

		}

	};

	static dispatch(input, eventName, detail) {

		detail.input = input;

		const event = new CustomEvent(eventName, {detail: detail});

		if(input.dataset.autocompleteDispatch) {
			qs(input.dataset.autocompleteDispatch, node => node.dispatchEvent(event));
		} else {
			input.dispatchEvent(event);
		}

	}

	static move(input) {

		if(input.classList.contains('autocomplete-open')) {
			return;
		}

		input.dataset.scroll = window.scrollY;
		input.classList.add('autocomplete-open');

		const dropdown = this.getDropdown(input);

		if(this.isFullscreen()) {

			input.insertAdjacentHTML('beforebegin', '<div id="'+ input.id +'-placeholder" class="autocomplete-placeholder"></div>');

			document.body.insertAdjacentHTML('beforeend', '<div id="'+ input.id +'-wrapper" class="autocomplete-wrapper" style="z-index: '+ Lime.getZIndex() +'"></div>');

			const wrapper = qs('#'+ input.id +'-wrapper');
			wrapper.insertAdjacentElement('beforeend', input);
			wrapper.insertAdjacentElement('beforeend', dropdown);

			Lime.History.pushLayer(dropdown, () => this.internalRemove(input), document.location.href, null);

			document.body.classList.add('autocomplete-fullscreen-open');

		} else {
			input.style.zIndex = Lime.getZIndex();
			dropdown.style.zIndex = Lime.getZIndex();
		}

	};

	static source(input, values) {

		const dropdown = this.getDropdown(input);

		let html = '<ul class="autocomplete-list">';

		if(values.length === 0) {

			const labelEmpty = input.dataset.autocompleteEmpty.replace('{value}', input.value);

			if(input.dataset.autocompleteTextual) {
				html += '<li class="autocomplete-list-empty">';
					html += '<a>'+ labelEmpty + '</a>';
				html += '</li>';
			} else {
				html += '<li class="autocomplete-not-selectable autocomplete-list-empty">' + labelEmpty + '</li>';
			}

		} else {

			values.forEach((value, key) => {

				const type = value.type || 'value';

				if(type === 'title') {
					html += '<li class="autocomplete-not-selectable">';
						html += value['itemHtml'];
					html += '</li>';
				} else if(type === 'link') {
					html += '<li class="autocomplete-link">';
						html += '<a href="'+ value['link'] +'" '+ (value['target'] !== undefined ? 'target="'+ value['target'] +'"' : '') +'>'+ value['itemHtml'] +'</a>';
					html += '</li>';
				} else {
					html += '<li data-n="'+ key +'" data-dropdown-keep>';
						html += value['itemHtml'];
					html += '</li>';
				}

			});

		}

		html += '</ul>';

		dropdown.innerHTML = html;

		dropdown.qs('ul.autocomplete-list', list => {

			const listBounds = list.getBoundingClientRect();
			const inputBounds = input.getBoundingClientRect();

			list.scroll(0, 0);
			list.style.width = inputBounds.width +'px';

			const translateX = inputBounds.left - listBounds.left;
			const translateY = inputBounds.bottom + 1 - listBounds.top;

			list.style.transform = 'translate('+ translateX +'px, '+ translateY +'px)';

		});

		dropdown.qsa('li', node => node.addEventListener('click', e => AutocompleteField.onClick(node, input, values, node)));
		dropdown.qsa('li', node => node.addEventListener('autocompleteEnter', e => AutocompleteField.onClick(node, input, values, node)));

	};

	static onClick(node, input, values, selected) {

		let value;

		if(
			input.dataset.autocompleteTextual &&
			node.classList.contains('autocomplete-list-empty')
		) {

			value = {
				value: input.value,
				itemText: input.value,
				itemHtml: input.value
			};

		} else {

			if(selected.dataset.n === undefined) {
				return;
			}

			const position = parseInt(selected.dataset.n);
			value = values[position];

		}

		// On applique et appelle les callbacks
		this.apply(input, value);

	};

	static empty(target) {

		const input = target.previousSibling;

		// On applique et on appelle les callbacks
		if(input.value) {

			this.apply(input, {
				value: '',
				itemText: ''
			});

		}

	};

	static apply(input, value) {

		if(input.dataset.autocompleteItems) {

				let itemSelector = '#'+ input.dataset.autocompleteItems;

				const field = input.getAttribute('data-autocomplete-field');
				const multiple = (field.indexOf('[]') !== -1);

				if(multiple === false) {

					if(input.dataset.autocompleteTextual) {

						input.value = value.itemText;

					} else {

						const newInput = document.createElement('input');
						newInput.setAttribute('type', 'hidden');
						newInput.setAttribute('name', field);
						newInput.setAttribute('value', value.value);

						qs(itemSelector, item => {
							if(item.length > 0) {
								item.firstChild.remove();
							}
							item.appendChild(newInput);
						});

						input.value = value.itemText;

					}

					this.onSelect(input, value);

				} else {

					if(qsa(itemSelector +' > [data-value="'+ value.value +'"]').length === 0) {

						let item = '<div class="autocomplete-item" data-value="'+ value.value +'">';
							item += '<input type="hidden" name="'+ field +'" value="'+ value.value +'"/>';
							item += value.itemHtml;
							item += '<div class="autocomplete-item-actions">';
								item += '<a onclick="AutocompleteField.upItem(this)" class="btn btn-sm btn-outline-primary autocomplete-reorder-up">'+ Lime.Asset.icon('arrow-up') +'</a>';
								item += '<a onclick="AutocompleteField.downItem(this)" class="btn btn-sm btn-outline-primary autocomplete-reorder-down">'+ Lime.Asset.icon('arrow-down') +'</a>';
								item += '<a onclick="AutocompleteField.removeItem(this)" class="btn btn-sm btn-outline-primary">'+ Lime.Asset.icon('trash-fill') +'</a>';
							item += '</div>';
						item += '</div>';

						qs(itemSelector).insertAdjacentHTML('beforeend', item);

					}

					input.value = '';
					this.onUpdate(input);

				}

		} else {

			input.value = value.value;
			this.onSelect(input, value);

		}

		// On remet l'élément à sa place
		this.remove(input);

	};

	static hover(input, direction, e) {

		const list = this.getDropdown(input).qsa('li');
		const length = list.length;

		if(length === 0) {
			return;
		}

		e.preventDefault();

		let position = 0;
		let currentPosition = null;

		list.forEach(entry => {

			if(entry.classList.contains('selected')) {

				entry.classList.remove('selected');

				currentPosition = position;

			}

			position++;

		});

		let newPosition;

		if(direction === 'up') {

			if(currentPosition === 0) {
				newPosition = length - 1;
			} else {
				newPosition = currentPosition - 1;
			}

		} else {

			if(currentPosition === null || currentPosition === length - 1) {
				newPosition = 0;
			} else {
				newPosition = currentPosition + 1;
			}

		}

		this.getDropdown(input).qs('li[data-n="' + newPosition + '"]').classList.add('selected');


	};

	static removeItem(item) {

		const wrapper = item.firstParent('.autocomplete-items');
		item.firstParent('.autocomplete-item').remove();

		const input = qs('[data-autocomplete-items="'+ wrapper.id +'"]');

		if(input !== null) {
			this.onUpdate(input);
		}

	}

	static upItem(target) {

		const item = target.firstParent('.autocomplete-item');
		item.parentElement.insertBefore(item, item.previousSibling);

	}

	static downItem(target) {

		const item = target.firstParent('.autocomplete-item');
		item.parentElement.insertBefore(item.nextSibling, item);

	}

	static remove(input) {

		const dropdown = this.getDropdown(input);

		if(dropdown !== null) {

			if(this.isFullscreen()) {
				Lime.History.popLayer(dropdown);
			} else {
				this.internalRemove(input);
			}

		}

	};

	static internalRemove(input) {

		const dropdown = this.getDropdown(input);

		if(dropdown === null) {
			return;
		}

		input.classList.remove('autocomplete-open');

		if(this.isFullscreen()) {

			qs('#'+ input.id +'-placeholder', placeholder => {

				placeholder.insertAdjacentElement('afterend', dropdown);
				placeholder.insertAdjacentElement('afterend', input);
				placeholder.remove();

			}/*, () => {
				input.remove();
			}*/);

			qs('#'+ input.id +'-wrapper', wrapper => wrapper.remove());

			document.body.classList.remove('autocomplete-fullscreen-open');

			setTimeout(() => {
				window.scrollTo(0, input.dataset.scroll);
				input.dataset.scroll = null;
			}, 0);

		}

		if(this.isFullscreen() === false) {
			document.removeEventListener('click', this.clickListener[input.id], {once: true})
			delete this.clickListener[input.id];
		}

		dropdown.remove();

		return true;

	}

	static isFullscreen() {
		return document.body.matches('[data-touch="yes"]');
	};

};

class PasswordField {

	static toggleVisibility(target) {

		const input = target.previousSibling;

		if(target.dataset.visible === '0') {

			input.type = 'text';
			target.dataset.visible = '1';

		} else {

			input.type = 'password';
			target.dataset.visible = '0';

		}

	}

}