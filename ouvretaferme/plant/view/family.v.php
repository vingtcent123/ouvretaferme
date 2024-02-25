<?php
new AdaptativeView('family', function($data, PanelTemplate $t) {

	return (new \plant\FamilyUi())->display($data->eFamily, $data->cPlant);


});
?>
