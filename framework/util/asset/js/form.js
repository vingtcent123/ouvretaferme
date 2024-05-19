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

	switch(method) {

		case 'GET' :

			url += url.includes('?') ? '&' : '?';
			url += new URLSearchParams(body).toString();

			new object(form)
				.method(method)
				.url(url)
				.fetch();

			break;

		case 'POST' :

			new object(form)
				.method(method)
				.url(url)
				.skipHistory()
				.body(body)
				.fetch();

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

/**
 * Alternative for multi-select field
 */

document.delegateEventListener('click', 'a[data-action="form-selects-delete"]', function() {

	const item = this.parentElement;
	const root = item.parentElement;

	if(root.childNodes.filter('.form-selects-item').length > 1) {
		item.remove();
	} else {
		item.childNodes.filter('select').forEach(node => {
			node.value = ''
		});
	}

});


document.delegateEventListener('click', 'a[data-action="form-selects-add"]', function() {

	const root = this.parentElement.parentElement;

	let newItem = root.childNodes.filter('.form-selects-item')[0].cloneNode(true);
	newItem.qsa('option', node => node.removeAttribute('selected'));

	root.insertAdjacentElement('beforeend', newItem);

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

			rangeField.value = newValue;
			rangeField.dispatchEvent(new Event("input"));

		});

	}

	static updateLabel(target) {

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

		input.checked = active;

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

		this.updateWeeks(qs('[data-fallback="weekNumber"]'));

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

		Lime.Dropdown.open(wrapper.qs('[data-fallback="weekNumber"]'), "bottom-start");


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

	static all(target, selector, callback, parent = 'form') {

		const form = target.firstParent(parent);
		form.qsa(selector, field => {

			if(field.disabled === true) {
				return;
			}

			field.checked = target.checked;

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

		const wrapper = field.firstParent('.field-color')

		const hiddenField = wrapper.qs('input[type="hidden"]');
		wrapper.qs('input[type="checkbox"]').checked = false;
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
			input.insertAdjacentHTML('afterend', '<div id="'+ dropdownId +'" class="autocomplete-dropdown"></div>');
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
			this.queryTimeout = null;
			clearTimeout(this.queryTimeout);
		}

		this.queryTimeout = setTimeout(() => {
			this.queryTimeout = null;
			this.query(input);
		}, 250);

	};

	static query(input) {

		const url = input.dataset.autocompleteUrl;
		const body = new URLSearchParams();

		if(input.dataset.autocompleteBody) {
			Object.entries(JSON.parse(input.dataset.autocompleteBody)).forEach(([key, value]) => {
				if(typeof value === 'object') {
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

			Lime.History.pushLayer(dropdown, () => this.internalRemove(input), true, null);

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
			html += '<li class="not-selectable autocomplete-list-empty">'+ input.dataset.autocompleteEmpty +'</li>';
		} else {

			values.forEach((value, key) => {

				if(value['separator'] !== undefined) {
					html += '<li class="not-selectable">';
						html += value['separator'];
					html += '</li>';
				} else if(value['value'] === null) {
					html += '<li class="not-selectable">';
						html += value['itemHtml'];
					html += '</li>';
				} else {
					html += '<li data-n="'+ key +'">';
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

		dropdown.qsa('li', node => node.addEventListener('click', e => AutocompleteField.onClick(input, values, node)));
		dropdown.qsa('li', node => node.addEventListener('autocompleteEnter', e => AutocompleteField.onClick(input, values, node)));

	};

	static onClick(input, values, selected) {

		if(selected.dataset.n === undefined) {
			return;
		}

		const position = parseInt(selected.dataset.n);
		const value = values[position];

		// On applique et appelle les callbacks
		this.apply(input, value);

	};

	static empty(target) {

		const input = target.previousSibling;

		// On applique et appelle les callbacks
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
					this.onSelect(input, value);

				} else {

					if(qsa(itemSelector +' > [data-value="'+ value.value +'"]').length === 0) {

						let item = '<div class="autocomplete-item" data-value="'+ value.value +'">';
							item += value.itemHtml +'&nbsp;<a onclick="AutocompleteField.removeItem(this)" class="btn btn-muted">'+ Lime.Asset.icon('trash-fill') +'</a>';
							item += '<input type="hidden" name="'+ field +'" value="'+ value.value +'"/>';
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
		item.firstParent('.autocomplete-item').remove();
	}

	static remove(input) {

		const dropdown = this.getDropdown(input);

		if(dropdown !== null) {

			if(this.isFullscreen()) {
				Lime.History.removeLayer(dropdown);
			} else {
				this.internalRemove(input);
			}

		}

	};

	static internalRemove(input) {

		const dropdown = this.getDropdown(input);

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