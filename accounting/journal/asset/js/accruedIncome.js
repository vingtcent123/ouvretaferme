document.delegateEventListener('autocompleteBeforeQuery', '#journal-accrued-income-create [data-autocomplete-field="accountLabel"]', function(e) {

	if(e.detail.input.firstParent('form').qs('[name^="account"]') !== null) {
		const account = e.detail.input.firstParent('form').qs('[name^="account"]').getAttribute('value');
		e.detail.body.append('account', account);
	}

});
