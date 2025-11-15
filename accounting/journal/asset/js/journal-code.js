document.delegateEventListener('click', '#journal-code-accounts button', function(e) {

	JournalCode.checkConfirm(e);

});

class JournalCode {

	static checkConfirm(e) {

		const currentJournalCode = qs('#journal-code-accounts input[name="id"]').value;

		let confirmText = '';
		let count = 0;

		qsa('input[type="checkbox"][data-journal-current]:checked', function(node) {
			if(node.dataset.journalCurrent.length > 0 && node.dataset.journalCurrent !== currentJournalCode) {
				count++;
				if(qs('#journal-code-accounts').dataset.confirm === undefined) {
				}
			}
		});

		if(count === 1) {
			confirmText = qs('[data-warning="journal-code-overwrite"][data-singular]').innerHTML;
		} else {
			confirmText = qs('[data-warning="journal-code-overwrite"][data-plural]').innerHTML;
		}

		if(confirmText.length > 0 && confirm(confirmText) === false) {

			e.preventDefault();
			e.stopImmediatePropagation();

		} else {

			submitAjaxForm(qs('#journal-code-accounts'));

		}

	}
}
