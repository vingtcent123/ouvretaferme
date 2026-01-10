document.delegateEventListener('autocompleteBeforeQuery', '[data-third-party="journal-operation-create"]', function(e) {
	ThirdParty.createNewThirdParty(e.detail.input);
});

document.delegateEventListener('autocompleteSelect', '[write-third-party] [data-autocomplete-field="customer"]', function(e) {
	ThirdParty.updateInfo(e.detail);
});

class ThirdParty {

	static focusInput() {
		qs('#journal-thirdParty-create input[name="name"]').focus();
	}

	static createNewThirdParty(element) {

		const index = parseInt(element.dataset.index);
		qs('[third-party-create-index]').setAttribute('third-party-create-index', index);

	}

	static setNewThirdParty(id, name) {

		if(!qs('[third-party-create-index]')) {
			return;
		}

		const index = qs('[third-party-create-index]').getAttribute('third-party-create-index');

		qs('[data-autocomplete-field="thirdParty[' + index + ']"]').focus();
		qs('[data-autocomplete-field="thirdParty[' + index + ']"]').setAttribute('value', name);
		AutocompleteField.change(qs('[data-autocomplete-field="thirdParty[' + index + ']"]'));

	}

	static updateInfo(details) {

		qs('[write-third-party] [name="vatNumber"]').value = details.vatNumber || '';
		qs('[write-third-party] [name="siret"]').value = details.siret || '';

	}

}
