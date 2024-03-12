<?php
(new \series\RepeatPage())
	->doUpdateProperties('doUpdateStop', ['stop'], function($data) {
		throw new ReloadLayerAction();
	}, validate: ['canUpdateStop']);
?>
