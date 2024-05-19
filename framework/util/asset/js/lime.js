function evalScope(node, script) {

	(function() {
		eval(script);
	}).call(node);

}

function d(...arguments) {
	console.log(...arguments);
}

if('scrollRestoration' in history) { // what an amazing feature!
	history.scrollRestoration = 'manual';
}

document.addEventListener("keydown", e => {

    if(e.key === 'Backspace' && e.target.matches('input, textarea, [contenteditable="true"]') === false) {
        e.preventDefault();
    }

});

document.addEventListener('DOMContentLoaded', () => {

	/**
	 * Save touch state
	 */
	document.body.dataset.touch = isTouch() ? 'yes' : 'no';

	Lime.History.init();

	document.body.parseRender();

});

function isTouch() {
	return ("ontouchstart" in window);
}

/**
 * Load browser information
 */
let browser = {

	get: function() {

		return browser.isOpera ? 'Opera' :
			browser.isFirefox ? 'Firefox' :
				browser.isSafari ? 'Safari' :
					browser.isChrome ? 'Chrome' :
						browser.isIE ? 'IE' :
							browser.isEdge ? 'Edge' :
								browser.isBlink ? 'Blink' :
									'';
	}

};

// Opera 8.0+
browser.isOpera = (!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;

// Firefox 1.0+
browser.isFirefox = typeof InstallTrigger !== 'undefined';

// Safari 3.0+ "[object HTMLElementConstructor]"
browser.isSafari = /constructor/i.test(window.HTMLElement) || (function (p) { return p.toString() === "[object SafariRemoteNotification]"; })(!window['safari'] || (typeof safari !== 'undefined' && window['safari'].pushNotification));

// Internet Explorer 6-11
browser.isIE = /*@cc_on!@*/false || !!document.documentMode;

// Edge 20+
browser.isEdge = !browser.isIE && !!window.StyleMedia;

// Chrome 1 - 79
browser.isChrome = !!window.chrome && (!!window.chrome.webstore || !!window.chrome.runtime);

// Blink engine detection
browser.isBlink = (browser.isChrome || browser.isOpera) && !!window.CS;

/**
 * Convert current rem unit in pixels
 */
function rem() {
	return parseFloat(getComputedStyle(document.documentElement).fontSize);
}

function money(value, precision = 2) {

	const multiplier = Math.pow(10, precision);

	value *= multiplier;
	value = Math.round(value);

	let output = Math.floor(value / multiplier);

	value = value.toString();

	if(precision > 0) {
		output += ',';
		output += value.substring(value.length - precision, value.length);
	}

	return output +' €';

}

function doCopy(target) {

    const fromElement = document.querySelector(target.dataset.selector);
    if(!fromElement) return;

    const range = document.createRange();
    const selection = window.getSelection();
    range.selectNode(fromElement);
    selection.removeAllRanges();
    selection.addRange(range);

	if(document.execCommand('copy')) {
		alert(target.dataset.message);
	}

    window.getSelection().removeAllRanges();
}

function parseBool(value) {

	switch(typeof value) {

		case 'boolean' :
			return value;

		case 'number' :
			return (value > 0 || value < 0);

		case 'string' :
			return (value !== '' && value !== '0');

		default :
			return true;

	}
}

/**
 * Shortcut for document.qs()
 *
 * @return Element
 */
function qs(selector, found, empty) {
	return document.qs(selector, found, empty);
}

/**
 * Shortcut for querySelector()
 *
 * @return Element
 */
Node.prototype.qs = function(selector, found, empty) {

	const node = this.querySelector(selector);

	if(node !== null) {
		if(found) {
			found.call(this, node);
		}
	} else {
		if(empty) {
			empty.call(this);
		}
	}

	return node;

}

/**
 * Shortcut for document.qsa()
 *
 * @return Element
 */
function qsa(selector, each, empty) {
	return document.qsa(selector, each, empty);
}

/**
 * Shortcut for querySelectorAll()
 *
 * @return NodeList
 */
Node.prototype.qsa = function(selector, each, empty) {

	let nodes = this.querySelectorAll(selector);

	if(nodes.length > 0) {
		if(each) {
			nodes.forEach(each);
		}
	} else {
		if(empty) {
			empty.call(this);
		}
	}

	return nodes;

}

/**
 * Shortcut for document.ref()
 *
 * @return Element
 */
function ref(ref, each, empty, cssSelector) {
	return document.ref(ref, each, empty, cssSelector);
}

/**
 * Lookup for a ref
 *
 * @return NodeList
 */
Node.prototype.ref = function(ref, each, empty, cssSelector) {

	if(cssSelector === undefined) {
		cssSelector = '~=';
	}

	if(['~=', '=', '^=', '$=', '|=', '*='].includes(cssSelector) === false) {
		throw 'Invalid selector';
	}

	return this.qsa('[data-ref'+ cssSelector +'"'+ ref +'"]', each, empty);

}

/**
 * Search for first parent node that matches the given selector
 */
Element.prototype.firstParent = function(selector) {

	let currentNode = this;

	while((currentNode = currentNode.parentNode) && currentNode !== document) {

		if(currentNode.matches(selector)) {
			return currentNode;
		}

	}

	return null;

};

/**
 * Search for siblings node that matches the given selector
 */
Element.prototype.firstPreviousSiblingMatches = function(selector) {

	let currentNode = this;

	while(currentNode = currentNode.previousElementSibling) {

		if(currentNode.matches(selector)) {
			return currentNode;
		}

	}

	return null;

};

Element.prototype.firstNextSiblingMatches = function(selector) {

	let currentNode = this;

	while(currentNode = currentNode.nextElementSibling) {

		if(currentNode.matches(selector)) {
			return currentNode;
		}

	}

	return null;

};

Element.prototype.parseScripts = function() {

	if(this.tagName === 'SCRIPT') {
		evalScope(document.body, this.innerHTML);
		return;
	}

	const scripts = this.getElementsByTagName('script');
	let currentLength = scripts.length;

	for(let i = 0; i < currentLength; i++) {

		evalScope(document.body, scripts[i].innerHTML);

		const newLength = scripts.length;

		if(newLength !== currentLength) {
			i = i + newLength - currentLength;
			currentLength = newLength;
		}

	}

};

Element.prototype.parseRender = function() {

	if(this.hasAttribute('onrender')) {
		evalScope(this, this.getAttribute('onrender'));
	}

	this.qsa('[onrender]', function(node) {
		evalScope(node, node.getAttribute('onrender'));
	});

};

Element.prototype.hide = function() {
	this.classList.add('hide');
}

Element.prototype.removeHide = function() {
	this.classList.remove('hide');
}

Element.prototype.toggle = function() {
	if(this.classList.contains('hide')) {
		this.removeHide();
		return 'removeHide';
	} else {
		this.hide();
		return 'hide';
	}
}

Element.prototype.toggleSwitch = function() {
	this.classList.toggle('field-switch-off');
	this.classList.toggle('field-switch-on');
}

Element.prototype.slideUp = function(options = {}) {

	const duration = options.duration || 0.5;
	const done = options.done || function() {};

	this.style.overflowY = 'hidden';
	this.style.maxHeight = this.offsetHeight +'px';
	this.style.transition = 'all '+ duration +'s';

	setTimeout(() => this.style.maxHeight = '0px', 10);
	setTimeout(() => done.call(this), 10 + duration * 1000);

};

Element.prototype.fadeOut = function(options = {}) {

	const duration = options.duration || 0.5;
	const done = options.done || function() {};

	this.style.pointerEvents = 'none';
	this.style.transition = 'all '+ duration +'s';

	setTimeout(() => this.style.opacity = 0, 10);
	setTimeout(() => done.call(this), 10 + duration * 1000);

};

Element.prototype.fadeIn = function(options = {}) {

	const duration = options.duration || 0.5;
	const done = options.done || function() {};

	this.style.pointerEvents = 'initial';
	this.style.transition = 'all '+ duration +'s';

	setTimeout(() => this.style.opacity = 1, 10);
	setTimeout(() => done.call(this), 10 + duration * 1000);

};

Element.prototype.isVisible = function() {
	return (this.offsetWidth > 0 || this.offsetHeight > 0);
};

Element.prototype.isHidden = function() {
	return !this.isVisible();
};

Element.prototype.renderInner = function(html) {

	this.innerHTML = html;

	this.childNodes.forEach(node => {
		if(node instanceof Element) {
			node.parseScripts();
			node.parseRender();
		}
	});

}

Element.prototype.renderOuter = function(html) {

	const document = new DOMParser().parseFromString(html, 'text/html');
	const body = document.body;

	if(
		document.head.childNodes.length > 0 ||
		body.childNodes.length > 1
	) {
		throw "HTML must be formed of only a root node";
	}

	const node = body.firstChild;

	if(node === null) {
		this.remove();
	} else {
		this.parentNode.replaceChild(node, this);

		if(node instanceof Element) {
			node.parseScripts();
			node.parseRender();
		}
	}

}

Element.prototype.renderAdjacentHTML = function(where, html) {

	if(html === '') {
		return;
	}

	const document = new DOMParser().parseFromString(html, 'text/html');
	const body = document.body;

	if(
		document.head.childNodes.length > 0 ||
		body.childNodes.length > 1
	) {
		throw "HTML must be formed of only a root node";
	}

	const node = body.firstChild;

	if(node instanceof Text) {
		this.insertAdjacentText(where, node.data);
	} else {
		this.insertAdjacentElement(where, node);
		node.parseScripts();
		node.parseRender();
	}

}

/**
 * Get all post-* attributes as an object key=>value
 *
 * @returns Object
 */
Element.prototype.post = function(formData) {

	if(formData === undefined) {
		formData = new URLSearchParams();
	}

	for(let i = 0; i < this.attributes.length; i++) {

		let attribute = this.attributes.item(i);

		if(attribute.name.indexOf('post-') === 0) {

			let key = attribute.name.substring(5).replace(/\-([a-z])/gi, function(match, letter) {
				return letter.toUpperCase();
			});

			formData.set(key, attribute.value);
		}

	};

	return formData;

};

Element.prototype.form = function() {

	let data = new FormData(this);

	this.qsa('.editor', node => {

		// Can't save if loaded
		if(node.querySelectorAll('[data-type="progress"]').length > 0) {
			throw node.getAttribute('data-progress');
		}

		data.set(
			node.getAttribute('data-name'),
			node.innerHTML
		);

		Editor.resetInstance('#'+ node.id);

	});

	return data;

};

Array.prototype.unique = function() {
	return [...new Set(this)];
}

NodeList.prototype.filter = function(selector) {

	let newList = [];

	this.forEach(node => {
		if(node.matches(selector)) {
			newList.push(node);
		}
	});

	return newList;

};

/**
 * Create a delegated event listener
 */
Node.prototype.delegateEventListener = function(type, selector, listener, options) {

	options = options || {capture: true};

	const delegateTarget = (e) => {

		const target = e.target;

		if(target !== document && target.matches(selector)) {
			e.delegateTarget = target;
			listener.call(target, e);
		}

	}

	const delegateTargetAndParents = (e) => {

		for(let target = e.target; target && target != this; target = target.parentNode) {

			if(target.matches(selector)) {
				e.delegateTarget = target;
				listener.call(target, e);
				break;
			}

		}

	}

	if(type === 'mouseenter' || type === 'mouseleave' || type === 'mouseover' || type === 'mouseout') {
		return this.addEventListener(type, delegateTarget, options);
	} else {
		return this.addEventListener(type, delegateTargetAndParents, options);
	}

};

/**
 * Event dispatched on a new page
 */
Document.prototype.ready = function(listener) {
	if(document.readyState === 'interactive' || document.readyState === 'complete') {
		listener();
	} else {
		this.addEventListener("DOMContentLoaded", listener, {once: true});
	}
};

document.delegateEventListener('click', '[data-confirm]', function(e) {

	if(confirm(this.getAttribute('data-confirm')) === false) {

		e.preventDefault();
		e.stopImmediatePropagation();

	}


});

document.delegateEventListener('click', '[data-alert]', function(e) {

	alert(this.getAttribute('data-alert'));


});

URLSearchParams.prototype.importObject = function(start) {

	const browse = (object, prefix) => {

		Object.entries(object).forEach(([key, value]) => {
			const newKey = prefix + (prefix === '' ? key : '['+ key +']');
			if(value instanceof Object) {
				browse(value, newKey)
			} else {
				this.append(newKey, value);
			}
		});

	}
	browse(start, '');

}

/*
 * Object handling
 * Object.prototype.... causes too much problems
 */
function objectMerge(...arguments) {

	// create a new object
	let target = {};

	// deep merge the object into the target object
	const merger = (obj) => {
		for (let prop in obj) {
			if (obj.hasOwnProperty(prop)) {
				if (Object.prototype.toString.call(obj[prop]) === '[object Object]') {
					// if the property is a nested object
					target[prop] = objectMerge(target[prop], obj[prop]);
				} else {
					// for regular property
					target[prop] = obj[prop];
				}
			}
		}
	};

	// iterate through all objects and
	// deep merge them with target
	for (let i = 0; i < arguments.length; i++) {
		merger(arguments[i]);
	}

	return target;
};

/*
 * String handling
 */

String.prototype.encode = function() {

	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};

	return this.replace(/[&<>"]/g, function(m) {
		return map[m];
	});

}

