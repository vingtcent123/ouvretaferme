document.addEventListener('keyup', function(e) {

	Farm.changeArrowSection(e)

});

document.delegateEventListener('mouseleave', 'header', function(e) {

	if(window.matchMedia('(min-width: 1100px) and (min-height: 650px)').matches) {
		return;
	}

	Farm.clearSection();
	Farm.closeSection();

});

class Farm {

	static pendingSection = null;

	static querySiret(target) {

		const form = target.firstParent('form');

		const siret = target.value.replace(/\s/g, '');

		if(siret.match(/^[0-9]{14}$/) === null) {
			return false;
		}

		d(siret);

	}

	static changeArrowSection(e) {

		if(
			document.activeElement !== document.body ||
			document.body.classList.contains('panel-open')
		) {
			return;
		}

		if(e.key === 'ArrowLeft' || e.key === 'ArrowRight') {

			const sections = [];
			qsa('#farm-nav-sections .farm-nav-section', node => sections[sections.length] = node.dataset.section);

			let position = sections.findIndex(section => document.body.dataset.template.includes('farm-section-'+ section));

			if(position === -1) {
				position = sections.findIndex(section => document.body.dataset.template.includes('farm-'+ section));
			}

			let newPosition;

			switch(e.key) {

				case 'ArrowLeft' :
					newPosition = (position - 1 + sections.length) % sections.length;
					break;

				case 'ArrowRight' :
					newPosition = (position + 1) % sections.length;
					break;

			}

			this.setSection(sections[newPosition]);

		}

	}

	static changeSection(target, event, delay = 0) {

		this.clearSection();

		if(event === 'click') {

			if(
				isTouch() &&
				window.matchMedia('(max-width: 1099px), (max-height: 649px)').matches &&
				document.body.dataset.section === target.dataset.section
			) {
				this.closeSection();
				return;
			}

		}

		this.pendingSection = setTimeout(() => {

			this.setSection(target.dataset.section);

			this.pendingSection = null;

		}, delay);

	}

	static closeSection() {

		if(document.body.dataset.section === undefined) {
			return false;
		}

		document.body.dataset.template = document.body.dataset.template.replace(' farm-section-' + document.body.dataset.section, '');

		delete document.body.dataset.section;

		return true;

	}

	static setSection(section) {

		document.body.dataset.template = document.body.dataset.template.replace(' farm-section-' + document.body.dataset.section, '');

		document.body.dataset.template += ' farm-section-'+ section;
		document.body.dataset.section = section;

	}

	static clearSection() {

		if(this.pendingSection !== null) {
			clearTimeout(this.pendingSection);
		}

	}

	static scrollBreadCrumbs(target, selected) {

		const subNavOffset = qs('#farm-breadcrumbs-section').getBoundingClientRect().width;

		target.style.paddingLeft = subNavOffset +'px';

		const subNavSelected = target.qs('.selected');

		if(subNavSelected) {

			const subNavBounding = subNavSelected.getBoundingClientRect();
			const breadcrumbsBounding = target.getBoundingClientRect();

			const subNavLeft = subNavBounding.left + target.scrollLeft;

			const leftTarget = subNavOffset + (breadcrumbsBounding.width - subNavOffset) / 2 - subNavBounding.width / 2;

			const scroll = Math.max(0, subNavLeft - leftTarget);

			target.scroll(scroll, 0);

		}

	}

	static changeSearchFamily(target) {

		const seenField = target.firstParent('form').qs('.bed-rotation-search-seen');

		if(target.value) {
			seenField.classList.remove('hide');
		} else {
			seenField.classList.add('hide');
		}

	}

	static changeCalendarMonth(farmId, target) {

		const form = target.firstParent('form');

		new Ajax.Query(form)
			.method('get')
			.url('/farm/farm:calendarMonth?id='+ farmId +'&calendarMonthStart='+ form.qs('[name="calendarMonthStart"]').value +'&calendarMonthStop='+ form.qs('[name="calendarMonthStop"]').value)
			.fetch();

	}

}
