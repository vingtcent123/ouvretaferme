class SeriesSelector {

	static select(target) {

		if(target.classList.contains('selected')) {
			return;
		} else {

			this.close();

			target.classList.add('selected');

			Place.scroll(target.dataset.series);

			qsa('#zone-content .bed-item-grid:has(.place-grid-series-timeline[data-series="'+ target.dataset.series +'"])', node => node.classList.add('selected'));

		}

	}

	static close() {

		qsa('#zone-content .bed-item-grid.selected', node => node.classList.remove('selected'));
		qs("#series-selector-list .series-selector-cultivation.selected", node => node.classList.remove('selected'));

	}

}