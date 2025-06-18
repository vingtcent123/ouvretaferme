<?php
new HtmlView('index', function($data, PdfTemplate $t) {

	$h = '<style>@page {	size: A4 landscape; margin: 0.5cm; }</style>';

	$h .= '<div class="pdf-sales-summary-wrapper">';

		$h .= '<h1>Ma boutique</h1>';
		$h .= '<h2>Titre h2</h2>';


	$h .= '</div>';

	$h .= 'je suis un test de pDF à '.\util\DateUi::textual(date('Y-m-d H:i:s'));

	echo $h;
});
?>
