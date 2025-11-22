<?php
new Page(function($data) {

		$data->ePlayer = \game\PlayerLib::getOnline();

		if($data->ePlayer->empty()) {
			throw new RedirectAction('/jouer');
		}

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


	})
	->post('doRestart', function($data) {

		\game\PlayerLib::restart($data->ePlayer);

	});


new \game\TilePage(function($data) {

		$data->ePlayer = \game\PlayerLib::getOnline();

		if($data->ePlayer->empty()) {
			throw new RedirectAction('/jouer');
		}

	})
	->write('doSeedling', function($data) {

		$data->eGrowing = \game\GrowingLib::getById(POST('growing'))->validate();

		\game\ActionLib::seed($data->ePlayer, $data->e, $data->eGrowing) ?
			throw new ReloadAction('game', 'Action::planted') :
			throw new ReloadAction('game', 'Action::notPlanted');

	})
	->write('doWatering', function($data) {

		\game\ActionLib::water($data->ePlayer, $data->e) ?
			throw new ReloadAction('game', 'Action::watered') :
			throw new ReloadAction('game', 'Action::notWatered');

	})
	->write('doWeed', function($data) {

		\game\ActionLib::weed($data->ePlayer, $data->e) ?
			throw new ReloadAction('game', 'Action::weeded') :
			throw new ReloadAction('game', 'Action::notWeeded');

	})
	->write('doHarvest', function($data) {

		\game\ActionLib::harvest($data->ePlayer, $data->e) ?
			throw new ReloadAction('game', 'Action::harvested') :
			throw new ReloadAction('game', 'Action::notHarvested');

	});
?>