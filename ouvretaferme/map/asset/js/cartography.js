class Cartography {

	static instances = {};

	container;
	form;
	mapbox = null;
	season;
	interactive = true;

	draw;
	drawFeature;

	boundsFarm = undefined;
	boundsZones = {};
	boundsPlots = {};

	plotsZones = {};

	popupsZones = {};
	popupsPlots = {};

	selectedZone = null;
	selectedPlot = null;
	zoom = 'farm';

	constructor(container = null, season, interactive, map, options = {}) {

		this.container = container;
		Cartography.instances[container] = this;

		if(map) {

			options.style = 'mapbox://styles/mapbox/satellite-streets-v11';

			if(options.scrollZoom === undefined) {
				options.scrollZoom = false;
			}


			this.mapbox = new MapboxContainer(container).create(options);
			this.mapbox.addControl(new mapboxgl.NavigationControl());
			this.mapbox.addControl(new mapboxgl.FullscreenControl());

			this.form = qs('#'+ container).firstParent('form');

		}

		this.season = season;
		this.interactive = interactive;

	};

	static get(container, callback = undefined) {

		const instance = Cartography.instances[container];

		if(instance !== undefined && callback !== undefined) {
			callback(instance);
		}

		return instance;

	}

	reload() {

		if(this.selectedPlot !== null) {
			this.loadPlot(this.selectedPlot, this.selectedZone);
		} else if(this.selectedZone !== null) {
			this.loadZone(this.selectedZone);
		}

	}

	addZone(zoneId, zoneName, coords, display = true) {

		if(coords === null) {
			return this;
		}

		const zoneBounds = this.extractBoundsFromCoords(coords);
		this.boundsZones[zoneId] = zoneBounds;

		if(this.boundsFarm === undefined) {
			this.boundsFarm = this.extractBoundsFromCoords(coords) /* Problème de référence par rapport à boundsZone (?) */;
		} else {
			this.boundsFarm[0][0] = Math.min(this.boundsFarm[0][0], zoneBounds[0][0]);
			this.boundsFarm[0][1] = Math.max(this.boundsFarm[0][1], zoneBounds[0][1]);
			this.boundsFarm[1][0] = Math.max(this.boundsFarm[1][0], zoneBounds[1][0]);
			this.boundsFarm[1][1] = Math.min(this.boundsFarm[1][1], zoneBounds[1][1]);
		}

		if(display) {

			this.do(() => {

				this.showPolygon('cartography-zone-'+ zoneId, turf.polygon([coords]), 'zone');

				this.addZonePopup(
					zoneId, zoneName, (zoneBounds[0][0] + zoneBounds[1][0]) / 2, zoneBounds[0][1],
					(popup) => this.interactive ? popup.getElement().addEventListener('click', (e) => this.clickZone(zoneId)) : ''
				);

			});
		}

		return this;

	}

	eventZone(zoneId) {

		if(this.interactive) {

			this.mapbox.on('click', 'cartography-zone-'+ zoneId, (e) => {
				if(e.originalEvent.defaultPrevented) {
					return;
				}
				this.clickZone(zoneId);
			});

		}

		return this;

	}

	addZonePopup(zoneId, zoneName, lng, lat, callback) {

		let label = '<h4>'+ zoneName.encode() +'</h4>';

		this.popupsZones[zoneId] = new mapboxgl.Popup({
				closeButton: false,
				closeOnClick: false,
				closeOnMove: false,
				maxWidth: 'auto',
				padding: '1rem'
			})
			.setLngLat([lng, lat])
			.setHTML(label)
			.addClassName('cartography-popup')
			.addClassName('cartography-zone-popup')
			.addTo(this.mapbox);

		if(this.selectedZone === zoneId) {
			this.popupsZones[zoneId].addClassName('selected');
		}

		if(callback !== undefined) {
			callback(this.popupsZones[zoneId]);
		}

	}

	clickZone(zoneId) {

		if(this.mapbox === null) {
			return this.loadZone(zoneId);
		}

		this.fitZoneBounds(zoneId);

		this.loadZone(zoneId);

		return this;

	}

	loadZone(zoneId) {

		// Sélectionne le bon onglet
		qs('#cartography-farm-tabs .tab-item.selected', tab => tab.classList.remove('selected'));
		qs('#cartography-farm-tab-'+ zoneId, tab => {
			tab.classList.add('selected');
			Lime.History.replaceState(tab.dataset.url);
		});

		new Ajax.Query()
			.method('get')
			.url('/map/zone:getCartography?id='+ zoneId +'&season='+ this.season)
			.fetch();

		return this;

	}

	addPlot(plotId, plotName, zoneId, coords, display = true) {

		if(coords === null) {
			return this;
		}

		const plotBounds = this.extractBoundsFromCoords(coords);
		this.boundsPlots[plotId] = plotBounds;
		this.plotsZones[plotId] = zoneId;

		if(display) {

			this.do(() => {

				this.showPolygon('cartography-plot-'+ plotId, turf.polygon([coords]), 'plot');

				this.addPlotPopup(
					plotId, plotName, (plotBounds[0][0] + plotBounds[1][0]) / 2, (plotBounds[0][1] + plotBounds[1][1]) / 2,
					(popup) => this.interactive ? popup.getElement().addEventListener('click', (e) => this.clickPlot(plotId, zoneId)) : ''
				);

			});

		}

		return this;

	}

	eventPlot(plotId, zoneId) {

		if(this.interactive) {

			this.mapbox.on('click', 'cartography-plot-'+ plotId, (e) => {
				e.originalEvent.preventDefault();
				this.clickPlot(plotId, zoneId);
			});

		}

		return this;

	}

	addPlotPopup(plotId, plotName, lng, lat, callback) {

		let label = '<h5>'+ plotName.encode() +'</h5>';

		this.popupsPlots[plotId] = new mapboxgl.Popup({
				closeButton: false,
				closeOnClick: false,
				closeOnMove: false,
				maxWidth: 'auto',
				padding: '1rem'
			})
			.setLngLat([lng, lat])
			.setHTML(label)
			.addClassName('cartography-popup')
			.addClassName('cartography-plot-popup')
			.addTo(this.mapbox);

		if(this.selectedZone === plotId) {
			this.popupsPlots[plotId].addClassName('selected');
		}

		if(callback !== undefined) {
			callback(this.popupsPlots[plotId]);
		}

	}

	clickPlot(plotId, zoneId) {

		if(this.mapbox === null) {
			return this.loadPlot(plotId, zoneId);
		}

		this.fitPlotBounds(plotId);

		this.loadPlot(plotId, zoneId);

		return this;

	}

	loadPlot(plotId, zoneId) {

		new Ajax.Query()
			.method('get')
			.url('/map/zone:getCartography?id='+ zoneId +'&plot='+ plotId +'&season='+ this.season)
			.fetch();

		return this;

	}

	extractBoundsFromCoords(coords) {

		let lng1 = coords[0][0], lng2 = coords[0][0], lat1 = coords[0][1], lat2 = coords[0][1];

		for(let i = 1; i < coords.length; i++) {

			lng1 = Math.min(lng1, coords[i][0]);
			lat1 = Math.max(lat1, coords[i][1]);

			lng2 = Math.max(lng2, coords[i][0]);
			lat2 = Math.min(lat2, coords[i][1]);

		}

		return [[lng1, lat1], [lng2, lat2]];
	}

	clickFarm() {

		this.fitFarmBounds();

		qs('#cartography-farm-tabs .tab-item.selected', tab => tab.classList.remove('selected'));
		qs('#cartography-zone').innerHTML = '';

		const location = document.location.href.removeArgument('zone');
		Lime.History.replaceState(location);

	}

	unzoom() {

		if(this.countZones() <= 1) {
			return;
		}

		switch(this.zoom) {

			case 'farm' :
				break;

			case 'plot' :
				this.clickZone(this.selectedZone);
				break;

			case 'zone' :
				this.clickFarm();
				break;

		}

	}

	fitFarmBounds(options) {
		return this.fitBounds(this.boundsFarm, 'farm', null, options);
	}

	fitZoneBounds(zoneId, options) {
		if(this.boundsZones[zoneId] === undefined) {
			return this.fitFarmBounds(options);
		} else {
			return this.fitBounds(this.boundsZones[zoneId], 'zone', zoneId, options);
		}
	}

	fitPlotBounds(plotId, options) {
		if(this.boundsPlots[plotId] === undefined) {
			return this.fitZoneBounds(this.plotsZones[plotId], options);
		} else {
			return this.fitBounds(this.boundsPlots[plotId], 'plot', plotId, options);
		}
	}

	fitBounds(bounds, zoom, zoomId, options = {}) {

		if(bounds === undefined) {
			return this;
		}

		switch(zoom) {

			case 'farm' :
				options = {...{
					duration: 1000,
					padding: 20
				}, ...options};
				break;

			case 'zone' :
			case 'plot' :
				options = {...{
					duration: 1000,
					padding: 40
				}, ...options};
				break;

		}

		this.mapbox.fitBounds(bounds, options);

		qs('#'+ this.container).dataset.zoom = zoom;

		if(this.interactive && this.countZones() > 1) {

			qsa('.cartography-zone-popup.selected', node => node.classList.remove('selected'));

			if(zoom === 'farm') {

				qs('#cartography-farm-zoom-out').classList.add('hide');

				this.selectFarm();

			} else if(zoom === 'zone') {

				qs('#cartography-farm-zoom-out').classList.remove('hide');

				this.selectZone(zoomId);

			} else if(zoom === 'plot') {

				qs('#cartography-farm-zoom-out').classList.remove('hide');

				this.selectPlot(zoomId);

			}

		}

		this.zoom = zoom;

		return this;

	}

	selectFarm() {

		this.selectedPlot = null;
		this.selectedZone = null;

		Object.entries(this.popupsZones).forEach(([key, node]) => node.removeClassName('selected'));

	}

	selectZone(zoneId) {

		this.selectedPlot = null;
		this.selectedZone = zoneId;

		Object.entries(this.popupsZones).forEach(([key, node]) => {
			if(parseInt(key) === zoneId) {
				node.addClassName('selected');
			} else {
				node.removeClassName('selected');
			}
		});

		return this;

	}

	selectPlot(plotId) {

		this.selectedPlot = plotId;
		this.selectedZone = this.plotsZones[plotId];

		return this;

	}

	countZones() {
		return Object.entries(this.boundsZones).length;
	}

	drawShape() {

		this.draw = new MapboxDraw({
			displayControlsDefault: false,
			defaultMode: 'simple_select',
			featureId: 'polygon',
			modes: {
				rotate: MapboxDrawRotate,
				...MapboxDraw.modes
			},
		});

		this.mapbox.addControl(this.draw);

		const drawEvent = (e) => {

			let data = this.draw.getAll();

			if(data.features.length > 0) {
				this.drawFeature = data.features[0].id;
				this.savePolygon(data.features[0]);
			} else {
				this.drawFeature = null;
				this.deletePolygon();
			}

		}

		this.mapbox.on('draw.create', (e) => {
			drawEvent(e);
			qs('#'+ this.container +'-actions').classList.remove('hide');
		});
		this.mapbox.on('draw.delete', drawEvent);
		this.mapbox.on('draw.update', drawEvent);

		return this;

	}

	showDrawSelector() {

		const polygonShapes = qs('#mapbox-polygon-shapes');

		if(polygonShapes) {

			polygonShapes.classList.remove('hide');

		} else {

			this.draw.changeMode('draw_polygon', {
				featureId: 'polygon'
			});

		}

	}

	hideDrawSelector() {

		const polygonShapes = qs('#mapbox-polygon-shapes');

		if(polygonShapes) {
			polygonShapes.classList.add('hide');
			Lime.Dropdown.closeAll();
		}

	}

	drawRectangle(node) {

		const form = node.firstParent('.mapbox-polygon-shape-form');

		const length = form.qs('[name="length"]');
		const width = form.qs('[name="width"]');

		if(length.reportValidity() === false ||  width.reportValidity() === false) {
			return;
		}

		let errors = 0;

		if(length.value === '') {
			length.firstParent('.form-group').classList.add('form-error-wrapper');
			errors++;
		} else {
			length.firstParent('.form-group').classList.remove('form-error-wrapper');
		}

		if(width.value === '') {
			width.firstParent('.form-group').classList.add('form-error-wrapper');
			errors++;
		} else {
			width.firstParent('.form-group').classList.remove('form-error-wrapper');
		}

		if(errors > 0) {
			return;
		}

		const wLng = parseInt(length.value);
		const wLat = parseInt(width.value);

		const mapCenter = this.mapbox.getBounds().getCenter();
		const mLng =  1 / ((Math.PI / 180) * 6378 * Math.cos(mapCenter.lat * Math.PI / 180) * 1000);
		const mLat = 1 / ((Math.PI / 180) * 6378 * 1000);


		const coords = [];
		coords.push([mapCenter.lng - mLng * wLng / 2, mapCenter.lat - mLat * wLat / 2]); // NO
		coords.push([mapCenter.lng + mLng * wLng / 2, mapCenter.lat - mLat * wLat / 2]); // NE
		coords.push([mapCenter.lng + mLng * wLng / 2, mapCenter.lat + mLat * wLat / 2]); // SE
		coords.push([mapCenter.lng - mLng * wLng / 2, mapCenter.lat + mLat * wLat / 2]); // SW
		coords.push(coords[0]);

		this.drawPolygon(coords);

	}

	drawPolygon(coords) {

		this.hideDrawSelector();

		this.do(() => {

			if(coords) {

				const feature = {
					'id': 'polygon',
					'type': 'Feature',
					'geometry': {
						'type': 'Polygon',
						'coordinates': [coords]
					},
					'properties' : {}
				};

				this.draw.add(feature);

				this.drawFeature = 'polygon';

				this.setPolygonMode('draw');

				qs('#'+ this.container +'-actions').classList.remove('hide');

				this.savePolygon(feature);

			} else {

				this.draw.changeMode('draw_polygon', {
					featureId: 'polygon'
				});

			}

		})

	}

	savePolygon(feature) {

		let area = turf.area(turf.polygon(feature.geometry.coordinates));
		let coordinates = feature.geometry.coordinates[0];

		if(this.form) {
			this.form.qs('[name="area"]').value = Math.round(area);
			this.form.qs('[name="coordinates"]').value = JSON.stringify(coordinates);
		}

	}

	setPolygonMode(mode) {

		qs('#'+ this.container +'-actions .selected', action => action.classList.remove('selected'));

		switch(mode) {

			case 'draw' :
				this.draw.changeMode('direct_select', {
					featureId: this.drawFeature
				});
				qs('#'+ this.container +'-actions .mapbox-polygon-action-draw').classList.add('selected');
				break;

			case 'rotate' :
				this.draw.changeMode('rotate', {
					featureId: this.drawFeature
				});
				qs('#'+ this.container +'-actions .mapbox-polygon-action-rotate').classList.add('selected');
				break;

		}

	}

	deletePolygon() {

		this.draw.deleteAll();

		qs('#'+ this.container +'-actions').classList.add('hide');
		qs('#'+ this.container +'-actions .mapbox-polygon-action').classList.remove('selected');

		if(this.form) {
			this.form.qs('[name="area"]').value = '';
			this.form.qs('[name="coordinates"]').value = '';
		}

		this.showDrawSelector();

	}

	getLineColor(theme) {

		switch(theme) {

			case 'zone' :
				return '#505075';

			case 'plot' :
				return '#93b96d';

			default :
				throw 'Invalid theme';

		}

	}

	showPolygon(id, feature, theme) {

		this.mapbox.addSource(id, this.getGeoJson(feature));

		let paint, paintLine;

		switch(theme) {

			case 'zone' :
				paint =  {
					'fill-color': '#99b',
					'fill-opacity': 0.66
				};
				break;

			case 'plot' :
				paint =  {
					'fill-color': '#93b96d',
					'fill-opacity': 0.4
				};
				break;

			default :
				throw 'Invalid theme';

		}

		this.mapbox.addLayer({
			'id': id,
			'type': 'fill',
			'source': id,
			'layout': {},
			'paint': paint
		});

		this.mapbox.addLayer({
			'id': id +'-line',
			'type': 'line',
			'source': id,
			'layout': {},
			'paint': {
				'line-color': this.getLineColor(theme),
				'line-width': 2
			}
		});

	}

	do(callback) {

		if(this.mapbox.loaded()) {
			callback(this);
		} else {

			this.mapbox.on('load', () => {
				callback(this);
			});

		}

		return this;

	}

	getGeoJson(data) {

		return {
			'type': 'geojson',
			'data': data
		};

	}

	/* Beds drawing */

	bedsTheme = null;
	bedsCoords = [];
	bedsIds = [];
	bedsList = [];
	bedsPopupLength = null;

	hoverBedCoords = null;

	setBedsTheme(theme) {
		this.bedsTheme = theme;
		return this;
	}

	setBedsList(list) {
		this.bedsList = list;
		return this;
	}

	drawBeds(coords) {

		this.do(() => {

			const features = [];

			coords.forEach((coords, key) => {
				features.push(turf.point(coords, {
					id: key
				}));
			});

			this.showBedsPoints('cartography-beds', turf.featureCollection(features));

			this.drawBedsExistingPoints(coords);
			this.initBedsDrawing('cartography-beds-draw');
			this.initBedsSources('cartography-beds-draw');

		});

	}

	drawBedsExistingPoints(coords) {

		/* Gestion de la position du curseur au-dessus d'un point du polygone */
		this.mapbox.on('mouseenter', 'cartography-beds', (e) => {

			if(this.bedsCoords.length >= 3) {
				return;
			}

			this.mapbox.getCanvas().style.cursor = 'pointer';

			const featureId = e.features[0].properties.id;

			this.hoverBedCoords = coords[featureId];

			this.highlightBedsPoints([...this.bedsIds, featureId].unique());

			this.mapbox.triggerRepaint();

		});

		let selectedFeature = null;

		this.mapbox.on('click', 'cartography-beds', (e) => {

			if(this.bedsCoords.length >= 3) {
				return;
			}

			selectedFeature = e.features[0].properties.id;

		});

		this.mapbox.on('click', (e) => {

			if(this.bedsCoords.length >= 3) {
				return;
			}

			if(selectedFeature !== null) {
				this.addBedCoords(coords[selectedFeature], selectedFeature);
				selectedFeature = null;
			}  else {
				this.addBedCoords([e.lngLat.lng, e.lngLat.lat], null);
			}

			if(this.bedsCoords.length === 3) {
				this.mapbox.getCanvas().style.cursor = '';
			}

		});

		this.mapbox.on('mouseleave', 'cartography-beds', (e) => {

			if(this.bedsCoords.length >= 3) {
				return;
			}

			this.mapbox.getCanvas().style.cursor = 'crosshair';

			this.hoverBedCoords = null;

			this.highlightBedsPoints(this.bedsIds);

			this.mapbox.triggerRepaint();

		});

	}

	initBedsDrawing(source) {

		this.mapbox.getCanvas().style.cursor = 'crosshair';

		this.mapbox.on('mousemove', (e) => {

			if(this.bedsCoords.length === 0 || this.bedsCoords.length >= 3) {
				return;
			}

			this.drawBedsLine(source, this.bedsList, [
				...this.bedsCoords,
				this.hoverBedCoords ? this.hoverBedCoords : [e.lngLat.lng, e.lngLat.lat]
			]);

		});

	}

	initBedsSources(source, fillOpacity = null) {
		
		if(fillOpacity === null) {
			fillOpacity = 0.66;
		}

		/* Mouvement de la ligne sur le mouvement du curseur */
		this.mapbox.addSource(source, this.getGeoJson({
			'type': 'Feature'
		}));
		this.mapbox.addLayer({
			'id': source,
			'type': 'line',
			'source': source,
			'paint': {
				'line-color': '#FFF',
				'line-width': 5
			}
		});

		this.mapbox.addSource(source +'-polygon', this.getGeoJson({
			'type': 'FeatureCollection',
			'features': []
		}));
		this.mapbox.addLayer({
			'id': source +'-polygon',
			'type': 'fill',
			'source': source +'-polygon',
			'paint': {
				'fill-color': '#ffffff',
				'fill-opacity': fillOpacity
			}
		});

		this.mapbox.addSource(source +'-label', this.getGeoJson({
			'type': 'FeatureCollection',
			'features': []
		}));
		this.mapbox.addLayer({
			'id': source +'-label',
			'type': 'symbol',
			'source': source +'-label',
			'layout': {
				'text-field': ['get', 'title'],
				'text-size': [
					'interpolate',
					['linear'],
					['zoom'],
					17,
					9,
					19,
					11
				],
				'text-allow-overlap' : true
			},
			'paint': {
				'text-color': '#000',
				'text-opacity': [
					'interpolate',
					['linear'],
					['zoom'],
					17,
					0,
					18,
					1
				]
			}
		});

		return this;

	}

	addBeds(plotFqn, bedsList, coords, fillOpacity = null) {

		return this.do(() => {

			const source = 'cartography-beds-' + plotFqn;

			this.initBedsSources(source, fillOpacity);
			this.drawBedsList(source, bedsList, coords);

		});

	}

	drawBedsLine(source, bedsList, coords) {

		if(coords.length < 2) {
			return;
		}

		const lineString = turf.lineString(coords);
		this.mapbox.getSource(source).setData(lineString);

		this.drawBedsLength(coords);

		if(coords.length > 2) {
			this.drawBedsList(source, bedsList, coords);
		}

	}

	drawBedsList(source, bedsList, coords) {

		const bedsLine = coords.slice(0, 2);
		const bedsLineWidth = turf.distance(...bedsLine);
		const bedsLineBearing = turf.bearing(...bedsLine);

		const bedsDirection = coords.slice(1, 3);
		const bedsDirectionBearing = turf.bearing(...bedsDirection);

		const bedsRealWidth = bedsList.reduce((total, bed) => total + bed.width / 100 / 1000, 0);

		const bedsAlleyWidth = (bedsList.length > 1) ? (bedsLineWidth - bedsRealWidth) / (bedsList.length - 1) : null;

		const polygonsList = [];
		const labelsList = [];

		let currentLinePosition = 0;

		this.mapbox.setLayoutProperty(
			source +'-label',
			'text-rotate',
			((bedsLineBearing < -90) ? bedsLineBearing + 180 : ((bedsLineBearing > 75) ? bedsLineBearing - 180 : bedsLineBearing))
		);

		bedsList.forEach((bed, position) => {

			const bedLength = bed.length / 1000;
			const bedWidth = bed.width / 100 / 1000;

			const p1 = turf.destination(coords[0], currentLinePosition, bedsLineBearing).geometry.coordinates;

			currentLinePosition += bedWidth;

			const p2 = turf.destination(coords[0], currentLinePosition, bedsLineBearing).geometry.coordinates;

			currentLinePosition += bedsAlleyWidth;

			const p3 = turf.destination(p2, bedLength, bedsDirectionBearing).geometry.coordinates;
			const p4 = turf.destination(p1, bedLength, bedsDirectionBearing).geometry.coordinates;

			const polygon = turf.polygon([[p1, p2, p3, p4, p1]]);
			polygonsList.push(polygon);

			const point = turf.centroid(polygon, {
				properties: {
					title: bed.name
				}
			});


			labelsList.push(point);

		});

		const polygons = turf.featureCollection(polygonsList);
		this.mapbox.getSource(source +'-polygon').setData(polygons);

		const labels = turf.featureCollection(labelsList);
		this.mapbox.getSource(source +'-label').setData(labels);

	}

	drawBedsLength(coords) {

		if(coords.length < 2) {
			return;
		}

		const points = turf.points(coords.slice(0, 2));

		const center = turf.center(points).geometry.coordinates;
		const distance = Math.ceil(turf.distance(...points.features) * 1000) +' m';

		if(this.bedsPopupLength === null) {

			this.bedsPopupLength = new mapboxgl.Popup({
					closeButton: false,
					closeOnClick: false,
					closeOnMove: false,
					maxWidth: 'auto',
					padding: '1rem'
				})
				.setLngLat(center)
				.setHTML(distance)
				.addClassName('cartography-beds-popup')
				.addTo(this.mapbox);

		} else {
			this.bedsPopupLength
				.setLngLat(center)
				.setHTML(distance);
		}


	}

	cleanBedsDrawing(source) {

		this.mapbox.removeLayer(source);
		this.mapbox.removeSource(source);

		this.mapbox.removeLayer(source +'-polygon');
		this.mapbox.removeSource(source +'-polygon');

		this.mapbox.removeLayer(source +'-label');
		this.mapbox.removeSource(source +'-label');

		if(this.bedsPopupLength !== null) {
			this.bedsPopupLength.remove();
			this.bedsPopupLength = null;
		}

	}

	addBedCoords(lngLat, id) {

		this.bedsCoords.push(lngLat);
		this.bedsIds.push(id);

		this.highlightBedsPoints(this.bedsIds);
		this.drawBedsLine('cartography-beds-draw', this.bedsList, this.bedsCoords);

		qs('#'+ this.container +'-actions').classList.remove('hide');

		if(this.bedsCoords.length === 3) {

			if(this.form) {
				this.form.qs('[name="coordinates"]').value = JSON.stringify(this.bedsCoords);
			}

		}

	}

	showBedsPoints(id, featureCollection) {

		this.mapbox.addSource(id, this.getGeoJson(featureCollection));

		this.mapbox.addLayer({
			'id': id,
			'type': 'circle',
			'source': id,
			'paint': {
				'circle-radius': 12,
				'circle-color': this.getLineColor(this.bedsTheme)
			}
		});

	}

	deleteBeds() {

		this.bedsCoords = [];
		this.bedsIds = [];
		this.hoverBedCoords = null;

		this.highlightBedsPoints(this.bedsIds);
		this.cleanBedsDrawing('cartography-beds-draw');

		qs('#'+ this.container +'-actions').classList.add('hide');

		if(this.form) {
			this.form.qs('[name="coordinates"]').value = '';
		}

		this.initBedsDrawing('cartography-beds-draw');
		this.initBedsSources('cartography-beds-draw');

	}

	highlightBedsPoints(selectedIds) {

		selectedIds = selectedIds.filter(value => value != null);

		if(selectedIds.length > 0) {

			this.mapbox.setPaintProperty(
				'cartography-beds',
				'circle-color',
				['match', ['get', 'id'], selectedIds, '#fff', this.getLineColor(this.bedsTheme)]
			);

		} else {

			this.mapbox.setPaintProperty(
				'cartography-beds',
				'circle-color',
				this.getLineColor(this.bedsTheme)
			);

		}

	}

}