class AccountSettings {

	static scrollTo(accountId) {

		const { top: mainTop} = qs('main').getBoundingClientRect();
		const { top: trTop} = qs('tr[name="account-'+ accountId +'"]').getBoundingClientRect();
		window.scrollTo({top: trTop - mainTop - 200, behavior: 'smooth'});

		qs('tr[name^="account"]').classList.remove('row-emphasis');
		qs('tr[name="account-'+ accountId +'"]').classList.add('row-emphasis');

	}
}
