class SeriesSelector {

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