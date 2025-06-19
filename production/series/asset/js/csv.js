class Csv {

	static import(target) {

		if(confirm(target.dataset.confirmText)) {

			target.classList.add('hide');

			new Ajax.Query()
				.url('/series/csv:doCreateCultivations')
				.body(target.post())
				.method('post')
				.fetch();

		}

	}

}