<?php
new HtmlView('index', function($data, PdfTemplate $t) {
	echo new \journal\PdfUi()->getJournal($data->eCompany, $data->cOperation);
});

?>
