document.delegateEventListener('input', '#place-update [name="beds[]"]', function() {
	Place.selectBed(this);
});

document.delegateEventListener('input', '#place-update [name^="sizes"]', function() {
	Place.updateSelected();
});


class Place {

	static clickSeries(target) {

		if(target.classList.contains('selected')) {
			return true;
		}

		qsa('.place-grid-series-timeline.selected', node => node.classList.remove('selected'));
		target.classList.add('selected');

		return false;

	}

	static toggleSelection(target) {

		CheckboxField.all(target, '[name^="beds[]"]', (bed) => this.selectBed(bed), '.place-grid-container');

	}

	static selectBed(target) {

		let wrapper = target.firstParent('div.place-grid');

		if(target.checked) {
			wrapper.classList.add('selected');
		} else {
			wrapper.classList.remove('selected');
		}

		Place.updateSelected();

		// Présence d'onglets
		if(qs('#place-grid-wrapper')) {

			const panel = target.firstParent('.tab-panel');
			const beds = panel.qsa('[name="beds[]"]:checked').length;
			const zone = panel.dataset.tab;

			qs('#place-grid-wrapper [data-tab="'+ zone +'"] .place-tab-beds').innerHTML = (beds > 0) ? '('+ beds +')' : '';

		}

	}

	static submitUpdate() {
		submitAjaxForm(qs("#place-update"));
	}

	static updateSelected() {

		const area = qs('#place-update-area');
		const length = qs('#place-update-length');

		if(area === null || length === null) {
			return;
		}

		const form = qs('#place-update');

		let total = 0;
		form.qsa('div.place-grid.selected [name^="sizes"]', (node) => total += parseInt(node.value || 0));

		if(length) {
			length.innerHTML = total;
		}

		if(area) {
			area.innerHTML = total;
		}

	}

}