String.prototype.attr = function(value) {
	let encodedValue = value;
	if(typeof value === 'string') {
		encodedValue = value.encode();
	}
	return this +'="'+ encodedValue +'"';
}

String.prototype.toFqn = function() {

	return this
		.normalize("NFD")
		.replace(/\p{Diacritic}/gu, '')
		.replace(/\s+/g, '-')
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.replace(/^\-+|\-+$/g, '');

}

String.prototype.removeArgument = function(name) {

	let location = this;

	const regex = new RegExp('([\&\?])'+ name.replace('[', '\\[').replace(']', '\\]') +'(=[a-z0-9/\.\%\:\\-\\\\+]*)*[&]?', 'gi');
	location = location.replace(regex, '$1');
	location = location.replace('?&', '?');
	location = location.replace('&&', '&');

	if(
		location.charAt(location.length - 1) === '?' ||
		location.charAt(location.length - 1) === '&'
	) {
		location = location.substring(0, location.length - 1);
	}

	return location;

};

String.prototype.setArgument = function(name, value) {

	let location = this;

	const regex = new RegExp('([\&\?])'+ name +'=([^\&]*)', 'i');

	if(location.match(regex)) {

		location = location.replace(regex, '$1'+ name +'='+ encodeURIComponent(value));

	} else {

		location = location + (location .indexOf('?') === -1 ? '?' : '&');

		if(typeof value !== 'undefined') {
			location = location + name +'='+ encodeURIComponent(value);
		} else {
			location = location + name;
		}

	}

	return location;

};

