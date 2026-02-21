<?php

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \pdp\AddressUi()->create($data->eFarm, $data->e);

});
