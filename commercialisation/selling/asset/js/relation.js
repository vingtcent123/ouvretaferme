document.delegateEventListener('autocompleteSelect', '[id^="relation-create-"]', function(e) {

	if(e.detail.value === '') {
		return '';
	}

	const parent = e.target.dataset.parent;
	const child = e.detail.value;

	new Ajax.Query(this)
		.body({parent, child})
		.url('/selling/relation:doCreate')
		.fetch();

	e.detail.input.value = '';

});