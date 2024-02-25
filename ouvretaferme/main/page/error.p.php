<?php
(new Page())
	->http('403', function($data) {

		\user\ConnectionLib::checkLogged();

		if(Route::getRequestedWith() === 'http') {
			$action = new ViewAction($data);
			$action->setStatusCode(403);
		} else if(Route::getRequestedWith() === 'ajax') {
			$action = new FailAction('dev\403');
		} else {
			$action = new StatusAction(403);
		}


		throw $action;

	})
	->http('404', function($data) {

		$data->error ??= dev\ErrorPhpLib::notFound();

		if(Route::getRequestedWith() === 'http') {

			$action = new ViewAction($data);
			$action->setStatusCode(404);

		} else {
			$action = new DataAction($data->error);

		}

		throw $action;

	});
?>
