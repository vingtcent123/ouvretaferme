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
	.register('updateNavPlanning', function(url, period) {

		qs('#farm-nav [data-nav="home"]', node => node.setAttribute('href', url));

		if(qs('#farm-tab-planning-period')) {
			qs('#farm-tab-planning-period').innerHTML = qs('#farm-tab-planning-'+ period).innerHTML;
			qsa('[data-dropdown-id="farm-tab-planning-list"] .dropdown-item', item => item.classList.remove('selected'))
			qs('#farm-tab-planning-'+ period).classList.add('selected');
		}

	})
	.register('updateNavCultivation', function(url) {
		qs('#farm-nav [data-nav="cultivation"]', node => node.setAttribute('href', url));
	})
	.register('updateNavShop', function(url) {
		qs('#farm-nav [data-nav="shop"]', node => node.setAttribute('href', url));
	})
	.register('updateNavSelling', function(url) {
		qs('#farm-nav [data-nav="selling"]', node => node.setAttribute('href', url));
	})
	.register('updateNavCommunications', function(url) {
		qs('#farm-nav [data-nav="communications"]', node => node.setAttribute('href', url));
	})
	.register('updateNavAnalyze', function(url, category) {
		
		qs('#farm-nav [data-nav="analyze"]', node => node.setAttribute('href', url));

		if(qs('#farm-tab-analyze-category')) {
			qs('#farm-tab-analyze-category').innerHTML = qs('#farm-tab-analyze-'+ category).innerHTML;
			qsa('[data-dropdown-id="farm-tab-analyze-list"] .dropdown-item', item => item.classList.remove('selected'))
			qs('#farm-tab-analyze-'+ category).classList.add('selected');
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