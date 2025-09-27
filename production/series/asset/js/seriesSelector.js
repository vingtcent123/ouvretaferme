class SeriesSelector {

	static edit(cultivation) {

		const target = qs('#series-selector-'+ cultivation);

		Place.scroll(target.dataset.series);

	}

	static select(cultivation) {

		const target = qs('#series-selector-'+ cultivation);

		if(target.classList.contains('selected')) {

			if(qs('#zone-content.zone-update') === null) {
				Place.scroll(target.dataset.series);
			}

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

		qs('#zone-content').classList.remove('zone-update');

		// TODO reste de l'édition et retour sur Modifier/Supprimer
		qsa('#place-update-length, #place-update-area', node => node.id = null);

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