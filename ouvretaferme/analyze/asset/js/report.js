document.delegateEventListener('autocompleteSelect', '#report-create', function(e) {
	Report.refreshCreateSeries();
});

class Report {

	static refreshCreateSeries() {

		new Ajax.Query()
			.url('/analyze/report:create?'+ new URLSearchParams(qs('#report-create').form()))
			.method('get')
			.fetch();

	}

	static refreshCreateProducts() {

		new Ajax.Query()
			.url('/analyze/report:products')
			.body(qs('#report-create').form())
			.fetch();

	}

	static refreshStats() {

		const form = qs('#report-create');

		let turnover = 0;

		form.qsa('input[name="products[]"]:checked', node => {

			const product = node.value;

			const currentTurnover = form.qs('[name="turnover[' + product + ']"]').value;
			const currentQuantity = form.qs('[name="quantity[' + product + ']"]').value;
			const currentPrice = currentQuantity > 0 ? Math.round(currentTurnover / currentQuantity * 10) / 10 : '?';

			if(currentTurnover) {
				turnover += parseFloat(currentTurnover);
			}

			ref('report-price-'+ product, node => node.innerHTML = currentPrice);

		});

		let areaCultivations = 0;
		let workingTimeCultivations = 0;
		let costsCultivations = 0;

		const cultivationsField = form.qsa('input[name="cultivations[]"]:checked');

		cultivationsField.forEach(node => {

			const currentArea = form.qs('[name="area[' + node.value + ']"]').value;
			const currentWorkingTime = form.qs('[name="workingTime[' + node.value + ']"]').value;
			const currentCosts = form.qs('[name="costs[' + node.value + ']"]').value;

			if(currentArea) {
				areaCultivations += parseInt(currentArea);
			}

			if(currentWorkingTime) {
				workingTimeCultivations += parseFloat(currentWorkingTime);
			}

			if(currentCosts) {
				costsCultivations += parseInt(currentCosts);
			}

		});

		qs('#report-create-turnover').innerHTML = (turnover > 0) ? turnover : '?';
		qs('#report-create-area').innerHTML = (areaCultivations > 0) ? areaCultivations : '?';
		qs('#report-create-working-time').innerHTML = (workingTimeCultivations > 0) ? Math.round(workingTimeCultivations * 100 / 100) : '?';

		const costsTotalField = form.qs('[name="costsTotal"]');

		if(form.qs('[name="costsUser"]').checked === false) {
			costsTotalField.value = (costsCultivations > 0) ? costsCultivations : '';
		} else if(cultivationsField.length > 0) {

			if(costsTotalField.value === '') {

				cultivationsField.forEach(node => {
					form.qs('[name="costs[' + node.value + ']"]').value = '';
				});

			} else if(areaCultivations > 0) {

				const costsTotal = parseInt(costsTotalField.value);
				let costsRemainder = costsTotal;

				cultivationsField.forEach(node => {

					const currentArea = form.qs('[name="area[' + node.value + ']"]').value;

					if(currentArea) {
						const costs = Math.floor(parseInt(currentArea) / areaCultivations * costsTotal);
						costsRemainder -= costs;
						form.qs('[name="costs[' + node.value + ']"]').value = costs;
					}

				});

				cultivationsField.forEach(node => {
					if(costsRemainder-- > 0) {
						form.qs('[name="costs[' + node.value + ']"]').value = 1 + parseInt(form.qs('[name="costs[' + node.value + ']"]').value);
					}
				});

			} else {

				const costsTotal = parseInt(costsTotalField.value);
				const costsSlice = Math.floor(costsTotal / cultivationsField.length);
				let costsRemainder = costsTotal % cultivationsField.length;

				cultivationsField.forEach(node => {
					form.qs('[name="costs[' + node.value + ']"]').value = costsSlice + (costsRemainder-- > 0 ? 1 : 0);
				});

			}

		}

		const costs = form.qs('[name="costsTotal"]').value;
		qs('#report-create-costs').innerHTML = (costs !== '') ? costs : '?';

	}

	static selectCheckbox(field) {

		const parent = field.firstParent('.report-create-series-name');

		if(field.checked) {
			parent.classList.remove('report-create-disabled');
		} else {
			parent.classList.add('report-create-disabled');
		}

		this.refreshStats();

	}

	static selectCosts(field) {

		const form = qs('#report-create');

		if(field.checked) {
			form.qs('[name="costsTotal"]').removeAttribute('disabled');
			form.qsa('[name^="costs["]', node => {
				node.setAttribute('disabled', '');
			});
		} else {
			form.qs('[name="costsTotal"]', node => {
				node.setAttribute('disabled', '');
			});
			form.qsa('[name^="costs["]', node => node.removeAttribute('disabled'));
		}

		this.refreshStats();

	}

	static toggleCultivations(button) {

		const report = button.dataset.id;

		if(button.dataset.toggle === 'collapse') {
			ref('report-'+ report, node => node.classList.remove('hide'));
			button.classList.add('btn-primary');
			button.classList.remove('btn-outline-primary');
			button.dataset.toggle = 'expand';
			button.innerHTML = Lime.Asset.icon('chevron-up');
		} else {
			ref('report-'+ report, node => node.classList.add('hide'));
			button.classList.add('btn-outline-primary');
			button.classList.remove('btn-primary');
			button.dataset.toggle = 'collapse';
			button.innerHTML = Lime.Asset.icon('chevron-down');
		}

	}

}