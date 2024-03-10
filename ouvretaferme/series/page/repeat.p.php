<?php
(new \series\RepeatPage())
	->doUpdateProperties('doUpdateDeleted', ['deleted'], function($data) {
		throw new ReloadLayerAction();
	});
?>
