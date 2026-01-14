class FinancialYearDocument {

	static checkGeneration(url) {

		let intervalId = setInterval(async () =>

			new Ajax.Query()
				.method('post')
				.url(url)
				.fetch()
				.then((response) => (!response.result ? clearInterval(intervalId) : null)), 1000);
	}

}
