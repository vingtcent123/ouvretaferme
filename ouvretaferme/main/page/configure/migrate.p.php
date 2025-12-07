<?php
new Page()
	->cli('index', function($data) {

		$c = \farm\Farm::model()
			->select('id')
			->whereLegalEmail(NULL)
			->getCollection();

		foreach($c as $e) {

			$f = \farm\Farmer::model()
				->select([
					'user' => ['email']
				])
				->whereFarm($e)
				->whereRole(\farm\Farmer::OWNER)
				->sort(['id' => SORT_ASC])
				->get();

			if(
				$f->notEmpty()
			) {

				\farm\Farm::model()->update($e, [
					'legalEmail' => $f['user']['email']
				]);

			}

		}

	});
?>