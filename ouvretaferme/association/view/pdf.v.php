<?php
new HtmlView('index', function($data, PdfTemplate $t) {

	echo new \association\AssociationUi()->getPdfDocument($data->eHistory, $data->eFarmOtf);

});

?>
