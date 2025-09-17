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

	static select(target) {

		let location;

		const zone = target.dataset.zone;
		const zoneDropdown = qs('#zone-tabs .zone-dropdown');

		if(zone === undefined) {

			qsa('#zone-tabs .zone-tab', tab => tab.removeHide());
			qsa('[data-wrapper="#zone-tabs"] .dropdown-item', tab => tab.dataset.zone === undefined ? tab.classList.add('selected') : tab.classList.remove('selected'));

			location = document.location.href.removeArgument('zone');

			zoneDropdown.innerHTML = zoneDropdown.dataset.placeholder;

		} else {

			qsa('#zone-tabs .zone-tab', tab => tab.dataset.zone === zone ? tab.removeHide() : tab.hide());
			qsa('[data-wrapper="#zone-tabs"] .dropdown-item', tab => tab.dataset.zone === zone ? tab.classList.add('selected') : tab.classList.remove('selected'));

			location = document.location.href.setArgument('zone', zone);

			zoneDropdown.innerHTML = target.dataset.placeholder;

		}

		Lime.Dropdown.purge();
		Lime.History.replaceState(location);

	}

}