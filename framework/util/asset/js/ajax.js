const Ajax = {

}

Ajax.Query = class {

	context;
	headers;
	locked;

	fetchMethod = 'POST';
	fetchUrl;
	fetchBody;

	static elementsLocked = { };

	isWaiter = null;
	requestedWith = 'query';

	constructor(context) {

		this.context = context || null;

		this.headers = new Headers();
		this.headers.set('x-requested-with', 'ajax');

		Ajax.Asset.pushVersionHeader(this.headers);

		if(this.context !== null) {

			this.locked = true;

			let origin = this.context.getAttribute('data-ajax-origin');

			if(origin !== null) {
				this.headers.set('x-requested-origin', origin);
			}

		} else {
			this.locked = false;
		}

	};

	method(method) {
		this.fetchMethod = method;
		return this;
	};

	url(url) {
		this.fetchUrl = url;
		return this;
	};

	body(body) {

		if(body instanceof URLSearchParams || body instanceof FormData) {
			this.fetchBody = body;
		} else if(body) {
			this.fetchBody = this.getBodyFromObject(body);
		}

		return this;
	};

	getBodyFromObject(body) {

		const params = new URLSearchParams();

		function createParam(basename, values) {

			if(values === null) {
				values = '';
			}

			if(typeof values === 'object') {
				Object.entries(values).forEach(([key, subValues]) => createParam(basename +'['+ key +']', subValues))
			} else if(typeof values === 'array') {
				values.forEach((key, subValues) => createParam(basename +'['+ key +']', subValues))
			} else {
				params.set(basename, values);
			}

		};

		Object.entries(body).forEach(([key, values]) => {
			createParam(key, values);
		});

		return params;

	};

	lock() {

		if(this.locked === false) {
			return;
		}

		const lockId = this.getLockId();

		// Check for existing lock
		if(Ajax.Query.elementsLocked[lockId] !== undefined) {
			throw 'LOCK_FAILED';
		}

		// Lock
		Ajax.Query.elementsLocked[lockId] = setTimeout(() => this.unlock(), 5000);

		this.context.qsa('[type="submit"]', node => node.setAttribute('disabled', 'disabled'));

		return true;

	};

	unlock() {

		if(this.locked === false) {
			return;
		}

		const lockId = this.getLockId();

		if(typeof Ajax.Query.elementsLocked[lockId] === 'undefined') {
			return false;
		}

		clearTimeout(Ajax.Query.elementsLocked[lockId]);
		Ajax.Query.elementsLocked[lockId] = undefined;

		this.context.qsa('[type="submit"]', node => node.removeAttribute('disabled'));

		return true;

	};

	fetch() {

		const settings = {
			method: this.fetchMethod,
			headers: this.headers,
		};

		if(this.fetchBody) {
			settings.body = this.fetchBody;
		}

		this.lock();

		if(this.context) {
			Lime.Alert.hideErrors(this.context);
		}

		return new Promise((resolve, reject) => {

			fetch(this.fetchUrl, settings).then(response => {

				this.unlock();

				if(response.ok) {

					response
						.text()
						.then(text => this.ok(text, resolve, reject))

				} else {

					response
						.text()
						.then(text => this.ko(text, reject))

				}

			});

		});

	};

	xmlHttpRequest(callback) {

		return new Promise((resolve, reject) => {

			this.lock();

			if(this.context) {
				Lime.Alert.hideErrors(this.context);
			}

			const xhr = new window.XMLHttpRequest();

			xhr.open(this.fetchMethod, this.fetchUrl, true);

			this.headers.forEach((value, name) => {
				xhr.setRequestHeader(name, value);
			});

			if(callback) {
				callback.call(this, xhr);
			}

			xhr.addEventListener('readystatechange', () => {

				if(xhr.readyState !== 4) {
					return;
				}

				this.unlock();

				if(xhr.status === 200) {
					this.ok(xhr.responseText, resolve, reject);
				} else {
					this.ko(xhr.responseText, reject);
				}

			});

			xhr.addEventListener('abort', () => {
				this.unlock();
				this.ko('', reject);
			});

			xhr.addEventListener('error', () => {
				this.unlock();
				this.ko('', reject);
			});

			xhr.addEventListener('timeout', () => {
				this.unlock();
				this.ko('', reject);
			});

			xhr.send(this.fetchBody);

		});

	};

	ok(text, resolve, reject) {

		let json;

		try {
			json = JSON.parse(text);
		} catch(e) {
			console.log(e);
			this.ko(text, reject);
			return;
		}

		this.loadAssets(json).then(() => {

			this.renderOk(json).then(errors => {

				this.done(json);
				resolve(json, errors);

			});

		});

	};

	ko(text, reject) {

		this.renderKo(text).then(() => {
			this.failed(text);
			reject(text);
		});

	};

	done(json) {

	};

	failed(text) {

	};

	loadAssets(json) {

		if(json.__assets === undefined) {
			return Promise.resolve();
		}

		const assets = new Ajax.Asset(json.__assets);

		if(assets.isLastVersion() === false) {
			location.reload();
			return Promise.resolve();
		}

		return assets.getPromises().then(() => {

			if(json.__assets.jsCode) {
				evalScope(document.body, json.__assets.jsCode);
			}

			if(json.__assets.cssCode) {
				document.head.insertAdjacentHTML('beforeend', '<style>'+ json.__assets.cssCode +'</style>');
			}

		});

	};

	renderOk(json) {

		return new Promise(resolve => {

			if(this.isWaiter) {
				this.hideWaiter();
			}

			const errors = new Ajax.Instruction(json, this.requestedWith, this.context).render();

			if(typeof Dev !== 'undefined') {
				Dev.hideError();
			}

			resolve(errors);

		});

	};

	renderKo(text) {

		if(this.isWaiter) {
			this.hideWaiter();
		}

		return new Promise(resolve => {

			// Hide previous errors
			if(this.context) {
				this.context.qsa('.form-error-wrapper', node => node.classList.remove('form-error-wrapper'));
				this.context.qsa('p.form-error-message', node => node.remove());
			}

			if(typeof Dev !== 'undefined') {
				Dev.showError('<h4>AJAX query to '+ this.fetchUrl +' expected a valid JSON response</h4><hr/>'+ text);
			}

			resolve();

		});

	};

	getLockId() {

		if(this.context.hasAttribute('data-lock') === false) {
			this.context.setAttribute('data-lock', Date.now());
		}

		return this.context.getAttribute('data-lock');

	}

	waiter(zIndex, delay) {

		this.isWaiter = 'waiter-'+ new Date().getTime();

		setTimeout(() => {

			if(this.isWaiter !== null) {
				this.showWaiter(zIndex);
			}

		}, delay);

		return this;

	};

	showWaiter(zIndex) {

		document.body.insertAdjacentHTML('beforeend', '<div id="'+ this.isWaiter +'" class="waiter" style="z-index: '+ zIndex +'">'+ Lime.Asset.icon('hourglass-top') +'</div>');

	};

	hideWaiter() {

		qs('#'+ this.isWaiter, node => {

			node.style.opacity = 0;

			setTimeout(() => node.remove(), 200);

		});

		this.isWaiter = null;

	}

}

