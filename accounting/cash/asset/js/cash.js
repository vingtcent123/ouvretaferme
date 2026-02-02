class Cash {

	static changeDate(target) {

		const farm = Farm.getId();

		new Ajax.Query()
			.method('get')
			.url('/'+ farm + '/financialYear/:getByDate?date='+ target.value.encode())
			.fetch()
			.then((response) => {
				alert(response);
			});

	}

}