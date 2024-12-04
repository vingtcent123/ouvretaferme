new Lime.Instruction('main')
	.register('updateHeader', function(tab, subTab, farmHtml, subNavHtml) {

		const farmNav = qs('#farm-nav');

		if(farmNav) {

			farmNav.qsa('[data-tab].selected', node => node.classList.remove('selected'));
			farmNav.qsa('.farm-subnav-item.selected', node => node.classList.remove('selected'));

			farmNav.qsa('[data-tab].selected', node => node.classList.remove('selected'));
			farmNav.qs('[data-tab="'+ tab +'"]', node => node.classList.add('selected'));

			if(subTab !== null) {
				farmNav.qs('[data-sub-tab="'+ subTab +'"]', node => node.classList.add('selected'));
			}

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
	.register('updateNavPlanning', function(url, period) {

		qs('#farm-nav [data-tab="home"]', node => node.setAttribute('href', url));

		if(qs('#farm-tab-planning-period')) {
			qs('#farm-tab-planning-period').innerHTML = qs('#farm-tab-planning-'+ period).innerHTML;
			qsa('[data-dropdown-id="farm-tab-planning-list"] .dropdown-item', item => item.classList.remove('selected'))
			qs('#farm-tab-planning-'+ period).classList.add('selected');
		}

	})
	.register('updateNavCultivation', function(url) {
		qs('#farm-nav [data-tab="cultivation"]', node => node.setAttribute('href', url));
	})
	.register('updateNavShop', function(url) {
		qs('#farm-nav [data-tab="shop"]', node => node.setAttribute('href', url));
	})
	.register('updateNavSelling', function(url) {
		qs('#farm-nav [data-tab="selling"]', node => node.setAttribute('href', url));
	})
	.register('updateNavAnalyze', function(url, category) {
		
		qs('#farm-nav [data-tab="analyze"]', node => node.setAttribute('href', url));

		if(qs('#farm-tab-analyze-category')) {
			qs('#farm-tab-analyze-category').innerHTML = qs('#farm-tab-analyze-'+ category).innerHTML;
			qsa('[data-dropdown-id="farm-tab-analyze-list"] .dropdown-item', item => item.classList.remove('selected'))
			qs('#farm-tab-analyze-'+ category).classList.add('selected');
		}

		
	})
	.register('updateNavSettings', function(url) {
		qs('#farm-nav [data-tab="settings"]', node => node.setAttribute('href', url));
	})
	.register('keepScroll', function() {

		if(
			document.body.getAttribute('data-touch') === 'yes' ||
			window.innerHeight < 767
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