/*
 * History handling
 */
history.removeArgument = function(name, state) {

	const location = document.location.href.removeArgument(name);

	if(state === false) {
		Lime.History.pushState(location);
	} else {
		Lime.History.replaceState(location);
	}

}

history.updateArgument = function(name, value, state) {

	const location = document.location.href.setArgument(name, value);

	if(state === 'push') {
		Lime.History.pushState(location);
	} else {
		Lime.History.replaceState(location);
	}

}

let isMouseDown = false;

document.addEventListener('mousedown', function() {
	isMouseDown = true;
})

document.addEventListener('mouseup', function() {
	isMouseDown = false;
});

let isTouchDown = false;

document.addEventListener('touchstart', function() {
	isTouchDown = true;
})

document.addEventListener('touchend', function() {
	isTouchDown = false;
});

const Lime = {

	counter: 1,
	zIndex: 1100,

	getZIndex: function() {
		Lime.zIndex += 10;
		return Lime.zIndex;
	}

};

Lime.Pagination = class {

	static form(url, target) {

		new Ajax.Navigation()
			.url(url.replace('{page}', parseInt(target.qs('[name="page"]').value) - 1))
			.fetch();

	}

}

Lime.Alert = class {

	static showStaticErrors(errors) {

		let message = Lime.Alert.getInlineErrors(errors);

		if(message !== '') {
			Lime.Alert.showStaticError(message);
		}

	};

	static getInlineErrors(errors) {

		let messages = [];

		errors.forEach(([, message]) => {

			if(message !== null) {
				messages[messages.length] = message;
			}

		});

		switch(messages.length) {

			case 0 :
				return '';

			case 1 :
				return messages[0];

			case 2 :

				let h = '<ul>';

				messages.forEach(message => {
					h += '<li>'+ message +'</li>';
				});

				h += '</ul>';

				return h;

		}

	};

	static showStaticError(message) {

		if(message === '') {
			return false;
		}

		if(document.getElementById('alert-danger') === null) {
			document.body.insertAdjacentHTML('beforeend', '<div id="alert-danger" class="util-box-danger util-box-sticked"></div>');
		}

		document.getElementById('alert-danger').innerHTML = message + '<a onclick="Lime.Alert.hideStaticErrors()" class="util-box-close">'+ Lime.Asset.icon('x-circle') +'</a>';
		document.getElementById('alert-danger').style.display = 'block';

		setTimeout(() => history.removeArgument('error'), 2500);
		setTimeout(() => this.hideStaticErrors(), 5000);

	};

	static hideStaticErrors() {
		this.hideStaticBox('#alert-danger');
	};

	static showStaticSuccess(message) {

		if(message === '') {
			return false;
		}

		if(document.getElementById('alert-success') === null) {
			document.body.insertAdjacentHTML('beforeend', '<div id="alert-success" class="util-box-success util-box-sticked"></div>');
		}

		document.getElementById('alert-success').innerHTML = message + '<a onclick="Lime.Alert.hideStaticSuccess()" class="util-box-close">'+ Lime.Asset.icon('x-circle') +'</a>';
		document.getElementById('alert-success').style.display = 'block';

		setTimeout(() => history.removeArgument('success'), 2500);
		setTimeout(() => this.hideStaticSuccess(), 5000);

	};

	static hideStaticSuccess() {
		this.hideStaticBox('#alert-success');
	};

	static hideStaticBox(id) {

		qs(id, node => {

			node.classList.add('util-box-remove');
			setTimeout(() => node.remove(), 500);

		});

	};

	static hideErrors(selector) {

		// Hide previous errors
		selector.qsa('.util-box-danger', node => node.remove());

		selector.qsa('.form-error', node => node.classList.remove('form-error'));
		selector.qsa('.form-error-wrapper', node => node.classList.remove('form-error-wrapper'));
		selector.qsa('p.form-error-message', node => node.remove());

	};

	static showErrors(context, errors) {

		if(context !== null) {
			Lime.Alert.hideErrors(context);
		}

		if(
			typeof errors === 'undefined' ||
			errors.length === 0
		) {
			return;
		}

		let remainingErrors = [];

		errors.forEach(error => {

			let message, errorName, wrapper;

			[errorName, message, wrapper] = error;

			// No custom message
			if(errorName === message) {
				message = null;
			}

			// No wrapper specified
			if(wrapper === null) {

				// If the error is linked to a field, we take the first part of error name
				if(errorName.indexOf('.') !== -1) {
					wrapper = errorName.substring(0, errorName.indexOf('.'));
				}
				// This is a generic error (not linked to a field)
				else {
					wrapper = null;
				}

			}

			if(wrapper === null) {

				remainingErrors.push(error);

			} else {


				if(context !== null) {

					context.qsa('[data-wrapper~="'+ wrapper +'"]', node => {

						node.classList.add('form-error-wrapper');

						if(message !== null) {
							node.qs('.form-control-label:first-child', nodeLabel => nodeLabel.insertAdjacentHTML('beforeend', '<p class="form-error-message">'+ message +'</p>'), () => node.insertAdjacentHTML('beforeend', '<p class="form-error-message">'+ message +'</p>'));
						}


					}, () => {
						remainingErrors.push(error);
					});

				} else {
					remainingErrors.push(error);
				}

			}

		});

		if(remainingErrors.length > 0) {
			Lime.Alert.showStaticErrors(remainingErrors);
		}

		if(context !== null) {
			context.classList.add('form-error');
		}

	};

}

