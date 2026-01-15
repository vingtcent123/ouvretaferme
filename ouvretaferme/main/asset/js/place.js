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

	static querySiret(target) {

		const wrapper = target.firstParent('[data-wrapper]');

		const siret = target.value.replace(/\s/g, '');

		if(siret.match(/^[0-9]{14}$/) === null) {
			return false;
		}


		new Ajax.Query()
			.url('/main/place:siret?siret='+ siret)
			.method('get')
			.fetch()
			.then((json) => {

				const result = json.result;

				if(result === null) {
					this.querySiretNotFound(wrapper);
				} else {
					this.querySiretFound(wrapper, result);
				}

			});

	}

	static fillSiret(target, prefix) {

		const form = target.firstParent('form');

		form.qs('input[name="legalName"]').value = form.qs('.siret-found .siret-name').innerHTML;
		form.qs('input[name="'+ prefix +'Street1"]').value = form.qs('.siret-found .siret-street1').innerHTML;
		form.qs('input[name="'+ prefix +'Street2"]').value = form.qs('.siret-found .siret-street2').innerHTML;
		form.qs('input[name="'+ prefix +'Postcode"]').value = form.qs('.siret-found .siret-postcode').innerHTML;
		form.qs('input[name="'+ prefix +'City"]').value = form.qs('.siret-found .siret-city').innerHTML;

		form.qs('.siret-found').hide();

	}

	static querySiretFound(wrapper, result) {

		wrapper.qs('.siret-name').innerHTML = result.legalName;
		wrapper.qs('.siret-street1').innerHTML = result.legalStreet1;
		wrapper.qs('.siret-street2').innerHTML = result.legalStreet2;
		wrapper.qs('.siret-postcode').innerHTML = result.legalPostcode;
		wrapper.qs('.siret-city').innerHTML = result.legalCity;

		wrapper.qs('.siret-found').removeHide();
		wrapper.qs('.siret-unknown').hide();

		return false;

	}

	static querySiretNotFound(wrapper) {

		wrapper.qs('.siret-found').hide();
		wrapper.qs('.siret-unknown').removeHide();

		return false;

	}

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