/*
 * Locking for ajax queries
 */
window.onerror = function(message) {

	if(message.indexOf('LOCK_FAILED') !== -1) {
		return true;
	} else {
		return false;
	}

};

Ajax.Navigation = class extends Ajax.Query {

	fetchMethod = 'GET';

	isPurgeLayers = false;
	isPushHistory = true;

	requestedWith = 'navigation';

	constructor(context) {

		document.dispatchEvent(new CustomEvent('navigationStart'));

		super(context);

	}

	url(url) {

		if(url.substring(0, 1) === '/') {
			url = window.location.origin + url;
		}

		if(Ajax.Navigation.isUrlValid(url) === false) {
			throw 'Invalid URL';
		}

		return super.url(url);

	}

	static isUrlValid(url) {

		return (
			typeof url === 'string' &&
			(url.startsWith('/') || url.startsWith(window.location.origin))
		);

	};

	renderOk(json) {

		return new Promise(resolve => {

			// Clean layers ?
			if(json.__context === 'new') {
				this.isPurgeLayers = true;
			}

			const promise = this.isPurgeLayers ?
				Lime.History.purgeLayers() :
				Promise.resolve()

			promise.then(() => {

				Lime.Alert.hideStaticErrors();

				if(json.__context === 'new') {
					Lime.Panel.purge();
				}

				if(json.__context === 'new') {
					document.dispatchEvent(new CustomEvent('navigation.sleep'));
				}

				const url = this.fetchUrl;

				if(this.isPushHistory) {

					// Cas particulier : on recharge un panel précédemment chargé
					// Dans ce cas, pas de push sur l'historique
					if(
						json.__panel !== undefined &&
						qs('#'+ json.__panel +'.open') !== null
					) {

						Lime.History.replaceState(url);

						// Traité du coup comme une simple requête ajax
						this.requestedWith = 'query';

					} else {

						Lime.History.push(url);

						if(json.__context === 'new') {
							document.body.dataset.context = url;
						}

					}

				}

				super.renderOk(json).then(status => {

					if(url.indexOf('#') !== -1) {
						qs(url.substring(url.indexOf('#')), node => window.scrollTo(0, node.getBoundingClientRect().top + window.scrollY));
					}

					if(this.headers.get('x-requested-history') !== null) {
						Lime.History.restoreScroll(this.headers.get('x-requested-history'));
					} else {

						qs('.panel.open .panel-dialog', node => {
							node.scrollIntoView(true);
						});

					}

					if(json.__context === 'new') {
						document.dispatchEvent(new CustomEvent('navigation.wakeup'));
					}

					resolve(status);

				});

			});

		});

	};

	skipHistory() {
		this.isPushHistory = false;
		return this;
	};

	purgeLayers(value) {
		this.isPurgeLayers = value;
		return this;
	};

}

