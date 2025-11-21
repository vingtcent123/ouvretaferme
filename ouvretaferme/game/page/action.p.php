<?php
new Page(function($data) {

		$data->ePlayer = \game\PlayerLib::getOnline();

	})
	->post('doEat', function($data) {

		\game\ActionLib::eat($data->ePlayer) ?
			throw new ReloadAction('game', 'Action::eaten') :
			throw new ReloadAction('game', 'Action::notEaten');

	})
	->post('doCook', function($data) {

		\game\ActionLib::cook($data->ePlayer, POST('value', 'int')) ?
			throw new ReloadAction('game', 'Action::cooked') :
			throw new ReloadAction('game', 'Action::notCooked');


	});

?>