<?php

new AdaptativeView('importPaymentCollection', function($data, PanelTemplate $t) {

		return new \preaccounting\PaymentUi()->importPaymentCollection($data->eFarm);

});
