document.delegateEventListener('change', '[data-action="plot-mode-change"] input', function(e) {

	const node = qs('#plot-mode-greenhouse');

	if(this.value === 'greenhouse') {
		node.classList.remove('hide');
	} else {
		node.classList.add('hide');
	}

});

document.delegateEventListener('change', '#plot-mode-greenhouse input', function(e) {

	qs('#mapbox-polygon-shapes input[name="'+ e.target.name +'"]').value = e.target.value;

});