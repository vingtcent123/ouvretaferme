<?php
new AdaptativeView('set', function($data, PanelTemplate $t) {

	return new \journal\DeferralUi()->set($data->eFarm, $data->e);

});
