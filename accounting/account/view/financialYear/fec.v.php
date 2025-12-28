<?php

new AdaptativeView('view', function($data, PanelTemplate $t) {

	return new \account\FecUi()->getView($data->eFarm, $data->eFarm['eFinancialYear'], $data->fecInfo);

});

?>
