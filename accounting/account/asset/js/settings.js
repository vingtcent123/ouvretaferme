class AccountSettings {

	static scrollTo(accountId) {

		const { top: mainTop} = qs('main').getBoundingClientRect();
		const { top: trTop} = qs('tr[name="account-'+ accountId +'"]').getBoundingClientRect();
		window.scrollTo({top: trTop - mainTop - 200, behavior: 'smooth'});

		qs('tr[name^="account"]').classList.remove('row-highlight');
		qs('tr[name="account-'+ accountId +'"]').classList.add('row-highlight');

	}

	static toggleSelection(target) {

		CheckboxField.all(target.firstParent('table'), target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static changeSelection(target) {

		return Batch.changeSelection('#batch-journal', null, function(selection) {

			let ids = '';
			let idsList = [];

			selection.forEach(node => {


				ids += '&ids[]='+ node.value;
				idsList[idsList.length] = node.value;

			});

			return 1;

		});

	}

}
