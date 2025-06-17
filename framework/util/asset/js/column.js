window.addEventListener('resize', () => {
	ColumnLime.resize();
});

class ColumnLime {

	static resize() {

		qsa('.columns', columns => {

			const nItems = columns.qsa('.column-item').length;
			const conf = ColumnLime.getConf(columns);

			const newColumns = Math.min(conf.columns, nItems);
			const currentColumns = columns.childNodes.filter('.column').length;

			if(newColumns !== currentColumns) {

				for(let i = 0; i < nItems; i++) {
					columns.insertAdjacentElement('beforeend', columns.qs('[data-column-item="'+ i +'"]'));
				}

				this.reorganizeAll();

			}

		});


	};

	static reorganizeAll() {

		qsa('.columns:not(.columns-calc)', columns => {
			this.reorganize(columns)
		});

	}

	static reorganize(columns) {

		const newItems = columns.childNodes.filter('.column-item');

		const nItems = columns.qsa('.column-item').length;
		const nUnbalanced = newItems.length;
		const nBalanced = nItems - nUnbalanced;

		if(nUnbalanced === 0) { // Nothing to do
			return;
		}

		if(nItems === 0) {
			columns.childNodes.filter('.column').forEach(column => column.remove());
			return;
		}

		columns.classList.add('columns-calc');

		const conf = this.getConf(columns);

		const newColumns = Math.min(conf.columns, nItems);
		const currentColumns = columns.childNodes.filter('.column').length;

		// Add new columns
		if(newColumns > currentColumns) {

			for(let i = currentColumns; i < newColumns; i++) {
				columns.insertAdjacentHTML('beforeend', '<div class="column" data-column="'+ i +'"></div>');
			}

		}

		if(conf.maxLines !== null) {

			const maxItemsForLines = conf.maxLines * newColumns;

			if(conf.maxItems !== null) {
				conf.maxItems = Math.min(maxItemsForLines, conf.maxItems);
			} else {
				conf.maxItems = maxItemsForLines;
			}

		}

		if(conf.maxItems !== null) {
			conf.maxItems = Math.min(nItems, conf.maxItems);
		}

		newItems.forEach((item, key) => {

			const position = key + nBalanced;

			this.apply(conf.order)(columns, item, newColumns, position, nItems, conf);

			const isFullLine = ((((position / newColumns) >> 0) + 1) * newColumns <= conf.maxItems);

			if(
				(conf.maxItems !== null && position >= conf.maxItems) ||
				(conf.requiresFullLines === true && isFullLine === false)
			) {
				item.style.display = 'none';
			} else {
				item.style.display = '';
			}

			if(item.matches('[data-column-item]') === false) {
				item.setAttribute('data-column-item', position);
			}

		});

		// Remove unused columns
		if(newColumns < currentColumns) {

			for(let i = newColumns; i < currentColumns; i++) {
				columns.qs('[data-column="'+ i +'"]').remove();
			}

		}

		columns.classList.remove('columns-calc');

	};

	static apply(order) {

		switch(order) {

			case 'size' :

				return function(columns, item, newColumns, position, elements, conf) {

					if(conf.sizes === undefined) {

						conf.sizes = [];

						for(let i = 0; i < newColumns; i++) {

							const column = columns.qs('[data-column="'+ i +'"]');

							conf.sizes[i] = parseFloat(column.offsetHeight) / parseFloat(column.offsetWidth);
						}

					}

					let minValue = 9999999;
					let minColumn = null;

					for(let i = 0; i < newColumns; i++) {

						if(conf.sizes[i] < minValue) {
							minValue = conf.sizes[i];
							minColumn = i;
						}

					}

					columns.qs('[data-column="'+ minColumn +'"]').insertAdjacentElement('beforeend', item);

					conf.sizes[minColumn] += parseFloat(item.getAttribute('data-size-ratio'));

				};

			case 'row' :

				return function(columns, item, newColumns, position) {

					if(position < newColumns) {
						columns.qs('[data-column="'+ position +'"]').insertAdjacentElement('afterbegin', item);

					} else {
						const column = position % newColumns;
						columns.qs('[data-column="'+ column +'"]').insertAdjacentElement('beforeend', item);
					}

				}

			case 'column' :
				return function(columns, item, newColumns, position, elements) {

					const column = Math.floor(position / elements * newColumns);
					columns.qs('[data-column="'+ column +'"]').insertAdjacentElement('beforeend', item);

				};

		}

	};

	static getConf(container) {

		const conf = {
			requiresFullLines: container.hasAttribute('data-full-lines') ? (parseInt(container.getAttribute('data-full-lines')) === 1) : false,
			maxItems: container.hasAttribute('data-max-items') ? parseInt(container.getAttribute('data-max-items')) : null,
			maxLines: container.hasAttribute('data-max-lines') ? parseInt(container.getAttribute('data-max-lines')) : null,
			order: container.hasAttribute('data-order') ? container.getAttribute('data-order') : 'row'
		};

		const computedStyle = window.getComputedStyle(container, ':before').getPropertyValue('content') || window.getComputedStyle(container, null).getPropertyValue('content');
		const computedColumns = computedStyle.slice(1, -1).replace(/\\(.)/mg, "$1");

		if(isNaN(computedColumns)) {
			throw "Missing number of columns";
		} else {
			conf.columns = parseInt(computedColumns);
		}

		return conf;

	}

};
