<?php
new HtmlView('summary', function($data, PdfTemplate $t) {
	echo new \overview\PdfUi()->getSummarizedBalance($data->balanceSummarized);
});

?>
