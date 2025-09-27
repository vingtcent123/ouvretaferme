class SeriesSelector {

	static mode = 'all';

	static restoreFilter() {

		qs('#series-selector-filter').value = this.mode;
		this.filter();

	}

	static filter() {

		this.mode = qs('#series-selector-filter').value;

		qsa('.series-selector-plant', plant => {

			let hide = true;

			plant.qsa('.series-selector-cultivation', cultivation => {

				const value = parseInt(cultivation.qs('.series-selector-value').innerHTML);

				switch(this.mode) {

					case 'all' :
						cultivation.removeHide();
						hide = false;
						break;

					case 'zero' :

						if(
							cultivation.dataset.status !== 'closed' &&
							value === 0
						) {
							cultivation.removeHide();
							hide = false;
						} else {
							cultivation.hide();
						}

						break;

					case 'gap' :

						const target = cultivation.qs('.series-selector-place').dataset.valueTarget;

						if(
							cultivation.dataset.status !== 'closed' &&
							target !== '' &&
							value < parseInt(target)
						) {
							cultivation.removeHide();
							hide = false;
						} else {
							cultivation.hide();
						}

						break;

				}

			});

			if(hide) {
				plant.hide();
			} else {
				plant.removeHide();
			}

		});

	}

	static edit(target) {

		const cultivation = target.dataset.cultivation;

		new Ajax.Query()
			.url('/series/place:update?cultivation='+ cultivation)
			.method('get')
			.fetch()
			.then((json) => {

				qs('#zone-container').renderOuter(json.plan);
				qs('#zone-form-search').renderInner(json.search);

				const selector = qs('#series-selector-'+ cultivation);

				document.body.classList.add('bed-updating');
				qs('#place-update [name="cultivation"]').value = cultivation;

				qs('#place-update-value', node => node.removeAttribute('id'));

				selector.qs('.series-selector-value').id = 'place-update-value';

				Place.scroll(selector.dataset.series);

			});

	}

	static select(cultivation) {

		const target = qs('#series-selector-'+ cultivation);

		if(target.classList.contains('selected')) {
			return;
		} else {

			this.deselect();

			target.classList.add('selected');

			Place.scroll(target.dataset.series);

			qsa('#zone-content .bed-item-grid:has(.place-grid-series-timeline[data-series="'+ target.dataset.series +'"])', node => node.classList.add('selected'));

		}

	}

	static deselect() {

		qsa('#zone-content .bed-item-grid.selected', node => node.classList.remove('selected'));
		qs("#series-selector-list .series-selector-cultivation.selected", node => node.classList.remove('selected'));

		document.body.classList.remove('bed-updating');

	}

	static show() {

		const style =  document.createElement('style');
		style.id = 'series-selector-style';
		style.innerHTML = ':root { --nav-width: 25rem; }';

		qs('#series-selector').append(style);

		qs('#series-selector').removeHide();

	}

	static close() {

		this.deselect();

		qs('#series-selector-style').remove();
		qs('#series-selector').hide();

	}

	static update(target) {

		new Ajax.Query()
			.url(qs('#contact-export-link').getAttribute('data-ajax'))
			.method('get')
			.fetch();

	}

}