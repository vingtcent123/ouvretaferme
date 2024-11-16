<?php
(new \shop\ProductPage())
	->doDelete(function($data) {
		throw new ReloadAction('shop', 'Product::deleted');
	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data))
	->quick(['available', 'price']);
?>