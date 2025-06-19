var MapboxDrawRotate = {};

MapboxDrawRotate.onSetup = function(opts) {
	return {
		move: null
	};
};

MapboxDrawRotate.onMouseDown = MapboxDrawRotate.onTouchStart = function(state, e) {

	if(e.featureTarget) {

		e.target['dragPan'].disable();

		state.move = this._ctx.api.get(e.featureTarget.properties.id);
		state.center = turf.centroid(state.move).geometry.coordinates;
		state.position = [e.lngLat.lng, e.lngLat.lat];

	}

	return state;

}

MapboxDrawRotate.onDrag = MapboxDrawRotate.onTouchMove = function(state, e) {

	if(state.move === null) {
		return;
	}

	const originalBearing = turf.bearing(state.center, state.position);
	const newPosition = [e.lngLat.lng, e.lngLat.lat];
	const newBearing = turf.bearing(state.center, newPosition);

	if(Math.abs(originalBearing - newBearing) < 1) {
		return;
	}

	state.position = newPosition;

	const angle = (originalBearing < newBearing) ? 2 : -2;

	var turfPolygon = turf.polygon(state.move.geometry.coordinates);
	var rotatedPoly = turf.transformRotate(turfPolygon, angle);
	const coords = turf.getCoords(rotatedPoly);

	var newFeature = state.move;
	newFeature.geometry.coordinates = coords;
	this._ctx.api.add(newFeature);

};

MapboxDrawRotate.onMouseUp = MapboxDrawRotate.onTouchEnd = function(state, e) {

	if(state.move === null) {
		return;
	}

	if(e.featureTarget) {
		e.target['dragPan'].enable();
	}

	const containerId = e.target.getContainer().id;
	Cartography.get(containerId).savePolygon(state.move);

	state.move = null;

	return state;

};

MapboxDrawRotate.toDisplayFeatures = function(state, geojson, display) {
  display(geojson);
};