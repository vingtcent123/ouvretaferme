new Lime.Instruction('main')
	.register('updateHeader', function(tab, subTab, companyHtml, subNavHtml) {

		const companyNav = qs('#company-nav');

		if(companyNav) {

			companyNav.qsa('[data-tab].selected', node => node.classList.remove('selected'));
			companyNav.qsa('.company-subnav-item.selected', node => node.classList.remove('selected'));

			companyNav.qsa('[data-tab].selected', node => node.classList.remove('selected'));
			companyNav.qs('[data-tab="'+ tab +'"]', node => node.classList.add('selected'));

			if(subTab !== null) {
				companyNav.qs('[data-sub-nav="'+ subTab +'"]', node => node.classList.add('selected'));
			}

			const seasonNav = qs('#company-subnav');

			if(seasonNav) {
				seasonNav.renderOuter(subNavHtml);
			} else {
				companyNav.renderAdjacentHTML('afterend', subNavHtml);
			}

		} else {
			qs('header').renderInner(companyHtml + subNavHtml);
		}


	})
	.register('updateNavBank', function(url) {
		qs('#company-nav [data-tab="bank"]', node => node.setAttribute('href', url));
	})
	.register('updateNavShop', function(url) {
		qs('#company-nav [data-tab="shop"]', node => node.setAttribute('href', url));
	})
	.register('updateNavOverview', function(url, category) {

		qs('#company-nav [data-tab="overview"]', node => node.setAttribute('href', url));

		if(qs('#company-tab-overview-category')) {
			qs('#company-tab-overview-category').innerHTML = qs('#company-tab-overview-'+ category).innerHTML;
			qsa('[data-dropdown-id="company-tab-overview-list"] .dropdown-item', item => item.classList.remove('selected'))
			qs('#company-tab-overview-'+ category).classList.add('selected');
		}


	})
	.register('updateNavFinances', function(url) {
		qs('#company-nav [data-tab="finances"]', node => node.setAttribute('href', url));
	})
	.register('updateNavSuppliers', function(url) {
		qs('#company-nav [data-tab="suppliers"]', node => node.setAttribute('href', url));
	})
	.register('updateNavCustomers', function(url) {
		qs('#company-nav [data-tab="customers"]', node => node.setAttribute('href', url));
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
