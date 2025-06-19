class Forecast {

	static changeUnit(target, change) {

		const form = target.firstParent('form');

		ref(change, (node) => node.innerHTML = form.dataset[target.value]);

	}

	static changePart(target) {

		const form = target.firstParent('form');
		const value = parseInt(target.value);

		if(target.name === 'privatePart') {
			form.qs('[name="proPart"]').value = (100 - value);
		}

		if(target.name === 'proPart') {
			form.qs('[name="privatePart"]').value = (100 - value);
		}

	}

}