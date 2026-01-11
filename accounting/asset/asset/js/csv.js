class Csv {

	static import(target) {

		if(confirm(target.dataset.confirmText)) {

			new Ajax.Query()
				.url(target.dataset.url)
				.body(target.post())
				.method('post')
				.fetch();

		}

	}

}
