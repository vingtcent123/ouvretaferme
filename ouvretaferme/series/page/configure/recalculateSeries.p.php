<?php
(new Page())
	->get('index', function($data) {

        $cSeries = \series\Series::model()
            ->select(\series\Series::getSelection())
            ->getCollection();

        foreach($cSeries as $eSeries) {
            \series\SeriesLib::recalculate($eSeries['farm'], $eSeries);
        }

	});
?>