Ajax.Instruction = class {

	json;
	context;
	requestedWith;

	initialScrollY = window.scrollY;

	errors;

	constructor(json, requestedWith, context) {
		this.json = json;
		this.requestedWith = requestedWith;
		this.context = context;
	}

	render() {

		this.errors = false;

		if(this.json.__redirect) {
			this.renderRedirect(this.json.__instructions, this.json.__redirect);
		} else {
			this.renderInstructions(this.json.__instructions);
		}

		return this.errors;

	};

	renderRedirect(__instructions, __redirect) {

		__redirect.forEach((redirect) => {

			let mode, url, data;
			[mode, url, ...data] = redirect;

			switch(mode) {

				case 'ajaxNavigation' : {

					let purgeLayers;
					[purgeLayers] = data;

					const query = new Ajax.Navigation();

					query
						.purgeLayers(purgeLayers)
						.url(url)
						.fetch()
						.then(() => this.renderInstructions(__instructions));

				}
					break;

				case 'ajaxReload' :

					let layer, purgeLayers;
					[layer, purgeLayers] = data;

					const query = new Ajax.Query();

					if(layer === false && purgeLayers === false) {

						query.headers.set('x-requested-context', 'reuse');

					} else {

						Lime.History.saveScroll(Lime.History.currentTimestamp);
						query.headers.set('x-requested-history', Lime.History.currentTimestamp);

					}

					const promise = purgeLayers ?
						Lime.History.purgeLayers() :
						Promise.resolve();

					promise.then(() => {

						query
							.method('get')
							.url(layer ? location.href : document.body.dataset.context)
							.fetch()
							.then(() => this.renderInstructions(__instructions));

					});

					break;

				case 'http' :

					let queryStringAppend, mode;
					[queryStringAppend, mode] = data;

					// Redirect handling
					if(url === null) {
						url = location.href;
					}

					if(queryStringAppend !== '') {

						if(url.includes('?')) {
							url += '&';
						} else {
							url += '?';
						}

						url += queryStringAppend;

					}

					switch(mode) {

						case 'assign' :
							this.renderInstructions(__instructions);
							location.assign(url);
							break;

						case 'replace' :
							this.renderInstructions(__instructions);
							location.replace(url);
							break;

						default :
							throw 'Invalid mode "'+ mode +'"';

					}

					break;

			}

		});

	};

	renderInstructions(__instructions) {

		if(!__instructions) {
			return;
		}

		const loop = (key) => {

			if(key === __instructions.length) {
				return;
			}

			let type, instructions, promise;

			[type, ...instructions] = __instructions[key];

			switch(type) {

				case 'package' :
					this.renderPackageInstructions(...instructions);
					break;

				case 'js' : {
					let about;
					[about] = instructions;
					promise = this.renderWindowInstructions(about);
				}
					break;

				case 'qs' : {
					let about, selector;
					[selector, about] = instructions;
					qs(selector, node => this.renderQuerySelectorInstructions(node, about))
				}
					break;

				case 'qsa' : {
					let about, selector;
					[selector, about] = instructions;
					qsa(selector, node => this.renderQuerySelectorInstructions(node, about))
				}
					break;

			}

			if(promise instanceof Promise) {

				promise.then(() => {
					loop(key + 1);
				});

			} else {
				loop(key + 1);
			}

		}

		loop(0);

	}

	renderPackageInstructions(packageName, instructions) {

		const object = new Lime.Instruction(packageName);

		instructions.forEach(([methodName, data]) => {
			object.call(this, methodName, data);
		});

	}

	renderWindowInstructions(instructions) {

		return new Promise(resolve => {

			const loop = (key) => {

				if(key === instructions.length) {
					resolve();
					return;
				}

				const promise = this.renderWindowInstruction(...instructions[key]);

				if(promise instanceof Promise) {

					promise.then(() => {
						loop(key + 1);
					});

				} else {
					loop(key + 1);
				}

			}

			loop(0);

		})

	}

	renderWindowInstruction(name, data) {

		switch(name) {

			case 'ajax' :

				let method, url, body;
				[method, url, body] = data;

				const ajax = new Ajax.Query(document.body);

				if(method !== 'GET') {
					ajax.body(body);
				}

				ajax
					.method(method)
					.url(url)
					.fetch();

				return;

			case 'success' :
				this.showSuccess(...data);
				return;

			case 'errors' :
				this.errors = true;
				this.showErrors(...data);
				return;

			case 'purgeLayers' :
				return Lime.History.purgeLayers();

			case 'pushPanel' : {

				const panel = Lime.Panel.render(data[0]);
				if(data[0].layer) {
					Lime.Panel.layer(panel, this.requestedWith);
				}
			}
				return;

			case 'showPanel' :
				qs(data[0], node => Lime.Panel.show(node));
				return;

			case 'closePanel' :
				qs(data[0], node => Lime.Panel.close(node));
				return;

			case 'closeDropdown' :
				qs(data[0], node => Lime.Dropdown.close(node));
				return;

			case 'closeDropdowns' :
				Lime.Dropdown.closeAll();
				return;

			case 'pushHistory' :
				Lime.History.pushState(data[0]);
				return;

			case 'replaceHistory' :
				Lime.History.replaceState(data[0]);
				return;

			case 'eval' :
				evalScope(document.body, data[0]);
				return;

			case 'updateHistory' :
				history.updateArgument(...data);
				return;

			case 'moveHistory' :
				return Lime.History.go(...data);


		}

	}

	showSuccess(success) {
		Lime.Alert.showStaticSuccess(success);
	};

	showErrors(errors) {
		Lime.Alert.showErrors(this.context, errors);
	};

	renderQuerySelectorInstructions(node, instructions) {

		instructions.forEach(([name, data]) => {

			switch(name) {

				case 'focus' :
					setTimeout(() => node.focus(), 100);
					break;

				case 'hide' :
					node.hide();
					break;

				case 'removeHide' :
					node.removeHide();
					break;

				case 'toggle' :
					node.toggle();
					break;

				case 'toggleSwitch' :
					node.toggleSwitch();
					break;

				case 'outerHtml' :
					node.renderOuter(...data);
					break;

				case 'innerHtml' :
					node.renderInner(...data);
					break;

				case 'replacePanel' :
					qs('#'+ data.id, panel => Lime.Panel.paint(panel, data));
					break;

				case 'insertAdjacentHtml' :
					node.renderAdjacentHTML(...data);
					break;

				case 'value' :
					node.value = data[0];
					break;

				case 'setAttribute' :
					node.setAttribute(...data)
					break;

				case 'removeAttribute' :
					node.removeAttribute(...data)
					break;

				case 'style' :
					let styles;
					[styles] = data;
					Object.entries(styles).forEach(([name, value]) => node.style[name] = value);
					break;

				case 'scrollTo' :
					let block, behavior;
					[block, behavior] = data;

					setTimeout(() => {
						node.scrollIntoView({
							behavior: behavior,
							block: block
						})
					}, 200);

					break;

				case 'remove' :

					let options;
					[options] = data;

					const remove = () => {

						switch(options.mode) {

							case 'slideUp' :
								node.slideUp({
									done: () => node.remove()
								});
								break;

							case 'fadeOut' :
								node.fadeOut({
									done: () => node.remove()
								});
								break;

							default :
								node.remove();
								break;

						}

					};

					if(options.delay !== undefined) {
						setTimeout(remove, options.delay);
					} else {
						remove();
					}

					break;

				case 'addClass' :

				function addClass() {

					node.classList.add(data[0]);

					if(data[1].lifetime !== undefined) {
						setTimeout(function() {
							node.classList.remove(data[0]);
						}, data[1].lifetime);
					}

				};

					if(data[1].delay !== undefined) {
						setTimeout(function() {
							addClass();
						}, data[1].delay);
					} else {
						addClass();
					}

					break;

				case 'removeClass' :

				function removeClass() {

					node.classList.remove(data[0]);

					if(data[1].lifetime !== undefined) {
						setTimeout(function() {
							node.classList.add(data[0]);
						}, data[1].lifetime);
					}

				};

					if(data[1].delay !== undefined) {
						setTimeout(function() {
							removeClass();
						}, data[1].delay);
					} else {
						removeClass();
					}

					break;

			}

		});

	};

}

