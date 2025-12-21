class Customer {

	static changeCategory(target) {

		const form = target.firstParent('form');

		const idField = form.qs('[name="id"]');

		if(idField !== null) {

			new Ajax.Query(target)
				.body({
					id: idField.value,
					type: target.value
				})
				.url('/selling/customer:getGroupField')
				.fetch();

		}

		switch(target.value) {

			case 'private' :
				form.qsa('.customer-form-category:not(.customer-form-private)', field => field.setAttribute('disabled', ''));
				form.qsa('.customer-form-category.customer-form-private', field => field.removeAttribute('disabled'));
				form.classList.add('customer-form-private');
				form.classList.remove('customer-form-pro');
				form.classList.remove('customer-form-collective');
				break;

			case 'pro' :
				form.qsa('.customer-form-category:not(.customer-form-pro)', field => field.setAttribute('disabled', ''));
				form.qsa('.customer-form-category.customer-form-pro', field => field.removeAttribute('disabled'));
				form.classList.add('customer-form-pro');
				form.classList.remove('customer-form-private');
				form.classList.remove('customer-form-collective');
				break;

			case 'collective' :
				form.qsa('.customer-form-category:not(.customer-form-collective)', field => field.setAttribute('disabled', ''));
				form.qsa('.customer-form-category.customer-form-collective', field => field.removeAttribute('disabled'));
				form.classList.add('customer-form-collective');
				form.classList.remove('customer-form-private');
				form.classList.remove('customer-form-pro');
				break;

		}

		form.classList.remove('customer-form-unknown');

	}

	static toggleSelection(target) {

		CheckboxField.all(target.firstParent('table'), target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static changeSelection()	 {

		return Batch.changeSelection('#batch-customer', null, function(selection) {

			let idsCollection = '';
			let lastId = '';

			selection.forEach(node => {

				idsCollection += '&customers[]='+ node.value;
				lastId = node.value;

			});

			qs(
				'.batch-sale',
				selection.filter('[data-batch~="not-active"]').length > 0 ?
					node => node.hide() :
					node => {

						node.removeHide();

						if(selection.length === 1) {
							node.setAttribute('href', node.dataset.url + lastId);
						} else {
							node.setAttribute('href', node.dataset.urlCollection + idsCollection);
						}

					}
			);

			if(selection.filter('[data-batch~="not-private"]').length > 0) {
				qsa('.batch-private', node => node.hide());
				qsa('.batch-pro', node => node.removeHide());
			}

			if(selection.filter('[data-batch~="not-pro"]').length > 0) {
				qsa('.batch-private', node => node.removeHide());
				qsa('.batch-pro', node => node.hide());
			}

			qs(
				'.batch-group',
				(
					selection.filter('[data-batch~="not-group"]').length > 0 ||
					(selection.filter('[data-batch~="not-private"]').length > 0 && selection.filter('[data-batch~="not-pro"]').length > 0) ||
					qsa('.batch-type:not(.hide)').length === 0
				) ?
					node => node.hide() :
					node => node.removeHide()
			);

		});

	}

}