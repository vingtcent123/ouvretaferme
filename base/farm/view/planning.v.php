<?php
new AdaptativeView('getWeeklyPdf', function($data, PdfTemplate $t) {

	$t->title = s("Planning de la semaine {week}, {year}", ['week' => week_number($data->week), 'year' => week_year($data->week)]);

	echo '<style>';
		echo '@page {	size: A4; margin: 0.75cm; }';
		echo 'html { font-size: 9px !important; }';
	echo '</style>';
	
	echo '<div class="flex-align-center: flex-justify-space-between mb-2">';
		echo '<div>';
			echo '<h1>'.$t->title.'</h1>';
			echo '<div class="color-muted">'.s("Du {from} au {to}", ['from' => \util\DateUi::textual(week_date_starts($data->week)), 'to' => \util\DateUi::textual(week_date_ends($data->week))]).'</div>';
		echo '</div>';
		echo '<h3>';
			if($data->e['vignette'] !== NULL) {
				echo \farm\FarmUi::getVignette($data->e, '2rem').'  ';
			}
			echo encode($data->e['name']);
		echo '</h3>';
	echo '</div>';

	echo '<div id="planning-export-container">';
		echo  new \series\PlanningUi()->getExportPlanning($data->e, $data->week, $data->ccTask);
	echo '</div>';

});
?>