Lime.Panel = class {

	/*
	 * Create a panel on-the-fly
	 */
	static create(id, options) {

		if(document.getElementById(id) === null) {

			const attributes = options?.attributes || {};
			attributes.class = 'panel' + (attributes.class ? ' '+ attributes.class : '');

			let h = '';

			h += '<div class="panel" id="'+ id +'">';
				h += '<div class="panel-backdrop"></div>';
				h += options.dialogOpen ?? '<div class="panel-dialog container">';
					h += '<div class="panel-header"></div>';
					h += '<div class="panel-body"></div>';
					h += '<div class="panel-footer"></div>';
				h += options.dialogClose ?? '</div>';
			h += '</div>';

			document.querySelector('body').insertAdjacentHTML('beforeend', h);

			const node = document.getElementById(id);

			Object.entries(attributes).forEach(([name, value]) => node.setAttribute(name, value));

		}

		return document.getElementById(id);

	};

	static render(data) {

		if(data.id === undefined) {
			throw 'data.id is missing';
		}

		const panelId = data.id;
		let panel;

		if(document.getElementById(panelId) === null) {

			panel = this.create(panelId, data);

			panel.dataset.new = 'true';

		} else {

			panel = document.getElementById(panelId);
			panel.classList.remove('closing');
			panel.classList.remove('closing-last');
			panel.dataset.new = 'false';

		}

		// Affiche le panel en premier afin que les éléments affichés ultérieurement aient immédiatement une taille
		Lime.Panel.show(panel);
		Lime.Panel.paint(panel, data);

		return panel;

	};

	static paint(panel, data) {

		const title = data.title || '';
		const back = data.back || null;
		const subTitle = data.subTitle || '';
		const body = data.body || '';
		const header = data.header || '';
		const footer = data.footer || '';

		Lime.Panel.header(panel, back, title, subTitle, header);
		Lime.Panel.body(panel, body);
		Lime.Panel.footer(panel, footer);

	};

	static layer(panel, requestedWith) {

		switch(requestedWith) {

			case 'html' :
				panel.dataset.context = 'new';
				Lime.History.forceRefreshOnBack = true;
				break;

			case 'navigation' :

				panel.dataset.context = 'layer';
				Lime.History.pushLayer(panel, (history) => this.internalClose(panel, history), false /* No new entry in the history */, document.location.href);

				break;

			case 'query' :
				break;

		}

	};

	static show(panel) {

		panel.dispatchEvent(new CustomEvent('panelBeforeShow'));

		if(document.body.classList.contains('panel-open') === false) {
			document.body.classList.add('panel-open');
		}

		panel.classList.add('open');

		panel.style.zIndex = Lime.getZIndex();

		panel.dispatchEvent(new CustomEvent('panelAfterShow'));

	};

	static purge() {

		qsa('.panel', panel => panel.remove());
		document.body.classList.remove('panel-open');

	}

	static close(panel) {

		switch(panel.dataset.context) {

			case 'new' :
				Lime.History.forceRefreshOnBack = true;
				history.go(-1);
				break;

			case 'layer' :
				Lime.History.removeLayer(panel);
				break;

			default :
				this.internalClose(panel);
				break;


		}

	};

	static closeLast() {

		const nodes = qsa('.panel.open:not(.closing)');

		if(nodes.length > 0) {

			const node = nodes[nodes.length - 1];
			this.close(node);

			return true;

		} else {
			return false;
		}

	};

	static closeEscape(e) {

		if(e.key === 'Escape') {
			this.closeLast();
		}

	};

	static internalClose(panel, history = false) {

		if(panel.classList.contains('closing')) {
			return false;
		}

		// Reload panel
		if(
			panel.dataset.close === 'reload' ||
			panel.dataset.close === 'reloadIgnoreCascade' ||
			(history && panel.dataset.close === 'reloadOnHistory')
		) {

			const query = new Ajax.Query(panel);

			// Restauration du scroll
			Lime.History.saveScroll(Lime.History.currentTimestamp);
			query.headers.set('x-requested-history', Lime.History.currentTimestamp);

			query
				.method('get')
				.waiter(panel.style.zIndex - 1, 333)
				.url(location.href)
				.fetch();

			if(panel.dataset.close !== 'reloadIgnoreCascade') {
				qsa('.panel[data-close="reloadOnCascade"]', panel => panel.dataset.close = 'reload');
			}

		}

		panel.classList.add('closing');

		// No more panel open
		if(qsa('.panel.open:not(.closing)').length === 0) {
			panel.classList.add('closing-last');
			document.body.classList.remove('panel-open');
		}

		setTimeout(() => {

			if(panel.classList.contains('closing')) { // May have been reopen
				panel.remove();
			}

		}, 500);

		return true;
			
	};

	static header(panel, back, title, subTitle, header) {

		let h = '';
		h += '<div class="panel-header-content">';
			if(header) {
				h += header;
			} else {
				if(back || title || subTitle) {
					if(back) {
						h += '<div class="panel-back-wrapper">';
							h += '<a href="'+ back +'" class="panel-back">'+ Lime.Asset.icon('arrow-left-short') +'</a>';
							h += '<h2 class="panel-title">'+ title +'</h2>';
						h += '</div>';
					} else {
						h += '<h2 class="panel-title">'+ title +'</h2>';
					}
					if(subTitle) {
						h += subTitle;
					}
				}
			}
		h += '</div>';
		h += this.removeButton();

		panel.qs('.panel-header').renderInner(h);
		return this;

	};

	static removeButton() {

		let h = '<a class="panel-close-desktop" onclick="Lime.Panel.closeLast()">'+ Lime.Asset.icon('x') +'</a>';
		h += '<a class="panel-close-mobile" onclick="Lime.Panel.closeLast()">'+ Lime.Asset.icon('arrow-left-short') +'</a>';

		return h;

	};

	static body(panel, body) {
		panel.qs('.panel-body').renderInner(body);
		return this;
	};

	static footer(panel, footer) {
		panel.qs('.panel-footer').renderInner(footer);
		return this;
	};

}

