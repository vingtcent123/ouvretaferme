<?php
new AdaptativeView('increment', function($data, PanelTemplate $t) {
	return (new \selling\StockUi())->increment($data->e);
});

new JsonView('doIncrement', function($data, AjaxTemplate $t) {
	$t->js()->moveHistory(-1);
});
?>
