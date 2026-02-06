class FinancialYearDocument {

	static checkGeneration(url) {

		let intervalId = setInterval(async () =>

			new Ajax.Query()
				.method('post')
				.url(url)
				.fetch()
				.then((response) => {
					if(response.result === 'not-finished') {
						return;
					}
					clearInterval(intervalId);
					window.location.reload();
				}), 2000);
	}

}
