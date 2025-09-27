document.delegateEventListener('input', '#place-update [name="beds[]"]', function() {
	Place.selectBed(this);
});

document.delegateEventListener('input', '#place-update [name^="sizes"]', function() {
	Place.updateSelected();
});


class Place {

	static toggleSelection(target) {

		CheckboxField.all(target.firstParent('.plot-wrapper, .zone-wrapper'), target.checked, '.bed-item-grid:not(.bed-item-fill) [name^="beds[]"]', (bed) => this.selectBed(bed));

	}

	static search() {

		const wrapper = qs('#place-search');

		this.updateSearch({
			mode: wrapper.qs('[name="mode"]').value,
			width: wrapper.qs('[name="width"]')?.value === '1' ? true : false,
			free: parseInt(wrapper.qs('[name="free"]').value),
			rotation: parseInt(wrapper.qs('[name="rotation"]').value)
		});

	}

	static scroll(series) {

		// Placement du scroll pour centrer la série
		qs('body:not(.bed-updating) .place-grid-series-timeline[data-series="'+ series +'"], body.bed-updating [name="beds[]"]:checked', bed => {

			bed.scrollIntoView({
				block: 'center',
				inline: 'center'
			});

		});

	}

	static updateSearch(query) {

		const searchMode = query.mode || '';
		const searchWidth = query.width || false;
		const searchFree = query.free || 0;
		const searchRotation = query.rotation || 0;
		const searchSeries = query.series || null;

		qsa('.zone-wrapper', zoneWrapper => {

			let zoneHide = 1;

			zoneWrapper.qsa('.plot-wrapper', plotWrapper => {

				let plotHide = 1;

				plotWrapper.qsa('.bed-item-grid', bed => {

					let bedHide = 0;

					if(searchSeries !== null) {

						if(bed.qs('.place-grid-series-timeline[data-series="'+ searchSeries +'"]') === null) {
							bedHide = 1;
						}

					}

					switch(searchFree) {

						case 0 :
							break;

						case 100 :
							if(bed.dataset.free !== '0') {
								bedHide = 1;
							}
							break;

						case 1 :
							if(bed.dataset.free !== '0' && bed.dataset.free !== '1') {
								bedHide = 1;
							}
						case 2 :
							if(bed.dataset.free !== '0' && bed.dataset.free !== '1' && bed.dataset.free !== '2') {
								bedHide = 1;
							}
							break;

					}

					switch(searchMode) {

						case '' :
							break;

						case 'open-field' :
							if(bed.dataset.greenhouse === '1') {
								bedHide = 1;
							}
							break;

						case 'greenhouse' :
							if(bed.dataset.greenhouse === '0') {
								bedHide = 1;
							}
							break;

					}

					if(
						searchWidth === true &&
						bed.dataset.sameWidth === '0'
					) {
						bedHide = 1;
					}

					if(
						searchRotation > 0 &&
						bed.dataset.rotation !== ''
					) {

						const rotation = parseInt(bed.dataset.rotation);

						if(searchRotation > rotation) {
							bedHide = 1;
						}

					}

					if(bedHide === 0) {
						zoneHide = 0;
						plotHide = 0;
					}

					bed.dataset.hide = bedHide;

				});

				plotWrapper.dataset.hide = plotHide;

			});

			zoneWrapper.dataset.hide = zoneHide;

		});

	}

	static selectBed(target) {

		Place.updateSelected();

		// Présence d'onglets
		const zoneWrapper = target.firstParent('.zone-wrapper');
		const zoneCount = qs('#zone-count-'+ zoneWrapper.dataset.zone);

		if(zoneCount) {

			const beds = zoneWrapper.qsa('[name="beds[]"]:checked').length;
			zoneCount.innerHTML = (beds > 0) ? beds : '';

		}

	}

	static submitUpdate() {
		submitAjaxForm(qs("#place-update"));
	}

	static updateSelected() {

		const target = qs('#place-update-value');

		if(target === null) {
			return;
		}

		const form = qs('#place-update');

		let total = 0;
		form.qsa('div.bed-item-grid:has([name^="beds"]:checked) [name^="sizes"], div.zone-wrapper:has(input.zone-title-fill:checked) .bed-item-fill-zone [name^="sizes"], div.plot-wrapper:has(input.plot-title-fill:checked) .bed-item-fill-plot [name^="sizes"]', (node) => total += parseInt(node.value || 0));

		target.innerHTML = total;

	}

}