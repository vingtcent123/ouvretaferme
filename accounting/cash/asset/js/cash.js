class Cash {

	static changeDate(target) {

		const date = new Date(target.value);

		if(
			isNaN(date) ||
			date.getFullYear() < 2000
		) {
			this.adjustFormFromDate(null);
		} else {

			const farm = Farm.getId();

			new Ajax.Query()
				.method('get')
				.url('/'+ farm + '/account/financialYear/:getByDate?date='+ encodeURIComponent(target.value))
				.fetch()
				.then((json) => this.adjustFormFromDate(date, json));

		}

	}

	static adjustFormFromDate(date, json = []) {

		d(date, json);

	}

}