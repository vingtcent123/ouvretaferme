<?php
new Page(function($data) {

		$data->ePlayer = \game\PlayerLib::getOnline();

	})
	->get('/jouer', function($data) {

		if($data->ePlayer->empty()) {
			throw new ViewAction($data, ':start');
		}

		$data->cTile = \game\TileLib::getByUser($data->ePlayer['user'], 1);

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
	->doCreate(fn() => throw new RedirectAction('/jouer'), page: 'doNew');
?>
