<?php
(new Page(fn() => Privilege::check('dev\admin')))
	->get('index', function($data) {

		$data->page = GET('page', 'int');
		$data->number = 100;

		if(get_exists('id')) {

			$eError = dev\ErrorMonitoringLib::getById(GET('id'));

			if($eError->notEmpty()) {
				$data->cError = new Collection([$eError]);
			} else {
				$data->cError = new Collection();
			}

		} else {

			$data->search = new Search([
				'message' => GET('message'),
				'unexpected' => GET('unexpected', 'bool'),
				'user' => GET('user', 'user\User'),
				'type' => GET('type', '?string')
			]);

			[$data->cError, $data->nError] = dev\ErrorMonitoringLib::getLast($data->page * $data->number, $data->number, $data->search);

		}

		throw new ViewAction($data);


	})
	->post('doStatus', function($data){

		$data->eError  = POST('id', 'dev\Error');

		if($data->eError->notEmpty()) {

			\dev\ErrorMonitoringLib::close($data->eError);

			throw new ViewAction($data);

		}

	})
	->post('doStatusByMessage', function($data){

		$message = POST('message');

		if($message !== '') {

			\dev\ErrorMonitoringLib::closeByMessage($message);

			throw new RedirectAction('/dev/admin/?success=dev:Error.closedByMessage');

		}

	});
?>
