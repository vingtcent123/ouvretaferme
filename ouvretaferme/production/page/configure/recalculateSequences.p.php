<?php
(new Page())
	->get('index', function($data) {

        $cSequence = \production\Sequence::model()
            ->select(\production\Sequence::getSelection())
            ->getCollection();

        foreach($cSequence as $eSequence) {
            \production\SequenceLib::recalculate($eSequence['farm'], $eSequence);
        }

	});
?>