Ajax.Asset = class {

	assets;

	static version;
	static loaded = [];

	constructor(assets) {
		this.assets = assets;
	}

	static pushVersionHeader(headers) {

		try {
			headers.set('x-asset-version', this.getVersion());
		} catch(e) {
		}

	}

	static getVersion() {

		if(this.version === undefined) {
			throw "Undefined version";
		}

		return this.version;

	}

	isLastVersion() {
		return (Ajax.Asset.version === this.assets.version);
	}

	getPromises() {

		const promises = [].concat(
			this.js(),
			this.css()
		);

		return Promise.all(promises);

	};

	js() {

		return this.load('js', this.assets.js, function(type, loadUrl) {

			return new Promise(resolve => {

				let node = document.createElement('script');
				node.setAttribute('type', 'text/javascript');
				node.setAttribute('src', loadUrl);
				node.setAttribute('async', 'true');

				node.onload = function() {
					setTimeout(() => resolve(), 10);
				};

				node.onerror = function() {
					console.log('Can not load: '+ loadUrl);
					resolve();
				};

				document.getElementsByTagName("head")[0].appendChild(node);


			});

		});

	};

	css() {

		return this.load('css', this.assets.css, function(type, loadUrl) {

			return new Promise(resolve => {

				let node = document.createElement('link');
				node.setAttribute('rel', 'stylesheet');
				node.setAttribute('type', 'text/css');
				node.setAttribute('href', loadUrl);
				node.setAttribute('async', 'true');

				node.onload = function() {
					setTimeout(() => resolve(), 10);
				};

				node.onerror = function() {
					console.log('Can not load: '+ loadUrl);
					resolve();
				};

				document.getElementsByTagName("head")[0].appendChild(node);

			});

		});

	};

	load(type, urls, callback) {

		let promises = [];
		let urlsToMinify = [];

		urls.forEach(url => {

			if(Ajax.Asset.loaded.indexOf(url) >= 0) {
				return;
			}

			if(this.assets.minify) {

				if(
					url.slice(0, 4) !== 'http' && // http:// urls are not minified
					url.slice(- type.length - 1) === '.'+ type // PHP built CSS are not minified
				) {

					// Constructs the list of min JS to retreive
					const explode = url.split('/').slice(2, url.split('/').length);
					const app = explode.shift();
					const appPackage = explode.shift();
					const basename = explode.join('/');

					if(app === undefined || appPackage === undefined || basename === undefined) {
						throw 'Invalid file "'+ url +'"';
					}

					const urlMin = app + '/' + appPackage + ':' + basename.substring(0, basename.length - type.length - 1);

					urlsToMinify.push(urlMin);

					Ajax.Asset.loaded.push(url);

				} else {

					Ajax.Asset.loaded.push(url);
					promises.push(callback(type, url +'?'+ Ajax.Asset.version));

					if(urlsToMinify.length > 0) {

						promises.push(this.minify(type, urlsToMinify, callback));
						urlsToMinify = [];

					}

				}

			} else {

				Ajax.Asset.loaded.push(url);
				promises.push(callback(type, url +'?'+ Ajax.Asset.version));

			}

		});

		if(urlsToMinify.length > 0) {
			promises.push(this.minify(type, urlsToMinify, callback));
		}

		return promises;

	};

	minify(type, urls, callback) {

		let hashFile = this.md5(urls.join('|')) +'.'+ type;
		return callback(type, '/minify/'+ Ajax.Asset.version +'/' + hashFile + '?m=' + urls.join(','));

	};

	/*
	 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
	 * Digest Algorithm, as defined in RFC 1321.
	 * Copyright (C) Paul Johnston 1999 - 2000.
	 * Updated by Greg Holt 2000 - 2001.
	 * See http://pajhome.org.uk/site/legal.html for details.
	 */
	md5(str) {

		/*
		 * Convert a 32-bit number to a hex string with ls-byte first
		 */
		let hex_chr = "0123456789abcdef";
		let rhex = function(num) {
			str = "";
			for(let j = 0; j <= 3; j++)
				str += hex_chr.charAt((num >> (j * 8 + 4)) & 0x0F) +
					hex_chr.charAt((num >> (j * 8)) & 0x0F);
			return str;
		}

		/*
		 * Convert a string to a sequence of 16-word blocks, stored as an array.
		 * Append padding bits and the length, as described in the MD5 standard.
		 */
		let str2blks_MD5 = function(str) {
			let nblk = ((str.length + 8) >> 6) + 1;
			let blks = new Array(nblk * 16);
			for(let i = 0; i < nblk * 16; i++) blks[i] = 0;
			for(let i = 0; i < str.length; i++)
				blks[i >> 2] |= str.charCodeAt(i) << ((i % 4) * 8);
			blks[str.length >> 2] |= 0x80 << ((str.length % 4) * 8);
			blks[nblk * 16 - 2] = str.length * 8;
			return blks;
		}

		/*
		 * Add integers, wrapping at 2^32. This uses 16-bit operations internally
		 * to work around bugs in some JS interpreters.
		 */
		let add = function(x, y) {
			let lsw = (x & 0xFFFF) + (y & 0xFFFF);
			let msw = (x >> 16) + (y >> 16) + (lsw >> 16);
			return (msw << 16) | (lsw & 0xFFFF);
		}

		/*
		 * Bitwise rotate a 32-bit number to the left
		 */
		let rol = function(num, cnt) {
			return (num << cnt) | (num >>> (32 - cnt));
		}

		/*
		 * These functions implement the basic operation for each round of the
		 * algorithm.
		 */
		let cmn = function(q, a, b, x, s, t) {
			return add(rol(add(add(a, q), add(x, t)), s), b);
		}
		let ff = function(a, b, c, d, x, s, t) {
			return cmn((b & c) | ((~b) & d), a, b, x, s, t);
		}
		let gg = function(a, b, c, d, x, s, t) {
			return cmn((b & d) | (c & (~d)), a, b, x, s, t);
		}
		let hh = function(a, b, c, d, x, s, t) {
			return cmn(b ^ c ^ d, a, b, x, s, t);
		}
		let ii = function(a, b, c, d, x, s, t) {
			return cmn(c ^ (b | (~d)), a, b, x, s, t);
		}

		let x = str2blks_MD5(str);
		let a = 1732584193;
		let b = -271733879;
		let c = -1732584194;
		let d = 271733878;

		for(let i = 0; i < x.length; i += 16) {
			let olda = a;
			let oldb = b;
			let oldc = c;
			let oldd = d;

			a = ff(a, b, c, d, x[i + 0], 7, -680876936);
			d = ff(d, a, b, c, x[i + 1], 12, -389564586);
			c = ff(c, d, a, b, x[i + 2], 17, 606105819);
			b = ff(b, c, d, a, x[i + 3], 22, -1044525330);
			a = ff(a, b, c, d, x[i + 4], 7, -176418897);
			d = ff(d, a, b, c, x[i + 5], 12, 1200080426);
			c = ff(c, d, a, b, x[i + 6], 17, -1473231341);
			b = ff(b, c, d, a, x[i + 7], 22, -45705983);
			a = ff(a, b, c, d, x[i + 8], 7, 1770035416);
			d = ff(d, a, b, c, x[i + 9], 12, -1958414417);
			c = ff(c, d, a, b, x[i + 10], 17, -42063);
			b = ff(b, c, d, a, x[i + 11], 22, -1990404162);
			a = ff(a, b, c, d, x[i + 12], 7, 1804603682);
			d = ff(d, a, b, c, x[i + 13], 12, -40341101);
			c = ff(c, d, a, b, x[i + 14], 17, -1502002290);
			b = ff(b, c, d, a, x[i + 15], 22, 1236535329);

			a = gg(a, b, c, d, x[i + 1], 5, -165796510);
			d = gg(d, a, b, c, x[i + 6], 9, -1069501632);
			c = gg(c, d, a, b, x[i + 11], 14, 643717713);
			b = gg(b, c, d, a, x[i + 0], 20, -373897302);
			a = gg(a, b, c, d, x[i + 5], 5, -701558691);
			d = gg(d, a, b, c, x[i + 10], 9, 38016083);
			c = gg(c, d, a, b, x[i + 15], 14, -660478335);
			b = gg(b, c, d, a, x[i + 4], 20, -405537848);
			a = gg(a, b, c, d, x[i + 9], 5, 568446438);
			d = gg(d, a, b, c, x[i + 14], 9, -1019803690);
			c = gg(c, d, a, b, x[i + 3], 14, -187363961);
			b = gg(b, c, d, a, x[i + 8], 20, 1163531501);
			a = gg(a, b, c, d, x[i + 13], 5, -1444681467);
			d = gg(d, a, b, c, x[i + 2], 9, -51403784);
			c = gg(c, d, a, b, x[i + 7], 14, 1735328473);
			b = gg(b, c, d, a, x[i + 12], 20, -1926607734);

			a = hh(a, b, c, d, x[i + 5], 4, -378558);
			d = hh(d, a, b, c, x[i + 8], 11, -2022574463);
			c = hh(c, d, a, b, x[i + 11], 16, 1839030562);
			b = hh(b, c, d, a, x[i + 14], 23, -35309556);
			a = hh(a, b, c, d, x[i + 1], 4, -1530992060);
			d = hh(d, a, b, c, x[i + 4], 11, 1272893353);
			c = hh(c, d, a, b, x[i + 7], 16, -155497632);
			b = hh(b, c, d, a, x[i + 10], 23, -1094730640);
			a = hh(a, b, c, d, x[i + 13], 4, 681279174);
			d = hh(d, a, b, c, x[i + 0], 11, -358537222);
			c = hh(c, d, a, b, x[i + 3], 16, -722521979);
			b = hh(b, c, d, a, x[i + 6], 23, 76029189);
			a = hh(a, b, c, d, x[i + 9], 4, -640364487);
			d = hh(d, a, b, c, x[i + 12], 11, -421815835);
			c = hh(c, d, a, b, x[i + 15], 16, 530742520);
			b = hh(b, c, d, a, x[i + 2], 23, -995338651);

			a = ii(a, b, c, d, x[i + 0], 6, -198630844);
			d = ii(d, a, b, c, x[i + 7], 10, 1126891415);
			c = ii(c, d, a, b, x[i + 14], 15, -1416354905);
			b = ii(b, c, d, a, x[i + 5], 21, -57434055);
			a = ii(a, b, c, d, x[i + 12], 6, 1700485571);
			d = ii(d, a, b, c, x[i + 3], 10, -1894986606);
			c = ii(c, d, a, b, x[i + 10], 15, -1051523);
			b = ii(b, c, d, a, x[i + 1], 21, -2054922799);
			a = ii(a, b, c, d, x[i + 8], 6, 1873313359);
			d = ii(d, a, b, c, x[i + 15], 10, -30611744);
			c = ii(c, d, a, b, x[i + 6], 15, -1560198380);
			b = ii(b, c, d, a, x[i + 13], 21, 1309151649);
			a = ii(a, b, c, d, x[i + 4], 6, -145523070);
			d = ii(d, a, b, c, x[i + 11], 10, -1120210379);
			c = ii(c, d, a, b, x[i + 2], 15, 718787259);
			b = ii(b, c, d, a, x[i + 9], 21, -343485551);

			a = add(a, olda);
			b = add(b, oldb);
			c = add(c, oldc);
			d = add(d, oldd);
		}
		return rhex(a) + rhex(b) + rhex(c) + rhex(d);
	}


}


