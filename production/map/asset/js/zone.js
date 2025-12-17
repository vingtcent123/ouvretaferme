if(isTouch()) {

	let zoneEvent = null;

	document.addEventListener('scroll', function() {

		if(window.oldScrollY > window.scrollY) {
			qs('#zone-header', node => node.style.top = 0);
		}

		if(zoneEvent !== null) {

			clearTimeout(zoneEvent);

		}

		zoneEvent = setTimeout(() => {

			const zone = qs('#zone-content');
			const header = qs('#zone-header');

			if(header === null) {
				return;
			}

			const zoneTop = zone.getBoundingClientRect().top;
			const sticky = parseFloat(window.getComputedStyle(document.body).getPropertyValue('--mainSticky')) * rem();

			if(zoneTop < sticky) {
				header.style.top = (sticky - zoneTop) +'px';
			} else {
				header.style.top = 0;
			}

			zoneEvent = null;

		}, 250);

	});

}

class Zone {

	static select(zoneId, plotId = undefined) {

		const zoneTarget = qs('#zone-selector [data-zone="'+ zoneId +'"]');

		let location;

		const zoneDropdown = qs('#zone-container .zone-dropdown');

		if(zoneId === undefined) {

			qsa('#zone-container .zone-wrapper', wrapper => wrapper.removeHide());
			qsa('[data-wrapper="#zone-container"] .dropdown-item', item => item.dataset.zone === undefined ? item.classList.add('selected') : item.classList.remove('selected'));

			location = document.location.href.removeArgument('zone');

			zoneDropdown.innerHTML = zoneDropdown.dataset.placeholder;

		} else {

			qsa('#zone-container .zone-wrapper', tab => tab.dataset.zone === zoneTarget.dataset.zone ? tab.removeHide() : tab.hide());
			qsa('[data-wrapper="#zone-container"] .dropdown-item', tab => tab.dataset.zone === zoneTarget.dataset.zone ? tab.classList.add('selected') : tab.classList.remove('selected'));

			location = document.location.href.setArgument('zone', zoneId);

			zoneDropdown.innerHTML = zoneTarget.dataset.placeholder;

		}

		if(plotId !== undefined) {

			qs('#plot-item-'+ plotId).scrollIntoView({
				block: 'center',
				inline: 'center'
			});

		}

		Lime.Dropdown.purge();
		Lime.History.replaceState(location);

	}

}