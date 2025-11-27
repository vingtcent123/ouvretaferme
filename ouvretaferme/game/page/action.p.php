<?php
new Page(function($data) {

		$data->ePlayer = \game\PlayerLib::getOnline();

		if($data->ePlayer->empty()) {
			throw new RedirectAction('/jouer');
		}

	})
	->post('doEat', function($data) {

		\game\ActionLib::eat($data->ePlayer, POST('value', 'int')) ?
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

		throw new RedirectAction('/jouer');

	})
	->post('doFriendAdd', function($data) {

		\game\PlayerLib::addFriend($data->ePlayer, POST('code')) ?
			throw new ReloadAction('game', 'Action::friendAdded') :
			throw new ReloadAction('game', 'Action::friendNotAdded');

	})
	->post('doMotivation', function($data) {

		$data->ePlayerFriend = \game\PlayerLib::getByUser(POST('friend', 'user\User'))->validate();

		\game\PlayerLib::motivate($data->ePlayer, $data->ePlayerFriend) ?
			throw new ReloadAction('game', 'Action::motivated') :
			throw new ReloadAction('game', 'Action::notMotivated');

	})
	->post('doFriendRemove', function($data) {

		$data->ePlayerFriend = \game\PlayerLib::getByUser(POST('friend', 'user\User'))->validate();

		\game\PlayerLib::removeFriend($data->ePlayer, $data->ePlayerFriend);

		throw new ReloadAction();

	});


new \game\TilePage(function($data) {

		$data->ePlayer = \game\PlayerLib::getOnline();

		if($data->ePlayer->empty()) {
			throw new RedirectAction('/jouer');
		}

	})
	->applyElement(function($data, \game\Tile $e) {

		if($e['board'] > $data->ePlayer->getBoards()) {
			throw new NotExpectedAction('Invalid board');
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