class SeriesSelector {

	static select(target) {

		if(target.classList.contains('selected')) {

			if(qs('#zone-content.zone-update') === null) {
				Place.scroll(target.dataset.series);
			}

			return;

		} else {

			this.close();

			// TODO reste de l'édition et retour sur Modifier/Supprimer
			qsa('#place-update-length, #place-update-area', node => node.id = null);

			target.classList.add('selected');

			Place.scroll(target.dataset.series);

			qs('#zone-content').classList.remove('zone-update');
			qsa('#zone-content .bed-item-grid:has(.place-grid-series-timeline[data-series="'+ target.dataset.series +'"])', node => node.classList.add('selected'));

		}

	}

	static close() {

		qsa('#zone-content .bed-item-grid.selected', node => node.classList.remove('selected'));
		qs("#series-selector-list .series-selector-cultivation.selected", node => node.classList.remove('selected'));

	}

	static update(target) {

		new Ajax.Query()
			.url(qs('#contact-export-link').getAttribute('data-ajax'))
			.method('get')
			.fetch();

	}

}