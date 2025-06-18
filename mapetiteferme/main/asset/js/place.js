document.delegateEventListener('autocompleteUpdate', 'input[data-autocomplete-map]', function(e) {

	const lngLat = qs('#'+ this.getAttribute('data-autocomplete-lnglat'));
	lngLat.value = '';

	const container = this.getAttribute('data-autocomplete-map');

	Place.updateMap(container, null);


});

document.delegateEventListener('autocompleteSelect', 'input[data-autocomplete-map]', function(e) {

	const lngLat = qs('#'+ this.getAttribute('data-autocomplete-lnglat'));
	const container = this.getAttribute('data-autocomplete-map');

	if(e.detail.lngLat !== undefined) {
		lngLat.value = JSON.stringify(e.detail.lngLat);
		Place.updateMap(container, e.detail.lngLat);
	} else {
		lngLat.value = null;
		Place.updateMap(container, null);
	}


});

class Place {

	static updateMap(container, lngLat, options) {

		let wrapper = qs('#'+ container);

		if(lngLat !== null) {

			wrapper.style.display = 'block';

			let drawer = new MapboxContainer(container);

			if(drawer.exists()) {

				const mapbox = drawer.get();
				mapbox.setCenter(lngLat);
				mapbox.marker.setLngLat(lngLat);

			} else {

				if(options === undefined) {
					options = {};
				}

				options.center = lngLat;

				if(options.zoom === undefined) {
					options.zoom = 11;
				}

				const mapbox = drawer.create(options);

				mapbox.marker = new mapboxgl.Marker()
					.setLngLat(lngLat)
					.addTo(mapbox);

			}

		} else {
			wrapper.style.display = 'none';
		}

	}

}