Lime.Tab = class {

	static select(target) {

		const tab = target.dataset.tab;
		const wrapper = target.firstParent('.tabs-h, .tabs-v');

		wrapper.qsa('[data-tab]', node => {

			if(node.firstParent('.tabs-h, .tabs-v') !== wrapper) {
				return;
			}

			if(node.getAttribute('data-tab') === tab) {
				node.classList.add('selected');
			} else {
				node.classList.remove('selected');
			}

		});

		if(wrapper.id) {
			localStorage.setItem('tabs-'+ wrapper.id +'-selected', tab);
		}

	}

	static restore(wrapper, defaultTab, forceTab) {

		let tabName;

		if(wrapper.id) {
			tabName = forceTab || localStorage.getItem('tabs-'+ wrapper.id +'-selected') || defaultTab;
		} else {
			tabName = forceTab || defaultTab;
		}

		wrapper.qs(
			'.tab-item[data-tab="'+ tabName +'"]',
			node => this.select(node),
			() => this.select(wrapper.qs('.tab-item:first-child'))
		);

	}

}

Lime.Dropdown = class {

	static mutationObserver = {};
	static clickListener = {};

	static toggle(button, position) {

		// Fermeture du dropdown actuellement ouvert
		if(position === 'close') {

			const button = qs('[data-dropdown-display]');

			if(button !== null) {
				this.close(button);
			}

		} else {

			if(button.hasAttribute('data-dropdown-display')) {
				this.close(button);
			} else {
				this.open(button, position);
			}

		}

	};

	static open(button, position) {

		if(button.id === '') {
			button.id = 'dropdown-'+ ++Lime.counter;
		}

		const list = Lime.Dropdown.getListFromButton(button);

		this.closeAll();

		list.classList.add('dropdown-list-open');

		if(list.dataset.dropdownBackground !== 'no') {

			if(
				button.classList.contains('btn-primary') ||
				button.classList.contains('btn-outline-primary') ||
				button.classList.contains('btn-color-primary')
			) {
				list.classList.add('bg-primary');
			}

			if(
				button.classList.contains('btn-secondary') ||
				button.classList.contains('btn-outline-secondary') ||
				button.classList.contains('btn-color-secondary')
			) {
				list.classList.add('bg-secondary');
			}

		}

		list.insertAdjacentHTML('beforebegin', '<div data-dropdown-id="'+ button.id +'-placeholder" class="dropdown-placeholder"></div>');
		document.body.insertAdjacentElement('beforeend', list);

		button.dispatchEvent(new CustomEvent('dropdownBeforeShow', {
			detail: {button: button, list: list}
		}));

		if(this.isFullscreen(list)) {
			this.openFullscreen(button, list);
		} else {
			this.openAround(button, position, list);
		}

		button.dispatchEvent(new CustomEvent('dropdownAfterShow', {
			detail: {button: button, list: list}
		}));

		this.startCloseListener(button);

	};

	static isFullscreen(list) {

		if(isTouch()) { // Mobile
			return true;
		} else {

			// Fullscreen if height is too high
			const listBounding = list.getBoundingClientRect();
			const listHeight = listBounding.height
			const windowHeight = window.innerHeight;

			return (
				listHeight > windowHeight - 200 ||
				list.classList.contains('dropdown-list-minimalist')
			);

		}

	};

	static openFullscreen(button, list) {

		Lime.History.pushLayer(button, () => this.internalClose(button), true, null);

		document.body.classList.add('dropdown-fullscreen-open');

		button.setAttribute('data-dropdown-display', 'fullscreen');

		const windowWidth = window.innerWidth;
		const windowHeight = window.innerHeight;

		list.style.position = 'absolute';

		// Laisse le temps pour recalculer la taille de l'élément
		setTimeout(() => {

			const listBounding = list.getBoundingClientRect();

			const positionX = (windowWidth - listBounding.width) / 2;
			const positionY = (windowHeight - listBounding.height) / 2;

			const translateX = positionX - listBounding.left;
			const translateY = positionY - listBounding.top;

			list.style.transform = 'translate('+ translateX +'px, '+ translateY +'px)';
			list.insertAdjacentHTML('afterend', '<div data-dropdown-id="'+ button.id +'-backdrop" class="dropdown-backdrop" style="z-index: '+ Lime.getZIndex() +'"></div>');
			list.style.zIndex = Lime.getZIndex();

		}, 5)

	};

	static isOpen(button) {
		return button.dataset.dropdownDisplay !== undefined;
	}

	static openAround(button, position, list) {

		button.setAttribute('data-dropdown-display', 'around');

		const isTop = position.startsWith('top');

		button.stick = new Lime.Stick(
			button,
			list,
			position,
			{x: 0, y: isTop ? -5 : 5}
		);

		list.style.zIndex = Lime.getZIndex();
		list.classList.add(isTop ? 'dropdown-list-top' : 'dropdown-list-bottom')

	};

	static startCloseListener(button) {

		// Si on clique quelque part sauf sur le dropdown
		// Eviter que l'événement soit pris dans le clic en cours
		this.clickListener[button.id] = (e) => {

			if(
				isTouch() === false &&
				button.dataset.dropdownHover === 'true'
			) {
				return;
			}

			function keep(target) {
				return target.dataset.dropdownKeep !== undefined;
			}

			let target;
			for(target = e.target; target instanceof Element && keep(target) === false; target = target.parentNode) ;

			if(
				target instanceof Element === false ||
				keep(target) === false
			) {
				this.close(button);
			} else {
				watch();
			}

		};

		const watch = () => setTimeout(() => document.addEventListener('click', this.clickListener[button.id], {once: true}), 0);

		watch();

		// Si le bouton est supprimé en dur
		this.mutationObserver[button.id] = new MutationObserver(mutations => {

			if(document.body.contains(button)) {
				return;
			}

			if(window.matchMedia('(max-width: 575px)').matches) { // Mobile

				Lime.History.getElementsOfLayers()
					.filter(button => button.hasAttribute('data-dropdown-display'))
					.forEach(button => {

						if(document.body.contains(button) === false) {
							this.close(button);
						}

					});

			} else {

				this.close(button);

			}

		});

		// pass in the target node, as well as the observer options
		this.mutationObserver[button.id].observe(document.body, {
			childList: true,
			subtree: true
		});

	};

	static closeAll() {
		qsa('[data-dropdown-display]', button => this.close(button));
	};

	static close(button) {

		switch(button.getAttribute('data-dropdown-display')) {

			case 'fullscreen' :
				Lime.History.removeLayer(button);
				break;

			case 'around' :
				this.internalClose(button);
				break;

		}

	};

	static internalClose(button) {

		if(this.mutationObserver[button.id] === undefined) { // Already closed
			return false;
		}

		if(document.body.contains(button) === false) {

			qs('[data-dropdown-id="'+ button.id +'-list"]', list => {

				list.remove();
				document.body.classList.remove('dropdown-fullscreen-open');

			})

			qs('[data-dropdown-id="'+ button.id +'-backdrop"]', backdrop => backdrop.remove())

		} else {

			const list = Lime.Dropdown.getListFromButton(button);

			switch(button.getAttribute('data-dropdown-display')) {

				case 'fullscreen' :
					this.internalCloseFullscreen(button, list);
					break;

				case 'around' :
					this.internalCloseAround(button, list);
					break;

			}

			button.removeAttribute('data-dropdown-display');

			list.classList.remove('dropdown-list-open');

		}

		this.mutationObserver[button.id].disconnect();
		delete this.mutationObserver[button.id];

		document.removeEventListener('click', this.clickListener[button.id], {once: true})
		delete this.clickListener[button.id];

		return true;

	};

	static internalCloseFullscreen(button, list) {

		list.style.position = '';
		list.style.transform = '';

		list.classList.add('fullscreen-closing');
		qs('[data-dropdown-id="'+ button.id +'-backdrop"]', node => node.classList.add('fullscreen-closing'));

		setTimeout(() => {

			list.classList.remove('fullscreen-closing');
			list.style.zIndex = '';

			this.closePlaceholder(button, list)

		}, 250);

		document.body.classList.remove('dropdown-fullscreen-open');

	};

	static internalCloseAround(button, list) {

		list.style.zIndex = '';

		this.closePlaceholder(button, list);

		button.stick.destroy();

	};

	static closePlaceholder(button, list) {

		qs('[data-dropdown-id="'+ button.id +'-placeholder"]', placeholder => {

			placeholder.insertAdjacentElement('afterend', list);
			placeholder.remove();

		}, () => {
			list.remove();
		});

		qs('[data-dropdown-id="'+ button.id +'-backdrop"]', node => node.remove());

	}

	static getListFromButton(button) {

		let list = null;

		if(button.dataset.dropdownId) {
			list = qs('[data-dropdown-id="'+ button.dataset.dropdownId +'-list"]');
		}

		if(list === null) {

			list = button.nextElementSibling;

			if(list === null || list.classList.contains('dropdown-list') === false) {
				throw "Missing dropdown target for button #"+ button.id;
			} else {
				button.setAttribute('data-dropdown-id', button.id);
				list.setAttribute('data-dropdown-id', button.id +'-list');
			}

		}

		list.button = button;

		return list;

	};

	static hoverActive = null;
	static hoverTrigger = null;
	static hoverPending = null;
	static hoverActivating = null;

	static hoverEnter(button) {

		if(isTouch()) {
			return;
		}

		if(this.hoverActive !== button && this.hoverTrigger !== null) {
			this.hoverTrigger.call(this.hoverTrigger);
		}

		clearTimeout(this.hoverPending);

		if(Lime.Dropdown.isOpen(button)) {
			return;
		}

		button.addEventListener('dropdownAfterShow', (e) => {

			const list = e.detail.list;

			if(list.dataset.dropdownHoverLoaded === undefined) {

				list.addEventListener('mouseenter', () => {

					clearTimeout(this.hoverPending);

				});

				list.addEventListener('mouseleave', () => {

					this.hoverTrigger = () => {

						if(button.dataset.dropdownDisplay) {
							Lime.Dropdown.close(button);
						}

						this.hoverActive = null;

					};

					this.hoverPending = setTimeout(this.hoverTrigger, 500);

				});

				list.dataset.dropdownHoverLoaded = 'true';

			}

		}, {once: true});

		this.hoverActivating = setTimeout(() => {

			if(Lime.Dropdown.isOpen(button)) {
				this.hoverActivating = null;
				return;
			}

			Lime.Dropdown.open(button, button.dataset.dropdown);
			this.hoverActive = button;
			this.hoverActivating = null;

		}, 200);

	};

	static hoverLeave(button) {

		if(isTouch()) {
			return;
		}

		if(this.hoverActivating !== null) {
			clearTimeout(this.hoverActivating);
			this.hoverActivating = null;
			return;
		}

		this.hoverTrigger = () => {

			if(button.dataset.dropdownDisplay) {
				Lime.Dropdown.close(button);
			}

			this.hoverActive = null;

		};

		this.hoverPending = setTimeout(this.hoverTrigger, 500);

	}

}

