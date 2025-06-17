class GalleryEditor {

	selector;
	options = {};

	constructor(selector, options) {

		if(selector instanceof Element === false) {
			throw "Dom element expected";
		}

		this.selector = selector;
		Object.assign(this.options, this.getDefaultOptions(), options);

		this.start();

	};

	start() {

		const containers = this.selector.qsa(this.options.container);

		if(containers.length === 0) {
			return;
		}

		if(this.selector.getAttribute('data-size-before')) {
			this.selector.setAttribute('data-size', this.selector.getAttribute('data-size-before'));
			this.selector.removeAttribute('data-size-before');
		}

		const size = this.selector.getAttribute('data-size');

		if(
			(size === 'left' || size === 'right') &&
			containers.length > 1
		) {
			this.selector.setAttribute('data-size-before', size);
			this.selector.setAttribute('data-size', 'compressed');
		}

		this.options.selectedMaxHeight = (typeof this.options.maxHeight === 'function') ? this.options.maxHeight : this.options.maxHeight[this.selector.getAttribute('data-size') || 'compressed'];
		this.options.selectedMaxWidth = this.options.maxWidth[this.selector.getAttribute('data-size') || 'compressed'];
		this.options.selectedGridWidth = this.options.gridWidth[this.selector.getAttribute('data-size') || 'compressed'];

		const rows = this.getRows(containers);

		this.makeGrid(rows, true);


	};

	/**
	 * Update gallery on window resize
	 */
	listenResize() {

		const listener = () => {

			if(document.body.contains(this.selector)) {
				this.reorganize(true);
			} else {
				removeListener();
			}

		};

		function removeListener() {
			window.removeEventListener('resize', listener);
		};

		window.addEventListener('resize', listener);

		return this;

	}

	/**
	 * Update gallery on mutation
	 */
	listenMutation() {

		const observer = new MutationObserver(() => {
			this.reorganize(false);
		})

		observer.observe(this.selector, {
			childList: true,
			subtree: true
		});

		document.addEventListener('navigation.sleep', () => {
			observer.disconnect();
		}, {once: true});

		return this;

	};

	reorganize(resize) {
		const containers = this.selector.qsa(this.options.container);
		const newRows = this.getRows(containers);
		this.makeGrid(newRows, resize);
	};

	getDefaultOptions() {

		function lineBy2(n) {
			return (n > 1) ? 2 : 1;
		}

		function lineBy3(n) {

			if(n <= 3) {
				return n;
			}

			switch(n) {

				case 4 :
					return 2;

				default :
					return 3;

			}

		}

		return {
			container: '.item',
			maxItems: () => null,
			maxHeight: {
				compressed: gallery => 1500,
				left: gallery => 300,
				right: gallery => 300
			},
			maxWidth: {
				compressed: gallery => null,
				left: gallery => this.isMobile() ? 150 : 300,
				right: gallery => this.isMobile() ? 150 : 300
			},
			gridWidth: {
				compressed: gallery => this.computeWidth(gallery),
				left: gallery => this.computeWidth(gallery.parentElement),
				right: gallery => this.computeWidth(gallery.parentElement),
			},

			lineBy1: n => 1,
			lineBy2: lineBy2,
			lineBy3: lineBy3,

			lineBy4: n => {

				if(n <= 4) {
					return n;
				}

				switch(n) {

					case 5 :
					case 6 :
						return 3;

					default :
						return 4;

				}

			},

			lineBy5: n => {

				if(n <= 5) {
					return n;
				}

				switch(n) {

					case 6 :
						return 3;
					case 7 :
					case 8 :
						return 4;

					default :
						return 5;

				}

			},

			lineBy8: n => {

				if(n <= 8) {
					return n;
				}

				switch(n) {

					case 9 :
					case 10 :
						return 5;
					case 11 :
					case 12 :
						return 6;
					case 13 :
					case 14 :
						return 7;

					default :
						return 8;

				}

			},

			lineByN: n => n,

			lineSize: n => this.isMobile() ? lineBy2(n) : lineBy3(n),

			canExceedMax: function() {
				return false;
			}
		};

	};

	makeGrid(rows, resize) {

		this.applyGallerySize(rows);

		const gridWidth = this.options.selectedGridWidth(this.selector);

		for(let i = 0; i < rows.length; i++) {
			this.makeRow(rows, i);
		}

		// scroll bars added or removed during rendering new layout?
		if(resize && gridWidth !== this.computeWidth(this.selector)) {
			this.makeGrid(rows, false);
		}

		// end callback
		if(this.options.onReorganized) {
			this.options.onReorganized(this.selector);
		}

	};

	makeRow(rows, indexRow) {

		const items = rows[indexRow];

		const itemStyle = getComputedStyle(items[0].reference, null);
		const itemPaddingWidth =
			parseFloat(itemStyle.getPropertyValue('padding-left')) +
			parseFloat(itemStyle.getPropertyValue('padding-right'));
		const itemPaddingHeight =
			parseFloat(itemStyle.getPropertyValue('padding-top')) +
			parseFloat(itemStyle.getPropertyValue('padding-bottom'));

		const grid_w = this.options.selectedGridWidth(this.selector) - itemPaddingWidth * items.length;
		const row_w = items.reduce((value, item) => value + item.norm_w, 0);

		// reduce image size
		const factor = Math.min(1,	grid_w / row_w);

		let calc_w = 0;

		items.forEach(item =>  {

			item.calc_w = Math.round(item.norm_w * factor);
			item.calc_h = Math.round(item.norm_h * factor);

			calc_w += item.calc_w;

		});

		// too much pixels?
		if(calc_w > grid_w) {
			items[0].calc_w -= (calc_w - grid_w);
		}

		for(let i = 0; i < items.length; i++) {

			const item = items[i];

			item.reference.style.width = (item.calc_w + itemPaddingWidth) +'px';
			item.reference.style.height = (item.calc_h + itemPaddingHeight) +'px';

			item.reference.classList.remove('gallery-left');
			item.reference.classList.remove('gallery-center');
			item.reference.classList.remove('gallery-right');
			item.reference.classList.remove('gallery-one');
			item.reference.classList.remove('gallery-bottom');

			if(i === 0) {
				item.reference.classList.add('gallery-left');
			}
			if(i === items.length - 1) {
				item.reference.classList.add('gallery-right');
			}

			if(items.length === 1) {
				item.reference.classList.add('gallery-one');
			}

			if(indexRow === rows.length - 1) {
				item.reference.classList.add('gallery-bottom');
			}

			if(
				i > 0 &&
				i < items.length - 1
			) {
				item.reference.classList.add('gallery-center');
			}

		}

	};

	applyGallerySize(rows) {

		if(
			(
				this.options.canExceedMax() === true &&
				this.selector.getAttribute('data-size') !== 'left' &&
				this.selector.getAttribute('data-size') !== 'right'
			)
		) {

			this.selector.style.marginLeft = '';
			this.selector.style.marginRight = '';

			const style = getComputedStyle(this.selector, null);

			let styleMarginLeft = style.getPropertyValue('margin-left') ? parseFloat(style.getPropertyValue('margin-left')) : 0;
			let styleMarginRight = style.getPropertyValue('margin-right') ? parseFloat(style.getPropertyValue('margin-right')) : 0;

			if(styleMarginLeft < 0) {
				styleMarginLeft = 0;
			}

			if(styleMarginRight < 0) {
				styleMarginRight = 0;
			}

			const offset = this.selector.getBoundingClientRect().left + window.scrollX - parseInt(style.marginLeft);
			const parentWidth = this.selector.parentElement.offsetWidth;

			const imagesWidth = rows[0].reduce((value, row) => value + row.w, 0);
			const maxWidth = this.options.selectedMaxWidth(this.selector) || 2000;

			const windowWidth = window.innerWidth - styleMarginLeft - styleMarginRight;
			let realWidth = Math.min(maxWidth, imagesWidth, windowWidth + 1);

			const forcedWidth = this.selector.getAttribute('data-width-for-maxheight');

			if(forcedWidth !== null) {
				realWidth = Math.min(realWidth, forcedWidth);
			}

			let marginLeft;
			let marginRight;

			if(windowWidth - imagesWidth > 200) {
				// Center figure around the text
				marginLeft = (realWidth - parentWidth) / 2 * -1;
				marginRight = marginLeft;
			} else {
				// Center figure inside the window
				const margin = (- offset + (windowWidth - realWidth) / 2) - 1;
				marginLeft = margin + styleMarginLeft;
				marginRight = margin + styleMarginRight;
			}

			this.selector.style.marginLeft = marginLeft +'px';
			this.selector.style.marginRight = marginRight +'px';
			this.selector.style.width = realWidth +'px';

		} else {

			this.selector.style.marginLeft = '';
			this.selector.style.marginRight = '';
			this.selector.style.width = '';

		}

	};

	getRows(containers) {

		let rows = [];
		let position = 0;

		const global_max_w = this.options.selectedMaxWidth(this.selector);
		const global_max_h = this.options.selectedMaxHeight(this.selector);
		const size_can_exceed_max = this.options.canExceedMax();
		const maxPosition = this.options.maxItems(this.options);

		while(position < containers.length) {

			let available = containers.length - position;
			const take = this.options.lineSize(available);

			let items = [];
			let max_h = 0;

			for(let i = position; i < position + take; i++) {

				const item = containers[i];

				if(maxPosition !== null) {

					if(i >= maxPosition) {
						item.style.display = 'none';
					} else {
						item.style.display = '';
					}

				}

				let w = parseInt(item.getAttribute('data-w'));
				let h = parseInt(item.getAttribute('data-h'));

				if(global_max_w !== null && (w > global_max_w || size_can_exceed_max)) {
					h *= (global_max_w / w);
					w = global_max_w;
				}

				if(h > max_h) {
					max_h = h;
				}

				items.push({
					reference: item,
					w: w,
					h: h
				});

			}

			if(global_max_h !== null && max_h > global_max_h) {
				max_h = global_max_h;
			}

			items.forEach(item => {

				item.norm_w = item.w * (max_h / item.h);
				item.norm_h = max_h;

			});

			rows.push(items);

			position += take;


		}

		return rows;


	};

	computeWidth(selector) {

		const style = getComputedStyle(selector, null);

		let width =
			selector.getBoundingClientRect().width -
			parseFloat(style.getPropertyValue('padding-left').replace(',', '.')) -
			parseFloat(style.getPropertyValue('padding-right').replace(',', '.'));

		if(style.getPropertyValue('border-left-width')) {
			width -= parseFloat(style.getPropertyValue('border-left-width').replace(',', '.'));
		}

		if(style.getPropertyValue('border-right-width')) {
			width -= parseFloat(style.getPropertyValue('border-right-width').replace(',', '.'));
		}

		return width;

	};

	isMobile() {
		return window.matchMedia('(max-width: 575px)').matches;
	}

}