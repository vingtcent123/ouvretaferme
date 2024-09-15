<?php
new AdaptativeView('updateNote', function($data, PanelTemplate $t) {
	return (new \farm\FarmUi())->updateStockNotes($data->e);
});

new AdaptativeView('add', function($data, PanelTemplate $t) {
	return (new \selling\StockUi())->add($data->eFarm);
});

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
