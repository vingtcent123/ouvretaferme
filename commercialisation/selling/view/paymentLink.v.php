<?php

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \selling\PaymentLinkUi()->create($data->eElement, $data->cPaymentLink);

});

