<?php

new AdaptativeView('importInvoiceCollection', function($data, PanelTemplate $t) {

		return new \preaccounting\InvoiceUi()->importInvoiceCollection($data->eFarm);

});
