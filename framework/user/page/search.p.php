<?php
(new Page())
	->post('query', function($data) {

		// Bof bof bof permet de retrouver facilement les admins
		$eRole = \user\RoleLib::getByFqn(POST('role'));

		$data->cUser = \user\UserLib::getFromQuery(POST('query'), $eRole);

		throw new \ViewAction($data);

	});
?>