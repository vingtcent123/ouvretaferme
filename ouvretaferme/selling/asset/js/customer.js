class Customer {

	static changeType(target) {

		const form = target.firstParent('form');

		switch(target.value) {

			case 'private' :
				form.qsa('.customer-pro', (wrapper) => {
					wrapper.hide();
					wrapper.qsa('input', field => field.setAttribute('disabled', ''));
				});
				form.classList.add('customer-form-private');
				form.classList.remove('customer-form-pro');
				break;

			case 'pro' :
				form.qsa('.customer-pro', (wrapper) => {
					wrapper.removeHide();
					wrapper.qsa('input', field => field.removeAttribute('disabled'));
				});
				form.classList.add('customer-form-pro');
				form.classList.remove('customer-form-private');
				break;

		}

	}

}