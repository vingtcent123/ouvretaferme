<?php
new Page()
	->cli('index', function($data) {

		foreach(\sequence\Requirement::model()
		        ->select([
					  'flow',
			        'tools' => new Sql('GROUP_CONCAT(tool ORDER BY tool SEPARATOR ",")')
		        ])
					->group('flow')
		        ->getCollection( ) as $e) {

			\sequence\Flow::model()->update($e['flow'], [
				'tools' => array_map(fn($v) => (int)$v, explode(',', $e['tools'])),
			]);

		}

		foreach(\series\Requirement::model()
		        ->select([
					  'task',
			        'tools' => new Sql('GROUP_CONCAT(tool ORDER BY tool SEPARATOR ",")')
		        ])
					->group('task')
		        ->getCollection( ) as $e) {

			\series\Task::model()->update($e['task'], [
				'tools' => array_map(fn($v) => (int)$v, explode(',', $e['tools'])),
			]);

		}

	});
?>