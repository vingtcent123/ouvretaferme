class Customer {

	static changeCategory(target) {

		const form = target.firstParent('form');

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

}