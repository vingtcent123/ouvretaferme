new Lime.Instruction('main')
	.register('updateHeader', function(tab, farmHtml, subNavHtml) {

		const farmNav = qs('#farm-nav');

		if(farmNav) {

			farmNav.qsa('[data-tab].selected', node => node.classList.remove('selected'));
			farmNav.qs('[data-tab="'+ tab +'"]', node => node.classList.add('selected'));

			const seasonNav = qs('#farm-subnav');

			if(seasonNav) {
				seasonNav.renderOuter(subNavHtml);
			} else {
				farmNav.renderAdjacentHTML('afterend', subNavHtml);
			}

		} else {
			qs('header').renderInner(farmHtml + subNavHtml);
		}


	})
	.register('updateNavPlanning', function(url) {
		qs('#farm-nav [data-tab="home"]', node => node.setAttribute('href', url));
	})
	.register('updateNavCultivation', function(url) {
		qs('#farm-nav [data-tab="cultivation"]', node => node.setAttribute('href', url));
	})
	.register('updateNavMap', function(url) {
		qs('#farm-nav [data-tab="map"]', node => node.setAttribute('href', url));
	})
	.register('updateNavSelling', function(url) {
		qs('#farm-nav [data-tab="selling"]', node => node.setAttribute('href', url));
	})
	.register('updateNavAnalyze', function(url) {
		qs('#farm-nav [data-tab="analyze"]', node => node.setAttribute('href', url));
	})
	.register('updateNavSettings', function(url) {
		qs('#farm-nav [data-tab="settings"]', node => node.setAttribute('href', url));
	})
	.register('keepScroll', function() {

		if(document.body.getAttribute('data-touch') === 'yes') {

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