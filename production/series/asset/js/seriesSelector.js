class SeriesSelector {

	static select(target) {

		if(target.classList.contains('selected')) {
			return;
		} else {

			this.close();

			target.classList.add('selected');

			const location = document.location.href.setArgument('selector', target.dataset.series);
			Lime.History.replaceState(location)

		}

	}

	static close() {

		qs("#series-selector-list .series-selector-cultivation.selected", node => node.classList.remove('selected'));

	}

}