document.delegateEventListener('click', '[data-action="admin-error-expand"]', function(e) {

	const currentTrace = this.parentElement.qs('div.dev-error-trace');
	const visible = currentTrace.isVisible();

	qsa('#errors-monitoring div.dev-error-trace', trace => trace.style.display = 'none');

	if(visible === false) {
		currentTrace.style.display = 'block';
	}

});