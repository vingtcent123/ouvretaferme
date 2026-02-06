document.delegateEventListener('autocompleteSelect', '[name^="comptes["]', function(e) {

	const accountId = e.detail.value;
	const importId = qs('#import-update-rules input[name="id"][type="hidden"]').value;
	const accountLabel = this.dataset.accountLabel;
	const url = qs('#import-update-rules').getAttribute('url-update');

	new Ajax.Query()
		.url(url)
		.body({ type: 'comptes', value: accountId, id: importId, key: accountLabel })
		.fetch();

});

class Import {

	static check(url) {

		let intervalId = setInterval(async () =>

			new Ajax.Query()
				.method('post')
				.url(url)
				.fetch()
				.then((response) => (response.result === 'finished' ? clearInterval(intervalId) && window.location.reload() : null)), 1000);
	}

	static updatePayment(target) {

		const url = qs('#import-update-rules').getAttribute('url-update');
		const importId = qs('#import-update-rules input[name="id"][type="hidden"]').value;
		const methodValue = target.dataset.label;
		const methodId = target.options[target.selectedIndex].value;

		new Ajax.Query()
			.url(url)
			.body({ type: 'paiements', value: methodId, id: importId, key: methodValue })
			.fetch();


	}

	static updateJournal(target) {

		const url = qs('#import-update-rules').getAttribute('url-update');
		const importId = qs('#import-update-rules input[name="id"][type="hidden"]').value;
		const journalValue = target.dataset.label;
		const journalId = target.options[target.selectedIndex].value;

		new Ajax.Query()
			.url(url)
			.body({ type: 'journaux', value: journalId, id: importId, key: journalValue })
			.fetch();


	}
}
