class Routine {

	static refresh(target) {

		const form = target.firstParent('form');

		form.ref('routines', wrapper => {

			new Ajax.Query(target)
				.url('/farm/tool:getRoutinesField')
				.body({
					farm: wrapper.dataset.farm,
					action: target.value
				})
				.fetch()
				.then((json) => {

					wrapper.renderInner(json.field);

				});

		});

	}

	static select(target) {

		const form = target.firstParent('form');

		const routineName = target.value;

		form.ref('routine-', node => node.classList.add('hide'), () => null, '^=');

		if(routineName) {
			form.ref('routine-'+ routineName, node => node.classList.remove('hide'))
		}

	}

}