/*
 * Ajax handling
 */
document.delegateEventListener('click', '[data-ajax]', function(e) {

	e.preventDefault();
	e.stopImmediatePropagation();

	const className = this.dataset.ajaxClass ?
		eval(this.dataset.ajaxClass) :
		Ajax.Query;

	const object = new className(this)
		.url(this.getAttribute('data-ajax'));

	switch(this.dataset.ajaxMethod) {

		case 'get' :
			object.method('get');
			break;

		case 'post' :
		default :

			const body = this.hasAttribute('data-ajax-body') ?
				new URLSearchParams(JSON.parse(this.dataset.ajaxBody)) :
				this.post();

			object
				.method('post')
				.body(body);

			break;

	}

	return object.fetch();

});

let mouseOnLink = null;

document.delegateEventListener('mouseenter', 'a', function(e) {
	mouseOnLink = e.target;
});
document.delegateEventListener('mouseleave', 'a', function(e) {
	mouseOnLink = null;
});

document.delegateEventListener('click', '[data-get]', function(e) {

	if(mouseOnLink !== null && mouseOnLink !== e.delegateTarget) {
		return;
	}

	e.preventDefault();
	e.stopImmediatePropagation();

	const object = this.hasAttribute('data-ajax-class') ?
		eval(this.getAttribute('data-ajax-class')) :
		Ajax.Navigation;

	new object()
		.method('get')
		.url(this.getAttribute('data-get'))
		.fetch();

});

