<?php
new AdaptativeView('increment', function($data, PanelTemplate $t) {
	return (new \selling\StockUi())->increment($data->e);
});

new AdaptativeView('decrement', function($data, PanelTemplate $t) {
	return (new \selling\StockUi())->decrement($data->e);
});
?>
