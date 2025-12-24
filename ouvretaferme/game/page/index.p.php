<?php
new Page(function($data) {

		if(get_exists('player')) {

			$data->ePlayer = \game\PlayerLib::getById(GET('player'))->validate();

			if(\game\FriendLib::are($data->ePlayer) === FALSE) {
				throw new NotExpectedAction();
			}

		} else {
			$data->ePlayer = \game\PlayerLib::getOnline();
		}

	})
	->get('/jouer', function($data) {

		if(\game\GameSetting::isPlaying()) {

			if($data->ePlayer->empty()) {
				throw new ViewAction($data, ':start');
			}

			if(get_exists('board')) {

				$data->board = GET('board', 'int');

				if($data->board < 1 or $data->board > $data->ePlayer->getBoards()) {
					$data->board = 1;
				}

				\session\SessionLib::set('gameBoard', $data->board);

			} else {
				try {
					$data->board = \session\SessionLib::get('gameBoard');
				} catch(Exception) {
					$data->board = 1;
				}
			}

			$data->cTile = \game\TileLib::getByBoard($data->ePlayer, $data->board);
			$data->cGrowing = \game\GrowingLib::getAll();

			if($data->ePlayer->isOnline()) {

				$data->cFood = \game\FoodLib::getByPlayer($data->ePlayer);
				$data->cHistory = \game\HistoryLib::getByPlayer($data->ePlayer);
				$data->cPlayerFriend = \game\FriendLib::getByPlayer($data->ePlayer);

				\game\FoodLib::fillRankings($data->cFood);

			}

		}

		if(\game\GameSetting::isFinished()) {
			$data->points = \game\PlayerLib::getTotalPoints();
		}

		$data->cPlayerRanking = \game\PlayerLib::getPointsRanking(\game\PlayerLib::getOnline());


		throw new ViewAction($data);

	});

new \game\PlayerPage(function($data) {

		if(\game\GameSetting::isPlaying() === FALSE) {
			throw new NotAllowedAction();
		}

		\user\ConnectionLib::checkLogged();

		$ePlayer = \game\PlayerLib::getOnline();

		if($ePlayer->notEmpty()) {
			throw new RedirectAction('/jouer');
		}

	})
	->getCreateElement(function() {

		$eUser = \user\ConnectionLib::getOnline();

		return new \game\Player([
			'user' => $eUser
		]);

	})
	->create(page: 'new')
	->doCreate(fn() => throw new RedirectAction('/jouer?start'), page: 'doNew');
?>
