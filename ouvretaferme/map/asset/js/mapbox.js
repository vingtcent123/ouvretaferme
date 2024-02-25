class MapboxContainer {

	static maps = {};

	container = null;

	constructor(container) {

		this.container = container;

		if(mapboxgl.accessToken === null) {
			mapboxgl.accessToken = mapboxToken;
		}

	}

	create(options) {

		options.container = this.container;

		const create = function() {

			if(options.style === undefined) {
				options.style = 'mapbox://styles/mapbox/outdoors-v11';
			}

			options.attributionControl = false;

			MapboxContainer.maps[options.container] = new mapboxgl.Map(options);

		};

		if(MapboxContainer.maps[this.container]) {

			MapboxContainer.maps[this.container].on('remove', create);
			MapboxContainer.maps[this.container].remove();

		} else {
			create();
		}

		return MapboxContainer.maps[options.container];

	}

	exists() {

		if(MapboxContainer.maps[this.container] === undefined) {
			return false;
		}

		const node = qs('#'+ this.container);

		if(node === null) {
			delete MapboxContainer.maps[this.container];
			return false;
		} else if(node.classList.contains('mapboxgl-map') === false) {

			MapboxContainer.maps[this.container].remove();
			delete MapboxContainer.maps[this.container];

			return false;

		}

		return true;

	}

	get() {

		if(MapboxContainer.maps[this.container] === undefined) {
			throw 'Not exists';
		} else {
			return MapboxContainer.maps[this.container];
		}

	}

}