document.delegateEventListener('mouseenter', '[data-dropdown-hover]', function(e) {

	e.preventDefault();

	Lime.Dropdown.hoverEnter(this);

});

Lime.Stick = class {

	element;
	free;
	placement;
	offset;

	events;

	constructor(element, free, placement, offset) {

		this.element = element;
		this.free = free;
		this.placement = placement;
		this.offset = offset;

		this.free.style.position = 'absolute';

		this.events = () => {
			this.move();
		};

		window.addEventListener('resize', this.events);
		window.addEventListener('scroll', this.events);

		this.move();

	}
	
	move() {

		const elementBounds = this.element.getBoundingClientRect();
		const freeBounds = this.free.getBoundingClientRect();

		let translateX = 0;
		let translateY = 0;

		if(this.free.style.transform) {

			const transform = this.free.style.transform
				.slice(10, -1)
				.split(', ')
				.map(parseFloat);

			translateX += transform[0];
			translateY += transform[1];

		}

		if(this.placement.endsWith('-start')) {
			translateX += elementBounds.left - freeBounds.left;
		} else if(this.placement.endsWith('-center')) {
			translateX += (elementBounds.left + elementBounds.width / 2 - freeBounds.width / 2) - freeBounds.left;
		} else if(this.placement.endsWith('-end')) {
			translateX += (elementBounds.right - freeBounds.width) - freeBounds.left;
		}

		if(this.placement.startsWith('top-')) {
			translateY += (elementBounds.bottom - freeBounds.height - elementBounds.height) - freeBounds.top;
		} else if(this.placement.startsWith('bottom-')) {
			translateY += elementBounds.bottom - freeBounds.top;
		}

		translateX += this.offset.x ?? 0;
		translateY += this.offset.y ?? 0;

		this.free.style.transform = 'translate('+ translateX +'px, '+ translateY +'px)';

	}

	destroy() {

		this.free.style.transform = '';
		this.free.style.position = '';

		window.removeEventListener('resize', this.events);
		window.removeEventListener('scroll', this.events);

	}

}

