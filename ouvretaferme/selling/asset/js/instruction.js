new Lime.Instruction('selling')
	.register('activateLastItem', function(id) {

		const list = qs('#item-add-list');
		const last = list.firstElementChild;

		const position = list.childNodes.length - 1;

		last.ref('product-number', node => node.innerHTML = position + 1);

		last.qsa('input[name], select[name]', node => {
			node.name = node.name.replace('[]', '[' + position + ']');
		});

		last.qsa('[data-wrapper]', node => {
			node.dataset.wrapper += '[' + position + ']';
		});

		qs('#item-add-discount', node => node.removeHide());

	});