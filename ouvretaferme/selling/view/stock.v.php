<?php
new AdaptativeView('history', function($data, PanelTemplate $t) {
	return (new \selling\StockUi())->getHistory($data->e, $data->cStock);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \selling\StockUi())->update($data->e);
});

new AdaptativeView('increment', function($data, PanelTemplate $t) {
	return (new \selling\StockUi())->increment($data->e);
});

new AdaptativeView('decrement', function($data, PanelTemplate $t) {
	return (new \selling\StockUi())->decrement($data->e);
});
?>
