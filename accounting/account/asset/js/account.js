class Account {

	static checkForThirdParty(classPrefix) {

		const name = qs('[name="class"]').value;

		if(name.startsWith(classPrefix)) {
			qs('[data-wrapper="thirdParty"]').classList.add('mandatory');
		} else {
			qs('[data-wrapper="thirdParty"]').classList.remove('mandatory');
		}

	}

}
