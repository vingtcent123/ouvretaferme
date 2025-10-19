document.delegateEventListener('autocompleteSelect', '#grid-write-product', function(e) {

	if(e.detail.value === '') {
		return;
	}

	const url = document.location.href.setArgument('product', e.detail.value);
	Lime.History.replaceState(url);

	new Ajax.Query()
		.url(url)
		.method("get")
		.fetch();

});

document.delegateEventListener('autocompleteSelect', '#grid-write-customer', function(e) {

	if(e.detail.value === '') {
		return;
	}

	const url = document.location.href.setArgument('customer', e.detail.value);
	Lime.History.replaceState(url);

	new Ajax.Query()
		.url(url)
		.method("get")
		.fetch();

});

class Grid {

	static changeGroup(target) {

		const url = document.location.href.setArgument("group", target.value);
		Lime.History.replaceState(url);

		new Ajax.Query()
			.url(url)
			.method("get")
			.fetch();

	}

}