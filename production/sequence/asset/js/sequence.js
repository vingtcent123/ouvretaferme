document.delegateEventListener('change', '[data-action="sequence-use-change"] input', function(e) {

	const fieldWidth = this.firstParent('form').qsa('[data-wrapper="bedWidth"], [data-wrapper="alleyWidth"]');

	switch(this.value) {

		case 'bed' :
			fieldWidth.forEach(node => node.style.display = '');
			break;

		case 'block' :
			fieldWidth.forEach(node => {
				node.qs('input').value = '';
				node.style.display = 'none';
			});
			break;

	}

});

document.delegateEventListener('change', '[data-action="sequence-cycle-change"] input', function(e) {

	const lifetime = qs('#sequence-write-perennial-lifetime');

	if(this.value === 'perennial') {
		lifetime.style.display = '';
		lifetime.qs('input').value = ''
	} else {
		lifetime.style.display = 'none';
	}

});