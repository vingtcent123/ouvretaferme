<?php
new AdaptativeView('index', function($data, PanelTemplate $t) {
	return new \mail\EmailUi()->get($data->e);
});
?>
