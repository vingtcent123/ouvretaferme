class User {

	static changeAddress(target) {

		target.firstParent('.user-form-address').qsa('.form-control-address input', (node) => node.value = '');

	}
	
}