document.delegateEventListener('input', '#place-update [name="beds[]"]', function() {
	Place.selectBed(this);
});

document.delegateEventListener('input', '#place-update [name^="sizes"]', function() {
	Place.updateSelected();
});


class Place {

	static toggleSelection(target) {

		CheckboxField.all(target.firstParent('.place-grid-container'), target.checked, '[name^="beds[]"]', (bed) => this.selectBed(bed));

	}

	static updateSearch() {

		const wrapper = qs('#place-search');

		const searchMode = wrapper.qs('[name="mode"]').value;
		const searchWidth = wrapper.qs('[name="width"]')?.value;
		const searchFree = wrapper.qs('[name="free"]').value;
		const searchRotation = parseInt(wrapper.qs('[name="rotation"]').value);

		qsa('.place-grid-container', container => {

			let containerHide = 1;

			container.qsa('.place-grid-bed', bed => {

				let bedHide = 0;

				switch(searchFree) {

					case '0' :
						break;

					case '100' :
						if(bed.dataset.free !== '0') {
							bedHide = 1;
						}
						break;

					case '1' :
						if(bed.dataset.free !== '0' && bed.dataset.free !== '1') {
							bedHide = 1;
						}
					case '2' :
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
					searchWidth === '1' &&
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
					containerHide = 0;
				}

				bed.dataset.hide = bedHide;

			});

			container.dataset.hide = containerHide;

		});

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

			qs('#place-grid-wrapper [data-tab="'+ panel.dataset.tab +'"] .tab-item-count').innerHTML = (beds > 0) ? beds : '';

		}

	}

	static submitUpdate() {
		submitAjaxForm(qs("#place-update"));
	}

	static updateSelected() {

		const area = qs('#place-update-area');
		const length = qs('#place-update-length');

		if(area === null && length === null) {
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