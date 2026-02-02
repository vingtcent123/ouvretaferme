class Cash {

	static changeDate(target) {

		const date = new Date(target.value.encode());

		if(
			isNaN(date) ||
			date.getFullYear() < 2000
		) {
			this.adjustFormFromDate(null);
		} else {

			const farm = Farm.getId();

			new Ajax.Query()
				.method('get')
				.url('/'+ farm + '/account/financialYear/:getByDate?date='+ target.value.encode())
				.fetch()
				.then((json) => this.adjustFormFromDate(date, json));

		}

	}

	static adjustFormFromDate(date, json = []) {

		d(date, json);

	}

}