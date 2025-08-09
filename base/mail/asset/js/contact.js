class Contact {

	static loadExport() {

		new Ajax.Query()
			.url(qs('#contact-export-link').getAttribute('data-ajax'))
			.method('get')
			.fetch();

	}

}