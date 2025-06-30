new Lime.Instruction('main')
	.register('updateHeader', function(nav, subNav, sectionHtml, navHtml, breadcrumbs) {

		const farmNav = qs('#farm-nav');

		if(farmNav) {

			farmNav.qsa('[data-nav].selected', node => node.classList.remove('selected'));
			farmNav.qs('[data-nav="'+ nav +'"]', node => node.classList.add('selected'));

			farmNav.qsa('[data-sub-nav].selected', node => node.classList.remove('selected'));
			farmNav.qsa('[data-sub-nav="'+ subNav +'"]', node => node.classList.add('selected'));

			farmNav.qs('#farm-breadcrumbs', node => node.renderInner(breadcrumbs));

		} else {
			qs('header').renderInner(sectionHtml + navHtml);
		}


	})
	.register('updateNavPlanning', function(url, period) {

		qs('#farm-nav [data-nav="planning"]', node => node.setAttribute('href', url));

		if(qs('#farm-tab-planning-period')) {
			qs('#farm-tab-planning-period').innerHTML = qs('#farm-tab-planning-'+ period).innerHTML;
			qsa('[data-dropdown-id="farm-tab-planning-list"] .dropdown-item', item => item.classList.remove('selected'))
			qs('#farm-tab-planning-'+ period).classList.add('selected');
		}

	})
	.register('keepScroll', function() {

		if(
			document.body.getAttribute('data-touch') === 'yes' ||
			window.innerHeight < 600 /* Hauteur minimale pour la navigation de gauche */
		) {

			const node = qs('body > nav');

			const bounds = node.getBoundingClientRect();
			const position = bounds.top + bounds.height + window.scrollY;

			window.scrollTo(0, Math.min(position, this.initialScrollY));

		} else {
			window.scrollTo(0, 0);
		}

	})
	.register('resetScroll', function() {

		window.scrollTo(0, 0);

	});