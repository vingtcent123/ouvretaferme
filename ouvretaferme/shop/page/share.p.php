<?php
new \shop\SharePage()
	->update(function($data) {
		throw new \ViewAction($data);
	})
	->write('doIncrementPosition', function($data) {

		$increment = POST('increment', 'int');
		\shop\ShareLib::incrementPosition($data->e, $increment);

		throw new ReloadAction();

	})
	->quick(['label'])
	->doUpdate(fn() => throw new ReloadAction('shop', 'Share::updated'));
?>