Lime.Asset = class {

	static icon(name) {

		return '<svg class="asset-icon asset-icon-'+ name +'" fill="currentColor">'+
			'<use xlink:href="/asset/framework/util/lib/bootstrap-icons-1.10.2/bootstrap-icons.svg?2#'+ name +'"/>'+
			'</svg>';

	}

};

Lime.Search = class {

	static toggle(selector) {

		if(qs(selector).toggle() === 'removeHide') {
			window.scrollTo(0, 0);
		}

	}

}

Lime.Quick = class {

	static start(module, button) {

		if(button.dataset.dropdown === undefined) {

			new Ajax.Query(button)
				.url('/@module/' + module + '/quick')
				.body(button.post())
				.fetch()
				.then((json) => {

					// Gestion de la concurrence en cas de double-click
					if(button.dataset.dropdown) {
						return;
					}

					button.dataset.dropdown = 'bottom-center';
					button.insertAdjacentHTML('afterend', '<div class="dropdown-list dropdown-list-minimalist util-quick-dropdown" data-dropdown-keep></div>');

					const list = button.nextElementSibling;

					button.addEventListener('dropdownAfterShow', () => {

						list.renderInner(json.field);
						list.qs(
							'.util-quick-form select',
							(node) => node.focus(),
							() => list.qs(
								'.util-quick-form input',
								(node) => node.select()
							)
						);

					});


					Lime.Dropdown.open(button, 'bottom-center');

				});

		}

	}

}

