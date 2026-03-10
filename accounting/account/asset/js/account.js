class Account {

	static checkForThirdParty(classPrefix) {

		const name = qs('[name="class"]').value;

		if(name.startsWith(classPrefix)) {
			qs('[data-wrapper="thirdParty"]').classList.add('required');
		} else {
			qs('[data-wrapper="thirdParty"]').classList.remove('required');
		}

	}

}
