class Dev {

	static init() {

		document.body.insertAdjacentHTML('beforeend', '<div id="dev-error"><a class="btn btn-exit" onclick="Dev.hideError()">&times;</a><div id="dev-error-content"></div></div>');

		let sqlQueries = '';

		qsa('div.dev-sql', value => {
			sqlQueries += value.innerHTML;
			value.remove();
		});

		let cacheQueries = '';

		qsa('div.dev-cache', value => {
			cacheQueries += value.innerHTML;
			value.remove();
		});

		let queries = '';

		if(sqlQueries) {
			queries += '<h4>SQL queries</h4><br/>'+ sqlQueries;
		}
		if(sqlQueries && cacheQueries) {
			queries += '<hr/>';
		}
		if(cacheQueries) {
			queries += '<h4>Cache queries</h4><br/>'+ cacheQueries;
		}

		if(queries) {
			this.showError(queries);
		}

	};

	static extends(node) {

		const trace = node.parentElement.qs('.dev-trace');

		if(trace.isHidden()) {

			qsa('.dev-trace', trace => trace.style.display = 'none');

			trace.style.display = 'block';

		} else {
			trace.style.display = 'none';
		}

	}

	static showError(content) {

		qs("#dev-error-content").innerHTML = content;
		qs("#dev-error").style.display = 'block';

	};

	static hideError() {

		qs("#dev-error").style.display = 'none';

	};

};

document.ready(() => {
	Dev.init();
});