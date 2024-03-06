<?php
(new \series\CommentPage(function($data) {

		\user\ConnectionLib::checkLogged();

	}))
	->update(fn($data) => throw new ViewAction($data))
	->doUpdate(fn() => throw new BackAction())
	->doDelete(fn($data) => throw new ViewAction($data));

(new Page(function($data) {

		$data->cTask = \series\TaskLib::getByIds(REQUEST('ids', 'array'))->validate();

		$eFarm = $data->cTask->first()['farm'];
		(new \series\Comment(['farm' => $eFarm]))->validate('canWrite');

		\series\Task::validateBatch($data->cTask);

	}))
	->get('createCollection', fn($data) => throw new ViewAction($data))
	->post('doCreateCollection', function($data) {

		$fw = new FailWatch();

		$e = new \series\Comment();
		$e->build(['text'], $_POST);

		$fw->validate();

		\series\CommentLib::createForTasks($data->cTask, $e);

		throw new BackAction();

	});
?>