/**
 * Navigation handling
 */

document.delegateEventListener('click', '[data-href]', function(e) {

	e.preventDefault();
	e.stopImmediatePropagation();

	window.location.href = this.dataset.href;

});

document.delegateEventListener('click', 'a[href]', function(e) {

	let node = e.target;

	do {

		switch(node.dataset.ajaxNavigation) {

			case 'never' :
				return true;

			case 'touch' :
				if(isTouch() === FALSE) {
					return true;
				}
				break;

			case 'notouch' :
				if(isTouch()) {
					return true;
				}

		}

		if(node === this) {
			break;
		}

		node = node.parentElement;

	} while(node !== null);

	if(this.getAttribute('target') === '_blank') {
		return true;
	}

	if(this.getAttribute('href').startsWith('#')) {
		return true;
	}

	if(e.ctrlKey !== false || e.metaKey !== false) {
		return true;
	}

	const href = this.getAttribute('href');

	if(Ajax.Navigation.isUrlValid(href) === false) {
		return true;
	}

	e.preventDefault();

	new Ajax.Navigation(this)
		.url(href)
		.fetch();

});

/**
 * Page loaded
 */
document.addEventListener('DOMContentLoaded', () => {

	document.dispatchEvent(new CustomEvent('navigation.wakeup'));

	document.body.dataset.context = location.href;

	if(Ajax.Asset.loaded.length === 0) {

		qsa('script[type="text/javascript"]', node => {
			const src = node.getAttribute('src');
			if(src) {
				Ajax.Asset.loaded.push(src.indexOf('?') === -1 ? src : src.substring(0, src.indexOf('?')));
			}
		});

		qsa('link[type="text/css"]', node => {
			const href = node.getAttribute('href');
			if(href) {
				Ajax.Asset.loaded.push(href.indexOf('?') === -1 ? href : href.substring(0, href.indexOf('?')));
			}
		});

	}

});
