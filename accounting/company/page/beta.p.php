<?php
new \company\BetaApplicationPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

	})
	->doCreate(function($data) {

		throw new ReloadAction('company', 'Beta::registered');

	});

?>
