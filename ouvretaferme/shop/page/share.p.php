<?php
new \shop\SharePage()
	->update(function($data) {
		throw new \ViewAction($data);
	})
	->doUpdate(fn() => throw new ReloadAction('shop', 'Share::updated'));
?>