document.delegateEventListener('autocompleteBeforeQuery', '[data-third-party="journal-operation-create"]', function(e) {
    ThirdParty.createNewThirdParty(e.detail.input);
});

document.delegateEventListener('autocompleteSelect', 'form[data-ajax-form="/@module/account/ThirdParty/doQuick"] input[data-autocomplete-field="customer"]', function() {
    qs('form[data-ajax-form="/@module/account/ThirdParty/doQuick"] button[type="submit"]').click();
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

        const index = qs('[third-party-create-index]').getAttribute('third-party-create-index');

        qs('[data-autocomplete-field="thirdParty[' + index + ']"]').focus();
        qs('[data-autocomplete-field="thirdParty[' + index + ']"]').setAttribute('value', name);
        AutocompleteField.change(qs('[data-autocomplete-field="thirdParty[' + index + ']"]'));

    }

}
