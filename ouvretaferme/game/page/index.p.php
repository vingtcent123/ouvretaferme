<?php
new Page(function($data) {

		$data->ePlayer = \game\PlayerLib::getOnline();

	})
	->get('/jouer', function($data) {

		if($data->ePlayer->empty()) {
			throw new ViewAction($data, ':start');
		}

		$data->cTile = \game\TileLib::getByUser($data->ePlayer['user'], 1);
		$data->cGrowing = \game\GrowingLib::getAll();
		$data->cFood = \game\FoodLib::getByUser($data->ePlayer['user']);
		$data->cHistory = \game\HistoryLib::getByUser($data->ePlayer['user']);

		//\game\FoodLib::add($data->ePlayer, $data->cGrowing[3], new \game\Tile(), 3);

		throw new ViewAction($data);

	});

new Page(function($data) {

		$data->ePlayer = \game\PlayerLib::getOnline()->validate();

		$data->eTile = \game\TileLib::getOne($data->ePlayer['user'], INPUT('board'), INPUT('tile'))->validate();

	})
	->get('planting', function($data) {

		throw new ViewAction($data);

	});

new \game\PlayerPage(function($data) {

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
