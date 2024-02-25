<?php
(new Page())
	->cron('index', function($data) {

		session\Session::model()
			->where('updatedAt < NOW() - INTERVAL '.(\session\SessionLib::LIFETIME + \session\SessionLib::REGENERATION).' SECOND')
			->union()
			->delete();

	}, interval: '*/5 * * * *');
?>
