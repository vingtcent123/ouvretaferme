new Lime.Instruction('main')
	.register('updateHeader', function(nav, subNav, sectionHtml, navHtml) {

		const farmNav = qs('#farm-nav');

		if(farmNav) {

			farmNav.qsa('[data-nav].selected', node => node.classList.remove('selected'));
			farmNav.qsa('a.farm-subnav-item.selected', node => node.classList.remove('selected'));

			farmNav.qsa('[data-nav].selected', node => node.classList.remove('selected'));
			farmNav.qs('[data-nav="'+ nav +'"]', node => node.classList.add('selected'));

			farmNav.qs('[data-sub-nav="'+ subNav +'"]', node => node.classList.add('selected'));

		} else {
			qs('header').renderInner(sectionHtml + navHtml);
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