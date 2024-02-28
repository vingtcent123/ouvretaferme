<?php
(new Page())
	->get('index', function($data) {

        $cSeries = \series\Series::model()
            ->select(\series\Series::getSelection())
            ->getCollection();

        foreach($cSeries as $eSeries) {

            $cPlace = \series\PlaceLib::getByElement($eSeries);

            \series\Series::model()->beginTransaction();

            \series\PlaceLib::recalculateMetadata($eSeries, $cPlace);
            \series\PlaceLib::updateMetadata($eSeries);

            \series\Series::model()->commit();
        }

	});
?>