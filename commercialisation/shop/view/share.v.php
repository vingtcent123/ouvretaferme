<?php
new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \shop\ShareUi()->update($data->e);

});
?>