document.delegateEventListener('mouseleave', '[data-dropdown-hover]', function(e) {

	e.preventDefault();

	Lime.Dropdown.hoverLeave(this);

});

Lime.History = class {

	static popped = null;
	static forceRefreshOnBack = false;
	static ignoreNextPopstate = 0;
	static popstatePromises = [];

	static currentTimestamp = 0;
	static previousTimestamp = null;
	static initialTimestamp = new Date().getTime();

	static scrolls = [window.scrollY];

	static layers = [];

	static purgingLayers = false;
	static purgingResolve = null;


	static init() {

		// Handles the navigation buttons in the browser
		this.popped = (
			'state' in history &&
			history.state !== null
		);

		const poppedInitialURL = location.href;

		window.onpopstate = e => {

			// Handle promise
			if(this.popstatePromises.length > 0) {
				this.popstatePromises.pop()();
			}

			// Ignore inital popstate that some browsers fire on page load
			const initial = (this.popped === false && location.href === poppedInitialURL);

			this.popped = true;

			if(initial) {
				return;
			}

			if(this.ignoreNextPopstate > 0) {
				this.ignoreNextPopstate--;
				return;
			}

			this.previousTimestamp = this.currentTimestamp;
			this.currentTimestamp = e.state ?? this.initialTimestamp;

			const move = (this.previousTimestamp === 0 || this.currentTimestamp < this.previousTimestamp) ? 'back' : 'forward';

			if(this.forceRefreshOnBack) {

				if(move === 'back') {
					document.location.reload();
				} else {
					this.forceRefreshOnBack = false;
				}

			}

			if(move === 'back' && this.popLayerOnPopstate()) {

				if(this.purgingLayers) {
					this.purgeNextLayer();
				}

			} else {

				if(this.previousTimestamp !== 0) {
					this.saveScroll(this.previousTimestamp);
				}

				const url = location.href;
				const query = new Ajax.Navigation(document.body);

				query.headers.set('x-requested-history', this.currentTimestamp);

				query
					.url(url)
					.skipHistory()
					.fetch()
					.then(() => {
						document.body.dataset.context = url;
					});

			}


		};

	};

	static go(number) {

		return new Promise((resolve) => {

			this.popstatePromises.push(resolve);

			history.go(-1);

		});

	}

	static pushState(url) {

		this.previousTimestamp = this.currentTimestamp;
		this.currentTimestamp = new Date().getTime();

		this.saveScroll(this.previousTimestamp);

		history.pushState(this.currentTimestamp, '', url);

	}

	static replaceState(url) {
		history.replaceState(history.state, '', url);
	}

	static saveScroll(state) {
		this.scrolls[state] = window.scrollY;
	}

	static restoreScroll(state) {
		window.scrollTo(0, this.scrolls[state]);
	}

	static getElementsOfLayers() {
		return this.layers.map(layer => layer.element);
	}

	static pushLayer(element, onPop, isPushHistory, backReload) {

		this.layers.push({
			id: element.id,
			element: element,
			onPop: onPop,
			scrollY: window.scrollY,
			backReload: backReload
		});

		if(isPushHistory) {
			this.push(location.href);
		}

	};

	static removeLayer(element, isOnPop = true) {

		for(let i = this.layers.length - 1; i >= 0; i--) {

			if(this.layers[i].element === element) {

				this.ignoreNextPopstate++; // Simulate an history change

				return this.go(-1).then(() => {

					const layer = this.layers[i];

					if(isOnPop) {
						layer.onPop.call(this, false);
					}

					window.scrollTo(0, layer.scrollY);

					this.layers.splice(i, 1);

					return true;

				});

			}

		}

		return false;

	};

	/*
	 * Remove layer after history back
	 */
	static popLayerOnPopstate() {

		if(this.layers.length === 0) {
			return false;
		} else {

			const layer = this.layers.pop();

			layer.onPop.call(this, true);
			window.scrollTo(0, layer.scrollY);

			// Div lost, fully reloaded
			if(
				this.layers.length > 0 &&
				this.layers[this.layers.length - 1].backReload &&
				layer.id === this.layers[this.layers.length - 1].id
			) {

				new Ajax.Query()
					.method('get')
					.waiter(layer.element.style.zIndex + 1, 0)
					.url(this.layers[this.layers.length - 1].backReload)
					.fetch();

			}

			return true;

		}

	};

	static purgeLayers() {

		this.purgingLayers = true;

		return new Promise(resolve => {

			this.purgingResolve = resolve;
			this.purgeNextLayer();

		});

	};

	static purgeNextLayer() {

		if(this.layers.length > 0) {

			this.go(-1);

		} else {

			this.purgingLayers = false;
			this.purgingResolve();

		}

	};

	static push(url) {
		Lime.History.pushState(url);
		this.pop();
	};

	static pop() {
		this.popped = true;
	}

};

Lime.Instruction = class {

	package;

	static methods = {};

	constructor(packageName) {

		this.package = packageName;

		if(Lime.Instruction.methods[this.package] === undefined) {
			Lime.Instruction.methods[this.package] = {};
		}

	}

	register(methodName, callback) {
		Lime.Instruction.methods[this.package][methodName] = callback;
		return this;
	}

	call(context, methodName, data) {

		const callback = Lime.Instruction.methods[this.package][methodName];

		if(callback === undefined) {
			throw "Method '"+ methodName +"' does not exist in package '"+ this.package +"'";
		}

		callback.call(context, ...data);

	}

}

document.delegateEventListener('click', '[data-dropdown]', function(e) {

	e.preventDefault();

	if(
		isTouch() ||
		this.dataset.dropdownHover === undefined
	) {
		Lime.Dropdown.toggle(this, this.dataset.dropdown);
	}

});


document.addEventListener('keyup', (e) => {
	Lime.Panel.closeEscape(e);
});