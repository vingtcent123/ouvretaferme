<?php
new Page()
	->get('index', function($data) {

        $cSequence = \sequence\Sequence::model()
            ->select(\sequence\Sequence::getSelection())
            ->getCollection();

        foreach($cSequence as $eSequence) {
            \sequence\SequenceLib::recalculate($eSequence['farm'], $eSequence);
        }

	});
?>